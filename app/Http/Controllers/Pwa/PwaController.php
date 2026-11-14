<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\OnlineRoom;
use App\Models\OnlineRoomParticipant;
use App\Models\User;
use App\Services\ClassroomService;
use App\Services\OnlineRoomService;
use App\Services\AssessmentTakePageService;
use App\Services\PwaCourseService;
use App\Services\PwaHomeService;
use App\Services\StudentEvaluationService;
use App\Services\StudentSessionCatalogService;
use App\Services\UserActivityService;
use App\Support\ClassroomConfig;
use App\Support\PwaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PwaController extends Controller
{
    public function __construct(
        private PwaHomeService $homes,
        private PwaCourseService $courses,
        private AssessmentTakePageService $assessmentPages,
        private ClassroomService $classroom,
        private OnlineRoomService $rooms,
        private StudentSessionCatalogService $catalog,
        private ClassroomConfig $config,
    ) {
    }

    public function loginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('pwa.home');
        }

        return view('pwa.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = User::findByLogin($request->string('login')->trim()->toString());

        if (! $user || ! Auth::getProvider()->validateCredentials($user, [
            'password' => $request->string('password')->toString(),
        ])) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->with('error', 'Số điện thoại hoặc mật khẩu không đúng.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        app(UserActivityService::class)->startLoginSession($user, $request);
        PwaClient::mark($request);

        return redirect()->intended(route('pwa.home'));
    }

    public function home(Request $request): View
    {
        return view('pwa.home', $this->homes->build($request->user()));
    }

    public function courses(Request $request): View|RedirectResponse
    {
        $data = $this->courses->index($request->user());

        return $data instanceof RedirectResponse ? $data : view('pwa.courses', $data);
    }

    public function courseLevel(Request $request, int $level): View|RedirectResponse
    {
        $data = $this->courses->levelSessions($request->user(), $level);

        return $data instanceof RedirectResponse ? $data : view('pwa.sessions', $data);
    }

    public function courseTeacher(Request $request, int $course): View|RedirectResponse
    {
        $data = $this->courses->teacherSessions($request->user(), $course);

        return $data instanceof RedirectResponse ? $data : view('pwa.sessions', $data);
    }

    public function courseSession(Request $request, string $code): View|RedirectResponse
    {
        $data = $this->courses->session($request->user(), $code, $request->query('muc'));

        return $data instanceof RedirectResponse ? $data : view('pwa.session', $data);
    }

    public function zoom(Request $request): View
    {
        return view('pwa.zoom', $this->homes->zoom($request->user()));
    }

    public function assessments(Request $request): View
    {
        return view('pwa.assessments', $this->homes->assessments($request->user()));
    }

    public function assessmentShow(Request $request, int $id): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isLearner()) {
            return redirect()->route('pwa.assessments')->with('error', 'Chỉ học viên làm bài kiểm tra trên app.');
        }

        $page = $this->assessmentPages->resolve($id, (int) $user->id);

        if ($page['status'] === 'missing' || $page['status'] === 'closed') {
            return redirect()->route('pwa.assessments')->with('error', $page['message'] ?? 'Không mở được bài kiểm tra.');
        }

        if ($page['status'] === 'autosubmit') {
            return redirect()->route('pwa.assessments.show', $id)->with('success', $page['message'] ?? '');
        }

        return view('pwa.assessment', array_merge($this->homes->headerFields($user), $page['data'] ?? [], [
            'pwaTab' => 'assessments',
            'pwaReturn' => true,
            'startAction' => route('student.assessment.start', $id),
            'submitAction' => route('student.assessment.submit', $id),
            'autosaveUrl' => route('student.assessment.autosave', $id),
            'hubUrl' => route('pwa.assessments'),
        ]));
    }

    public function profile(Request $request): View
    {
        return view('pwa.profile', $this->homes->profile($request->user()));
    }

    public function evaluations(Request $request, StudentEvaluationService $evaluations): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isLearner()) {
            return redirect()->route('pwa.profile');
        }

        $payload = $evaluations->build((int) $user->id, trim((string) $request->query('tuan', '')) ?: null);

        if ($payload === null) {
            return redirect()->route('pwa.home')->with('error', 'Không thể tải nhận xét của giáo viên.');
        }

        return view('pwa.evaluations', array_merge($this->homes->headerFields($user), $payload, [
            'pwaTab' => 'evaluations',
        ]));
    }

    public function join(Request $request): RedirectResponse
    {
        $user = $request->user();
        $publicCode = trim((string) $request->input('public_code', ''));
        $roomId = (int) $request->input('room_id', 0);

        if ($user->isLearner() && $publicCode !== '') {
            return $this->joinStudentSession($publicCode, $user, $request);
        }

        if ($roomId > 0) {
            return $this->enterRoom($roomId, $user, $request);
        }

        return redirect()->route('pwa.home')->with('error', 'Chưa đến giờ vào lớp.');
    }

    public function room(Request $request, int $id): View|RedirectResponse
    {
        $user = $request->user();
        $room = $this->classroom->findRoom($id);

        if ($room === null || $user === null || ! $this->classroom->canAccess($room, $user)) {
            return redirect()->route('pwa.home')->with('error', 'Không vào được phòng học.');
        }

        if ($this->config->isJaasConfigured() && ! $this->config->isJaasReady()) {
            return redirect()->route('pwa.home')->with('error', 'Phòng video chưa sẵn sàng. Vui lòng thử lại sau.');
        }

        $participants = app(OnlineRoomParticipant::class);
        $participant = $participants->onlineParticipant($id, (int) $user->id, $this->config->presenceTimeout());

        if ($participant === null) {
            return $this->enterRoom($id, $user, $request);
        }

        $isModerator = $this->classroom->isModerator($room, $user);
        $breakoutIndex = (int) ($participant?->breakout_room_index ?? 0);
        $jitsi = $this->classroom->jitsiMeta($room, $breakoutIndex);
        $roleLabel = $this->classroom->roleLabel($user);

        return view('pwa.room', [
            'room' => $room,
            'roomId' => $id,
            'userName' => $user->name,
            'userEmail' => (string) ($user->email ?? ''),
            'userId' => (int) $user->id,
            'userRole' => (string) $user->role,
            'userRoleLabel' => $roleLabel,
            'jitsiDisplayName' => $user->name.' ('.$roleLabel.')',
            'isModerator' => $isModerator,
            'jitsiDomain' => $jitsi['jitsi_domain'],
            'useJaas' => $jitsi['use_jaas'],
            'presenceInterval' => $this->config->presenceIntervalMs(),
            'tokenUrl' => route('classroom.jitsi-token', $id),
            'leaveUrl' => route('pwa.leave', $id),
            'homeUrl' => route('pwa.home'),
            'participantsUrl' => route('classroom.participants', $id),
            'pingUrl' => route('classroom.ping', $id),
            'pollFeedUrl' => route('classroom.polls', $id),
            'pollBaseUrl' => url('lop-hoc-online/'.$id.'/cau-hoi'),
            'initialBreakout' => $breakoutIndex,
        ]);
    }

    public function leave(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            app(OnlineRoomParticipant::class)->leaveRoom($id, (int) $user->id);
        }

        return redirect()->route('pwa.home')->with('success', 'Bạn đã rời lớp học.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            app(UserActivityService::class)->endLoginSession($user, $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pwa.login')->with('success', 'Bạn đã đăng xuất.');
    }

    public function manifest(): Response
    {
        $icon180 = asset('assets/images/pwa-icon-180.png').'?v=3';
        $icon192 = asset('assets/images/pwa-icon-192.png').'?v=3';
        $icon512 = asset('assets/images/pwa-icon-512.png').'?v=3';

        return response()->json([
            'name' => 'Hàn Ngữ SUNNY',
            'short_name' => 'Hàn Ngữ SUNNY',
            'description' => 'Vào lớp học online trên điện thoại',
            'start_url' => url('/app'),
            'scope' => url('/app'),
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#fffaf6',
            'theme_color' => '#f26b21',
            'lang' => 'vi',
            'icons' => [
                [
                    'src' => $icon180,
                    'sizes' => '180x180',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $icon192,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $icon512,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
        ]);
    }

    public function serviceWorker(): Response
    {
        $cacheName = 'sunny-pwa-v5';
        $shell = json_encode([
            url('/app'),
            asset('assets/css/pwa.css'),
            asset('assets/js/pwa-room.js'),
            asset('assets/images/pwa-icon-180.png'),
            asset('assets/images/pwa-icon-192.png'),
            asset('assets/images/pwa-icon-512.png'),
        ], JSON_UNESCAPED_SLASHES);

        $js = <<<JS
const CACHE = '{$cacheName}';
const SHELL = {$shell};

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.includes('/lop-hoc-online/')) return;

  event.respondWith(
    fetch(req).then((res) => {
      if (res.ok && (url.pathname.startsWith('/app') || url.pathname.includes('/assets/'))) {
        const copy = res.clone();
        caches.open(CACHE).then((cache) => cache.put(req, copy));
      }
      return res;
    }).catch(() => caches.match(req).then((cached) => cached || caches.match('/app')))
  );
});
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => parse_url(url('/app'), PHP_URL_PATH) ?: '/app',
            'Cache-Control' => 'no-cache',
        ]);
    }

    private function joinStudentSession(string $publicCode, User $user, Request $request): RedirectResponse
    {
        $session = CourseSession::query()
            ->with(['course.teacher', 'course.room'])
            ->where('public_code', $publicCode)
            ->first();

        if ($session === null) {
            return redirect()->route('pwa.home')->with('error', 'Buổi học không tồn tại.');
        }

        $userId = (int) $user->id;
        $courseId = (int) $session->course_id;

        if (! CourseMember::isMemberOf($courseId, $userId)) {
            return redirect()->route('pwa.home')->with('error', 'Bạn không thuộc khóa học này.');
        }

        $active = CourseMember::activeCourseFor($userId);

        if ($active === null || (int) $active->id !== $courseId) {
            return redirect()->route('pwa.home')->with('error', 'Không tìm thấy khóa học.');
        }

        $row = $this->catalog->toStudentRow($session, $userId);
        $all = $this->catalog->forStudentCourse($courseId, $userId);
        $nearestId = $this->catalog->nearestSessionId($all);
        $row['is_viewable'] = $row['phase'] === 'ended' || ((int) $row['id'] === $nearestId && $nearestId > 0);

        if (empty($row['is_viewable'])) {
            return redirect()->route('pwa.home')->with('error', 'Buổi học này chưa mở.');
        }

        if (empty($row['is_joinable'])) {
            return redirect()->route('pwa.home')->with('error', 'Buổi học chưa đến giờ hoặc đã kết thúc.');
        }

        CourseSessionAttendance::markPresent((int) $session->id, $userId);

        $course = $session->course;

        if ($course === null) {
            return redirect()->route('pwa.home')->with('error', 'Không thể mở phòng học.');
        }

        try {
            $room = $this->rooms->ensure($course);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('pwa.home')->with('error', 'Không thể mở phòng học.');
        }

        if (! $room instanceof OnlineRoom) {
            return redirect()->route('pwa.home')->with('error', 'Không thể mở phòng học.');
        }

        return $this->enterRoom((int) $room->id, $user, $request);
    }

    private function enterRoom(int $roomId, User $user, Request $request): RedirectResponse
    {
        $room = $this->classroom->findRoom($roomId);

        if ($room === null || ! $this->classroom->canAccessLobby($room, $user)) {
            return redirect()->route('pwa.home')->with('error', 'Không có quyền vào phòng học.');
        }

        if (filled($room->room_password) && (string) $request->input('room_password') !== (string) $room->room_password) {
            return redirect()->route('pwa.zoom')->with('error', 'Mật khẩu phòng không đúng.');
        }

        $participants = app(OnlineRoomParticipant::class);
        $alreadyIn = $participants->onlineParticipant($roomId, (int) $user->id, $this->config->presenceTimeout()) !== null;
        $onlineCount = $participants->countOnline($roomId, $this->config->presenceTimeout());
        $max = max(1, (int) ($room->max_participants ?: 50));

        if (! $alreadyIn && $onlineCount >= $max) {
            return redirect()->route('pwa.zoom')->with('error', 'Phòng đã đầy ('.$max.' người). Vui lòng thử lại sau.');
        }

        if ($this->classroom->requiresApproval($room, $user)) {
            $result = $this->classroom->handleWaitingRoomEnter($room, $user);
            $redirect = redirect()->to($result['redirect'] ?? route('pwa.zoom'));

            if (! empty($result['message'])) {
                $redirect->with($result['tone'] ?? 'success', $result['message']);
            }

            return $redirect;
        }

        $this->classroom->enter($room, $user);

        return redirect()->route('pwa.room', $room->id);
    }
}
