<?php

namespace App\Services;

use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentRetake;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\OnlineRoomJoinRequest;
use App\Models\UserNotificationRead;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TopbarNotificationService
{
    /** @var array<string, bool> */
    private static array $tableExistsCache = [];

    /** @var array<string, bool>|null */
    private static ?array $existingTablesMap = null;

    /** @var array<string, array<string, mixed>> */
    private static array $buildCache = [];

    /** @var array<string, array<string, mixed>> */
    private static array $buildAllCache = [];

    public static function clearBuildCache(): void
    {
        self::$buildCache = [];
        self::$buildAllCache = [];
        self::$existingTablesMap = null;
        self::$tableExistsCache = [];
    }

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        'session' => 'Buổi học',
        'leave' => 'Đơn xin nghỉ',
        'chat' => 'Chat',
        'assessment' => 'Bài kiểm tra',
        'evaluation' => 'Đánh giá học viên',
        'homework' => 'Bài tập',
        'warning' => 'Cảnh báo',
        'room_join' => 'Xin vào lớp',
    ];

    private function tableExists(string $table): bool
    {
        if (self::$existingTablesMap === null) {
            try {
                self::$existingTablesMap = array_flip(Schema::getTableListing());
            } catch (Throwable) {
                return self::$tableExistsCache[$table] ??= Schema::hasTable($table);
            }
        }

        return isset(self::$existingTablesMap[$table]);
    }

    /**
     * @return array{count: int, items: list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>}
     */
    public function build(int $userId, string $role): array
    {
        $cacheKey = $userId . ':' . $role;
        if (isset(self::$buildCache[$cacheKey])) {
            return self::$buildCache[$cacheKey];
        }

        $all = $this->buildAll($userId, $role);
        $unread = array_values(array_filter(
            $all['items'],
            static fn (array $item): bool => ! empty($item['unread'])
        ));
        $read = array_values(array_filter(
            $all['items'],
            static fn (array $item): bool => empty($item['unread'])
        ));

        return self::$buildCache[$cacheKey] = [
            'count' => count($unread),
            'items' => array_slice(array_merge($unread, $read), 0, 10),
        ];
    }

    /**
     * @return array{
     *   items: list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>,
     *   unread_count: int,
     *   type_counts: array<string, int>
     * }
     */
    public function buildAll(int $userId, string $role): array
    {
        if ($userId <= 0) {
            return [
                'items' => [],
                'unread_count' => 0,
                'type_counts' => [],
            ];
        }

        $cacheKey = $userId . ':' . $role;
        if (isset(self::$buildAllCache[$cacheKey])) {
            return self::$buildAllCache[$cacheKey];
        }

        $items = match ($role) {
            'teacher' => $this->collectTeacherItems($userId),
            'admin' => $this->collectAdminItems($userId),
            default => $this->collectStudentItems($userId),
        };

        $items = $this->applyReadState($userId, $items);
        $items = $this->sortItems($items);

        $typeCounts = [];
        $unread = 0;

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');

            if ($type !== '') {
                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
            }

            if (! empty($item['unread'])) {
                ++$unread;
            }
        }

        return self::$buildAllCache[$cacheKey] = [
            'items' => $items,
            'unread_count' => $unread,
            'type_counts' => $typeCounts,
        ];
    }

    /**
     * @param  list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>  $items
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    public function filterItems(array $items, string $typeFilter, string $readFilter): array
    {
        $typeFilter = trim($typeFilter);
        $readFilter = trim($readFilter);
        $filtered = $items;

        if ($typeFilter !== '' && isset(self::TYPE_LABELS[$typeFilter])) {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (array $item): bool => ($item['type'] ?? '') === $typeFilter
            ));
        }

        if ($readFilter === 'unread') {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (array $item): bool => ! empty($item['unread'])
            ));
        } elseif ($readFilter === 'read') {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (array $item): bool => empty($item['unread'])
            ));
        }

        return $filtered;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getTypeOptions(string $role): array
    {
        $types = match ($role) {
            'teacher' => ['session', 'leave', 'room_join', 'assessment', 'evaluation', 'homework'],
            'admin' => ['leave', 'room_join', 'homework'],
            default => ['session', 'leave', 'assessment', 'homework'],
        };

        $options = [
            ['value' => '', 'label' => 'Tất cả loại'],
        ];

        foreach ($types as $type) {
            $options[] = [
                'value' => $type,
                'label' => self::TYPE_LABELS[$type] ?? $type,
            ];
        }

        return $options;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectTeacherItems(int $teacherId): array
    {
        if ($teacherId <= 0) {
            return [];
        }

        $items = [];
        $pendingCount = $this->countPendingLeavesForTeacher($teacherId);

        if ($pendingCount > 0) {
            foreach ($this->leavesForTeacher($teacherId, 'pending', 30) as $row) {
                $items[] = $this->leaveItemForTeacher($row);
            }

            $items = array_merge($items, $this->collectAdminJoinRequestItems());
        }

        $evalCount = $this->countPendingEvaluationsForTeacher($teacherId);

        if ($evalCount > 0) {
            $items[] = $this->item(
                'eval-pending-'.$evalCount,
                'evaluation',
                'Đánh giá học viên',
                $evalCount.' đánh giá tháng chưa hoàn thành',
                route('teacher.evaluations'),
                '',
                time(),
                'fa-clipboard-check'
            );
        }

        $items = array_merge($items, $this->collectTeacherSessionItems($teacherId));
        $items = array_merge($items, $this->collectTeacherJoinRequestItems($teacherId));
        $items = array_merge($items, $this->collectTeacherHomeworkItems($teacherId, false));
        $items = array_merge($items, $this->collectTeacherAssessmentItems($teacherId));

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectAdminItems(int $userId): array
    {
        $items = [];

        foreach ($this->allPendingLeaves(30) as $row) {
            $session = $this->formatSessionLabel($row);
            $created = $this->datetimeString($row['created_at'] ?? '');
            $courseId = (int) ($row['course_id'] ?? 0);

            $items[] = $this->item(
                'leave-'.($row['id'] ?? 0),
                'leave',
                'Đơn xin nghỉ chờ duyệt',
                trim(($row['student_name'] ?? 'Học viên').' · '.($row['course_title'] ?? '').($session !== '' ? ' · '.$session : '')),
                $this->adminCourseUrl($courseId, 'leave-requests'),
                $this->formatTime($created),
                $this->timestampFromDatetime($created),
                'fa-calendar-xmark'
            );
        }

        $items = array_merge($items, $this->collectTeacherHomeworkItems($userId, true));
        $items = array_merge($items, $this->collectAdminJoinRequestItems());

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectStudentItems(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $items = [];
        $pending = array_values(array_filter(
            $this->leavesForStudent($userId, 30),
            static fn (array $row): bool => ($row['status'] ?? '') === 'pending'
        ));

        foreach ($pending as $row) {
            $created = $this->datetimeString($row['created_at'] ?? '');

            $items[] = $this->item(
                'leave-pending-'.($row['id'] ?? 0),
                'leave',
                'Đơn đang chờ duyệt',
                ($row['course_title'] ?? 'Khóa học').' · '.$this->formatSessionLabel($row),
                route('overview'),
                $this->formatTime($created),
                $this->timestampFromDatetime($created),
                'fa-hourglass-half'
            );
        }

        foreach ($this->recentLeaveResponsesForStudent($userId, 30, 15) as $row) {
            $status = (string) ($row['status'] ?? '');
            $when = $this->datetimeString($row['reviewed_at'] ?? $row['updated_at'] ?? '');

            $items[] = $this->item(
                'leave-response-'.($row['id'] ?? 0),
                'leave',
                $status === 'approved' ? 'Đơn đã được duyệt' : 'Đơn bị từ chối',
                ($row['course_title'] ?? 'Khóa học').' · '.$this->formatSessionLabel($row),
                route('overview'),
                $this->formatTime($when),
                $this->timestampFromDatetime($when),
                $status === 'approved' ? 'fa-circle-check' : 'fa-circle-xmark'
            );
        }

        $items = array_merge($items, $this->collectStudentWarningItems($userId));
        $items = array_merge($items, $this->collectStudentCourseItems($userId));
        $items = array_merge($items, $this->collectStudentHomeworkItems($userId));

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectStudentHomeworkItems(int $userId): array
    {
        $items = [];

        try {
            if (! $this->tableExists('homework_assignments') || ! $this->tableExists('homework_submissions')) {
                return [];
            }

            foreach ($this->upcomingMissingHomework($userId, 48) as $row) {
                $id = (int) ($row['id'] ?? 0);
                $dueAt = $this->datetimeString($row['due_at'] ?? '');
                $sortAt = $dueAt !== '' ? (int) strtotime($dueAt) : time();

                $items[] = $this->item(
                    'homework-due-'.$id,
                    'homework',
                    'Bài tập sắp đến hạn',
                    trim(($row['title'] ?? 'Bài tập').' · '.($row['course_title'] ?? '')),
                    route('overview'),
                    $this->formatTimeFromTimestamp($sortAt),
                    $sortAt,
                    'fa-file-lines'
                );
            }

            foreach ($this->overdueMissingHomework($userId, 5) as $row) {
                $id = (int) ($row['id'] ?? 0);

                $items[] = $this->item(
                    'homework-missing-'.$id,
                    'homework',
                    'Chưa nộp bài tập',
                    trim(($row['title'] ?? 'Bài tập').' · '.($row['course_title'] ?? '')),
                    route('overview'),
                    'Quá hạn',
                    time(),
                    'fa-circle-exclamation'
                );
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectStudentWarningItems(int $userId): array
    {
        $items = [];

        try {
            if (! $this->tableExists('student_warnings')) {
                return [];
            }

            $rows = DB::table('student_warnings as w')
                ->join('courses as c', 'c.id', '=', 'w.course_id')
                ->join('users as t', 't.id', '=', 'w.teacher_id')
                ->where('w.student_user_id', $userId)
                ->whereNull('w.read_at')
                ->orderByDesc('w.created_at')
                ->limit(10)
                ->get([
                    'w.id',
                    'w.title',
                    'w.severity',
                    'w.created_at',
                    'c.title as course_title',
                    't.full_name as teacher_name',
                    't.username as teacher_username',
                ]);

            foreach ($rows as $row) {
                $id = (int) ($row->id ?? 0);
                $created = $this->datetimeString($row->created_at ?? '');
                $teacher = trim((string) ($row->teacher_name ?? '')) ?: (string) ($row->teacher_username ?? 'Giáo viên');
                $severity = (string) ($row->severity ?? 'warning');

                $items[] = $this->item(
                    'warning-'.$id,
                    'warning',
                    (string) ($row->title ?? 'Cảnh báo mới'),
                    trim($teacher.' · '.($row->course_title ?? '')),
                    route('overview'),
                    $this->formatTime($created),
                    $this->timestampFromDatetime($created),
                    $severity === 'critical' ? 'fa-circle-exclamation' : 'fa-triangle-exclamation'
                );
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectTeacherHomeworkItems(int $teacherId, bool $isAdmin): array
    {
        $items = [];

        try {
            if (! $this->tableExists('homework_assignments') || ! $this->tableExists('homework_submissions')) {
                return [];
            }

            $query = DB::table('homework_submissions as s')
                ->join('homework_assignments as a', 'a.id', '=', 's.assignment_id')
                ->join('courses as c', 'c.id', '=', 'a.course_id')
                ->whereIn('s.status', ['submitted', 'late']);

            if (! $isAdmin) {
                $query->where('c.teacher_id', $teacherId);
            }

            $stats = $query->selectRaw('COUNT(s.id) as total, MIN(c.id) as course_id')->first();
            $count = (int) ($stats->total ?? 0);

            if ($count <= 0) {
                return [];
            }

            $courseId = (int) ($stats->course_id ?? 0);
            $url = $isAdmin
                ? $this->adminCourseUrl($courseId, 'homework')
                : $this->teacherCourseUrl($courseId);

            $items[] = $this->item(
                'homework-grade-'.$count,
                'homework',
                'Bài tập chờ chấm',
                $count.' bài nộp cần chấm điểm',
                $url,
                'Vừa xong',
                time(),
                'fa-file-lines'
            );
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectTeacherAssessmentItems(int $teacherId): array
    {
        $items = [];

        try {
            if (
                ! $this->tableExists('course_assessment_submissions')
                || ! $this->tableExists('course_assessments')
                || ! $this->tableExists('course_assessment_answers')
            ) {
                return [];
            }

            $rows = DB::table('course_assessment_submissions as s')
                ->join('course_assessments as a', 'a.id', '=', 's.course_assessment_id')
                ->join('courses as c', 'c.id', '=', 'a.course_id')
                ->join('users as u', 'u.id', '=', 's.user_id')
                ->join('course_assessment_answers as ans', static function ($join): void {
                    $join->on('ans.course_assessment_id', '=', 's.course_assessment_id')
                        ->on('ans.user_id', '=', 's.user_id');
                })
                ->where('c.teacher_id', $teacherId)
                ->where('s.status', 'submitted')
                ->orderByDesc('s.submitted_at')
                ->orderByDesc('s.id')
                ->limit(15)
                ->get([
                    's.id',
                    's.course_assessment_id',
                    's.user_id',
                    's.submitted_at',
                    'a.title as assessment_title',
                    'a.course_id',
                    'c.title as course_title',
                    'u.full_name as student_name',
                    'u.username as student_username',
                ]);

            $grading = app(TeacherAssessmentGradingService::class);

            foreach ($rows as $row) {
                $submissionId = (int) ($row->id ?? 0);
                $courseId = (int) ($row->course_id ?? 0);
                $assessmentId = (int) ($row->course_assessment_id ?? 0);
                $studentId = (int) ($row->user_id ?? 0);

                if ($submissionId <= 0 || $courseId <= 0 || $assessmentId <= 0 || $studentId <= 0) {
                    continue;
                }

                $studentName = trim((string) ($row->student_name ?? '')) ?: (string) ($row->student_username ?? 'Học viên');
                $assessmentTitle = trim((string) ($row->assessment_title ?? 'Bài kiểm tra'));
                $courseTitle = trim((string) ($row->course_title ?? 'Khóa học'));
                $submittedAt = $this->datetimeString($row->submitted_at ?? '');

                $items[] = $this->item(
                    'assessment-grade-'.$submissionId,
                    'assessment',
                    'Bài kiểm tra chờ chấm',
                    trim($studentName.' · '.$assessmentTitle.' · '.$courseTitle),
                    $grading->pageUrl($courseId, $assessmentId, $studentId),
                    $this->formatTime($submittedAt),
                    $this->timestampFromDatetime($submittedAt),
                    'fa-list-check'
                );
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectStudentCourseItems(int $userId): array
    {
        $items = [];

        try {
            $course = CourseMember::activeCourseFor($userId);

            if ($course === null) {
                return [];
            }

            $courseId = (int) $course->id;
            $courseTitle = trim((string) ($course->title ?? 'Khóa học'));
            $nav = app(StudentCourseNavService::class);
            $levelId = $nav->resolveActiveLevelId($userId);

            if ($this->tableExists('course_sessions')) {
                $catalog = app(StudentSessionCatalogService::class);
                $catalogSessions = $catalog->forStudentCourse($courseId, $userId);

                $upcoming = [];

                foreach ($catalogSessions as $sessData) {
                    $phase = (string) ($sessData['phase'] ?? '');

                    if (! in_array($phase, ['live', 'upcoming'], true)) {
                        continue;
                    }

                    $upcoming[] = $sessData;
                }

                usort($upcoming, static function (array $a, array $b): int {
                    $phaseOrder = ['live' => 0, 'upcoming' => 1];
                    $phaseCmp = ($phaseOrder[$a['phase'] ?? ''] ?? 2) <=> ($phaseOrder[$b['phase'] ?? ''] ?? 2);

                    if ($phaseCmp !== 0) {
                        return $phaseCmp;
                    }

                    $aStart = (int) ($a['display']['window']['start'] ?? PHP_INT_MAX);
                    $bStart = (int) ($b['display']['window']['start'] ?? PHP_INT_MAX);

                    return $aStart <=> $bStart;
                });

                foreach (array_slice($upcoming, 0, 20) as $sessData) {
                    $sessionId = (int) ($sessData['id'] ?? 0);

                    if ($sessionId <= 0) {
                        continue;
                    }

                    $phase = (string) ($sessData['phase'] ?? '');
                    $isLive = $phase === 'live';
                    $title = trim((string) ($sessData['title'] ?? 'Buổi học'));
                    $display = $sessData['display'] ?? [];
                    $whenText = trim(($display['dateLabel'] ?? '').(! empty($display['timeLabel']) ? ' · '.$display['timeLabel'] : ''));
                    $sortAt = ! empty($display['window']['start'])
                        ? (int) $display['window']['start']
                        : time();
                    $code = trim((string) ($sessData['public_code'] ?? ''));
                    $sessLevelId = (int) ($sessData['level_id'] ?? $levelId);
                    $url = $sessLevelId > 0 && $code !== ''
                        ? $nav->dashboardSessionUrl($sessLevelId, $code, $nav->defaultTabForSession(['phase' => $phase]))
                        : $nav->dashboardUrlForStudent($userId);

                    $items[] = $this->item(
                        'session-'.$sessionId,
                        'session',
                        $isLive ? 'Buổi học đang diễn ra' : 'Buổi học sắp tới',
                        trim($courseTitle.' · '.$title.($whenText !== '' ? ' · '.$whenText : '')),
                        $url,
                        $isLive ? 'Đang live' : $this->formatTimeFromTimestamp($sortAt),
                        $isLive ? time() : $sortAt,
                        $isLive ? 'fa-video' : 'fa-calendar-day'
                    );
                }
            }

            if ($this->tableExists('course_assessments')) {
                $assessments = CourseAssessment::query()
                    ->with('slots')
                    ->where('course_id', $courseId)
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                $upcoming = [];
                $assessmentIds = $assessments->pluck('id')->all();

                $submittedAssessmentIds = [];
                if ($this->tableExists('course_assessment_answers') && ! empty($assessmentIds)) {
                    $submittedAssessmentIds = CourseAssessmentAnswer::query()
                        ->whereIn('course_assessment_id', $assessmentIds)
                        ->where('user_id', $userId)
                        ->pluck('course_assessment_id')
                        ->flip()
                        ->all();
                }

                $retakeAllowedMap = [];
                if ($this->tableExists('course_assessment_retakes') && ! empty($assessmentIds)) {
                    $retakeAllowedMap = CourseAssessmentRetake::query()
                        ->whereIn('course_assessment_id', $assessmentIds)
                        ->where('user_id', $userId)
                        ->pluck('course_assessment_id')
                        ->flip()
                        ->all();
                }

                foreach ($assessments as $assessment) {
                    $hasSubmitted = isset($submittedAssessmentIds[(int) $assessment->id]);
                    $allowRetake = isset($retakeAllowedMap[(int) $assessment->id]);
                    $phase = $assessment->studentPhase($hasSubmitted, $allowRetake);

                    if (! in_array($phase, ['open', 'upcoming', 'retake'], true)) {
                        continue;
                    }

                    $upcoming[] = [$assessment, $phase];
                }

                usort($upcoming, static function (array $a, array $b): int {
                    $phaseOrder = ['open' => 0, 'retake' => 0, 'upcoming' => 1];
                    $phaseCmp = ($phaseOrder[$a[1]] ?? 2) <=> ($phaseOrder[$b[1]] ?? 2);

                    if ($phaseCmp !== 0) {
                        return $phaseCmp;
                    }

                    $aStart = $a[0]->isScheduled() ? (int) ($a[0]->window()['start'] ?? PHP_INT_MAX) : PHP_INT_MAX;
                    $bStart = $b[0]->isScheduled() ? (int) ($b[0]->window()['start'] ?? PHP_INT_MAX) : PHP_INT_MAX;

                    return $aStart <=> $bStart;
                });

                $displayService = app(StudentAssessmentService::class);

                foreach (array_slice($upcoming, 0, 20) as [$assessment, $phase]) {
                    $assessmentId = (int) $assessment->id;

                    if ($assessmentId <= 0) {
                        continue;
                    }

                    $isOpen = in_array($phase, ['open', 'retake'], true);
                    $title = trim((string) ($assessment->title ?? 'Bài kiểm tra'));
                    $payload = $assessment->toStudentArray();
                    $payload['phase'] = $phase;
                    $display = $displayService->studentDisplay($payload);
                    $whenText = trim(($display['dateLabel'] ?? '').(! empty($display['timeLabel']) ? ' · '.$display['timeLabel'] : ''));
                    $sortAt = $assessment->isScheduled()
                        ? (int) ($assessment->window()['start'] ?? time())
                        : time();

                    $items[] = $this->item(
                        'assessment-'.$assessmentId,
                        'assessment',
                        $isOpen ? 'Bài kiểm tra đang mở' : 'Bài kiểm tra sắp tới',
                        trim($courseTitle.' · '.$title.($whenText !== '' ? ' · '.$whenText : '')),
                        route('student.assessment.show', $assessmentId),
                        $isOpen ? 'Đang mở' : $this->formatTimeFromTimestamp($sortAt),
                        $isOpen ? time() : $sortAt,
                        $isOpen ? 'fa-clipboard-check' : 'fa-file-lines'
                    );
                }
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectTeacherSessionItems(int $teacherId): array
    {
        $items = [];

        try {
            foreach (CourseSession::upcomingForTeacher($teacherId, 20) as $session) {
                $sessionId = (int) $session->id;
                $courseId = (int) $session->course_id;
                $courseTitle = trim((string) ($session->course?->title ?? 'Khóa học'));
                $title = trim((string) ($session->title ?? 'Buổi học'));
                $sessionDate = $session->dateForInput();
                $startTime = $session->timeForInput($session->start_time);
                $whenText = $sessionDate !== '' ? date('d/m/Y', strtotime($sessionDate) ?: time()) : '';

                if ($startTime !== '') {
                    $whenText = trim($whenText.($whenText !== '' ? ' · ' : '').$startTime);
                }

                $sortAt = $sessionDate !== ''
                    ? strtotime($sessionDate.' '.($startTime !== '' ? $startTime.':00' : '00:00:00'))
                    : time();

                if ($sortAt === false) {
                    $sortAt = time();
                }

                $items[] = $this->item(
                    'session-'.$sessionId,
                    'session',
                    'Buổi học sắp tới',
                    trim($courseTitle.' · '.$title.($whenText !== '' ? ' · '.$whenText : '')),
                    $this->teacherCourseUrl($courseId),
                    $this->formatTimeFromTimestamp((int) $sortAt),
                    (int) $sortAt,
                    'fa-calendar-day'
                );
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @param  list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>  $items
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function applyReadState(int $userId, array $items): array
    {
        $readSet = array_flip(UserNotificationRead::getKeysForUser($userId));

        foreach ($items as &$item) {
            $item['unread'] = ! isset($readSet[(string) ($item['id'] ?? '')]);
        }
        unset($item);

        return $items;
    }

    /**
     * @param  list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>  $items
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function sortItems(array $items): array
    {
        usort($items, static function (array $a, array $b): int {
            return ($b['sort_at'] ?? 0) <=> ($a['sort_at'] ?? 0);
        });

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectTeacherJoinRequestItems(int $teacherId): array
    {
        $items = [];

        try {
            foreach (OnlineRoomJoinRequest::pendingForModerator($teacherId, 'teacher', 30) as $row) {
                $items[] = $this->joinRequestItem($row);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @return list<array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}>
     */
    private function collectAdminJoinRequestItems(): array
    {
        $items = [];

        try {
            foreach (OnlineRoomJoinRequest::pendingForModerator(0, 'admin', 30) as $row) {
                $items[] = $this->joinRequestItem($row);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}
     */
    private function joinRequestItem(array $row): array
    {
        $created = $this->datetimeString($row['requested_at'] ?? $row['created_at'] ?? '');
        $roomId = (int) ($row['room_id'] ?? 0);
        $name = $this->joinRequestDisplayName($row);
        $suffix = ! empty($row['is_guest']) ? ' (nick ngoài)' : '';

        return $this->item(
            'room-join-'.($row['id'] ?? 0),
            'room_join',
            'Yêu cầu vào lớp online',
            trim($name.$suffix.' · '.($row['room_title'] ?? 'Phòng học')),
            $roomId > 0 ? route('classroom.room', $roomId) : route('classroom.index'),
            $this->formatTime($created),
            $this->timestampFromDatetime($created),
            'fa-door-open'
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function joinRequestDisplayName(array $row): string
    {
        if (! empty($row['is_guest']) && trim((string) ($row['guest_name'] ?? '')) !== '') {
            return trim((string) $row['guest_name']);
        }

        return trim((string) ($row['full_name'] ?? '')) ?: (string) ($row['username'] ?? 'Người tham gia');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}
     */
    private function leaveItemForTeacher(array $row): array
    {
        $session = $this->formatSessionLabel($row);
        $created = $this->datetimeString($row['created_at'] ?? '');
        $courseId = (int) ($row['course_id'] ?? 0);

        return $this->item(
            'leave-'.($row['id'] ?? 0),
            'leave',
            'Đơn xin nghỉ mới',
            trim(($row['student_name'] ?? 'Học viên').' · '.($row['course_title'] ?? '').($session !== '' ? ' · '.$session : '')),
            $this->teacherCourseUrl($courseId),
            $this->formatTime($created),
            $this->timestampFromDatetime($created),
            'fa-calendar-xmark'
        );
    }

    private function countPendingEvaluationsForTeacher(int $teacherId): int
    {
        try {
            return app(TeacherDashboardService::class)->pendingEvaluationCount($teacherId);
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    private function countPendingLeavesForTeacher(int $teacherId): int
    {
        if ($teacherId <= 0 || ! Schema::hasTable('course_leave_requests')) {
            return 0;
        }

        try {
            return (int) DB::table('course_leave_requests as r')
                ->join('courses as c', 'c.id', '=', 'r.course_id')
                ->where('c.teacher_id', $teacherId)
                ->where('r.status', 'pending')
                ->count();
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function leavesForTeacher(int $teacherId, string $status, int $limit): array
    {
        if ($teacherId <= 0 || ! Schema::hasTable('course_leave_requests')) {
            return [];
        }

        return DB::table('course_leave_requests as r')
            ->join('courses as c', 'c.id', '=', 'r.course_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('course_sessions as s', 's.id', '=', 'r.course_session_id')
            ->where('c.teacher_id', $teacherId)
            ->where('r.status', $status)
            ->orderByDesc('r.created_at')
            ->limit($limit)
            ->get([
                'r.id',
                'r.course_id',
                'r.created_at',
                'c.title as course_title',
                'u.full_name as student_name',
                's.title as session_title',
                's.session_date',
                's.start_time',
            ])
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allPendingLeaves(int $limit): array
    {
        if (! Schema::hasTable('course_leave_requests')) {
            return [];
        }

        return DB::table('course_leave_requests as r')
            ->join('courses as c', 'c.id', '=', 'r.course_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('course_sessions as s', 's.id', '=', 'r.course_session_id')
            ->where('r.status', 'pending')
            ->orderByDesc('r.created_at')
            ->limit($limit)
            ->get([
                'r.id',
                'r.course_id',
                'r.created_at',
                'c.title as course_title',
                'u.full_name as student_name',
                's.title as session_title',
                's.session_date',
                's.start_time',
            ])
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function leavesForStudent(int $userId, int $limit): array
    {
        if ($userId <= 0 || ! Schema::hasTable('course_leave_requests')) {
            return [];
        }

        return DB::table('course_leave_requests as r')
            ->join('courses as c', 'c.id', '=', 'r.course_id')
            ->leftJoin('course_sessions as s', 's.id', '=', 'r.course_session_id')
            ->where('r.user_id', $userId)
            ->orderByDesc('r.created_at')
            ->limit($limit)
            ->get([
                'r.id',
                'r.status',
                'r.created_at',
                'c.title as course_title',
                's.title as session_title',
                's.session_date',
                's.start_time',
            ])
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentLeaveResponsesForStudent(int $userId, int $days, int $limit): array
    {
        if ($userId <= 0 || $days <= 0 || ! Schema::hasTable('course_leave_requests')) {
            return [];
        }

        return DB::table('course_leave_requests as r')
            ->join('courses as c', 'c.id', '=', 'r.course_id')
            ->leftJoin('course_sessions as s', 's.id', '=', 'r.course_session_id')
            ->where('r.user_id', $userId)
            ->whereIn('r.status', ['approved', 'rejected'])
            ->whereNotNull('r.reviewed_at')
            ->where('r.reviewed_at', '>=', now()->subDays($days))
            ->orderByDesc('r.reviewed_at')
            ->limit($limit)
            ->get([
                'r.id',
                'r.status',
                'r.reviewed_at',
                'r.updated_at',
                'c.title as course_title',
                's.title as session_title',
                's.session_date',
                's.start_time',
            ])
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingMissingHomework(int $userId, int $hours): array
    {
        return array_map(
            static fn ($row): array => (array) $row,
            DB::select(
                'SELECT a.*, c.title AS course_title
                 FROM homework_assignments a
                 INNER JOIN courses c ON c.id = a.course_id
                 INNER JOIN course_members m ON m.course_id = a.course_id AND m.user_id = ? AND m.status = ?
                 LEFT JOIN homework_submissions s ON s.assignment_id = a.id AND s.user_id = ?
                 WHERE a.status = ?
                   AND a.due_at IS NOT NULL
                   AND a.due_at > NOW()
                   AND a.due_at <= DATE_ADD(NOW(), INTERVAL ? HOUR)
                   AND (s.id IS NULL OR s.status = ?)
                 ORDER BY a.due_at ASC
                 LIMIT 10',
                [$userId, 'active', $userId, 'open', $hours, 'pending']
            )
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function overdueMissingHomework(int $userId, int $limit): array
    {
        return array_map(
            static fn ($row): array => (array) $row,
            DB::select(
                'SELECT a.*, c.title AS course_title
                 FROM homework_assignments a
                 INNER JOIN courses c ON c.id = a.course_id
                 INNER JOIN course_members m ON m.course_id = a.course_id AND m.user_id = ? AND m.status = ?
                 LEFT JOIN homework_submissions s ON s.assignment_id = a.id AND s.user_id = ?
                 WHERE a.status = ?
                   AND a.due_at IS NOT NULL
                   AND a.due_at < NOW()
                   AND (s.id IS NULL OR s.status = ?)
                 ORDER BY a.due_at DESC
                 LIMIT ?',
                [$userId, 'active', $userId, 'open', 'pending', $limit]
            )
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function formatSessionLabel(array $row): string
    {
        $title = trim((string) ($row['session_title'] ?? ''));

        if ($title === '') {
            return 'Buổi học';
        }

        $date = $this->datetimeString($row['session_date'] ?? '');
        $date = $date !== '' ? substr($date, 0, 10) : '';
        $time = substr((string) ($row['start_time'] ?? ''), 0, 5);

        if ($date !== '') {
            $ts = strtotime($date);

            if ($ts !== false) {
                $label = $title.' · '.date('d/m/Y', $ts);

                if ($time !== '') {
                    $label .= ' '.$time;
                }

                return $label;
            }
        }

        return $title;
    }

    /**
     * @return array{id: string, type: string, title: string, text: string, url: string, time: string, sort_at: int, icon: string, unread: bool}
     */
    private function item(
        string $id,
        string $type,
        string $title,
        string $text,
        string $url,
        string $time,
        int $sortAt,
        string $icon
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'text' => $text,
            'url' => $url,
            'time' => $time,
            'sort_at' => $sortAt,
            'icon' => $icon,
            'unread' => true,
        ];
    }

    private function teacherCourseUrl(int $courseId): string
    {
        return $courseId > 0
            ? route('teacher.course.show', ['id' => $courseId])
            : route('dashboard');
    }

    private function adminCourseUrl(int $courseId, ?string $tab = null): string
    {
        if ($courseId <= 0) {
            return route('admin.courses.index');
        }

        $params = ['id' => $courseId];

        if ($tab !== null && $tab !== '') {
            $params['tab'] = $tab;
        }

        return route('admin.courses.show', $params);
    }

    private function datetimeString(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }

    private function timestampFromDatetime(string $datetime): int
    {
        if ($datetime === '') {
            return 0;
        }

        $ts = strtotime($datetime);

        return $ts === false ? 0 : $ts;
    }

    private function formatTimeFromTimestamp(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return '';
        }

        return $this->formatTime(date('Y-m-d H:i:s', $timestamp));
    }

    private function formatTime(string $datetime): string
    {
        if ($datetime === '') {
            return '';
        }

        $ts = strtotime($datetime);

        if ($ts === false) {
            return '';
        }

        $diff = time() - $ts;

        if ($diff < 60) {
            return 'Vừa xong';
        }

        if ($diff < 3600) {
            return ((int) floor($diff / 60)).' phút trước';
        }

        if ($diff < 86400) {
            return ((int) floor($diff / 3600)).' giờ trước';
        }

        if ($diff < 604800) {
            return ((int) floor($diff / 86400)).' ngày trước';
        }

        return date('d/m/Y H:i', $ts);
    }
}
