<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseKnowledgeReview;
use App\Models\CourseKnowledgeReviewAnswer;
use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\CourseSessionQuestion;
use App\Models\CourseSessionQuizAnswer;
use App\Models\CourseSessionRoomEvent;
use App\Support\HtmlContent;
use App\Support\RichContent;
use App\Support\StudentQuizGrader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherCourseManageService
{
    public const TAB_INFO = 'thong-tin';

    public const TAB_STUDENTS = 'hoc-sinh';

    public const FILTER_WEEK = 'week';

    public const FILTER_SESSION = 'session';

    public const PER_PAGE = 10;

    /** @var list<string> */
    private const AVATAR_TONES = ['blue', 'orange', 'green', 'purple', 'pink'];

    public function __construct(
        private ProgramCourseSyncService $sync,
        private OnlineRoomService $rooms,
        private ClassroomService $classroom,
        private StudentSessionCatalogService $catalog,
        private StudentQuizGrader $grader,
        private RichContent $richText,
        private ?TeacherGradingService $grading = null,
    ) {
        $this->grading = $grading ?? app(TeacherGradingService::class);
    }

    /**
     * Màn 1: Danh sách khóa học giáo viên phụ trách (#602:57584)
     *
     * @return array<string, mixed>
     */
    public function buildCourseList(int $teacherId, string $levelFilter = 'all', string $search = ''): array
    {
        $courses = Course::activeForTeacher($teacherId);
        $courses->load([
            'program.levels' => static fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);
        $courses->loadCount([
            'activeMembers as member_count',
            'sessions as session_count',
        ]);

        $courseIds = $courses->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $sessionsByCourse = $this->loadSessionsGrouped($courseIds);

        $allCards = [];
        $levelTabs = ['all' => 'Tất cả'];

        foreach ($courses as $course) {
            $cid = (int) $course->id;
            $memberCount = (int) ($course->member_count ?? 0);
            $courseSessions = $sessionsByCourse->get($cid, collect());
            $totalSessions = $courseSessions->count();
            $endedCount = 0;
            $currentSessionNo = 0;
            $levelTitle = '';

            foreach ($courseSessions->values() as $idx => $session) {
                $ordinal = $idx + 1;
                $phase = $session->livePhase();

                if ($phase === 'ended') {
                    $endedCount++;
                } elseif ($currentSessionNo === 0 && ($phase === 'live' || $phase === 'upcoming')) {
                    $currentSessionNo = $ordinal;
                    $levelTitle = trim((string) ($session->programSession?->level?->title ?? ''));
                }
            }

            if ($currentSessionNo === 0 && $totalSessions > 0) {
                $currentSessionNo = min($totalSessions, max(1, $endedCount));
            }

            if ($levelTitle === '') {
                $levelTitle = trim((string) ($course->program?->levels?->first()?->title ?? ''));
            }

            $tag = $this->resolveProgramTag($course, $levelTitle);
            $levelTabs[$tag['key']] = $tag['label'];

            $progressPct = $totalSessions > 0
                ? min(100, (int) round(($endedCount / $totalSessions) * 100))
                : 0;
            $isCompleted = $totalSessions > 0 && $endedCount >= $totalSessions;
            $progressText = $totalSessions === 0
                ? 'Chưa có buổi học'
                : ($isCompleted
                    ? 'Hoàn thành 100%'
                    : "Buổi {$endedCount}/{$totalSessions} · {$progressPct}%");

            $allCards[] = [
                'id' => $cid,
                'title' => (string) $course->title,
                'status' => (string) $course->status,
                'status_label' => $course->statusLabel(),
                'session_count' => $totalSessions,
                'completed_sessions' => $endedCount,
                'current_session_number' => $currentSessionNo,
                'member_count' => $memberCount,
                'progress_pct' => $progressPct,
                'is_completed' => $isCompleted,
                'progress_text' => $progressText,
                'level_key' => $tag['key'],
                'level_label' => $tag['label'],
                'level_tone' => $tag['tone'],
                'manage_url' => route('teacher.course.show', ['id' => $cid]),
            ];
        }

        $levelFilter = trim($levelFilter);
        if ($levelFilter === '' || ! isset($levelTabs[$levelFilter])) {
            $levelFilter = 'all';
        }

        $search = trim($search);
        $needle = mb_strtolower($search, 'UTF-8');

        $filteredCards = array_values(array_filter(
            $allCards,
            static function (array $card) use ($levelFilter, $needle): bool {
                if ($levelFilter !== 'all' && ($card['level_key'] ?? '') !== $levelFilter) {
                    return false;
                }

                if ($needle !== '') {
                    $hay = mb_strtolower(($card['title'] ?? '').' '.($card['level_label'] ?? ''), 'UTF-8');
                    if (! str_contains($hay, $needle)) {
                        return false;
                    }
                }

                return true;
            }
        ));

        return [
            'courses' => $filteredCards,
            'allCourses' => $allCards,
            'levelTabs' => $levelTabs,
            'activeLevel' => $levelFilter,
            'search' => $search,
            'totalCourses' => count($allCards),
        ];
    }

    /**
     * Màn 2: Chi tiết khóa học — Thông tin lớp (#570:47489) + Danh sách học sinh (#1443:35213 / #602:55351)
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    public function build(int $courseId, int $teacherId, int $selectedSessionId = 0, array $options = []): ?array
    {
        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->with(['program.levels' => static fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->first();

        if ($course === null) {
            return null;
        }

        if ((int) $course->program_id > 0 && ! CourseSession::query()->where('course_id', $courseId)->exists()) {
            $this->sync->syncProgramToCourse((int) $course->program_id);
        }

        $members = CourseMember::activeMembersForCourse($courseId);
        $memberCount = $members->count();
        $memberIds = $members->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();

        $rawSessions = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->with(['course.teacher', 'course.room', 'programSession.level'])
            ->inProgramOrder()
            ->get();

        $sessions = [];
        $sessionById = [];
        $endedCount = 0;
        $courseLevelTitle = '';
        $courseWeeksMap = [];

        foreach ($rawSessions as $index => $session) {
            $ordinal = $index + 1;
            $sessionId = (int) $session->id;
            $phase = $session->livePhase();
            $dateInput = $session->dateForInput();
            $weekKey = $this->dateToWeekKey($dateInput);
            $lvl = trim((string) ($session->programSession?->level?->title ?? ''));

            if ($phase === 'ended') {
                $endedCount++;
            } elseif ($courseLevelTitle === '' && $lvl !== '') {
                $courseLevelTitle = $lvl;
            }

            if ($weekKey !== '') {
                $courseWeeksMap[$weekKey] = true;
            }

            $dateLabel = $dateInput !== '' ? date('d/m/Y', strtotime($dateInput) ?: time()) : 'Chưa xếp ngày';
            $entry = [
                'id' => $sessionId,
                'ordinal' => $ordinal,
                'session_index' => $ordinal,
                'title' => (string) ($session->title ?: 'Buổi '.$ordinal),
                'short_label' => 'Buổi '.$ordinal.($dateInput !== '' ? ' · '.$dateLabel : ''),
                'date_input' => $dateInput,
                'date_label' => $dateLabel,
                'week_key' => $weekKey,
                'phase' => $phase,
                'program_knowledge_review_id' => (int) ($session->program_knowledge_review_id ?? 0),
                'level_title' => $lvl,
            ];

            $sessions[] = $entry;
            $sessionById[$sessionId] = $entry;
        }

        if ($courseLevelTitle === '') {
            $courseLevelTitle = trim((string) ($course->program?->levels?->first()?->title ?? ''));
        }

        if ($selectedSessionId <= 0 || ! isset($sessionById[$selectedSessionId])) {
            $selectedSessionId = $this->defaultSessionId($sessions);
        }

        $selectedSession = $selectedSessionId > 0 ? ($sessionById[$selectedSessionId] ?? null) : null;

        // Xác định tuần đang lọc
        $requestedWeek = trim((string) ($options['week'] ?? ''));
        $defaultWeek = ($selectedSession !== null && ($selectedSession['week_key'] ?? '') !== '')
            ? (string) $selectedSession['week_key']
            : CourseMonthlyEvaluation::currentWeekKey();
        $activeWeek = ($requestedWeek !== '' && CourseMonthlyEvaluation::isWeeklyPeriod($requestedWeek))
            ? CourseMonthlyEvaluation::normalizePeriod($requestedWeek)
            : $defaultWeek;

        // Danh sách tuần khả dụng (tuần có buổi học của khóa + 16 tuần gần nhất)
        $mergedWeeks = array_values(array_unique(array_merge(
            array_keys($courseWeeksMap),
            CourseMonthlyEvaluation::weekOptions(16),
            [$activeWeek]
        )));
        usort($mergedWeeks, static fn (string $a, string $b): int => CourseMonthlyEvaluation::compareWeeklyPeriods($b, $a));

        $weekOptions = [];
        foreach ($mergedWeeks as $wk) {
            $weekOptions[] = [
                'key' => $wk,
                'label' => $this->formatWeekDropdownLabel($wk),
                'short_label' => $this->formatWeekShortLabel($wk),
                'session_count' => count(array_filter($sessions, static fn (array $s): bool => ($s['week_key'] ?? '') === $wk)),
            ];
        }

        $activeTab = (string) ($options['tab'] ?? self::TAB_INFO);
        if (! in_array($activeTab, [self::TAB_INFO, self::TAB_STUDENTS], true)) {
            $activeTab = self::TAB_INFO;
        }

        $filterMode = (string) ($options['filter_mode'] ?? self::FILTER_WEEK);
        if (! in_array($filterMode, [self::FILTER_WEEK, self::FILTER_SESSION], true)) {
            $filterMode = self::FILTER_WEEK;
        }
        if ($filterMode === self::FILTER_SESSION && $selectedSession === null) {
            $filterMode = self::FILTER_WEEK;
        }

        // Phòng học online & thống kê Tab 1 (chỉ chạy khi mở Tab Thông tin lớp để tối ưu tốc độ)
        $onlineRoom = null;
        $roomLobbyUrl = null;
        $canEnterRoom = false;
        $sessionMetrics = [
            'has_session' => false,
            'attendance_recorded' => 0,
            'member_count' => $memberCount,
            'pre_submitted' => 0,
            'pre_has_questions' => false,
            'post_submitted' => 0,
            'post_has_questions' => false,
        ];
        $pendingGradingStats = ['count' => 0, 'subtitle' => 'Mở Chấm điểm để xử lý'];

        if ($activeTab === self::TAB_INFO) {
            if ($sessions !== []) {
                try {
                    $onlineRoom = $this->rooms->ensure($course->fresh(['sessions']) ?? $course);
                } catch (\Throwable $e) {
                    report($e);
                }
                $canEnterRoom = CourseSession::hasOpenWaitingRoomForCourse($courseId);
                if ($onlineRoom !== null) {
                    $roomLobbyUrl = $this->classroom->lobbyUrl((int) $onlineRoom->id);
                }
            }

            $sessionMetrics = $this->buildSelectedSessionMetrics($courseId, $selectedSession, $memberIds, $memberCount);
            $pendingGradingStats = $this->buildPendingGradingStats($teacherId, $courseId);
        }

        // Đánh giá tuần của khóa cho $activeWeek
        $evalMap = CourseMonthlyEvaluation::mapForCoursePeriod($courseId, $activeWeek);
        $evaluatedCount = 0;
        foreach ($members as $m) {
            $ev = $evalMap[(int) $m->user_id] ?? null;
            if ($ev !== null && $ev->isFilled()) {
                $evaluatedCount++;
            }
        }
        $pendingEvalCount = max(0, $memberCount - $evaluatedCount);

        // Dựng bảng học sinh (chỉ chạy khi ở Tab Danh sách học sinh hoặc cần mở nhanh Drawer)
        $openStudentId = max(0, (int) ($options['open_student'] ?? 0));
        $allStudentRows = [];
        $packageOptions = ['all' => 'Tất cả'];

        if ($activeTab === self::TAB_STUDENTS || $openStudentId > 0) {
            $scopeSessions = $filterMode === self::FILTER_SESSION && $selectedSession !== null
                ? [$selectedSession]
                : array_values(array_filter($sessions, static fn (array $s): bool => ($s['week_key'] ?? '') === $activeWeek));

            $rosterBundle = $this->buildStudentRoster(
                $course,
                $courseLevelTitle,
                $members,
                $sessions,
                $scopeSessions,
                $evalMap,
                $filterMode,
                $activeWeek,
                $selectedSessionId
            );

            $allStudentRows = $rosterBundle['rows'];
            $packageOptions = $rosterBundle['package_options'];
        }

        $packageFilter = trim((string) ($options['package_filter'] ?? 'all'));
        if ($packageFilter === '' || ! isset($packageOptions[$packageFilter])) {
            $packageFilter = 'all';
        }

        $search = trim((string) ($options['q'] ?? ''));
        $needle = mb_strtolower($search, 'UTF-8');

        $filteredStudents = array_values(array_filter(
            $allStudentRows,
            static function (array $row) use ($packageFilter, $needle): bool {
                if ($packageFilter !== 'all' && ($row['tag_key'] ?? '') !== $packageFilter) {
                    return false;
                }
                if ($needle !== '') {
                    $hay = mb_strtolower(
                        ($row['name'] ?? '').' '.($row['student_code'] ?? '').' '.($row['phone'] ?? '').' '.($row['tag_label'] ?? ''),
                        'UTF-8'
                    );
                    if (! str_contains($hay, $needle)) {
                        return false;
                    }
                }

                return true;
            }
        ));

        $totalStudentsFiltered = count($filteredStudents);
        $lastPage = max(1, (int) ceil($totalStudentsFiltered / self::PER_PAGE));
        $currentPage = min($lastPage, max(1, (int) ($options['page'] ?? 1)));
        $offset = ($currentPage - 1) * self::PER_PAGE;
        $pageStudents = array_slice($filteredStudents, $offset, self::PER_PAGE);

        $pagination = [
            'total' => $totalStudentsFiltered,
            'per_page' => self::PER_PAGE,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'from' => $totalStudentsFiltered > 0 ? $offset + 1 : 0,
            'to' => min($totalStudentsFiltered, $offset + count($pageStudents)),
            'pages' => $this->buildPageWindow($currentPage, $lastPage),
        ];

        return [
            'course' => $course,
            'courseLevelTitle' => $courseLevelTitle,
            'courseTag' => $this->resolveProgramTag($course, $courseLevelTitle),
            'activeTab' => $activeTab,
            'filterMode' => $filterMode,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $selectedSessionId,
            'activeWeek' => $activeWeek,
            'activeWeekShort' => $this->formatWeekShortLabel($activeWeek),
            'activeWeekFull' => $this->formatWeekDropdownLabel($activeWeek),
            'weekOptions' => $weekOptions,
            'phaseLabels' => CourseSession::phaseLabels(),
            'members' => $members,
            'memberCount' => $memberCount,
            'kpis' => [
                'completed_sessions' => $endedCount,
                'total_sessions' => count($sessions),
                'member_count' => $memberCount,
                'pending_grading_count' => $pendingGradingStats['count'],
                'pending_grading_subtitle' => $pendingGradingStats['subtitle'],
                'pending_eval_count' => $pendingEvalCount,
                'eval_week_label' => $this->formatWeekShortLabel($activeWeek),
                'eval_week_full_label' => $this->formatWeekFullRangeLabel($activeWeek),
            ],
            'sessionMetrics' => $sessionMetrics,
            'onlineRoom' => $onlineRoom,
            'canEnterRoom' => $canEnterRoom,
            'roomLobbyUrl' => $roomLobbyUrl,
            'sessionRoomEvents' => $selectedSessionId > 0
                ? CourseSessionRoomEvent::timelineForSession($selectedSessionId)
                : [],
            'allStudentRows' => $allStudentRows,
            'pageStudents' => $pageStudents,
            'pagination' => $pagination,
            'packageOptions' => $packageOptions,
            'packageFilter' => $packageFilter,
            'search' => $search,
            'openStudentId' => max(0, (int) ($options['open_student'] ?? 0)),
            'scoreLabels' => CourseMonthlyEvaluation::scoreLabels(),
            'gradingUrl' => route('teacher.grading', ['khoa' => $courseId, 'trang_thai' => 'pending']),
            'evaluationUrl' => route('teacher.evaluations', ['khoa' => $courseId, 'tuan' => $activeWeek]),
        ];
    }

    /**
     * Dữ liệu JSON cho Student Drawer (#602:56064, #953:136485, #953:136488, #1259:30826-34041)
     *
     * @return array<string, mixed>|null
     */
    public function buildStudentDrawer(
        int $courseId,
        int $teacherId,
        int $userId,
        string $evalWeek = '',
        int $selectedSessionId = 0
    ): ?array {
        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->with(['program.levels' => static fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->first();

        if ($course === null) {
            return null;
        }

        $member = CourseMember::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->with('user')
            ->first();

        if ($member === null || $member->user === null) {
            return null;
        }

        $user = $member->user;
        $rawSessions = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->with('programSession.level')
            ->inProgramOrder()
            ->get();

        $sessions = [];
        $courseLevelTitle = '';
        $selectedSessionWeek = '';

        foreach ($rawSessions as $idx => $session) {
            $ordinal = $idx + 1;
            $sid = (int) $session->id;
            $dateInput = $session->dateForInput();
            $weekKey = $this->dateToWeekKey($dateInput);
            $phase = $session->livePhase();
            $lvl = trim((string) ($session->programSession?->level?->title ?? ''));

            if ($courseLevelTitle === '' && $phase !== 'ended' && $lvl !== '') {
                $courseLevelTitle = $lvl;
            }
            if ($sid === $selectedSessionId && $weekKey !== '') {
                $selectedSessionWeek = $weekKey;
            }

            $sessions[] = [
                'id' => $sid,
                'ordinal' => $ordinal,
                'title' => (string) ($session->title ?: 'Buổi '.$ordinal),
                'date_input' => $dateInput,
                'date_label' => $dateInput !== '' ? date('d/m/Y', strtotime($dateInput) ?: time()) : '—',
                'week_key' => $weekKey,
                'phase' => $phase,
            ];
        }

        if ($courseLevelTitle === '') {
            $courseLevelTitle = trim((string) ($course->program?->levels?->first()?->title ?? ''));
        }

        if ($evalWeek === '' || ! CourseMonthlyEvaluation::isWeeklyPeriod($evalWeek)) {
            $evalWeek = $selectedSessionWeek !== ''
                ? $selectedSessionWeek
                : CourseMonthlyEvaluation::currentWeekKey();
        } else {
            $evalWeek = CourseMonthlyEvaluation::normalizePeriod($evalWeek);
        }

        $tag = $this->resolveMemberTag($course, $member, $courseLevelTitle);
        $commitment = $this->computeCommitmentSummary($course, $member, $sessions);

        // Danh sách buổi học trong tuần đang lọc cho tab Điểm danh
        $weekSessions = array_values(array_filter(
            $sessions,
            static fn (array $s): bool => ($s['week_key'] ?? '') === $evalWeek
        ));
        // Nếu tuần đang lọc không có buổi nào nhưng có buổi đang chọn, fallback hiển thị buổi đó
        if ($weekSessions === [] && $selectedSessionId > 0) {
            foreach ($sessions as $s) {
                if ((int) $s['id'] === $selectedSessionId) {
                    $weekSessions[] = $s;
                    break;
                }
            }
        }
        // Sắp xếp buổi mới nhất lên đầu giống Figma (Buổi 38 -> 37 -> 36)
        usort($weekSessions, static fn (array $a, array $b): int => ((int) $b['ordinal']) <=> ((int) $a['ordinal']));

        $weekSessionIds = array_map(static fn (array $s): int => (int) $s['id'], $weekSessions);
        $attMap = CourseSessionAttendance::statusMapForSessions($weekSessionIds, $userId)[$userId] ?? [];

        $attendanceItems = [];
        $presentCount = 0;
        foreach ($weekSessions as $ws) {
            $sid = (int) $ws['id'];
            $status = $attMap[$sid] ?? CourseSessionAttendance::STATUS_ABSENT;
            if ($status !== CourseSessionAttendance::STATUS_ABSENT) {
                $presentCount++;
            }
            $attendanceItems[] = [
                'session_id' => $sid,
                'ordinal' => (int) $ws['ordinal'],
                'label' => 'Buổi '.$ws['ordinal'],
                'title' => (string) $ws['title'],
                'date_label' => (string) $ws['date_label'],
                'status' => $status,
                'status_label' => $this->attendanceStatusLabel($status),
                'update_url' => route('teacher.course.attendance', [
                    'id' => $courseId,
                    'sessionId' => $sid,
                    'userId' => $userId,
                ]),
            ];
        }

        // Đánh giá tuần
        $evalRow = CourseMonthlyEvaluation::forStudent($courseId, $userId, $evalWeek);
        $evalStatus = 'pending';
        if ($evalRow !== null) {
            if ($evalRow->isFilled()) {
                $evalStatus = 'evaluated';
            } elseif ($evalRow->isDraft() && $evalRow->hasAnyContent()) {
                $evalStatus = 'draft';
            }
        }

        $scoreLabels = CourseMonthlyEvaluation::scoreLabels();
        $weekStart = CourseMonthlyEvaluation::weekToDate($evalWeek);
        $weekEnd = $weekStart !== null ? (clone $weekStart)->modify('+6 days') : null;
        $weekNo = $weekStart !== null ? (int) $weekStart->format('W') : 0;
        $weekDateRangeText = ($weekStart !== null && $weekEnd !== null)
            ? $weekStart->format('d/m/Y').' - '.$weekEnd->format('d/m/Y')
            : $evalWeek;

        $anchorSessionId = $selectedSessionId > 0
            ? $selectedSessionId
            : (int) ($weekSessions[0]['id'] ?? ($sessions[0]['id'] ?? 0));

        $metaParts = array_values(array_filter([
            trim((string) ($user->student_code ?? '')),
            trim((string) $course->title),
            $courseLevelTitle,
        ]));

        return [
            'student' => [
                'id' => (int) $user->id,
                'name' => (string) ($user->name ?: 'Học viên'),
                'student_code' => (string) ($user->student_code ?? ''),
                'avatar' => $user->avatarUrl(),
                'initials' => $this->initials((string) ($user->name ?: 'HV')),
                'avatar_tone' => self::AVATAR_TONES[(int) $user->id % count(self::AVATAR_TONES)],
                'meta_line' => implode(' · ', $metaParts),
                'tag_key' => $tag['key'],
                'tag_label' => $tag['label'],
                'tag_tone' => $tag['tone'],
            ],
            'commitment' => $commitment,
            'week' => [
                'key' => $evalWeek,
                'title' => $weekNo > 0 ? 'Tuần '.sprintf('%02d', $weekNo) : $evalWeek,
                'full_header' => $this->formatWeekFullRangeLabel($evalWeek),
                'range_text' => $weekDateRangeText,
                'session_count' => count($attendanceItems),
                'attended_count' => $presentCount,
                'can_edit' => CourseMonthlyEvaluation::canEditPeriod($evalWeek),
            ],
            'attendance' => [
                'items' => $attendanceItems,
                'attended_count' => $presentCount,
                'total_count' => count($attendanceItems),
            ],
            'evaluation' => [
                'status' => $evalStatus,
                'score_attitude' => $evalRow?->score_attitude ? (int) $evalRow->score_attitude : null,
                'score_progress' => $evalRow?->score_progress ? (int) $evalRow->score_progress : null,
                'score_participation' => $evalRow?->score_participation ? (int) $evalRow->score_participation : null,
                'label_attitude' => $evalRow?->score_attitude ? ($scoreLabels[(int) $evalRow->score_attitude] ?? '') : '',
                'label_progress' => $evalRow?->score_progress ? ($scoreLabels[(int) $evalRow->score_progress] ?? '') : '',
                'label_participation' => $evalRow?->score_participation ? ($scoreLabels[(int) $evalRow->score_participation] ?? '') : '',
                'comment_html' => $evalRow !== null ? $evalRow->renderComment() : '',
                'comment_plain' => $evalRow !== null ? HtmlContent::plain((string) ($evalRow->comment ?? '')) : '',
                'save_url' => route('teacher.course.student.evaluation', [
                    'id' => $courseId,
                    'userId' => $userId,
                ]),
            ],
            'commitment_url' => route('teacher.course.student.commitment', [
                'id' => $courseId,
                'userId' => $userId,
            ]),
            'detail_work_url' => $anchorSessionId > 0
                ? route('teacher.course.student', [
                    'id' => $courseId,
                    'sessionId' => $anchorSessionId,
                    'userId' => $userId,
                ])
                : null,
        ];
    }

    /**
     * Lưu đặc cách (−1 / +1) cho Vắng hoặc Thiếu bài tập
     *
     * @return array<string, mixed>|null
     */
    public function adjustCommitment(
        int $courseId,
        int $teacherId,
        int $userId,
        string $metric,
        int $delta,
        string $evalWeek = ''
    ): ?array {
        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->first();

        if ($course === null) {
            return null;
        }

        $member = CourseMember::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->first();

        if ($member === null) {
            return null;
        }

        $column = $metric === 'homework' ? 'homework_adjustment' : 'absence_adjustment';
        if (Schema::hasColumn('course_members', $column)) {
            $current = (int) ($member->{$column} ?? 0);
            $next = max(-200, min(200, $current + $delta));
            $member->{$column} = $next;
            $member->save();
        }

        return $this->buildStudentDrawer($courseId, $teacherId, $userId, $evalWeek);
    }

    /**
     * Cập nhật trạng thái điểm danh 3 mức (Có mặt / Đi muộn / Vắng) và trả payload mới
     *
     * @return array<string, mixed>|null
     */
    public function updateAttendanceStatus(
        int $courseId,
        int $teacherId,
        int $sessionId,
        int $userId,
        string $status,
        ?string $joinedAt = null,
        string $evalWeek = ''
    ): ?array {
        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->first();

        if ($course === null || ! CourseMember::isMemberOf($courseId, $userId)) {
            return null;
        }

        $session = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('id', $sessionId)
            ->first();

        if ($session === null) {
            return null;
        }

        CourseSessionAttendance::setStatus($sessionId, $userId, $status, $joinedAt);

        return $this->buildStudentDrawer($courseId, $teacherId, $userId, $evalWeek, $sessionId);
    }

    /**
     * Lưu nháp hoặc gửi đánh giá tuần ngay trong Student Drawer
     *
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, message: string, drawer?: array<string, mixed>}
     */
    public function saveDrawerEvaluation(
        int $courseId,
        int $teacherId,
        int $userId,
        string $evalWeek,
        array $data,
        bool $isDraft
    ): array {
        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->first();

        if ($course === null || ! CourseMember::isMemberOf($courseId, $userId)) {
            return ['ok' => false, 'message' => 'Không tìm thấy học viên trong khóa học.'];
        }

        $evalWeek = CourseMonthlyEvaluation::normalizePeriod($evalWeek);

        if (! CourseMonthlyEvaluation::canEditPeriod($evalWeek)) {
            return ['ok' => false, 'message' => 'Không thể đánh giá cho tuần tương lai.'];
        }

        CourseMonthlyEvaluation::upsertEvaluation($courseId, $userId, $teacherId, $evalWeek, $data, $isDraft);

        $drawer = $this->buildStudentDrawer($courseId, $teacherId, $userId, $evalWeek);
        $studentName = (string) ($drawer['student']['name'] ?? 'học viên');
        $weekTitle = (string) ($drawer['week']['title'] ?? $evalWeek);

        return [
            'ok' => true,
            'message' => $isDraft
                ? "Đã lưu nháp đánh giá {$weekTitle} cho {$studentName}."
                : "Đã gửi đánh giá {$weekTitle} cho {$studentName}.",
            'drawer' => $drawer ?? [],
        ];
    }

    /**
     * @param  list<int>  $courseIds
     * @return Collection<int, Collection<int, CourseSession>>
     */
    private function loadSessionsGrouped(array $courseIds): Collection
    {
        if ($courseIds === []) {
            return collect();
        }

        return CourseSession::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', '!=', 'cancelled')
            ->with('programSession.level')
            ->inProgramOrder()
            ->get()
            ->groupBy('course_id');
    }

    /**
     * @param  array<string, mixed>|null  $selectedSession
     * @param  list<int>  $memberIds
     * @return array<string, mixed>
     */
    private function buildSelectedSessionMetrics(
        int $courseId,
        ?array $selectedSession,
        array $memberIds,
        int $memberCount
    ): array {
        if ($selectedSession === null) {
            return [
                'has_session' => false,
                'attendance_recorded' => 0,
                'member_count' => $memberCount,
                'pre_submitted' => 0,
                'pre_has_questions' => false,
                'post_submitted' => 0,
                'post_has_questions' => false,
            ];
        }

        $sessionId = (int) $selectedSession['id'];
        $attendanceRecorded = $memberIds !== []
            ? CourseSessionAttendance::query()
                ->where('session_id', $sessionId)
                ->whereIn('user_id', $memberIds)
                ->distinct('user_id')
                ->count('user_id')
            : 0;

        $preQIds = CourseSessionQuestion::query()
            ->where('course_session_id', $sessionId)
            ->where('question_group', 'pre')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $postQIds = CourseSessionQuestion::query()
            ->where('course_session_id', $sessionId)
            ->where('question_group', 'post_practice')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $preSubmitted = ($preQIds !== [] && $memberIds !== [])
            ? CourseSessionQuizAnswer::query()
                ->where('course_session_id', $sessionId)
                ->whereIn('question_id', $preQIds)
                ->whereIn('user_id', $memberIds)
                ->distinct('user_id')
                ->count('user_id')
            : 0;

        $postSubmitted = ($postQIds !== [] && $memberIds !== [])
            ? CourseSessionQuizAnswer::query()
                ->where('course_session_id', $sessionId)
                ->whereIn('question_id', $postQIds)
                ->whereIn('user_id', $memberIds)
                ->distinct('user_id')
                ->count('user_id')
            : 0;

        return [
            'has_session' => true,
            'session_id' => $sessionId,
            'attendance_recorded' => $attendanceRecorded,
            'member_count' => $memberCount,
            'pre_submitted' => $preSubmitted,
            'pre_has_questions' => $preQIds !== [],
            'post_submitted' => $postSubmitted,
            'post_has_questions' => $postQIds !== [],
        ];
    }

    /**
     * @return array{count: int, subtitle: string}
     */
    private function buildPendingGradingStats(int $teacherId, int $courseId): array
    {
        try {
            $payload = $this->grading?->build(
                $teacherId,
                $courseId,
                TeacherGradingService::TAB_ALL,
                '',
                '',
                'pending',
                'priority',
                1
            );

            $count = (int) ($payload['pagination']['total'] ?? 0);
            $firstRow = $payload['pageItems'][0] ?? null;
            $subtitle = is_array($firstRow)
                ? trim((string) ($firstRow['subtitle'] ?? ($firstRow['content_type_label'] ?? '')))
                : '';

            if ($subtitle === '') {
                $subtitle = $count > 0 ? 'Mở Chấm điểm để xử lý' : 'Tất cả bài đã được chấm';
            }

            return ['count' => $count, 'subtitle' => $subtitle];
        } catch (\Throwable $e) {
            report($e);

            return ['count' => 0, 'subtitle' => 'Mở Chấm điểm để xử lý'];
        }
    }

    /**
     * Dựng dữ liệu bảng Danh sách học sinh (hỗ trợ cả chế độ Tuần Phương án 1 #1443:35213 và Buổi #602:55351)
     *
     * @param  Collection<int, CourseMember>  $members
     * @param  list<array<string, mixed>>  $allSessions
     * @param  list<array<string, mixed>>  $scopeSessions
     * @param  array<int, CourseMonthlyEvaluation>  $evalMap
     * @return array{rows: list<array<string, mixed>>, package_options: array<string, string>}
     */
    private function buildStudentRoster(
        Course $course,
        string $courseLevelTitle,
        Collection $members,
        array $allSessions,
        array $scopeSessions,
        array $evalMap,
        string $filterMode,
        string $activeWeek,
        int $selectedSessionId
    ): array {
        $courseId = (int) $course->id;
        $memberIds = $members->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
        $scopeSessionIds = array_map(static fn (array $s): int => (int) $s['id'], $scopeSessions);

        // 1. Batched pre/post questions & answers cho $scopeSessionIds
        $preQuestionsBySession = [];
        $postQuestionsBySession = [];
        $preAnsweredByUserSession = [];
        $postAnsweredByUserSession = [];

        if ($scopeSessionIds !== []) {
            $qRows = CourseSessionQuestion::query()
                ->whereIn('course_session_id', $scopeSessionIds)
                ->whereIn('question_group', ['pre', 'post_practice'])
                ->get(['id', 'course_session_id', 'question_group']);

            $qGroupMap = [];
            foreach ($qRows as $qr) {
                $sid = (int) $qr->course_session_id;
                $qid = (int) $qr->id;
                $grp = (string) $qr->question_group;
                $qGroupMap[$qid] = [$sid, $grp];
                if ($grp === 'pre') {
                    $preQuestionsBySession[$sid] = true;
                } else {
                    $postQuestionsBySession[$sid] = true;
                }
            }

            if ($qGroupMap !== [] && $memberIds !== []) {
                $ansRows = CourseSessionQuizAnswer::query()
                    ->whereIn('course_session_id', $scopeSessionIds)
                    ->whereIn('user_id', $memberIds)
                    ->get(['course_session_id', 'user_id', 'question_id']);

                foreach ($ansRows as $ar) {
                    $qid = (int) $ar->question_id;
                    if (! isset($qGroupMap[$qid])) {
                        continue;
                    }
                    [$sid, $grp] = $qGroupMap[$qid];
                    $uid = (int) $ar->user_id;
                    if ($grp === 'pre') {
                        $preAnsweredByUserSession[$uid][$sid] = true;
                    } else {
                        $postAnsweredByUserSession[$uid][$sid] = true;
                    }
                }
            }
        }

        // 2. Batched Knowledge Reviews & Assessments trong scope
        $krByProgramId = CourseKnowledgeReview::query()
            ->where('course_id', $courseId)
            ->pluck('id', 'program_knowledge_review_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $scopeKrIds = [];
        foreach ($scopeSessions as $ss) {
            $pKrId = (int) ($ss['program_knowledge_review_id'] ?? 0);
            if ($pKrId > 0 && isset($krByProgramId[$pKrId])) {
                $scopeKrIds[$krByProgramId[$pKrId]] = true;
            }
        }
        $scopeKrIdList = array_keys($scopeKrIds);

        $krDoneByUser = [];
        if ($scopeKrIdList !== [] && $memberIds !== []) {
            $krAns = CourseKnowledgeReviewAnswer::query()
                ->whereIn('course_knowledge_review_id', $scopeKrIdList)
                ->whereIn('user_id', $memberIds)
                ->select('course_knowledge_review_id', 'user_id')
                ->distinct()
                ->get();

            foreach ($krAns as $ka) {
                $krDoneByUser[(int) $ka->user_id][(int) $ka->course_knowledge_review_id] = true;
            }
        }

        // Bài thi (CourseAssessment) nằm trong tuần đang chọn (chỉ khi filterMode = week)
        $scopeAssessmentIds = [];
        if ($filterMode === self::FILTER_WEEK) {
            $range = CourseMonthlyEvaluation::weekDateRange($activeWeek);
            if ($range !== null) {
                $scopeAssessmentIds = CourseAssessment::query()
                    ->where('course_id', $courseId)
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('assessment_date')
                    ->whereDate('assessment_date', '>=', $range['start'])
                    ->whereDate('assessment_date', '<', $range['end'])
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
            }
        }

        $assDoneByUser = [];
        if ($scopeAssessmentIds !== [] && $memberIds !== []) {
            $assAns = CourseAssessmentAnswer::query()
                ->whereIn('course_assessment_id', $scopeAssessmentIds)
                ->whereIn('user_id', $memberIds)
                ->select('course_assessment_id', 'user_id')
                ->distinct()
                ->get();

            foreach ($assAns as $aa) {
                $assDoneByUser[(int) $aa->user_id][(int) $aa->course_assessment_id] = true;
            }
        }

        // 3. Batched Attendance cho $scopeSessionIds
        $attMap = CourseSessionAttendance::statusMapForSessions($scopeSessionIds);

        $totalPreScope = count($preQuestionsBySession);
        $totalPostScope = count($postQuestionsBySession);
        $totalTestScope = count($scopeKrIdList) + count($scopeAssessmentIds);
        $totalAttScope = count($scopeSessionIds);

        $rows = [];
        $packageOptions = ['all' => 'Tất cả'];

        foreach ($members as $member) {
            $user = $member->user;
            if ($user === null) {
                continue;
            }

            $uid = (int) $user->id;
            $tag = $this->resolveMemberTag($course, $member, $courseLevelTitle);
            $packageOptions[$tag['key']] = $tag['label'];

            $preDone = isset($preAnsweredByUserSession[$uid]) ? count($preAnsweredByUserSession[$uid]) : 0;
            $postDone = isset($postAnsweredByUserSession[$uid]) ? count($postAnsweredByUserSession[$uid]) : 0;
            $testDone = (isset($krDoneByUser[$uid]) ? count($krDoneByUser[$uid]) : 0)
                + (isset($assDoneByUser[$uid]) ? count($assDoneByUser[$uid]) : 0);

            $attDone = 0;
            $singleSessionAttStatus = CourseSessionAttendance::STATUS_ABSENT;
            foreach ($scopeSessionIds as $sid) {
                if (isset($attMap[$uid][$sid])) {
                    $attDone++;
                    $singleSessionAttStatus = $attMap[$uid][$sid];
                }
            }

            $evalRow = $evalMap[$uid] ?? null;
            $isEvaluated = $evalRow !== null && $evalRow->isFilled();

            $rows[] = [
                'user_id' => $uid,
                'name' => (string) ($user->name ?: 'Học viên'),
                'student_code' => (string) ($user->student_code ?? ''),
                'phone' => (string) ($user->phone ?? ''),
                'avatar' => $user->avatarUrl(),
                'initials' => $this->initials((string) ($user->name ?: 'HV')),
                'avatar_tone' => self::AVATAR_TONES[$uid % count(self::AVATAR_TONES)],
                'tag_key' => $tag['key'],
                'tag_label' => $tag['label'],
                'tag_tone' => $tag['tone'],
                'pre' => $this->formatWorkCell($preDone, $totalPreScope, $filterMode),
                'post' => $this->formatWorkCell($postDone, $totalPostScope, $filterMode),
                'test' => $this->formatWorkCell($testDone, $totalTestScope, $filterMode),
                'attendance' => $this->formatAttendanceCell($attDone, $totalAttScope, $filterMode, $singleSessionAttStatus),
                'evaluated' => $isEvaluated,
                'eval_label' => $isEvaluated ? 'Đã đánh giá' : 'Chưa đánh giá',
                'drawer_url' => route('teacher.course.student.drawer', [
                    'id' => $courseId,
                    'userId' => $uid,
                    'tuan' => $activeWeek,
                    'buoi' => $selectedSessionId,
                ]),
            ];
        }

        return [
            'rows' => $rows,
            'package_options' => $packageOptions,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return array<string, mixed>
     */
    private function computeCommitmentSummary(Course $course, CourseMember $member, array $sessions): array
    {
        $userId = (int) $member->user_id;
        $totalSessions = count($sessions);
        $pkg = $this->resolvePackageConfig($course, $member, $totalSessions);

        // Chỉ tính các buổi đã kết thúc (ended) hoặc đã có điểm danh/bài nộp
        $pastSessionIds = [];
        foreach ($sessions as $s) {
            if (($s['phase'] ?? '') === 'ended') {
                $pastSessionIds[] = (int) $s['id'];
            }
        }

        $allSessionIds = array_map(static fn (array $s): int => (int) $s['id'], $sessions);
        $attMap = CourseSessionAttendance::statusMapForSessions($allSessionIds, $userId)[$userId] ?? [];

        $rawAbsent = 0;
        foreach ($pastSessionIds as $sid) {
            if (! isset($attMap[$sid])) {
                $rawAbsent++;
            }
        }

        $rawLate = 0;
        foreach ($attMap as $st) {
            if ($st === CourseSessionAttendance::STATUS_LATE) {
                $rawLate++;
            }
        }
        $lateAsAbsent = intdiv($rawLate, 2);

        // Thiếu bài tập trên các buổi đã kết thúc có câu hỏi pre hoặc post_practice
        $rawMissingHw = 0;
        if ($pastSessionIds !== []) {
            $sessionsWithHw = CourseSessionQuestion::query()
                ->whereIn('course_session_id', $pastSessionIds)
                ->whereIn('question_group', ['pre', 'post_practice'])
                ->select('course_session_id', 'question_group')
                ->distinct()
                ->get();

            if ($sessionsWithHw->isNotEmpty()) {
                $answeredGroups = DB::table('course_session_quiz_answers as a')
                    ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
                    ->whereIn('a.course_session_id', $pastSessionIds)
                    ->where('a.user_id', $userId)
                    ->whereIn('q.question_group', ['pre', 'post_practice'])
                    ->select('a.course_session_id', 'q.question_group')
                    ->distinct()
                    ->get();

                $doneSet = [];
                foreach ($answeredGroups as $ag) {
                    $doneSet[(int) $ag->course_session_id.':'.$ag->question_group] = true;
                }

                $missingSessions = [];
                foreach ($sessionsWithHw as $req) {
                    $key = (int) $req->course_session_id.':'.$req->question_group;
                    if (! isset($doneSet[$key])) {
                        $missingSessions[(int) $req->course_session_id] = true;
                    }
                }
                $rawMissingHw = count($missingSessions);
            }
        }

        $absAdj = Schema::hasColumn('course_members', 'absence_adjustment') ? (int) ($member->absence_adjustment ?? 0) : 0;
        $hwAdj = Schema::hasColumn('course_members', 'homework_adjustment') ? (int) ($member->homework_adjustment ?? 0) : 0;

        $effectiveAbsent = max(0, $rawAbsent + $lateAsAbsent + $absAdj);
        $effectiveMissingHw = max(0, $rawMissingHw + $hwAdj);

        $limit = $pkg['limit'];
        $absenceDisplay = $limit !== null
            ? "{$effectiveAbsent}/{$limit} buổi"
            : "{$effectiveAbsent} buổi";
        $homeworkDisplay = $limit !== null
            ? "{$effectiveMissingHw}/{$limit} buổi"
            : "{$effectiveMissingHw} lần";

        return [
            'type' => $pkg['type'],
            'title' => $pkg['title'],
            'subtitle' => $pkg['subtitle'],
            'limit' => $limit,
            'absence_count' => $effectiveAbsent,
            'absence_display' => $absenceDisplay,
            'absence_raw' => $rawAbsent,
            'late_count' => $rawLate,
            'absence_adjustment' => $absAdj,
            'homework_count' => $effectiveMissingHw,
            'homework_display' => $homeworkDisplay,
            'homework_raw' => $rawMissingHw,
            'homework_adjustment' => $hwAdj,
        ];
    }

    /**
     * @return array{type: string, title: string, subtitle: string, limit: int|null}
     */
    private function resolvePackageConfig(Course $course, CourseMember $member, int $totalSessions): array
    {
        $stored = Schema::hasColumn('course_members', 'package_type')
            ? strtolower(trim((string) ($member->package_type ?? '')))
            : '';

        if ($stored === '') {
            $hay = mb_strtolower(
                trim((string) $course->title).' '.trim((string) ($course->program?->title ?? '')),
                'UTF-8'
            );

            if ($totalSessions >= 100 || str_contains($hay, 'topik 4') || str_contains($hay, 'topik 3') || str_contains($hay, 'định hướng') || str_contains($hay, 'dh-')) {
                $stored = 'combo_topik4';
            } elseif ($totalSessions >= 40 || str_contains($hay, 'topik 2') || str_contains($hay, 'sc12')) {
                $stored = 'combo_topik2';
            } else {
                $stored = 'single';
            }
        }

        return match ($stored) {
            'combo_topik4' => [
                'type' => 'combo_topik4',
                'title' => 'Cam kết đầu ra',
                'subtitle' => '',
                'limit' => 16,
            ],
            'combo_topik2' => [
                'type' => 'combo_topik2',
                'title' => 'Cam kết đầu ra',
                'subtitle' => '',
                'limit' => 6,
            ],
            default => [
                'type' => 'single',
                'title' => 'Quỹ vi phạm',
                'subtitle' => 'Khóa lẻ không cam kết đầu ra · dùng để nhắc học sinh và báo phụ huynh',
                'limit' => null,
            ],
        };
    }

    /**
     * @return array{key: string, label: string, tone: string}
     */
    private function resolveProgramTag(Course $course, string $levelTitle = ''): array
    {
        $label = trim($levelTitle);
        if ($label === '') {
            $label = trim((string) ($course->program?->title ?? ''));
        }
        if ($label === '') {
            $label = 'Khóa học';
        }

        $low = mb_strtolower($label, 'UTF-8');
        $tone = 'blue';
        if (str_contains($low, 'topik 3') || str_contains($low, 'topik 4') || $low === 'topik') {
            $tone = 'purple';
        } elseif (str_contains($low, 'trung cấp') || str_contains($low, 'trung cap')) {
            $tone = 'green';
        } elseif (str_contains($low, 'topik 2') || str_contains($low, 'sơ cấp 2') || str_contains($low, 'so cap 2')) {
            $tone = 'orange';
        } elseif (str_contains($low, 'sơ cấp 1') || str_contains($low, 'so cap 1')) {
            $tone = 'blue';
        } elseif (str_contains($low, 'định hướng') || str_contains($low, 'dinh huong')) {
            $tone = 'slate';
        }

        $key = preg_replace('/[^a-z0-9]+/u', '-', mb_strtolower($this->stripVietnamese($label), 'UTF-8')) ?: 'other';
        $key = trim($key, '-') ?: 'other';

        return [
            'key' => $key,
            'label' => $label,
            'tone' => $tone,
        ];
    }

    /**
     * @return array{key: string, label: string, tone: string}
     */
    private function resolveMemberTag(Course $course, CourseMember $member, string $courseLevelTitle): array
    {
        $pkg = Schema::hasColumn('course_members', 'package_type')
            ? strtolower(trim((string) ($member->package_type ?? '')))
            : '';

        if ($pkg === 'combo_topik4') {
            return ['key' => 'topik-3-4', 'label' => 'Topik 3-4', 'tone' => 'purple'];
        }
        if ($pkg === 'combo_topik2') {
            return ['key' => 'topik-2', 'label' => 'Topik 2', 'tone' => 'orange'];
        }

        return $this->resolveProgramTag($course, $courseLevelTitle);
    }

    /**
     * Format ô BTVN trước / BTVN sau / Bài kiểm tra theo Phương án 1 (#1443:35213) hoặc theo Buổi (#602:55351)
     *
     * @return array{mode: string, text: string, tone: string, done: int, total: int}
     */
    private function formatWorkCell(int $done, int $total, string $filterMode): array
    {
        if ($total <= 0) {
            return [
                'mode' => $filterMode,
                'text' => '—',
                'tone' => 'muted',
                'done' => 0,
                'total' => 0,
            ];
        }

        if ($filterMode === self::FILTER_SESSION) {
            $ok = $done >= 1;

            return [
                'mode' => self::FILTER_SESSION,
                'text' => $ok ? 'Đã làm' : 'Chưa làm',
                'tone' => $ok ? 'done-text' : 'missing-text',
                'done' => $done,
                'total' => $total,
            ];
        }

        // Chế độ Tuần — Phương án 1 (#1443:35213): pill màu giống cột Chuyên cần
        $ratio = $done / $total;
        if ($done === 0) {
            $tone = 'gray';
        } elseif ($done >= $total) {
            $tone = 'green';
        } elseif ($ratio >= 0.5) {
            $tone = 'yellow';
        } else {
            $tone = 'red';
        }

        return [
            'mode' => self::FILTER_WEEK,
            'text' => "{$done}/{$total} bài",
            'tone' => $tone,
            'done' => $done,
            'total' => $total,
        ];
    }

    /**
     * @return array{mode: string, text: string, tone: string, done: int, total: int}
     */
    private function formatAttendanceCell(int $done, int $total, string $filterMode, string $singleStatus): array
    {
        if ($total <= 0) {
            return [
                'mode' => $filterMode,
                'text' => '—',
                'tone' => 'gray',
                'done' => 0,
                'total' => 0,
            ];
        }

        if ($filterMode === self::FILTER_SESSION) {
            return match ($singleStatus) {
                CourseSessionAttendance::STATUS_PRESENT => [
                    'mode' => self::FILTER_SESSION,
                    'text' => 'Có mặt',
                    'tone' => 'green',
                    'done' => 1,
                    'total' => 1,
                ],
                CourseSessionAttendance::STATUS_LATE => [
                    'mode' => self::FILTER_SESSION,
                    'text' => 'Đi muộn',
                    'tone' => 'yellow',
                    'done' => 1,
                    'total' => 1,
                ],
                default => [
                    'mode' => self::FILTER_SESSION,
                    'text' => 'Vắng',
                    'tone' => 'red',
                    'done' => 0,
                    'total' => 1,
                ],
            };
        }

        $ratio = $done / $total;
        if ($done >= $total) {
            $tone = 'green';
        } elseif ($ratio >= 0.5) {
            $tone = 'yellow';
        } else {
            $tone = 'red';
        }

        return [
            'mode' => self::FILTER_WEEK,
            'text' => "{$done}/{$total} buổi",
            'tone' => $tone,
            'done' => $done,
            'total' => $total,
        ];
    }

    private function attendanceStatusLabel(string $status): string
    {
        return match ($status) {
            CourseSessionAttendance::STATUS_LATE => 'Đi muộn',
            CourseSessionAttendance::STATUS_ABSENT => 'Vắng',
            default => 'Có mặt',
        };
    }

    private function dateToWeekKey(string $dateYmd): string
    {
        $ts = $dateYmd !== '' ? strtotime($dateYmd) : false;

        return $ts ? date('o', $ts).'-W'.date('W', $ts) : '';
    }

    private function formatWeekDropdownLabel(string $evalWeek): string
    {
        $start = CourseMonthlyEvaluation::weekToDate($evalWeek);
        if ($start === null) {
            return $evalWeek;
        }
        $end = (clone $start)->modify('+6 days');

        return sprintf(
            'Tuần %02d (%s – %s)',
            (int) $start->format('W'),
            $start->format('d/m'),
            $end->format('d/m')
        );
    }

    private function formatWeekShortLabel(string $evalWeek): string
    {
        $start = CourseMonthlyEvaluation::weekToDate($evalWeek);
        if ($start === null) {
            return $evalWeek;
        }
        $end = (clone $start)->modify('+6 days');

        return sprintf(
            'Tuần %02d · %s – %s',
            (int) $start->format('W'),
            $start->format('d/m'),
            $end->format('d/m')
        );
    }

    private function formatWeekFullRangeLabel(string $evalWeek): string
    {
        $start = CourseMonthlyEvaluation::weekToDate($evalWeek);
        if ($start === null) {
            return $evalWeek;
        }
        $end = (clone $start)->modify('+6 days');

        return sprintf(
            'Tuần %02d · %s – %s',
            (int) $start->format('W'),
            $start->format('d/m'),
            $end->format('d/m/Y')
        );
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     */
    private function defaultSessionId(array $sessions): int
    {
        if ($sessions === []) {
            return 0;
        }

        foreach (array_reverse($sessions) as $session) {
            if (($session['phase'] ?? '') === 'ended') {
                return (int) ($session['id'] ?? 0);
            }
        }

        foreach ($sessions as $session) {
            if (($session['phase'] ?? '') === 'live') {
                return (int) ($session['id'] ?? 0);
            }
        }

        foreach ($sessions as $session) {
            if (($session['phase'] ?? '') === 'upcoming') {
                return (int) ($session['id'] ?? 0);
            }
        }

        return (int) ($sessions[0]['id'] ?? 0);
    }

    /**
     * @return list<int|string>
     */
    private function buildPageWindow(int $current, int $last): array
    {
        if ($last <= 5) {
            return range(1, $last);
        }

        $pages = [1];
        if ($current > 3) {
            $pages[] = '...';
        }

        $start = max(2, $current - 1);
        $end = min($last - 1, $current + 1);
        for ($p = $start; $p <= $end; $p++) {
            $pages[] = $p;
        }

        if ($current < $last - 2) {
            $pages[] = '...';
        }
        $pages[] = $last;

        return $pages;
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return 'HV';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2, 'UTF-8'), 'UTF-8');
        }

        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');

        return mb_strtoupper($first.$last, 'UTF-8');
    }

    private function stripVietnamese(string $str): string
    {
        $map = [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A',
            'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A',
            'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
            'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E',
            'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O',
            'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O',
            'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U',
            'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
            'Đ' => 'D',
        ];

        return strtr($str, $map);
    }
}
