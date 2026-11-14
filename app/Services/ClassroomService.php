<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\OnlineRoom;
use App\Models\OnlineRoomJoinRequest;
use App\Models\OnlineRoomParticipant;
use App\Models\OnlineRoomPresetQuestion;
use App\Models\CourseSessionQuestion;
use App\Models\User;
use App\Support\ClassroomConfig;
use App\Support\PwaClient;
use App\Support\JitsiJwt;
use App\Support\JitsiMeetLink;
use App\Support\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ClassroomService
{
    public function __construct(
        private ClassroomConfig $config,
        private OnlineRoomService $rooms,
        private OnlineRoomParticipant $participants,
    ) {
    }

    public function syncActiveCourseRooms(): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasTable('online_rooms')) {
            return;
        }

        Course::query()
            ->where('status', Course::STATUS_ACTIVE)
            ->whereNotNull('online_room_id')
            ->with(['sessions', 'room'])
            ->get()
            ->each(function (Course $course): void {
                $this->rooms->syncSchedule($course);
            });
    }

    /**
     * @return Collection<int, OnlineRoom>
     */
    public function availableForUser(User $user): Collection
    {
        if (! Schema::hasTable('online_rooms')) {
            return collect();
        }

        $this->syncActiveCourseRooms();

        $timeout = $this->config->presenceTimeout();
        $query = OnlineRoom::query()
            ->with('host')
            ->withCount([
                'participants as online_count' => function ($query) use ($timeout) {
                    $cutoff = now()->subSeconds(max(30, $timeout));
                    $query->where('is_online', 1)->where('last_seen_at', '>=', $cutoff);
                },
            ])
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('scheduled_start')
            ->orderBy('id');

        if ($user->isAdmin()) {
            return $query->get();
        }

        if ($user->isLearner()) {
            $course = CourseMember::activeCourseFor((int) $user->id);
            $roomId = (int) ($course?->online_room_id ?? 0);

            if ($roomId <= 0) {
                return collect();
            }

            return $query
                ->whereKey($roomId)
                ->whereIn('access_type', ['all', 'students'])
                ->get();
        }

        if ($user->isTeacher()) {
            $courseRoomIds = Course::query()
                ->where('teacher_id', (int) $user->id)
                ->where('status', Course::STATUS_ACTIVE)
                ->whereNotNull('online_room_id')
                ->pluck('online_room_id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            return $query
                ->whereIn('access_type', ['all', 'teachers', 'students'])
                ->where(function ($inner) use ($user, $courseRoomIds): void {
                    $inner->where('host_id', (int) $user->id);

                    if ($courseRoomIds !== []) {
                        $inner->orWhereIn('id', $courseRoomIds);
                    }
                })
                ->get();
        }

        return collect();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStandaloneRoom(User $user, array $data): OnlineRoom
    {
        $hostId = $user->isAdmin() && ! empty($data['host_id'])
            ? (int) $data['host_id']
            : (int) $user->id;

        return app(AdminClassroomService::class)->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'host_id' => $hostId,
            'scheduled_start' => $data['scheduled_start'] ?? null,
            'scheduled_end' => $data['scheduled_end'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
            'access_type' => $data['access_type'] ?? 'all',
            'max_participants' => (int) ($data['max_participants'] ?? 50),
            'waiting_room' => ! empty($data['waiting_room']),
            'room_password' => $data['room_password'] ?? '',
        ]);
    }

    public function findRoom(int $roomId): ?OnlineRoom
    {
        if ($roomId <= 0 || ! Schema::hasTable('online_rooms')) {
            return null;
        }

        $room = OnlineRoom::query()->with('host')->find($roomId);

        if ($room === null) {
            return null;
        }

        $course = Course::findByOnlineRoomId($roomId);

        if ($course !== null) {
            $this->rooms->syncSchedule($course->fresh(['sessions']) ?? $course);
            $room = $room->fresh(['host']) ?? $room;
        }

        return $room;
    }

    public function canAccess(OnlineRoom $room, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $room->host_id === (int) $user->id) {
            return true;
        }

        if ($user->isGuest()) {
            return (bool) $room->waiting_room
                && ! in_array((string) $room->status, ['draft', 'cancelled', 'ended'], true)
                && OnlineRoomJoinRequest::hasApprovedAccess((int) $room->id, (int) $user->id);
        }

        $course = Course::findByOnlineRoomId((int) $room->id);

        if ($course === null) {
            return $user->isTeacher() && (int) $room->host_id === (int) $user->id;
        }

        if ($user->isTeacher() && (int) $course->teacher_id === (int) $user->id) {
            return true;
        }

        return $user->isLearner() && CourseMember::isMemberOf((int) $course->id, (int) $user->id);
    }

    public function canAccessLobby(OnlineRoom $room, User $user): bool
    {
        if ($this->canAccess($room, $user)) {
            return true;
        }

        if (! $user->isGuest() || ! $room->waiting_room) {
            return false;
        }

        if (in_array((string) $room->status, ['draft', 'cancelled', 'ended'], true)) {
            return false;
        }

        return OnlineRoomJoinRequest::hasLobbyAccess((int) $room->id, (int) $user->id);
    }

    public function requiresApproval(OnlineRoom $room, User $user): bool
    {
        return (bool) $room->waiting_room && ! $this->isModerator($room, $user);
    }

    public function canAutoJoinMeeting(OnlineRoom $room, User $user): bool
    {
        if (! $this->canAccess($room, $user)) {
            return false;
        }

        if (filled($room->room_password)) {
            return false;
        }

        if ($this->config->isJaasConfigured() && ! $this->config->isJaasReady()) {
            return false;
        }

        $timeout = $this->config->presenceTimeout();
        $alreadyOnline = $this->participants->onlineParticipant((int) $room->id, (int) $user->id, $timeout) !== null;

        if (! $alreadyOnline) {
            $max = max(1, (int) ($room->max_participants ?: 50));

            if ($this->participants->countOnline((int) $room->id, $timeout) >= $max) {
                return false;
            }
        }

        if ($this->requiresApproval($room, $user)) {
            $request = OnlineRoomJoinRequest::getActiveForUser((int) $room->id, (int) $user->id);

            return $request !== null && $request->status === 'approved';
        }

        return true;
    }

    /**
     * @return list<int>
     */
    public function teacherCandidateIds(OnlineRoom $room): array
    {
        $ids = [(int) $room->host_id];
        $course = Course::findByOnlineRoomId((int) $room->id);

        if ($course !== null) {
            $ids[] = (int) $course->teacher_id;
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }

    /**
     * @return array{ok: bool, redirect: string, message?: string, tone?: string}
     */
    public function handleWaitingRoomEnter(OnlineRoom $room, User $user): array
    {
        $roomId = (int) $room->id;
        $userId = (int) $user->id;
        $existing = OnlineRoomJoinRequest::getActiveForUser($roomId, $userId);
        $lobby = $user->isLearner() && PwaClient::active()
            ? route('pwa.zoom')
            : route('classroom.lobby', $roomId);
        $isGuest = $user->isGuest();

        if ($existing !== null && $existing->status === 'approved') {
            if ($this->participants->onlineParticipant($roomId, $userId, $this->config->presenceTimeout()) === null) {
                $this->participants->joinRoom($roomId, $userId, $this->participantRole($room, $user));
            }

            if ($room->status === 'scheduled') {
                $room->update(['status' => 'live']);
            }

            return ['ok' => true, 'redirect' => PwaClient::roomUrl($roomId, $user)];
        }

        if ($existing !== null && $existing->status === 'pending') {
            return [
                'ok' => false,
                'redirect' => $lobby,
                'tone' => 'info',
                'message' => 'Bạn đã gửi yêu cầu vào lớp. Vui lòng chờ giáo viên duyệt.',
            ];
        }

        OnlineRoomJoinRequest::createPending(
            $roomId,
            $userId,
            $isGuest,
            $isGuest ? $user->name : null
        );

        $message = $existing !== null && $existing->status === 'rejected'
            ? 'Đã gửi lại yêu cầu vào lớp. Vui lòng chờ giáo viên duyệt.'
            : 'Đã gửi yêu cầu vào lớp. Vui lòng chờ giáo viên duyệt.';

        return [
            'ok' => true,
            'redirect' => $lobby,
            'tone' => 'success',
            'message' => $message,
        ];
    }

    public function participantRole(OnlineRoom $room, User $user): string
    {
        if ($user->isAdmin() || (int) $room->host_id === (int) $user->id) {
            return 'host';
        }

        $course = Course::findByOnlineRoomId((int) $room->id);

        if ($user->isTeacher() && $course !== null && (int) $course->teacher_id === (int) $user->id) {
            return 'host';
        }

        return 'participant';
    }

    public function isModerator(OnlineRoom $room, User $user): bool
    {
        return in_array($this->participantRole($room, $user), ['host', 'moderator'], true);
    }

    public function joinableSession(int $roomId): ?CourseSession
    {
        $course = Course::findByOnlineRoomId($roomId);

        if ($course === null) {
            return null;
        }

        return CourseSession::findJoinableForCourse((int) $course->id);
    }

    public function markStudentAttendance(int $roomId, int $userId): void
    {
        $session = $this->joinableSession($roomId);

        if ($session !== null) {
            CourseSessionAttendance::markPresent((int) $session->id, $userId);
        }
    }

    public function isBreakoutActive(int $roomId): bool
    {
        $state = $this->breakoutRaw($roomId);
        $roomCount = max(0, (int) ($state['room_count'] ?? $state['count'] ?? 0));

        return ! empty($state['active']) && $roomCount >= 2;
    }

    public function enter(OnlineRoom $room, User $user): void
    {
        $roomId = (int) $room->id;
        $this->participants->joinRoom($roomId, (int) $user->id, $this->participantRole($room, $user));

        if (! $this->isBreakoutActive($roomId)) {
            $this->participants->setBreakoutRoom($roomId, (int) $user->id, null);
        }

        if ($user->isLearner()) {
            $this->markStudentAttendance($roomId, (int) $user->id);
        }

        if ($room->status === 'scheduled') {
            $room->update(['status' => 'live']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jitsiMeta(OnlineRoom $room, int $breakoutIndex = 0): array
    {
        $ready = $this->config->isJaasReady();
        $domain = $this->config->effectiveDomain();
        $roomName = JitsiMeetLink::roomName(
            $this->config->jaasAppId(),
            (string) $room->room_code,
            $ready,
            $breakoutIndex
        );

        return [
            'jitsi_domain' => $domain,
            'jitsi_room_name' => $roomName,
            'jitsi_meet_url' => $roomName !== '' ? JitsiMeetLink::baseMeetUrl($domain, $roomName) : null,
            'use_jaas' => $ready,
            'jaas_app_id' => $this->config->jaasAppId(),
        ];
    }

    public function issueToken(OnlineRoom $room, User $user, bool $moderator, int $breakoutIndex = 0, string $clientInstance = ''): ?string
    {
        if (! $this->config->isJaasReady()) {
            return null;
        }

        $jwt = new JitsiJwt();

        return $jwt->generateForJaasRoom(
            $this->config->jaasAppId(),
            $this->config->jaasKid(),
            $this->config->privateKeyPem(),
            $this->jitsiIdentity($user, $clientInstance),
            $moderator,
            $this->config->jaasJwtTtl()
        );
    }

    public static function normalizeJitsiClientInstance(string $value): string
    {
        $value = strtolower(trim($value));

        if ($value === '' || strlen($value) > 80) {
            return '';
        }

        if (preg_match('/^[a-z0-9][a-z0-9:_-]{7,79}$/', $value) !== 1) {
            return '';
        }

        return $value;
    }

    /**
     * @return array{id: string, name: string, email: string}
     */
    private function jitsiIdentity(User $user, string $clientInstance): array
    {
        $clientInstance = self::normalizeJitsiClientInstance($clientInstance);
        $id = (string) $user->id;
        $email = trim((string) ($user->email ?? ''));

        if ($clientInstance !== '') {
            $id .= ':'.$clientInstance;
            $email = $this->jitsiEmailForClient($email, $clientInstance);
        }

        return [
            'id' => $id,
            'name' => $user->name.' ('.$this->roleLabel($user).')',
            'email' => $email,
        ];
    }

    private function jitsiEmailForClient(string $email, string $clientInstance): string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $tag = preg_replace('/[^a-z0-9]/i', '', $clientInstance) ?: 'c';

        if (strlen($tag) > 24) {
            $tag = substr($tag, 0, 24);
        }

        return $local.'+'.$tag.'@'.$domain;
    }

    public function roleLabel(User $user): string
    {
        return match ((string) $user->role) {
            UserRole::ADMIN => 'Quản trị',
            UserRole::TEACHER => 'Giáo viên',
            UserRole::GUEST => 'Khách',
            default => 'Học viên',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function polls(int $roomId): array
    {
        $polls = Cache::get($this->pollCacheKey($roomId), []);

        return is_array($polls) ? array_values($polls) : [];
    }

    /**
     * @param  list<string>|array<int, mixed>  $options
     * @return array<string, mixed>
     */
    public function createPoll(int $roomId, int $userId, string $question, string $type = 'fill', array $options = [], int $durationMinutes = 1): array
    {
        $question = $this->plainText($question);
        $type = $type === 'text' ? 'fill' : $type;
        $type = in_array($type, ['fill', 'choice'], true) ? $type : 'fill';
        $options = $this->normalizeOptions($options);
        $durationMinutes = in_array($durationMinutes, [1, 2, 3, 5], true) ? $durationMinutes : 1;

        if ($question === '' || mb_strlen($question) < 2) {
            throw new \InvalidArgumentException('Nội dung câu hỏi quá ngắn.');
        }

        if ($type === 'choice' && count($options) < 2) {
            throw new \InvalidArgumentException('Cần ít nhất 2 lựa chọn.');
        }

        $polls = $this->polls($roomId);
        $nextId = 1;

        foreach ($polls as $poll) {
            $nextId = max($nextId, (int) ($poll['id'] ?? 0) + 1);
        }

        $poll = [
            'id' => $nextId,
            'question' => $question,
            'type' => $type,
            'options' => $type === 'choice' ? $options : [],
            'answers' => [],
            'status' => 'open',
            'open' => true,
            'created_by' => $userId,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'duration_minutes' => $durationMinutes,
            'duration_seconds' => $durationMinutes * 60,
        ];
        $polls[] = $poll;
        $this->storePolls($roomId, $polls);

        return $this->formatPoll($poll);
    }

    /**
     * @return array<string, mixed>
     */
    public function launchPreset(int $roomId, int $userId, int $presetId): array
    {
        $formatted = $this->resolvePreset($roomId, $presetId);

        if ($formatted === null) {
            throw new \InvalidArgumentException('Không tìm thấy câu hỏi soạn sẵn.');
        }

        return $this->createPoll(
            $roomId,
            $userId,
            (string) $formatted['question'],
            (string) $formatted['type'],
            is_array($formatted['options'] ?? null) ? $formatted['options'] : []
        );
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function votePoll(int $roomId, int $pollId, int $userId, string $answer, string $userName = ''): array
    {
        $polls = $this->polls($roomId);
        $answer = trim($answer);

        if ($answer === '') {
            return ['ok' => false, 'message' => 'Vui lòng nhập câu trả lời.'];
        }

        foreach ($polls as $index => $poll) {
            if ((int) ($poll['id'] ?? 0) !== $pollId) {
                continue;
            }

            if (! $this->pollIsOpen($poll)) {
                return ['ok' => false, 'message' => 'Câu hỏi không còn mở.'];
            }

            $answers = is_array($poll['answers'] ?? null) ? $poll['answers'] : [];

            if (isset($answers[(string) $userId])) {
                return ['ok' => false, 'message' => 'Bạn đã trả lời câu hỏi này.'];
            }

            $type = (string) ($poll['type'] ?? 'fill');
            $options = is_array($poll['options'] ?? null) ? $poll['options'] : [];

            if ($type === 'choice' && ! in_array($answer, $options, true)) {
                return ['ok' => false, 'message' => 'Lựa chọn không hợp lệ.'];
            }

            $answers[(string) $userId] = [
                'answer' => $answer,
                'answered_at' => now()->format('Y-m-d H:i:s'),
                'name' => $userName,
            ];
            $polls[$index]['answers'] = $answers;
            $this->storePolls($roomId, $polls);

            return ['ok' => true];
        }

        return ['ok' => false, 'message' => 'Câu hỏi không còn mở.'];
    }

    public function closePoll(int $roomId, int $pollId): bool
    {
        $polls = $this->polls($roomId);

        foreach ($polls as $index => $poll) {
            if ((int) ($poll['id'] ?? 0) !== $pollId) {
                continue;
            }

            $polls[$index]['status'] = 'closed';
            $polls[$index]['open'] = false;
            $this->storePolls($roomId, $polls);

            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function pollFeed(int $roomId, int $userId, bool $isModerator): array
    {
        $polls = $this->polls($roomId);
        $answeredIds = [];
        $pendingPoll = null;

        foreach ($polls as $poll) {
            if (! $this->pollIsOpen($poll)) {
                continue;
            }

            $answers = is_array($poll['answers'] ?? null) ? $poll['answers'] : [];

            if (isset($answers[(string) $userId])) {
                $answeredIds[] = (int) ($poll['id'] ?? 0);
                continue;
            }

            if ($pendingPoll === null) {
                $pendingPoll = $this->formatPoll($poll);
            }
        }

        $payload = [
            'ok' => true,
            'isModerator' => $isModerator,
            'pendingPoll' => $isModerator ? null : $pendingPoll,
            'answeredIds' => $answeredIds,
            'reviewQuiz' => null,
        ];

        if (! $isModerator) {
            $payload['myAnswers'] = $this->studentAnswers($polls, $userId);
        }

        if ($isModerator) {
            $onlineStudents = $this->countOnlineStudents($roomId);
            $payload['polls'] = array_map(function (array $poll) use ($onlineStudents): array {
                $answers = is_array($poll['answers'] ?? null) ? $poll['answers'] : [];

                return $this->formatPoll($poll) + [
                    'answerCount' => count($answers),
                    'onlineStudents' => $onlineStudents,
                    'totalStudents' => $onlineStudents,
                ];
            }, $polls);
            $payload['presets'] = $this->roomPresets($roomId);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pollStats(int $roomId, int $pollId): ?array
    {
        $poll = $this->findPoll($roomId, $pollId);

        if ($poll === null) {
            return null;
        }

        $answers = is_array($poll['answers'] ?? null) ? $poll['answers'] : [];
        $stats = [];

        foreach ($this->studentAudience($roomId) as $row) {
            $userId = (int) ($row['userId'] ?? 0);
            $mine = $answers[(string) $userId] ?? null;
            $answerText = is_array($mine) ? trim((string) ($mine['answer'] ?? '')) : '';

            $stats[] = [
                'userId' => $userId,
                'name' => (string) ($row['name'] ?? ''),
                'answer' => $answerText !== '' ? $answerText : null,
                'answeredAt' => is_array($mine) ? ($mine['answered_at'] ?? null) : null,
                'answered' => $answerText !== '',
                'isOnline' => true,
            ];
        }

        $answeredCount = count(array_filter($stats, static fn (array $row): bool => ! empty($row['answered'])));

        return [
            'ok' => true,
            'poll' => $this->formatPoll($poll),
            'stats' => $stats,
            'summary' => [
                'answered' => $answeredCount,
                'total' => count($stats),
            ],
        ];
    }

    /**
     * @return array{active: bool, count: int}
     */
    public function breakoutState(int $roomId): array
    {
        $state = $this->breakoutRaw($roomId);

        return [
            'active' => ! empty($state['active']),
            'count' => max(0, (int) ($state['room_count'] ?? $state['count'] ?? 0)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function breakoutSessionPayload(int $roomId, int $userId, bool $includeGroups): array
    {
        $state = $this->breakoutRaw($roomId);
        $roomCount = max(0, (int) ($state['room_count'] ?? $state['count'] ?? 0));

        if (empty($state['active']) || $roomCount < 2) {
            return [
                'active' => false,
                'myBreakoutRoom' => 0,
            ];
        }
        $mine = 0;

        foreach ($this->participants->onlineList($roomId, $this->config->presenceTimeout()) as $row) {
            if ((int) ($row['userId'] ?? 0) === $userId) {
                $mine = (int) ($row['breakoutRoom'] ?? 0);
                break;
            }
        }

        $payload = [
            'active' => true,
            'roomCount' => $roomCount,
            'perRoomCapacity' => (int) ($state['per_room_capacity'] ?? 0),
            'myBreakoutRoom' => $mine,
            'startedAt' => (string) ($state['started_at'] ?? ''),
        ];

        if ($includeGroups) {
            $groups = $this->participants->breakoutRoomGroups($roomId, $roomCount, $this->config->presenceTimeout());
            $rooms = [];

            for ($i = 1; $i <= $roomCount; $i++) {
                $members = $groups[$i] ?? [];
                $rooms[] = [
                    'index' => $i,
                    'count' => count($members),
                    'members' => $members,
                ];
            }

            $payload['rooms'] = $rooms;
        }

        return $payload;
    }

    /**
     * @return array{ok: bool, message: string, status: int, breakoutSession?: array<string, mixed>}
     */
    public function startBreakout(int $roomId, int $actorId, int $roomCount, int $perRoomCapacity, string $assignMode, mixed $assignments): array
    {
        if (! empty($this->breakoutRaw($roomId)['active'])) {
            return [
                'ok' => false,
                'message' => 'Đang có phiên chia phòng. Hãy kết thúc trước khi chia lại.',
                'status' => 409,
            ];
        }

        $roomCount = max(2, min(12, $roomCount));
        $perRoomCapacity = max(2, min(10, $perRoomCapacity));
        $assignMode = $assignMode === 'manual' ? 'manual' : 'auto';
        $students = $this->onlineStudentsForBreakout($roomId);

        if ($students === []) {
            return [
                'ok' => false,
                'message' => 'Không có học viên online để chia phòng.',
                'status' => 422,
            ];
        }

        $this->participants->clearBreakouts($roomId);
        $assigned = 0;

        if ($assignMode === 'manual') {
            $validated = $this->parseManualAssignments($assignments, $students, $roomCount);

            if ($validated === []) {
                return [
                    'ok' => false,
                    'message' => 'Chọn ít nhất một học viên vào phòng thảo luận.',
                    'status' => 422,
                ];
            }

            foreach ($validated as $userId => $index) {
                $this->participants->setBreakoutRoom($roomId, (int) $userId, (int) $index);
                $assigned++;
            }
        } else {
            shuffle($students);
            $roomSizes = array_fill(1, $roomCount, 0);

            foreach ($students as $student) {
                $target = null;

                for ($i = 1; $i <= $roomCount; $i++) {
                    if ($roomSizes[$i] < $perRoomCapacity) {
                        $target = $i;
                        break;
                    }
                }

                if ($target === null) {
                    continue;
                }

                $this->participants->setBreakoutRoom($roomId, (int) $student['userId'], $target);
                $roomSizes[$target]++;
                $assigned++;
            }
        }

        $this->participants->setBreakoutRoom($roomId, $actorId, null);
        Cache::put($this->breakoutCacheKey($roomId), [
            'active' => true,
            'count' => $roomCount,
            'room_count' => $roomCount,
            'per_room_capacity' => $perRoomCapacity,
            'assign_mode' => $assignMode,
            'started_at' => now()->format('Y-m-d H:i:s'),
        ], now()->addHours(8));

        return [
            'ok' => true,
            'message' => 'Đã chia '.$assigned.' học viên vào '.$roomCount.' phòng.',
            'status' => 200,
            'breakoutSession' => $this->breakoutSessionPayload($roomId, $actorId, true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function endBreakout(int $roomId, int $userId): array
    {
        $this->participants->clearBreakouts($roomId);
        Cache::put($this->breakoutCacheKey($roomId), [
            'active' => false,
            'count' => 0,
            'room_count' => 0,
        ], now()->addHours(8));

        return [
            'ok' => true,
            'message' => 'Đã kết thúc chia phòng. Mọi người quay về phòng chính.',
            'breakoutSession' => [
                'active' => false,
                'myBreakoutRoom' => 0,
            ],
        ];
    }

    public function assignBreakout(int $roomId, int $userId, ?int $index): void
    {
        $this->participants->setBreakoutRoom($roomId, $userId, $index);
    }

    public function lobbyUrl(int $roomId): string
    {
        return route('classroom.lobby', ['id' => $roomId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function videoJoinPayload(OnlineRoom $room, User $user, int $breakoutIndex = 0, string $clientInstance = ''): array
    {
        if (! $this->config->isJaasReady()) {
            return [
                'ok' => false,
                'message' => 'JaaS chưa được cấu hình. Không mở được phòng video toàn màn hình.',
                'status' => 503,
            ];
        }

        $isModerator = $this->isModerator($room, $user);
        $jwt = $this->issueToken($room, $user, $isModerator, $breakoutIndex, $clientInstance);

        if ($jwt === null || $jwt === '') {
            return [
                'ok' => false,
                'message' => 'Không tạo được token phòng video.',
                'status' => 500,
            ];
        }

        $jitsi = $this->jitsiMeta($room, $breakoutIndex);
        $domain = (string) $jitsi['jitsi_domain'];
        $roomName = (string) $jitsi['jitsi_room_name'];
        $title = (string) $room->title;

        if ($breakoutIndex > 0) {
            $title .= ' — Phòng '.$breakoutIndex;
        }

        return [
            'ok' => true,
            'jwt' => $jwt,
            'domain' => $domain,
            'roomName' => $roomName,
            'roomTitle' => $title,
            'joinUrl' => JitsiMeetLink::webJoinUrl($domain, $roomName, $jwt, $title),
            'appDeepLink' => JitsiMeetLink::appDeepLink($domain, $roomName, $jwt),
        ];
    }

    private function pollCacheKey(int $roomId): string
    {
        return 'classroom.polls.'.$roomId;
    }

    private function breakoutCacheKey(int $roomId): string
    {
        return 'classroom.breakout.'.$roomId;
    }

    /**
     * @param  list<array<string, mixed>>  $polls
     */
    private function storePolls(int $roomId, array $polls): void
    {
        Cache::put($this->pollCacheKey($roomId), array_values($polls), now()->addHours(8));
    }

    /**
     * @return array<string, mixed>
     */
    private function breakoutRaw(int $roomId): array
    {
        $state = Cache::get($this->breakoutCacheKey($roomId), ['active' => false, 'count' => 0]);

        return is_array($state) ? $state : ['active' => false, 'count' => 0];
    }

    /**
     * @param  array<string, mixed>  $poll
     * @return array<string, mixed>
     */
    private function formatPoll(array $poll): array
    {
        $type = (string) (($poll['type'] ?? 'fill') === 'text' ? 'fill' : ($poll['type'] ?? 'fill'));

        return [
            'id' => (int) ($poll['id'] ?? 0),
            'question' => $this->plainText((string) ($poll['question'] ?? '')),
            'type' => in_array($type, ['fill', 'choice'], true) ? $type : 'fill',
            'options' => $this->normalizeOptions($poll['options'] ?? []),
            'status' => $this->pollIsOpen($poll) ? 'open' : 'closed',
            'createdAt' => (string) ($poll['created_at'] ?? ''),
            'durationMinutes' => $this->pollDurationMinutes($poll),
            'durationSeconds' => $this->pollDurationSeconds($poll),
        ];
    }

    /**
     * @param  array<string, mixed>  $poll
     */
    private function pollDurationMinutes(array $poll): int
    {
        $minutes = (int) ($poll['duration_minutes'] ?? 0);

        if (in_array($minutes, [1, 2, 3, 5], true)) {
            return $minutes;
        }

        $seconds = (int) ($poll['duration_seconds'] ?? 0);

        return match ($seconds) {
            120 => 2,
            180 => 3,
            300 => 5,
            default => 1,
        };
    }

    private function pollDurationSeconds(array $poll): int
    {
        return $this->pollDurationMinutes($poll) * 60;
    }

    private function pollIsOpen(array $poll): bool
    {
        $status = (string) ($poll['status'] ?? '');

        if ($status !== '') {
            return $status === 'open';
        }

        return ! empty($poll['open']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findPoll(int $roomId, int $pollId): ?array
    {
        foreach ($this->polls($roomId) as $poll) {
            if ((int) ($poll['id'] ?? 0) === $pollId) {
                return $poll;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $polls
     * @return list<array<string, mixed>>
     */
    private function studentAnswers(array $polls, int $userId): array
    {
        $rows = [];

        foreach ($polls as $poll) {
            $answers = is_array($poll['answers'] ?? null) ? $poll['answers'] : [];
            $mine = $answers[(string) $userId] ?? null;

            if (! is_array($mine)) {
                continue;
            }

            $formatted = $this->formatPoll($poll);
            $rows[] = [
                'pollId' => $formatted['id'],
                'question' => $formatted['question'],
                'type' => $formatted['type'],
                'options' => $formatted['options'],
                'answer' => (string) ($mine['answer'] ?? ''),
                'answeredAt' => (string) ($mine['answered_at'] ?? ''),
                'status' => $formatted['status'],
            ];
        }

        return array_reverse($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function studentAudience(int $roomId): array
    {
        return array_values(array_filter(
            $this->participants->onlineList($roomId, $this->config->presenceTimeout()),
            static fn (array $row): bool => ($row['role'] ?? '') === 'participant'
        ));
    }

    public function countOnlineStudents(int $roomId): int
    {
        return count($this->studentAudience($roomId));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function roomPresets(int $roomId): array
    {
        $presets = [];

        if (Schema::hasTable('online_room_preset_questions')) {
            foreach (OnlineRoomPresetQuestion::query()->where('room_id', $roomId)->orderBy('sort_order')->orderBy('id')->get() as $preset) {
                $type = (string) $preset->question_type === 'text' ? 'fill' : (string) $preset->question_type;
                $presets[] = [
                    'id' => (int) $preset->id,
                    'question' => $this->plainText((string) $preset->question_text),
                    'type' => in_array($type, ['fill', 'choice'], true) ? $type : 'fill',
                    'options' => $preset->options(),
                    'sortOrder' => (int) ($preset->sort_order ?? 0),
                    'source' => 'room',
                ];
            }
        }

        $session = $this->joinableSession($roomId);

        if ($session === null) {
            $course = Course::findByOnlineRoomId($roomId);
            $session = $course !== null
                ? CourseSession::query()->where('course_id', (int) $course->id)->orderByDesc('id')->first()
                : null;
        }

        if ($session === null || ! Schema::hasTable('course_session_questions')) {
            return $presets;
        }

        $labels = [
            'quiz' => 'Trắc nghiệm trong buổi',
        ];

        $rows = CourseSessionQuestion::query()
            ->where('course_session_id', (int) $session->id)
            ->whereNotIn('question_group', ['pre', 'post_practice', 'review'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $type = (string) $row->question_type === 'text' ? 'fill' : (string) $row->question_type;

            if (! in_array($type, ['fill', 'choice'], true)) {
                continue;
            }

            $options = $this->normalizeOptions(json_decode((string) ($row->options_json ?? '[]'), true) ?: []);
            $question = $this->plainText((string) $row->question_text);
            $group = (string) ($row->question_group ?? '');

            if (isset($labels[$group])) {
                $question = '['.$labels[$group].'] '.$question;
            }

            $presets[] = [
                'id' => -1 * abs((int) $row->id),
                'question' => $question,
                'type' => $type,
                'options' => $options,
                'source' => 'program_session',
            ];
        }

        return $presets;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolvePreset(int $roomId, int $presetId): ?array
    {
        if ($presetId > 0 && Schema::hasTable('online_room_preset_questions')) {
            $preset = OnlineRoomPresetQuestion::query()
                ->where('room_id', $roomId)
                ->whereKey($presetId)
                ->first();

            if ($preset !== null) {
                $type = (string) $preset->question_type === 'text' ? 'fill' : (string) $preset->question_type;

                return [
                    'question' => $this->plainText((string) $preset->question_text),
                    'type' => in_array($type, ['fill', 'choice'], true) ? $type : 'fill',
                    'options' => $preset->options(),
                ];
            }
        }

        if ($presetId >= 0) {
            return null;
        }

        $questionId = abs($presetId);
        $course = Course::findByOnlineRoomId($roomId);

        if ($course === null || ! Schema::hasTable('course_session_questions')) {
            return null;
        }

        $row = CourseSessionQuestion::query()
            ->whereKey($questionId)
            ->whereHas('courseSession', static function ($query) use ($course): void {
                $query->where('course_id', (int) $course->id);
            })
            ->first();

        if ($row === null) {
            return null;
        }

        $type = (string) $row->question_type === 'text' ? 'fill' : (string) $row->question_type;

        return [
            'question' => $this->plainText((string) $row->question_text),
            'type' => in_array($type, ['fill', 'choice'], true) ? $type : 'fill',
            'options' => $this->normalizeOptions(json_decode((string) ($row->options_json ?? '[]'), true) ?: []),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function onlineStudentsForBreakout(int $roomId): array
    {
        return array_values(array_filter(
            $this->participants->onlineList($roomId, $this->config->presenceTimeout()),
            static fn (array $row): bool => ($row['role'] ?? '') === 'participant' && empty($row['onBreak'])
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $students
     * @return array<int, int>
     */
    private function parseManualAssignments(mixed $rawAssignments, array $students, int $roomCount): array
    {
        $decoded = [];

        if (is_string($rawAssignments) && trim($rawAssignments) !== '') {
            $parsed = json_decode($rawAssignments, true);
            $decoded = is_array($parsed) ? $parsed : [];
        } elseif (is_array($rawAssignments)) {
            $decoded = $rawAssignments;
        }

        $eligible = [];

        foreach ($students as $student) {
            $userId = (int) ($student['userId'] ?? 0);

            if ($userId > 0) {
                $eligible[$userId] = true;
            }
        }

        $validated = [];

        foreach ($decoded as $userId => $roomIndex) {
            $userId = (int) $userId;
            $roomIndex = (int) $roomIndex;

            if ($userId <= 0 || ! isset($eligible[$userId]) || $roomIndex < 1 || $roomIndex > $roomCount) {
                continue;
            }

            $validated[$userId] = $roomIndex;
        }

        return $validated;
    }

    /**
     * @param  mixed  $options
     * @return list<string>
     */
    private function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($opt): string => $this->plainText((string) $opt), $options),
            static fn (string $opt): bool => $opt !== ''
        ));
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
