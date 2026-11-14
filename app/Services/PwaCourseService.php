<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class PwaCourseService
{
    public const MUC_INFO = 'thong-tin';

    public function __construct(
        private PwaHomeService $homes,
        private StudentOverviewService $overview,
        private StudentSessionCatalogService $catalog,
        private StudentWorkspaceService $workspace,
        private StudentCourseNavService $nav,
    ) {
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    public function index(User $user): array|RedirectResponse
    {
        $nearest = $this->nearestSessionRedirect($user);

        if ($nearest !== null) {
            return $nearest;
        }

        $data = $this->homes->courses($user);

        foreach ($data['levels'] as $index => $level) {
            $data['levels'][$index]['href'] = route('pwa.courses.level', (int) $level['id']);
        }

        foreach ($data['teacherCourses'] as $index => $course) {
            $data['teacherCourses'][$index]['href'] = route('pwa.courses.course', (int) $course['id']);
        }

        return array_merge($this->homes->headerFields($user), $data);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    public function levelSessions(User $user, int $levelId): array|RedirectResponse
    {
        if (! $user->isLearner()) {
            return redirect()->route('pwa.courses');
        }

        $overview = $this->overview->build((int) $user->id);
        $course = $overview['course'];
        $level = collect($overview['levels'])->firstWhere('id', $levelId);

        if (! $course instanceof Course || ! is_array($level)) {
            return redirect()->route('pwa.courses')->with('error', 'Không tìm thấy trình độ.');
        }

        $nearest = $this->nearestSessionRedirect($user, $levelId);

        if ($nearest !== null) {
            return $nearest;
        }

        $sessions = $this->catalog->forProgramLevel(
            $this->catalog->forStudentCourse((int) $course->id, (int) $user->id),
            $levelId
        );

        return array_merge($this->homes->headerFields($user), [
            'pwaTab' => 'courses',
            'listKicker' => 'Khóa học',
            'listTitle' => (string) ($level['display_title'] ?? 'Trình độ học'),
            'listHint' => (string) ($level['description'] ?? ''),
            'backUrl' => route('pwa.courses'),
            'backLabel' => 'Khóa học',
            'sessions' => $sessions,
            'phaseLabels' => CourseSession::phaseLabels(),
            'isTeacherList' => false,
        ]);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    public function teacherSessions(User $user, int $courseId): array|RedirectResponse
    {
        if (! $user->isTeacher()) {
            return redirect()->route('pwa.courses');
        }

        $course = Course::activeForTeacher((int) $user->id)->firstWhere('id', $courseId);

        if (! $course instanceof Course) {
            return redirect()->route('pwa.courses')->with('error', 'Không tìm thấy khóa học.');
        }

        $sessions = CourseSession::query()
            ->with(['programSession.level', 'course.teacher', 'course.room'])
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->inProgramOrder()
            ->get()
            ->map(function (CourseSession $session): array {
                $row = $this->catalog->toStudentRow($session, 0);
                $row['is_viewable'] = true;

                return $row;
            })
            ->all();

        return array_merge($this->homes->headerFields($user), [
            'pwaTab' => 'courses',
            'listKicker' => 'Khóa dạy',
            'listTitle' => (string) $course->title,
            'listHint' => '',
            'backUrl' => route('pwa.courses'),
            'backLabel' => 'Khóa học',
            'sessions' => $sessions,
            'phaseLabels' => CourseSession::phaseLabels(),
            'isTeacherList' => true,
        ]);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    public function session(User $user, string $code, mixed $muc): array|RedirectResponse
    {
        $code = trim($code);

        if ($code === '') {
            return redirect()->route('pwa.courses')->with('error', 'Buổi học không tồn tại.');
        }

        if ($user->isTeacher()) {
            return $this->teacherSession($user, $code);
        }

        if (! $user->isLearner()) {
            return redirect()->route('pwa.courses')->with('error', 'Không xem được buổi học.');
        }

        return $this->studentSession($user, $code, $muc);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function studentSession(User $user, string $code, mixed $muc): array|RedirectResponse
    {
        $userId = (int) $user->id;
        $course = CourseMember::activeCourseFor($userId);

        if ($course === null) {
            return redirect()->route('pwa.courses')->with('error', 'Chưa có khóa học.');
        }

        $sessions = $this->catalog->forStudentCourse((int) $course->id, $userId);
        $row = $this->findSessionRow($sessions, $code);

        if ($row === null) {
            return redirect()->route('pwa.courses')->with('error', 'Buổi học không thuộc khóa học của bạn.');
        }

        $levelId = (int) ($row['level_id'] ?? 0);
        $this->nav->rememberActiveContext((int) $course->id, $levelId);
        $backUrl = $levelId > 0
            ? route('pwa.courses.level', $levelId)
            : route('pwa.courses');

        if (empty($row['is_viewable'])) {
            return redirect()->to($backUrl)->with('error', 'Buổi học này chưa mở. Bạn chỉ xem được buổi học gần nhất.');
        }

        $requested = is_string($muc) ? trim($muc) : '';
        $tabParam = $requested === self::MUC_INFO
            ? StudentCourseNavService::TAB_OVERVIEW
            : ($requested !== '' ? $requested : null);

        $levelSessions = $levelId > 0
            ? $this->catalog->forProgramLevel($sessions, $levelId)
            : $sessions;
        $nearestSessionId = $this->catalog->nearestSessionId($levelSessions !== [] ? $levelSessions : $sessions);

        $panel = $this->workspace->resolveSessionPanel(
            $sessions,
            $nearestSessionId,
            $code,
            $userId,
            $tabParam
        );

        $sessionDetail = $panel['sessionDetail'];

        if (! is_array($sessionDetail)) {
            return redirect()->to($backUrl)->with(
                'error',
                (string) ($panel['sessionLockedMessage'] ?? 'Buổi học này chưa mở.')
            );
        }

        $hasKr = ! empty($panel['hasSessionKnowledgeReview']);
        $workspaceTab = (string) $panel['workspaceTab'];
        $contentTab = (string) $panel['contentTab'];
        $activeMuc = $requested === self::MUC_INFO || $workspaceTab === StudentCourseNavService::TAB_OVERVIEW
            ? self::MUC_INFO
            : $contentTab;

        $tabs = [
            ['id' => self::MUC_INFO, 'label' => 'Thông tin'],
            ['id' => StudentCourseNavService::TAB_PRE, 'label' => 'Trước buổi'],
            ['id' => StudentCourseNavService::TAB_POST, 'label' => 'Sau buổi'],
        ];

        if ($hasKr) {
            $tabs[] = ['id' => StudentCourseNavService::TAB_KNOWLEDGE_REVIEW, 'label' => 'Ôn tập'];
        }

        return array_merge($this->homes->headerFields($user), [
            'pwaTab' => 'courses',
            'isLearner' => true,
            'sessionDetail' => $sessionDetail,
            'sessionRow' => $sessionDetail['session'],
            'contentTab' => $contentTab,
            'activeMuc' => $activeMuc,
            'hasKnowledgeReview' => $hasKr,
            'tabs' => $tabs,
            'sessionMenu' => $sessions,
            'nearestSessionId' => $nearestSessionId,
            'selectedPublicCode' => $code,
            'backUrl' => $backUrl,
            'backLabel' => 'Danh sách buổi',
            'pwaReturn' => true,
            'pwaEmbed' => true,
            'phaseLabels' => CourseSession::phaseLabels(),
        ]);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function teacherSession(User $user, string $code): array|RedirectResponse
    {
        $session = CourseSession::query()
            ->with(['programSession.level', 'course.teacher', 'course.room'])
            ->where('public_code', $code)
            ->first();

        $course = $session?->course;

        if ($session === null || ! $course instanceof Course || (int) $course->teacher_id !== (int) $user->id) {
            return redirect()->route('pwa.courses')->with('error', 'Không tìm thấy buổi học.');
        }

        $row = $this->catalog->toStudentRow($session, 0);
        $row['is_viewable'] = true;

        $detail = [
            'session' => $row,
            'course' => [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
                'teacher_name' => (string) ($course->teacher?->name ?? $user->name),
            ],
            'phaseLabels' => CourseSession::phaseLabels(),
            'teacherName' => (string) ($course->teacher?->name ?? $user->name),
            'preSubmitted' => false,
            'quizSubmitted' => false,
            'knowledgeReviewSubmitted' => false,
            'preResult' => null,
            'quizResult' => null,
            'knowledgeReviewResult' => null,
            'display' => $row['display'],
        ];

        return array_merge($this->homes->headerFields($user), [
            'pwaTab' => 'courses',
            'isLearner' => false,
            'sessionDetail' => $detail,
            'sessionRow' => $row,
            'contentTab' => self::MUC_INFO,
            'activeMuc' => self::MUC_INFO,
            'hasKnowledgeReview' => false,
            'tabs' => [
                ['id' => self::MUC_INFO, 'label' => 'Thông tin'],
            ],
            'backUrl' => route('pwa.courses.course', (int) $course->id),
            'backLabel' => 'Danh sách buổi',
            'pwaReturn' => false,
            'pwaEmbed' => true,
            'phaseLabels' => CourseSession::phaseLabels(),
        ]);
    }

    private function nearestSessionRedirect(User $user, int $levelId = 0): ?RedirectResponse
    {
        if (! $user->isLearner()) {
            return null;
        }

        $userId = (int) $user->id;
        $course = CourseMember::activeCourseFor($userId);

        if ($course === null) {
            return null;
        }

        $all = $this->catalog->forStudentCourse((int) $course->id, $userId);

        if ($all === []) {
            return null;
        }

        if ($levelId <= 0) {
            $levelId = $this->nav->resolveActiveLevelId($userId);
        }

        $sessions = $levelId > 0
            ? $this->catalog->forProgramLevel($all, $levelId)
            : $all;

        if ($sessions === []) {
            $sessions = $all;
        }

        $nearestId = $this->catalog->nearestSessionId($sessions);
        $code = $this->catalog->resolveSelectedPublicCode($sessions, $nearestId, null);

        if ($code === null || $code === '') {
            return null;
        }

        $row = $this->findSessionRow($sessions, $code) ?? $this->findSessionRow($all, $code);

        if ($row === null || empty($row['is_viewable'])) {
            return null;
        }

        $muc = $this->nav->defaultTabForSession($row);

        $this->nav->rememberActiveContext((int) $course->id, (int) ($row['level_id'] ?? $levelId));

        return redirect()->route('pwa.courses.session', ['code' => $code, 'muc' => $muc]);
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return array<string, mixed>|null
     */
    private function findSessionRow(array $sessions, string $code): ?array
    {
        foreach ($sessions as $session) {
            if ((string) ($session['public_code'] ?? '') === $code) {
                return $session;
            }
        }

        return null;
    }
}
