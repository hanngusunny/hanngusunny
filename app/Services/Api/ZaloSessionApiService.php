<?php

namespace App\Services\Api;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\OnlineRoom;
use App\Models\OnlineRoomJoinRequest;
use App\Models\OnlineRoomParticipant;
use App\Models\User;
use App\Services\ClassroomService;
use App\Services\OnlineRoomService;
use App\Services\StudentSessionCatalogService;
use App\Support\ClassroomConfig;
use App\Support\JitsiMeetLink;
use Throwable;

class ZaloSessionApiService
{
    public function __construct(
        private ZaloApiSupport $api,
        private StudentSessionCatalogService $catalog,
        private OnlineRoomService $rooms,
        private ClassroomService $classroom,
        private ClassroomConfig $classroomConfig,
        private OnlineRoomParticipant $participants,
    ) {
    }

    /**
     * @return array{code: string, message: string, status: int}|null
     */
    public function ensureReady(): ?array
    {
        return $this->api->ensureTables(
            ['users', 'courses', 'course_members', 'course_sessions', 'online_rooms'],
            'Module khóa học chưa được cài database.'
        );
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getNearestZoomSession(int $userId): array
    {
        if ($error = $this->api->requireStudent($userId)) {
            return ['error' => $error];
        }

        $lookup = $this->api->courseForLookup($userId);

        if ($lookup === null) {
            return [
                'payload' => [
                    'course' => null,
                    'nearest_session' => null,
                    'online_room' => null,
                    'upcoming_sessions' => [],
                    'message' => 'Học viên chưa tham gia khóa học nào.',
                    'lookup' => [
                        'has_course' => false,
                        'membership_status' => null,
                        'course_lookup_source' => null,
                    ],
                ],
            ];
        }

        $course = $lookup['course'];
        $this->syncCourseRoom($course);
        $sessions = $this->sessionsForCourse((int) $course->id, $userId);
        $nearestId = $this->catalog->nearestSessionId($sessions);
        $nearest = $this->findSessionRow($sessions, $nearestId);
        $membershipStatus = $lookup['membership_status'];

        if ($nearest === null) {
            return [
                'payload' => [
                    'course' => $this->api->formatCourse($course, $membershipStatus, $lookup['course_lookup_source']),
                    'nearest_session' => null,
                    'online_room' => null,
                    'upcoming_sessions' => [],
                    'message' => $membershipStatus !== CourseMember::STATUS_ACTIVE
                        ? 'Học viên không còn trong khóa học đang hoạt động và chưa có buổi học Zoom sắp tới.'
                        : 'Chưa có buổi học Zoom sắp tới.',
                    'lookup' => [
                        'has_course' => true,
                        'membership_status' => $membershipStatus,
                        'course_lookup_source' => $lookup['course_lookup_source'],
                    ],
                ],
            ];
        }

        $model = CourseSession::query()->with(['course.teacher', 'course.room'])->find((int) $nearest['id']);
        $hasAttended = CourseSessionAttendance::hasAttended((int) $nearest['id'], $userId);

        return [
            'payload' => [
                'course' => $this->api->formatCourse($course, $membershipStatus, $lookup['course_lookup_source']),
                'nearest_session' => $this->formatSession($nearest, $model, $userId, $hasAttended),
                'online_room' => $this->formatOnlineRoom((int) ($nearest['online_room_id'] ?? 0)),
                'upcoming_sessions' => array_map(
                    fn (array $row): array => $this->formatSessionSummary($row),
                    $this->upcomingSessions($sessions, $nearestId, 4)
                ),
                'message' => null,
                'lookup' => [
                    'has_course' => true,
                    'membership_status' => $membershipStatus,
                    'course_lookup_source' => $lookup['course_lookup_source'],
                ],
            ],
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getNearestZoomSessionByPhone(string $phone): array
    {
        $student = User::findStudentByPhone($phone);

        if ($student === null) {
            return ['error' => $this->api->error(
                'STUDENT_NOT_FOUND',
                'Không tìm thấy học viên active với số điện thoại này. Kiểm tra SĐT đã đăng ký (0xxxxxxxxx hoặc +84...).',
                404
            )];
        }

        $result = $this->getNearestZoomSession((int) $student->id);

        if (isset($result['error'])) {
            return $result;
        }

        $result['payload']['student'] = $this->api->formatStudentBrief($student);
        $result['payload']['phone_normalized'] = (string) ($student->phone ?? User::normalizePhone($phone));

        return $result;
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function listCourseSessions(int $userId, ?int $courseId = null, ?int $levelId = null): array
    {
        if ($error = $this->api->requireStudent($userId)) {
            return ['error' => $error];
        }

        $course = $courseId !== null && $courseId > 0
            ? $this->api->resolveCourseForStudent($userId, $courseId)
            : CourseMember::activeCourseFor($userId);

        if ($course === null) {
            return [
                'payload' => [
                    'course' => null,
                    'nearest_session_id' => 0,
                    'summary' => ['total' => 0, 'live' => 0, 'attended' => 0, 'ended' => 0],
                    'total' => 0,
                    'sessions' => [],
                    'message' => $courseId !== null && $courseId > 0
                        ? 'Học viên không thuộc khóa học này.'
                        : 'Bạn chưa tham gia khóa học đang hoạt động.',
                ],
            ];
        }

        $this->syncCourseRoom($course);
        $sessions = $this->sessionsForCourse((int) $course->id, $userId);

        if ($levelId !== null && $levelId > 0) {
            $sessions = $this->catalog->forProgramLevel($sessions, $levelId);
        }

        $sessions = array_values(array_filter(
            $sessions,
            static fn (array $session): bool => ($session['phase'] ?? '') !== 'cancelled'
        ));

        $formatted = [];
        $sessionNo = 0;

        foreach ($sessions as $session) {
            $sessionNo++;
            $formatted[] = $this->formatSessionListItem($session, $sessionNo);
        }

        return [
            'payload' => [
                'course' => $this->api->formatCourse($course),
                'nearest_session_id' => $this->catalog->nearestSessionId($sessions),
                'summary' => $this->catalog->summarize($sessions),
                'total' => count($formatted),
                'sessions' => $formatted,
                'message' => $formatted === [] ? 'Chưa có buổi học trong khóa này.' : null,
            ],
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function getMeetingRoomStatus(int $userId, string $publicCode): array
    {
        if ($error = $this->api->requireStudent($userId)) {
            return ['error' => $error];
        }

        $resolved = $this->resolveStudentSession($userId, $publicCode);

        if (isset($resolved['error'])) {
            return $resolved;
        }

        $session = $resolved['session'];
        $row = $resolved['row'];
        $user = User::query()->find($userId);
        $roomId = (int) ($row['online_room_id'] ?? 0);
        $publicCode = (string) $session->public_code;
        $hasAttended = CourseSessionAttendance::hasAttended((int) $session->id, $userId);

        $payload = [
            'session' => $this->formatSession($row, $session, $userId, $hasAttended),
            'online_room' => $roomId > 0 ? $this->formatOnlineRoom($roomId) : null,
            'room_id' => $roomId > 0 ? $roomId : null,
            'room_status' => 'not_entered',
            'in_room' => false,
            'video' => null,
            'join_request' => null,
            'urls' => [
                'join_meeting' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/vao-phong'),
                'join_session' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/vao'),
                'meeting_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/phong-hoc'),
            ],
        ];

        if ($roomId <= 0) {
            $payload['can_join_video'] = false;
            $payload['message'] = 'Buổi học chưa có phòng video.';

            return ['payload' => $payload];
        }

        $payload['urls'] = array_merge($payload['urls'], $this->api->roomApiUrls($roomId));
        $joinRequest = $this->joinRequestPayload($userId, $roomId);
        $inRoom = $user !== null && $this->isUserInRoom($userId, $roomId);

        if ($inRoom && $user !== null) {
            $payload['room_status'] = 'joined';
            $payload['in_room'] = true;
            $payload['can_join_video'] = true;
            $payload['message'] = 'Bạn đang trong phòng học.';
            $tokenResult = $this->jitsiTokenPayload($user, $roomId);

            if (! isset($tokenResult['error'])) {
                $payload['video'] = $tokenResult['payload'];
            }

            return ['payload' => $payload];
        }

        if (($joinRequest['status'] ?? '') === 'pending') {
            $payload['room_status'] = 'pending';
            $payload['join_request'] = $joinRequest;
            $payload['can_join_video'] = false;
            $payload['message'] = 'Đang chờ giáo viên duyệt vào lớp.';

            return ['payload' => $payload];
        }

        if (($joinRequest['status'] ?? '') === 'approved') {
            $payload['room_status'] = 'approved';
            $payload['join_request'] = $joinRequest;
            $payload['can_join_video'] = true;
            $payload['message'] = 'Giáo viên đã duyệt — gọi POST vao-phong để vào video.';

            return ['payload' => $payload];
        }

        $payload['can_join_video'] = ! empty($row['is_joinable']);
        $payload['message'] = ! empty($row['is_joinable'])
            ? 'Có thể vào phòng học.'
            : 'Buổi học chưa đến giờ hoặc đã kết thúc.';

        return ['payload' => $payload];
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function joinSessionMeeting(int $userId, string $publicCode, ?array $body = null): array
    {
        $roomPassword = trim((string) ($body['room_password'] ?? ''));
        $joinResult = $this->joinSession($userId, $publicCode);

        if (isset($joinResult['error'])) {
            return $joinResult;
        }

        $base = $joinResult['payload'];
        $roomId = (int) ($base['online_room']['id'] ?? 0);
        $publicCode = (string) ($base['session']['public_code'] ?? $publicCode);

        if ($roomId <= 0) {
            return ['error' => $this->api->error('ROOM_UNAVAILABLE', 'Không tìm thấy phòng học video cho buổi này.', 503)];
        }

        $enterResult = $this->enterRoom($userId, $roomId, $roomPassword);

        if (isset($enterResult['error'])) {
            return $enterResult;
        }

        $enterPayload = $enterResult['payload'];
        $roomStatus = (string) ($enterPayload['status'] ?? 'joined');

        $payload = [
            'message' => (string) ($base['message'] ?? ''),
            'attendance' => $base['attendance'] ?? [],
            'session' => $base['session'] ?? [],
            'online_room' => $enterPayload['online_room'] ?? $base['online_room'] ?? null,
            'room_id' => $roomId,
            'room_status' => $roomStatus,
            'in_room' => $roomStatus === 'joined',
            'join_request' => $enterPayload['join_request'] ?? null,
            'video' => null,
            'urls' => array_merge(
                [
                    'meeting_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/phong-hoc'),
                    'ping' => $this->api->apiPath('lop-hoc-online/'.$roomId.'/ping'),
                    'leave' => $this->api->apiPath('lop-hoc-online/'.$roomId.'/roi-phong'),
                ],
                $this->api->roomApiUrls($roomId)
            ),
        ];

        if ($roomStatus === 'pending') {
            $payload['message'] = (string) ($enterPayload['message'] ?? 'Đang chờ giáo viên duyệt vào lớp.');
            $payload['next_step'] = 'GET '.$this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/phong-hoc');

            return ['payload' => $payload];
        }

        $payload['message'] = trim((string) ($base['message'] ?? '').' '.(string) ($enterPayload['message'] ?? ''));
        $user = User::query()->find($userId);
        $tokenResult = $user !== null
            ? $this->jitsiTokenPayload($user, $roomId)
            : ['error' => $this->api->error('USER_NOT_FOUND', 'Không tìm thấy tài khoản.', 404)];

        if (isset($tokenResult['error'])) {
            $payload['video_error'] = $tokenResult['error'];
            $payload['next_step'] = 'GET '.$this->api->apiPath('lop-hoc-online/'.$roomId.'/jitsi-token');
        } else {
            $payload['video'] = $tokenResult['payload'];
            $payload['next_step'] = 'Mở video bằng video.jitsi_app_link hoặc nhúng WebView với video.join_url';
        }

        return ['payload' => $payload];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function joinSession(int $userId, string $publicCode): array
    {
        if ($error = $this->api->requireStudent($userId)) {
            return ['error' => $error];
        }

        $resolved = $this->resolveStudentSession($userId, $publicCode);

        if (isset($resolved['error'])) {
            return $resolved;
        }

        $session = $resolved['session'];
        $row = $resolved['row'];

        if (empty($row['is_viewable'])) {
            return ['error' => $this->api->error('SESSION_NOT_AVAILABLE', 'Buổi học này chưa mở. Chỉ tham gia buổi học gần nhất.', 403)];
        }

        if (empty($row['is_joinable'])) {
            return ['error' => $this->api->error('SESSION_NOT_JOINABLE', 'Buổi học chưa đến giờ hoặc đã kết thúc.', 403)];
        }

        $attendance = CourseSessionAttendance::markPresent((int) $session->id, $userId);
        $course = $session->course;

        if ($course === null) {
            return ['error' => $this->api->error('ROOM_UNAVAILABLE', 'Không thể mở phòng học Zoom. Vui lòng thử lại.', 503)];
        }

        try {
            $room = $this->rooms->ensure($course);
        } catch (Throwable $e) {
            report($e);

            return ['error' => $this->api->error('ROOM_UNAVAILABLE', 'Không thể mở phòng học Zoom. Vui lòng thử lại.', 503)];
        }

        if ($room === null) {
            return ['error' => $this->api->error('ROOM_UNAVAILABLE', 'Không thể mở phòng học Zoom. Vui lòng thử lại.', 503)];
        }

        $row['online_room_id'] = (int) $room->id;
        $message = ! empty($attendance['first'])
            ? 'Đã điểm danh thành công.'
            : 'Bạn đã điểm danh trước đó.';

        if (! empty($row['waiting_teacher'])) {
            $message .= ' Giáo viên chưa vào lớp — vui lòng đợi.';
        }

        return [
            'payload' => [
                'message' => $message,
                'attendance' => [
                    'first_time' => ! empty($attendance['first']),
                    'joined_at' => (string) ($attendance['joined_at'] ?? ''),
                ],
                'session' => $this->formatSession($row, $session, $userId, true),
                'online_room' => $this->formatOnlineRoom((int) $room->id),
                'next_step' => 'POST '.$this->api->apiPath('lop-hoc-online/'.$room->id.'/vao'),
            ],
        ];
    }

    /**
     * @return array{session: CourseSession, row: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function resolveStudentSession(int $userId, string $publicCode): array
    {
        $publicCode = trim($publicCode);

        if ($publicCode === '') {
            return ['error' => $this->api->error('VALIDATION_ERROR', 'Mã buổi học không hợp lệ.', 422)];
        }

        $session = CourseSession::query()
            ->with(['course.teacher', 'course.room', 'course.program', 'programSession.level'])
            ->where('public_code', $publicCode)
            ->first();

        if ($session === null) {
            return ['error' => $this->api->error('SESSION_NOT_FOUND', 'Buổi học không tồn tại.', 404)];
        }

        $course = $session->course;

        if ($course === null || $course->status !== Course::STATUS_ACTIVE) {
            return ['error' => $this->api->error('COURSE_INACTIVE', 'Khóa học không hoạt động.', 403)];
        }

        if (! CourseMember::isMemberOf((int) $course->id, $userId)) {
            return ['error' => $this->api->error('FORBIDDEN', 'Bạn không thuộc khóa học này.', 403)];
        }

        $row = $this->catalog->toStudentRow($session, $userId);
        $all = $this->sessionsForCourse((int) $course->id, $userId);
        $nearestId = $this->catalog->nearestSessionId($all);
        $row['is_viewable'] = $row['phase'] === 'ended' || ((int) $row['id'] === $nearestId && $nearestId > 0);

        return [
            'session' => $session,
            'row' => $row,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessionsForCourse(int $courseId, int $userId): array
    {
        $sessions = CourseSession::query()
            ->with(['programSession.level', 'course.teacher', 'course.room'])
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->inProgramOrder()
            ->get();

        $rows = [];

        foreach ($sessions as $session) {
            $rows[] = $this->catalog->toStudentRow($session, $userId);
        }

        $nearestId = $this->catalog->nearestSessionId($rows);

        foreach ($rows as &$row) {
            $row['is_viewable'] = ($row['phase'] ?? '') === 'ended'
                || ((int) ($row['id'] ?? 0) === $nearestId && $nearestId > 0);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSession(array $row, ?CourseSession $model, int $userId, bool $hasAttended): array
    {
        $phase = (string) ($row['phase'] ?? '');
        $publicCode = (string) ($row['public_code'] ?? '');
        $roomId = (int) ($row['online_room_id'] ?? 0);
        $meetingUrls = $this->meetingUrlsForSession($roomId);
        $description = $this->api->htmlFields((string) ($row['description'] ?? ''));
        $window = $model?->window() ?? ['start' => PHP_INT_MAX, 'end' => PHP_INT_MAX, 'early' => PHP_INT_MAX];
        $display = $this->api->displayFromSessionRow($row);
        $display['description_label'] = $description['description'];

        return [
            'id' => (int) ($row['id'] ?? 0),
            'public_code' => $publicCode,
            'title' => (string) ($row['title'] ?? ''),
            'description' => $description['description'],
            'description_html' => $description['description_html'],
            'has_description' => $description['has_description'],
            'session_date' => (string) ($row['session_date'] ?? ''),
            'start_time' => substr((string) ($row['start_time'] ?? ''), 0, 8),
            'end_time' => substr((string) ($row['end_time'] ?? ''), 0, 8),
            'status' => (string) ($row['status'] ?? ''),
            'phase' => $phase,
            'phase_label' => CourseSession::phaseLabels()[$phase] ?? $phase,
            'is_viewable' => ! empty($row['is_viewable']),
            'is_joinable' => ! empty($row['is_joinable']),
            'waiting_teacher' => ! empty($row['waiting_teacher']),
            'teacher_in_room' => ! empty($row['teacher_in_room']),
            'has_attended' => $hasAttended,
            'online_room_id' => $roomId,
            'join_url' => $meetingUrls['join_url'],
            'meeting_url' => $meetingUrls['meeting_url'],
            'jitsi_domain' => $meetingUrls['jitsi_domain'] ?? null,
            'jitsi_room_name' => $meetingUrls['jitsi_room_name'] ?? null,
            'jitsi_meet_url' => $meetingUrls['jitsi_meet_url'] ?? null,
            'display' => $display,
            'window' => $this->api->formatWindow($window),
            'urls' => array_merge([
                'join' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/vao'),
                'join_meeting' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/vao-phong'),
                'meeting_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/phong-hoc'),
                'join_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/trang-thai'),
                'quiz_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/trang-thai-bai-tap'),
                'web_detail' => $this->api->absoluteUrl('khoa-hoc-cua-toi/buoi/'.$publicCode),
            ], $meetingUrls),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSessionHeader(array $row, CourseSession $session, int $userId): array
    {
        $phase = (string) ($row['phase'] ?? '');
        $display = $this->api->displayFromSessionRow($row);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'public_code' => (string) ($row['public_code'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'session_date' => (string) ($row['session_date'] ?? ''),
            'start_time' => substr((string) ($row['start_time'] ?? ''), 0, 8),
            'end_time' => substr((string) ($row['end_time'] ?? ''), 0, 8),
            'phase' => $phase,
            'phase_label' => CourseSession::phaseLabels()[$phase] ?? $phase,
            'is_viewable' => true,
            'is_joinable' => ! empty($row['is_joinable']),
            'waiting_teacher' => ! empty($row['waiting_teacher']),
            'teacher_in_room' => ! empty($row['teacher_in_room']),
            'has_attended' => CourseSessionAttendance::hasAttended((int) $session->id, $userId),
            'online_room_id' => (int) ($row['online_room_id'] ?? 0),
            'display' => [
                'time_label' => $display['time_label'],
                'date_label' => $display['date_label'],
                'weekday_full' => $display['weekday_full'],
                'countdown_label' => $display['countdown_label'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formatOnlineRoom(int $roomId): ?array
    {
        if ($roomId <= 0) {
            return null;
        }

        $room = OnlineRoom::query()->find($roomId);

        if ($room === null) {
            return null;
        }

        $urls = $this->buildMeetingUrls($room);

        return [
            'id' => $roomId,
            'title' => (string) $room->title,
            'room_code' => (string) $room->room_code,
            'status' => (string) $room->status,
            'waiting_room' => (bool) $room->waiting_room,
            'video_provider' => 'jitsi',
            'video_label' => 'Zoom trực tuyến (Jitsi/JaaS)',
            'needs_password' => trim((string) ($room->room_password ?? '')) !== '',
            'max_participants' => (int) ($room->max_participants ?? 50),
            'jaas_ready' => $this->classroomConfig->isJaasReady(),
            'join_url' => $urls['join_url'],
            'meeting_url' => $urls['meeting_url'],
            'guest_join_url' => $urls['guest_join_url'],
            'jitsi_domain' => $urls['jitsi_domain'] ?? null,
            'jitsi_room_name' => $urls['jitsi_room_name'] ?? null,
            'jitsi_meet_url' => $urls['jitsi_meet_url'] ?? null,
            'jitsi_app_scheme' => $urls['jitsi_app_scheme'] ?? JitsiMeetLink::APP_SCHEME,
            'urls' => $urls,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function meetingUrlsForSession(int $roomId): array
    {
        if ($roomId <= 0) {
            return [
                'join_url' => null,
                'meeting_url' => null,
                'guest_join_url' => null,
                'jitsi_domain' => null,
                'jitsi_room_name' => null,
                'jitsi_meet_url' => null,
                'jitsi_app_link' => null,
            ];
        }

        $urls = $this->buildMeetingUrls(OnlineRoom::query()->find($roomId));

        return [
            'join_url' => $urls['join_url'] ?? null,
            'meeting_url' => $urls['meeting_url'] ?? null,
            'guest_join_url' => $urls['guest_join_url'] ?? null,
            'web_lobby' => $urls['web_lobby'] ?? null,
            'web_room' => $urls['web_room'] ?? null,
            'enter_api' => $urls['enter_api'] ?? null,
            'jitsi_token' => $urls['jitsi_token'] ?? null,
            'jitsi_domain' => $urls['jitsi_domain'] ?? null,
            'jitsi_room_name' => $urls['jitsi_room_name'] ?? null,
            'jitsi_meet_url' => $urls['jitsi_meet_url'] ?? null,
            'jitsi_app_scheme' => $urls['jitsi_app_scheme'] ?? JitsiMeetLink::APP_SCHEME,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSessionListItem(array $session, int $sessionNo): array
    {
        $phase = (string) ($session['phase'] ?? '');
        $publicCode = (string) ($session['public_code'] ?? '');
        $sessionDate = (string) ($session['session_date'] ?? '');
        $title = trim((string) ($session['title'] ?? ''));
        $roomId = (int) ($session['online_room_id'] ?? 0);
        $meetingUrls = $this->meetingUrlsForSession($roomId);
        $description = $this->api->htmlFields((string) ($session['description'] ?? ''));

        return [
            'id' => (int) ($session['id'] ?? 0),
            'public_code' => $publicCode,
            'session_no' => $sessionNo,
            'title' => $title !== '' ? $title : ('Buổi '.$sessionNo),
            'description' => $description['description'],
            'description_html' => $description['description_html'],
            'has_description' => $description['has_description'],
            'session_date' => $sessionDate,
            'session_date_label' => $sessionDate !== '' ? date('d/m/Y', strtotime($sessionDate)) : '',
            'start_time' => substr((string) ($session['start_time'] ?? ''), 0, 5),
            'end_time' => substr((string) ($session['end_time'] ?? ''), 0, 5),
            'status' => (string) ($session['status'] ?? ''),
            'phase' => $phase,
            'phase_label' => CourseSession::phaseLabels()[$phase] ?? $phase,
            'is_viewable' => ! empty($session['is_viewable']),
            'is_joinable' => ! empty($session['is_joinable']),
            'waiting_teacher' => ! empty($session['waiting_teacher']),
            'teacher_in_room' => ! empty($session['teacher_in_room']),
            'has_attended' => ! empty($session['has_attended']),
            'program_session_id' => (int) ($session['program_session_id'] ?? 0),
            'online_room_id' => $roomId,
            'join_url' => $meetingUrls['join_url'],
            'meeting_url' => $meetingUrls['meeting_url'],
            'jitsi_domain' => $meetingUrls['jitsi_domain'] ?? null,
            'jitsi_room_name' => $meetingUrls['jitsi_room_name'] ?? null,
            'jitsi_meet_url' => $meetingUrls['jitsi_meet_url'] ?? null,
            'display' => $this->api->displayFromSessionRow($session),
            'urls' => array_merge([
                'web_detail' => $this->api->absoluteUrl('khoa-hoc-cua-toi/buoi/'.$publicCode),
                'join_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/trang-thai'),
                'quiz_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/trang-thai-bai-tap'),
                'join' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/vao'),
                'join_meeting' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/vao-phong'),
                'meeting_status' => $this->api->apiPath('hoc-vien/buoi-hoc/'.$publicCode.'/phong-hoc'),
            ], $meetingUrls),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSessionSummary(array $session): array
    {
        $phase = (string) ($session['phase'] ?? '');
        $display = $this->api->displayFromSessionRow($session);
        $description = $this->api->htmlFields((string) ($session['description'] ?? ''));

        return [
            'id' => (int) ($session['id'] ?? 0),
            'public_code' => (string) ($session['public_code'] ?? ''),
            'title' => (string) ($session['title'] ?? ''),
            'description' => $description['description'],
            'description_html' => $description['description_html'],
            'has_description' => $description['has_description'],
            'session_date' => (string) ($session['session_date'] ?? ''),
            'phase' => $phase,
            'phase_label' => CourseSession::phaseLabels()[$phase] ?? $phase,
            'time_label' => $display['time_label'],
            'date_label' => $display['date_label'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMeetingUrls(?OnlineRoom $room): array
    {
        if ($room === null) {
            return [
                'join_url' => null,
                'meeting_url' => null,
                'guest_join_url' => null,
                'web_lobby' => null,
                'web_room' => null,
                'web_detail' => null,
                'guest_join' => null,
                'enter_api' => null,
                'jitsi_token' => null,
                'join_request_status' => null,
                'video_link' => null,
                'jitsi_domain' => null,
                'jitsi_room_name' => null,
                'jitsi_meet_url' => null,
                'jitsi_app_scheme' => JitsiMeetLink::APP_SCHEME,
                'jitsi_app_link' => null,
            ];
        }

        $roomId = (int) $room->id;
        $urls = $this->api->roomWebUrls($roomId);
        $guestJoin = $room->waiting_room
            ? $this->api->absoluteUrl('lop-hoc-online/'.$roomId.'/khach')
            : null;

        return array_merge($urls, [
            'guest_join_url' => $guestJoin,
            'guest_join' => $guestJoin,
        ], $this->api->jitsiMeta((string) $room->room_code));
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    private function enterRoom(int $userId, int $roomId, string $roomPassword): array
    {
        $user = User::query()->find($userId);
        $room = OnlineRoom::query()->find($roomId);

        if ($user === null) {
            return ['error' => $this->api->error('USER_NOT_FOUND', 'Không tìm thấy tài khoản.', 404)];
        }

        if ($room === null || ! $this->classroom->canAccess($room, $user)) {
            return ['error' => $this->api->error('FORBIDDEN', 'Bạn không có quyền vào phòng học này.', 403)];
        }

        if (trim((string) ($room->room_password ?? '')) !== '' && $roomPassword !== (string) $room->room_password) {
            return ['error' => $this->api->error('INVALID_ROOM_PASSWORD', 'Mật khẩu phòng không đúng.', 403)];
        }

        if ($this->classroom->requiresApproval($room, $user)) {
            $existing = OnlineRoomJoinRequest::getActiveForUser($roomId, $userId);

            if ($existing === null || $existing->status !== 'approved') {
                if ($existing === null || $existing->status !== 'pending') {
                    OnlineRoomJoinRequest::createPending($roomId, $userId);
                    $existing = OnlineRoomJoinRequest::getActiveForUser($roomId, $userId);
                }

                return [
                    'payload' => [
                        'status' => 'pending',
                        'room_id' => $roomId,
                        'message' => 'Đang chờ giáo viên duyệt vào lớp.',
                        'online_room' => $this->formatOnlineRoom($roomId),
                        'join_request' => $this->joinRequestPayload($userId, $roomId),
                        'urls' => $this->api->roomApiUrls($roomId),
                    ],
                ];
            }
        }

        $timeout = $this->classroomConfig->presenceTimeout();
        $max = max(1, (int) ($room->max_participants ?: 50));

        if (
            $this->participants->onlineParticipant($roomId, $userId, $timeout) === null
            && $this->participants->countOnline($roomId, $timeout) >= $max
        ) {
            return ['error' => $this->api->error('ROOM_FULL', 'Phòng đã đầy ('.$max.' người).', 403)];
        }

        $this->classroom->enter($room, $user);

        return [
            'payload' => [
                'status' => 'joined',
                'room_id' => $roomId,
                'message' => 'Đã vào phòng học.',
                'online_room' => $this->formatOnlineRoom($roomId),
                'urls' => $this->api->roomApiUrls($roomId),
                'next_step' => 'GET '.$this->api->apiPath('lop-hoc-online/'.$roomId.'/jitsi-token'),
            ],
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    private function jitsiTokenPayload(User $user, int $roomId): array
    {
        $room = OnlineRoom::query()->find($roomId);

        if ($room === null) {
            return ['error' => $this->api->error('FORBIDDEN', 'Không có quyền vào phòng học.', 403)];
        }

        if (! $this->classroomConfig->isJaasReady()) {
            return ['error' => $this->api->error('VIDEO_NOT_CONFIGURED', 'Phòng học video chưa được cấu hình trên server.', 503)];
        }

        if (! $this->isUserInRoom((int) $user->id, $roomId)) {
            return ['error' => $this->api->error('NOT_IN_ROOM', 'Chưa tham gia phòng. Gọi POST /lop-hoc-online/{id}/vao trước.', 403)];
        }

        $video = $this->classroom->videoJoinPayload($room, $user);

        if (empty($video['ok'])) {
            return ['error' => $this->api->error(
                'VIDEO_TOKEN_ERROR',
                (string) ($video['message'] ?? 'Không tạo được token phòng học.'),
                (int) ($video['status'] ?? 500)
            )];
        }

        $roomName = (string) ($video['roomName'] ?? '');
        $domain = (string) ($video['domain'] ?? '');

        return [
            'payload' => [
                'provider' => 'jitsi',
                'jwt' => (string) ($video['jwt'] ?? ''),
                'domain' => $domain,
                'room_name' => $roomName,
                'room_code' => (string) $room->room_code,
                'expires_in' => $this->classroomConfig->jaasJwtTtl(),
                'jitsi_meet_url' => $roomName !== '' ? JitsiMeetLink::baseMeetUrl($domain, $roomName) : null,
                'join_url' => (string) ($video['joinUrl'] ?? ''),
                'jitsi_app_link' => (string) ($video['appDeepLink'] ?? ''),
                'jitsi_app_scheme' => JitsiMeetLink::APP_SCHEME,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function joinRequestPayload(int $userId, int $roomId): array
    {
        $request = OnlineRoomJoinRequest::getActiveForUser($roomId, $userId);

        if ($request === null) {
            return ['status' => 'none', 'can_enter' => false];
        }

        $status = (string) $request->status;

        return [
            'status' => $status,
            'request_id' => (int) $request->id,
            'review_note' => (string) ($request->review_note ?? ''),
            'can_enter' => $status === 'approved' && $this->isUserInRoom($userId, $roomId),
        ];
    }

    private function isUserInRoom(int $userId, int $roomId): bool
    {
        return $this->participants->onlineParticipant($roomId, $userId, $this->classroomConfig->presenceTimeout()) !== null;
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     */
    private function findSessionRow(array $sessions, int $sessionId): ?array
    {
        if ($sessionId <= 0) {
            return null;
        }

        foreach ($sessions as $session) {
            if ((int) ($session['id'] ?? 0) === $sessionId) {
                return $session;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return list<array<string, mixed>>
     */
    private function upcomingSessions(array $sessions, int $excludeId, int $limit): array
    {
        $items = [];

        foreach ($sessions as $session) {
            if ((int) ($session['id'] ?? 0) === $excludeId) {
                continue;
            }

            if (! in_array((string) ($session['phase'] ?? ''), ['live', 'upcoming'], true)) {
                continue;
            }

            $items[] = $session;
        }

        usort($items, static function (array $a, array $b): int {
            $phaseOrder = ['live' => 0, 'upcoming' => 1];
            $phaseCmp = ($phaseOrder[$a['phase'] ?? 'upcoming'] ?? 2) <=> ($phaseOrder[$b['phase'] ?? 'upcoming'] ?? 2);

            if ($phaseCmp !== 0) {
                return $phaseCmp;
            }

            return strcmp((string) ($a['session_date'] ?? ''), (string) ($b['session_date'] ?? ''));
        });

        return array_slice($items, 0, $limit);
    }

    private function syncCourseRoom(Course $course): void
    {
        try {
            $this->rooms->ensure($course);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
