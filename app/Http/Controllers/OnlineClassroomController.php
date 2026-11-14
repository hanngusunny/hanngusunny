<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOnlineClassroomRequest;
use App\Models\OnlineRoom;
use App\Models\OnlineRoomJoinRequest;
use App\Services\AdminClassroomService;
use App\Services\ClassroomService;
use App\Services\GuestUserService;
use App\Services\StudentSessionCatalogService;
use App\Support\ClassroomConfig;
use App\Support\PwaClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlineClassroomController extends Controller
{
    public function __construct(
        private ClassroomService $classroom,
        private ClassroomConfig $config,
        private StudentSessionCatalogService $catalog,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->isGuest()) {
            return redirect()->to($user->homePath());
        }

        $rooms = $this->classroom->availableForUser($user);

        return view('classrooms.index', [
            'rooms' => $rooms,
            'statuses' => OnlineRoom::statusLabels(),
            'liveCount' => $rooms->where('status', 'live')->count(),
            'scheduledCount' => $rooms->where('status', 'scheduled')->count(),
            'onlineTotal' => (int) $rooms->sum('online_count'),
            'canCreateRoom' => $user->isStaff(),
            'isStaff' => $user->isStaff(),
            'showJaasWarning' => ! $user->isLearner(),
            'jaasReady' => $this->config->isJaasReady(),
            'jaasConfigured' => $this->config->isJaasConfigured(),
            'jaasMissingKey' => $this->config->isJaasConfigured() && ! $this->config->isJaasReady(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null || ! $user->isStaff()) {
            return redirect()->route('classroom.index')->with('error', 'Bạn không có quyền tạo phòng học.');
        }

        return view('classrooms.form', [
            'teachers' => $user->isAdmin() ? app(AdminClassroomService::class)->hostOptions() : collect(),
            'statuses' => OnlineRoom::statusLabels(),
            'accessTypes' => OnlineRoom::accessTypeLabels(),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function store(StoreOnlineClassroomRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['scheduled_start'] = $this->normalizeDateTime($data['scheduled_start'] ?? null);
        $data['scheduled_end'] = $this->normalizeDateTime($data['scheduled_end'] ?? null);
        $data['waiting_room'] = $request->boolean('waiting_room');
        $data['room_password'] = trim((string) ($data['room_password'] ?? ''));

        $room = $this->classroom->createStandaloneRoom($request->user(), $data);

        return redirect()->route('classroom.lobby', $room->id)
            ->with('success', 'Đã tạo phòng học thành công.');
    }

    public function lobby(Request $request, int $id): View|RedirectResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($room === null || $user === null || ! $this->classroom->canAccessLobby($room, $user)) {
            return redirect()->to($user?->homePath() ?? route('login'))->with('error', 'Không có quyền truy cập phòng học.');
        }

        $timeout = $this->config->presenceTimeout();
        $onlineCount = app(\App\Models\OnlineRoomParticipant::class)->countOnline($id, $timeout);
        $isHost = $this->classroom->isModerator($room, $user);
        $watchSession = null;
        $waitingForTeacher = false;
        $jitsi = $this->classroom->jitsiMeta($room);
        $requiresApproval = $this->classroom->requiresApproval($room, $user);
        $joinRequest = $requiresApproval ? OnlineRoomJoinRequest::getActiveForUser($id, (int) $user->id) : null;

        if ($user->isLearner()) {
            $session = $this->classroom->joinableSession($id);

            if ($session !== null) {
                $watchSession = $this->catalog->toStudentRow($session, (int) $user->id);
                $waitingForTeacher = ! empty($watchSession['waiting_teacher']);
            }
        }

        if ($this->classroom->canAutoJoinMeeting($room, $user)) {
            return redirect()->to(PwaClient::roomUrl($id, $user, $request));
        }

        return view('classrooms.lobby', [
            'room' => $room,
            'statuses' => OnlineRoom::statusLabels(),
            'onlineCount' => $onlineCount,
            'isHost' => $isHost,
            'needsPassword' => filled($room->room_password),
            'waitingForTeacher' => $waitingForTeacher,
            'watchSession' => $watchSession,
            'showTeacherLobbyLinks' => $user->isTeacher() || $user->isAdmin(),
            'jitsiMeetUrl' => $isHost || $user->isTeacher() || $user->isAdmin() ? $jitsi['jitsi_meet_url'] : null,
            'jitsiRoomName' => $isHost || $user->isTeacher() || $user->isAdmin() ? $jitsi['jitsi_room_name'] : null,
            'jitsiDomain' => $isHost || $user->isTeacher() || $user->isAdmin() ? $jitsi['jitsi_domain'] : null,
            'userName' => $user->name,
            'roleLabel' => $this->classroom->roleLabel($user),
            'requiresApproval' => $requiresApproval,
            'joinRequest' => $joinRequest,
            'guestJoinUrl' => $room->waiting_room ? route('classroom.guest', $id) : null,
            'showGuestJoinLink' => ($user->isTeacher() || $user->isAdmin()) && (int) $room->host_id === (int) $user->id && $room->waiting_room,
            'joinStatusUrl' => route('classroom.join-status', $id),
        ]);
    }

    public function enter(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($room === null || $user === null || ! $this->classroom->canAccessLobby($room, $user)) {
            return redirect()->to($user?->homePath() ?? route('login'))->with('error', 'Không có quyền truy cập phòng học.');
        }

        if (filled($room->room_password) && (string) $request->input('room_password') !== (string) $room->room_password) {
            return redirect()->route('classroom.lobby', $id)->with('error', 'Mật khẩu phòng không đúng.');
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $onlineCount = $participants->countOnline($id, $this->config->presenceTimeout());
        $max = max(1, (int) ($room->max_participants ?: 50));

        if ($onlineCount >= $max) {
            return redirect()->route('classroom.lobby', $id)
                ->with('error', 'Phòng đã đầy ('.$max.' người). Vui lòng thử lại sau.');
        }

        if ($user->isLearner()) {
            $session = $this->classroom->joinableSession($id);

            if ($session !== null) {
                $row = $this->catalog->toStudentRow($session, (int) $user->id);

                if (! empty($row['waiting_teacher'])) {
                    session()->flash('info', 'Giáo viên chưa vào lớp. Bạn có thể vào phòng và đợi giáo viên.');
                }
            }
        }

        if ($this->classroom->requiresApproval($room, $user)) {
            $result = $this->classroom->handleWaitingRoomEnter($room, $user);
            $redirect = redirect()->to($result['redirect']);

            if (! empty($result['message'])) {
                $redirect->with($result['tone'] ?? 'success', $result['message']);
            }

            return $redirect;
        }

        $this->classroom->enter($room, $user);

        return redirect()->to(PwaClient::roomUrl($id, $user, $request));
    }

    public function guestLobby(int $id): View|RedirectResponse
    {
        $room = $this->classroom->findRoom($id);

        if ($room === null || ! $room->waiting_room) {
            return redirect()->route('login')->with('error', 'Phòng học không cho phép khách vào bằng nick ngoài.');
        }

        if (in_array((string) $room->status, ['draft', 'cancelled', 'ended'], true)) {
            return redirect()->route('login')->with('error', 'Phòng học hiện không mở.');
        }

        return view('classrooms.guest-lobby', [
            'room' => $room,
            'roomId' => $id,
        ]);
    }

    public function guestRequestJoin(Request $request, int $id): RedirectResponse
    {
        $room = $this->classroom->findRoom($id);

        if ($room === null || ! $room->waiting_room) {
            return redirect()->route('login')->with('error', 'Phòng học không nhận khách nick ngoài.');
        }

        if (filled($room->room_password) && (string) $request->input('room_password') !== (string) $room->room_password) {
            return redirect()->route('classroom.guest', $id)->with('error', 'Mật khẩu phòng không đúng.');
        }

        $guestName = trim((string) $request->input('guest_name', ''));

        if ($guestName === '' || mb_strlen($guestName) < 2) {
            return redirect()->route('classroom.guest', $id)->with('error', 'Vui lòng nhập tên hiển thị (tối thiểu 2 ký tự).');
        }

        try {
            $guest = app(GuestUserService::class)->createGuestUser($guestName);
        } catch (\Throwable $e) {
            return redirect()->route('classroom.guest', $id)->with('error', $e->getMessage());
        }

        $joinRequest = OnlineRoomJoinRequest::createPending($id, (int) $guest['id'], true, $guestName);

        app(GuestUserService::class)->loginGuestSession(
            $guest['user'],
            $id,
            (int) ($joinRequest?->id ?? 0)
        );

        return redirect()->route('classroom.lobby', $id)
            ->with('success', 'Đã gửi yêu cầu vào lớp. Vui lòng chờ giáo viên duyệt.');
    }

    public function joinRequestStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['ok' => false, 'message' => 'Cần đăng nhập.'], 401);
        }

        $joinRequest = OnlineRoomJoinRequest::getActiveForUser($id, (int) $user->id);

        if ($joinRequest === null) {
            return response()->json(['ok' => true, 'status' => 'none']);
        }

        $status = (string) $joinRequest->status;

        return response()->json([
            'ok' => true,
            'status' => $status,
            'requestId' => (int) $joinRequest->id,
            'reviewNote' => (string) ($joinRequest->review_note ?? ''),
            'canEnter' => $status === 'approved',
            'redirectUrl' => $status === 'approved'
                ? PwaClient::roomUrl($id, $user, $request)
                : route('classroom.lobby', $id),
        ]);
    }

    public function approveJoinRequest(Request $request, int $id, int $requestId): JsonResponse
    {
        return $this->reviewJoinRequest($request, $id, $requestId, true);
    }

    public function rejectJoinRequest(Request $request, int $id, int $requestId): JsonResponse
    {
        return $this->reviewJoinRequest($request, $id, $requestId, false);
    }

    private function reviewJoinRequest(Request $request, int $roomId, int $requestId, bool $approve): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($roomId);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Không có quyền duyệt.'], 403);
        }

        $joinRequest = OnlineRoomJoinRequest::query()->find($requestId);

        if ($joinRequest === null || (int) $joinRequest->room_id !== $roomId) {
            return response()->json(['ok' => false, 'message' => 'Yêu cầu không tồn tại.'], 404);
        }

        if ($joinRequest->status !== 'pending') {
            return response()->json(['ok' => false, 'message' => 'Yêu cầu đã được xử lý.'], 409);
        }

        $targetUserId = (int) ($joinRequest->user_id ?? 0);

        if ($targetUserId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Yêu cầu không hợp lệ.'], 422);
        }

        if ($approve) {
            $onlineCount = app(\App\Models\OnlineRoomParticipant::class)->countOnline($roomId, $this->config->presenceTimeout());
            $max = max(1, (int) ($room->max_participants ?: 50));

            if ($onlineCount >= $max) {
                return response()->json(['ok' => false, 'message' => 'Phòng đã đầy.'], 409);
            }

            if (! $joinRequest->approve((int) $user->id)) {
                return response()->json(['ok' => false, 'message' => 'Không duyệt được yêu cầu.'], 500);
            }

            $target = \App\Models\User::query()->find($targetUserId);
            $role = $target ? $this->classroom->participantRole($room, $target) : 'participant';
            app(\App\Models\OnlineRoomParticipant::class)->joinRoom($roomId, $targetUserId, $role);

            if ($room->status === 'scheduled') {
                $room->update(['status' => 'live']);
            }

            return response()->json([
                'ok' => true,
                'message' => 'Đã duyệt '.$joinRequest->fresh()?->displayName().' vào lớp.',
            ]);
        }

        if (! $joinRequest->reject((int) $user->id, (string) $request->input('note', ''))) {
            return response()->json(['ok' => false, 'message' => 'Không từ chối được yêu cầu.'], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Đã từ chối yêu cầu vào lớp.',
        ]);
    }

    public function room(Request $request, int $id): View|RedirectResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($room === null || $user === null || ! $this->classroom->canAccess($room, $user)) {
            return redirect()->route('dashboard')->with('error', 'Phòng học không tồn tại hoặc bạn không có quyền truy cập.');
        }

        if ($this->config->isJaasConfigured() && ! $this->config->isJaasReady()) {
            return redirect()->route('classroom.lobby', $id)->with(
                'error',
                'JaaS chưa sẵn sàng: thiếu private key. Đặt file vào storage/app/jaas/private.pk rồi thử lại.'
            );
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $participant = $participants->onlineParticipant($id, (int) $user->id, $this->config->presenceTimeout());

        if ($participant === null) {
            if (! $this->classroom->canAutoJoinMeeting($room, $user)) {
                return redirect()->route('classroom.lobby', $id);
            }

            $this->classroom->enter($room, $user);
            $participant = $participants->onlineParticipant($id, (int) $user->id, $this->config->presenceTimeout());
        }

        $isModerator = $this->classroom->isModerator($room, $user);
        $breakoutIndex = (int) ($participant?->breakout_room_index ?? 0);
        $jitsi = $this->classroom->jitsiMeta($room, $breakoutIndex);
        $roleLabel = $this->classroom->roleLabel($user);
        $avatarUrl = $user->avatarUrl()
            ?: 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=ea580c&color=fff&size=128';

        return view('classrooms.room', [
            'room' => $room,
            'userName' => $user->name,
            'userEmail' => (string) ($user->email ?? ''),
            'userId' => (int) $user->id,
            'userRole' => (string) $user->role,
            'userRoleLabel' => $roleLabel,
            'jitsiDisplayName' => $user->name.' ('.$roleLabel.')',
            'userAvatarUrl' => $avatarUrl,
            'isModerator' => $isModerator,
            'jitsiDomain' => $jitsi['jitsi_domain'],
            'jitsiRoomName' => $jitsi['jitsi_room_name'],
            'useJaas' => $jitsi['use_jaas'],
            'jaasAppId' => $jitsi['jaas_app_id'],
            'jaasSetupIssues' => $this->config->jaasSetupIssues(),
            'presenceInterval' => $this->config->presenceIntervalMs(),
            'breakoutIndex' => $breakoutIndex,
            'waitingRoomEnabled' => (bool) $room->waiting_room,
            'approveJoinBaseUrl' => url('lop-hoc-online/'.$id.'/xin-vao'),
            'guestJoinUrl' => $room->waiting_room ? route('classroom.guest', $id) : '',
            'tokenUrl' => route('classroom.jitsi-token', $id),
            'fullPageVideoUrl' => route('classroom.video-ios', $id),
            'videoLinkUrl' => route('classroom.video-link', $id),
            'leaveUrl' => route('classroom.leave', $id),
            'dashboardUrl' => route('dashboard'),
            'participantsUrl' => route('classroom.participants', $id),
            'pingUrl' => route('classroom.ping', $id),
            'breakOutUrl' => route('classroom.break-out', $id),
            'breakReturnUrl' => route('classroom.break-return', $id),
            'breakoutStartUrl' => route('classroom.breakout.start', $id),
            'breakoutEndUrl' => route('classroom.breakout.end', $id),
            'breakoutJoinUrl' => route('classroom.breakout.join', $id),
            'breakoutMainUrl' => route('classroom.breakout.main', $id),
            'pollFeedUrl' => route('classroom.polls', $id),
            'pollCreateUrl' => route('classroom.polls.create', $id),
            'pollBaseUrl' => url('lop-hoc-online/'.$id.'/cau-hoi'),
            'pollPresetBaseUrl' => url('lop-hoc-online/'.$id.'/cau-hoi-soan-san'),
            'reviewQuizBaseUrl' => '',
            'joinRequestStatusUrl' => route('classroom.join-status', $id),
        ]);
    }

    public function ping(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['ok' => false], 401);
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $participants->touchPresence($id, (int) $user->id);
        $participants->markStaleOffline($id, $this->config->presenceTimeout());

        return response()->json(['ok' => true]);
    }

    public function participants(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user)) {
            return response()->json(['ok' => false, 'participants' => []], 403);
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $list = $participants->onlineList($id, $this->config->presenceTimeout());
        $isModerator = $this->classroom->isModerator($room, $user);
        $pendingJoinRequests = [];

        if ($isModerator) {
            foreach (OnlineRoomJoinRequest::pendingForRoom($id) as $row) {
                $pendingJoinRequests[] = [
                    'requestId' => (int) $row->id,
                    'userId' => (int) ($row->user_id ?? 0),
                    'name' => $row->displayName(),
                    'isGuest' => (bool) $row->is_guest,
                    'requestedAt' => optional($row->requested_at)?->format('Y-m-d H:i:s'),
                ];
            }
        }

        $breakouts = array_map(static function (array $row): array {
            return [
                'userId' => (int) ($row['userId'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'userRole' => (string) ($row['userRole'] ?? 'student'),
                'breakOutAt' => (string) ($row['breakOutAt'] ?? ''),
            ];
        }, $participants->activeBreakouts($id, $this->config->presenceTimeout()));

        return response()->json([
            'ok' => true,
            'count' => count($list),
            'participants' => $list,
            'breakouts' => $breakouts,
            'breakout' => $this->classroom->breakoutState($id),
            'breakoutSession' => $this->classroom->breakoutSessionPayload($id, (int) $user->id, $isModerator),
            'teacherInRoom' => \App\Models\OnlineRoomParticipant::isTeacherPresent(
                $id,
                $this->classroom->teacherCandidateIds($room),
                $this->config->presenceTimeout()
            ),
            'pendingJoinRequests' => $pendingJoinRequests,
        ]);
    }

    public function leave(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            app(\App\Models\OnlineRoomParticipant::class)->leaveRoom($id, (int) $user->id);
        }

        if ($user?->isGuest()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Bạn đã rời phòng học.');
        }

        return redirect()->to(PwaClient::leaveUrl($user, $request))->with('success', 'Bạn đã rời phòng học.');
    }

    public function jitsiToken(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Không có quyền.'], 403);
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $participant = $participants->findForRoom($id, (int) $user->id);

        if ($participant === null) {
            return response()->json(['ok' => false, 'message' => 'Chưa tham gia phòng.'], 403);
        }

        $participants->touchPresence($id, (int) $user->id);

        $isModerator = $this->classroom->isModerator($room, $user);
        $breakoutIndex = max(0, (int) $request->query('breakout', $participant->breakout_room_index ?? 0));
        $jitsi = $this->classroom->jitsiMeta($room, $breakoutIndex);

        try {
            $jwt = $this->classroom->issueToken(
                $room,
                $user,
                $isModerator,
                $breakoutIndex,
                (string) $request->query('client', '')
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 503);
        }

        return response()->json([
            'ok' => true,
            'jwt' => $jwt,
            'domain' => $jitsi['jitsi_domain'],
            'roomName' => $jitsi['jitsi_room_name'],
            'breakoutIndex' => $breakoutIndex,
            'jaasWarnings' => $this->config->jaasSetupIssues() ?: null,
        ]);
    }

    public function polls(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user)) {
            return response()->json(['ok' => false, 'polls' => []], 403);
        }

        return response()->json(
            $this->classroom->pollFeed($id, (int) $user->id, $this->classroom->isModerator($room, $user))
        );
    }

    public function createPoll(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user) || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Chỉ giáo viên được tạo câu hỏi.'], 403);
        }

        $question = trim((string) $request->input('question', ''));
        $type = (string) $request->input('type', 'fill');
        $options = $request->input('options', []);
        $durationMinutes = (int) $request->input('duration', 1);

        try {
            $poll = $this->classroom->createPoll($id, (int) $user->id, $question, $type, is_array($options) ? $options : [], $durationMinutes);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'poll' => $poll]);
    }

    public function launchPreset(Request $request, int $id, string $presetId): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Chỉ giáo viên mới phát được câu hỏi.'], 403);
        }

        if (! preg_match('/^-?\d+$/', $presetId)) {
            return response()->json(['ok' => false, 'message' => 'Mã câu hỏi không hợp lệ.'], 422);
        }

        try {
            $poll = $this->classroom->launchPreset($id, (int) $user->id, (int) $presetId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 404);
        }

        return response()->json(['ok' => true, 'poll' => $poll]);
    }

    public function votePoll(Request $request, int $id, int $pollId): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user)) {
            return response()->json(['ok' => false], 403);
        }

        $answer = trim((string) $request->input('answer', ''));

        if ($answer === '' && $request->filled('option')) {
            $polls = $this->classroom->polls($id);

            foreach ($polls as $poll) {
                if ((int) ($poll['id'] ?? 0) !== $pollId) {
                    continue;
                }

                $options = $poll['options'] ?? [];
                $optionIndex = (int) $request->input('option', -1);
                $answer = isset($options[$optionIndex]) ? (string) $options[$optionIndex] : '';
                break;
            }
        }

        $result = $this->classroom->votePoll($id, $pollId, (int) $user->id, $answer, $user->name);

        return response()->json($result, ! empty($result['ok']) ? 200 : 422);
    }

    public function pollStats(Request $request, int $id, int $pollId): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Chỉ giáo viên xem được thống kê.'], 403);
        }

        $stats = $this->classroom->pollStats($id, $pollId);

        if ($stats === null) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }

        return response()->json($stats);
    }

    public function closePoll(Request $request, int $id, int $pollId): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Chỉ giáo viên mới đóng được câu hỏi.'], 403);
        }

        if (! $this->classroom->closePoll($id, $pollId)) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }

        return response()->json(['ok' => true]);
    }

    public function startBreakout(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Không có quyền chia phòng.'], 403);
        }

        $result = $this->classroom->startBreakout(
            $id,
            (int) $user->id,
            (int) $request->input('room_count', $request->input('count', 2)),
            (int) $request->input('per_room_capacity', 4),
            (string) $request->input('assign_mode', 'auto'),
            $request->input('assignments')
        );

        return response()->json($result, (int) ($result['status'] ?? 200));
    }

    public function endBreakout(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false], 403);
        }

        return response()->json($this->classroom->endBreakout($id, (int) $user->id));
    }

    public function joinBreakout(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Chỉ giáo viên mới vào được phòng con.'], 403);
        }

        $session = $this->classroom->breakoutSessionPayload($id, (int) $user->id, true);

        if (empty($session['active'])) {
            return response()->json(['ok' => false, 'message' => 'Chưa có phiên chia phòng.'], 404);
        }

        $index = max(0, (int) $request->input('breakout_index', $request->input('room', 0)));
        $roomCount = (int) ($session['roomCount'] ?? 0);

        if ($index < 1 || $index > $roomCount) {
            return response()->json(['ok' => false, 'message' => 'Phòng con không hợp lệ.'], 422);
        }

        $this->classroom->assignBreakout($id, (int) $user->id, $index);

        return response()->json([
            'ok' => true,
            'message' => 'Đang chuyển vào phòng '.$index.'.',
            'myBreakoutRoom' => $index,
            'breakoutSession' => $this->classroom->breakoutSessionPayload($id, (int) $user->id, true),
        ]);
    }

    public function returnMain(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Chỉ giáo viên mới quay về phòng chính theo cách này.'], 403);
        }

        $this->classroom->assignBreakout($id, (int) $user->id, null);

        return response()->json([
            'ok' => true,
            'message' => 'Đang quay về phòng chính.',
            'myBreakoutRoom' => 0,
            'breakoutSession' => $this->classroom->breakoutSessionPayload($id, (int) $user->id, true),
        ]);
    }

    public function breakOut(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Không có quyền truy cập phòng.'], 403);
        }

        if ($this->classroom->isModerator($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Giáo viên không cần xin ra ngoài.'], 422);
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $participants->touchPresence($id, (int) $user->id);
        $breakOutAt = $participants->requestBreakOut($id, (int) $user->id);

        if ($breakOutAt === null) {
            return response()->json(['ok' => false, 'message' => 'Không thể ghi nhận xin ra ngoài. Hãy thử tải lại trang.'], 500);
        }

        return response()->json([
            'ok' => true,
            'onBreak' => true,
            'breakOutAt' => $breakOutAt,
            'message' => 'Đã báo giáo viên bạn xin ra ngoài.',
        ]);
    }

    public function breakReturn(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user)) {
            return response()->json(['ok' => false, 'message' => 'Không có quyền truy cập phòng.'], 403);
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $participants->touchPresence($id, (int) $user->id);
        $participants->returnFromBreak($id, (int) $user->id);

        return response()->json([
            'ok' => true,
            'onBreak' => false,
            'message' => 'Đã báo giáo viên bạn quay lại lớp.',
        ]);
    }

    public function videoLink(Request $request, int $id): JsonResponse
    {
        $result = $this->resolveVideoJoin($request, $id);

        if (empty($result['ok'])) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($result['message'] ?? 'Không tạo được liên kết phòng video.'),
            ], (int) ($result['status'] ?? 403));
        }

        return response()->json([
            'ok' => true,
            'joinUrl' => (string) $result['joinUrl'],
            'appDeepLink' => (string) $result['appDeepLink'],
            'webShareSupported' => false,
            'message' => 'Safari trên iPad không hỗ trợ chia sẻ màn hình qua trình duyệt web.',
        ]);
    }

    public function videoIos(Request $request, int $id): RedirectResponse
    {
        $result = $this->resolveVideoJoin($request, $id);

        if (empty($result['ok'])) {
            return redirect()->to($result['redirect'] ?? route('classroom.room', $id))
                ->with('error', (string) ($result['message'] ?? 'Không mở được phòng video.'));
        }

        return redirect()->away((string) $result['joinUrl']);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveVideoJoin(Request $request, int $id): array
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($user === null || $room === null || ! $this->classroom->canAccess($room, $user)) {
            return [
                'ok' => false,
                'message' => 'Không có quyền truy cập phòng học.',
                'redirect' => route('classroom.index'),
                'status' => 403,
            ];
        }

        $participants = app(\App\Models\OnlineRoomParticipant::class);
        $participant = $participants->findForRoom($id, (int) $user->id);

        if ($participant === null) {
            return [
                'ok' => false,
                'message' => 'Bạn cần vào phòng chờ trước khi mở video.',
                'redirect' => route('classroom.lobby', $id),
                'status' => 403,
            ];
        }

        $participants->touchPresence($id, (int) $user->id);

        $payload = $this->classroom->videoJoinPayload(
            $room,
            $user,
            (int) ($participant->breakout_room_index ?? 0),
            (string) $request->query('client', '')
        );
        $payload['redirect'] = route('classroom.room', $id);

        return $payload;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);

        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }
}
