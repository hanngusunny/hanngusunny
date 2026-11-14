<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSessionQuizAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StudentLearningProgressService
{
    public const WEEKLY_GOAL_DAYS = 5;

    public function __construct(
        private StudentSessionCatalogService $catalog,
        private StudentCourseNavService $nav,
        private CourseScheduleService $schedule,
        private StudentAssessmentService $assessments,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPage(int $userId, mixed $thang = null, mixed $nam = null): array
    {
        $course = CourseMember::activeCourseFor($userId);

        if ($course !== null && ! $course->relationLoaded('teacher')) {
            $course->load('teacher');
        }

        $progress = $this->build($userId, $course);
        $evaluation = $this->buildEvaluation($course, $userId, $thang, $nam);

        return array_merge($progress, $evaluation, [
            'course' => $course,
            'navService' => $this->nav,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(int $userId, ?Course $course = null): array
    {
        if ($userId <= 0) {
            return $this->emptyPayload();
        }

        $course ??= CourseMember::activeCourseFor($userId);
        $overall = $this->getOverallProgress($userId, $course);
        $activeDates = $this->getActiveDates($userId);
        $streak = $this->calculateStreak($activeDates);
        $weekly = $this->buildWeeklyProgress($activeDates, $userId, $course);
        $counts = $this->getActivityCounts($userId);
        $recent = $this->getRecentActivities($userId, 12);

        return [
            'progress' => [
                'overall' => $overall,
                'streak' => $streak,
                'weekly' => $weekly,
                'weekly_progress' => $weekly['progress_percent'],
                'activity_counts' => $counts,
                'recent' => $recent,
            ],
        ];
    }

    /**
     * @return array{percent: int, sessions_percent: int, assessments_percent: int, lessons_percent: int, videos_percent: int, sessions_done: int, sessions_total: int, assessments_done: int, assessments_total: int, lessons_done: int, lessons_total: int, videos_done: int, videos_total: int}
     */
    public function getOverallProgress(int $userId, ?Course $course = null): array
    {
        $course ??= CourseMember::activeCourseFor($userId);
        $sessionsDone = 0;
        $sessionsTotal = 0;
        $assessmentsDone = 0;
        $assessmentsTotal = 0;

        if ($course !== null) {
            $sessions = $this->catalog->forStudentCourse((int) $course->id, $userId);
            $stats = $this->catalog->summarize($sessions);
            $sessionsTotal = (int) $stats['total'];
            $sessionsDone = (int) $stats['ended'];

            foreach ($this->assessments->listForStudentCourse((int) $course->id, $userId) as $row) {
                $assessmentsTotal++;

                if (! empty($row['has_submitted'])) {
                    $assessmentsDone++;
                }
            }
        }

        $sessionsPercent = $sessionsTotal > 0 ? (int) round(($sessionsDone / $sessionsTotal) * 100) : 0;
        $assessmentsPercent = $assessmentsTotal > 0 ? (int) round(($assessmentsDone / $assessmentsTotal) * 100) : 0;

        $library = $this->libraryProgress($userId);
        $avgParts = [];

        if ($sessionsTotal > 0) {
            $avgParts[] = $sessionsPercent;
        }

        if ($assessmentsTotal > 0) {
            $avgParts[] = $assessmentsPercent;
        }

        if ($library['lessons_total'] > 0) {
            $avgParts[] = $library['lessons_percent'];
        }

        if ($library['videos_total'] > 0) {
            $avgParts[] = $library['videos_percent'];
        }

        $percent = $avgParts !== [] ? (int) round(array_sum($avgParts) / count($avgParts)) : 0;

        return [
            'percent' => min(100, max(0, $percent)),
            'sessions_percent' => min(100, max(0, $sessionsPercent)),
            'assessments_percent' => min(100, max(0, $assessmentsPercent)),
            'lessons_percent' => $library['lessons_percent'],
            'videos_percent' => $library['videos_percent'],
            'sessions_done' => $sessionsDone,
            'sessions_total' => $sessionsTotal,
            'assessments_done' => $assessmentsDone,
            'assessments_total' => $assessmentsTotal,
            'lessons_done' => $library['lessons_done'],
            'lessons_total' => $library['lessons_total'],
            'videos_done' => $library['videos_done'],
            'videos_total' => $library['videos_total'],
        ];
    }

    /**
     * @return list<string>
     */
    public function getActiveDates(int $userId, int $lookbackDays = 120): array
    {
        if ($userId <= 0) {
            return [];
        }

        $since = date('Y-m-d', strtotime('-'.max(1, $lookbackDays).' days'));
        $parts = [];
        $bindings = [];

        $add = static function (string $sql, array $params) use (&$parts, &$bindings): void {
            $parts[] = $sql;
            $bindings = array_merge($bindings, $params);
        };

        if (Schema::hasTable('lesson_attempts')) {
            $add('SELECT DATE(created_at) AS activity_date FROM lesson_attempts WHERE user_id = ? AND DATE(created_at) >= ?', [$userId, $since]);
        }

        if (Schema::hasTable('video_attempts')) {
            $add('SELECT DATE(created_at) AS activity_date FROM video_attempts WHERE user_id = ? AND DATE(created_at) >= ?', [$userId, $since]);
        }

        if (Schema::hasTable('mock_exam_attempts')) {
            $add('SELECT DATE(submitted_at) AS activity_date FROM mock_exam_attempts WHERE user_id = ? AND submitted_at IS NOT NULL AND DATE(submitted_at) >= ?', [$userId, $since]);
        }

        if (Schema::hasTable('course_session_quiz_answers')) {
            $add('SELECT DATE(updated_at) AS activity_date FROM course_session_quiz_answers WHERE user_id = ? AND DATE(updated_at) >= ?', [$userId, $since]);
        }

        if (Schema::hasTable('course_assessment_submissions')) {
            $add('SELECT DATE(submitted_at) AS activity_date FROM course_assessment_submissions WHERE user_id = ? AND submitted_at IS NOT NULL AND DATE(submitted_at) >= ?', [$userId, $since]);
        }

        if (Schema::hasTable('course_session_attendance')) {
            $add('SELECT DATE(joined_at) AS activity_date FROM course_session_attendance WHERE user_id = ? AND joined_at IS NOT NULL AND DATE(joined_at) >= ?', [$userId, $since]);
        }

        if (Schema::hasTable('course_knowledge_review_answers')) {
            $add('SELECT DATE(updated_at) AS activity_date FROM course_knowledge_review_answers WHERE user_id = ? AND DATE(updated_at) >= ?', [$userId, $since]);
        }

        if ($parts === []) {
            return [];
        }

        $rows = DB::select(
            'SELECT DISTINCT activity_date FROM ('.implode(' UNION ', $parts).') AS combined
             WHERE activity_date IS NOT NULL
             ORDER BY activity_date DESC',
            $bindings
        );

        return array_values(array_filter(array_map(
            static fn (object $row): string => (string) ($row->activity_date ?? ''),
            $rows
        )));
    }

    /**
     * @param  list<string>  $activeDates
     * @return array{current: int, best: int}
     */
    public function calculateStreak(array $activeDates): array
    {
        if ($activeDates === []) {
            return ['current' => 0, 'best' => 0];
        }

        $dateSet = array_fill_keys($activeDates, true);
        $sorted = $activeDates;
        sort($sorted);

        $best = 0;
        $run = 0;
        $prev = null;

        foreach ($sorted as $date) {
            if ($prev !== null && strtotime($date) === strtotime($prev.' +1 day')) {
                $run++;
            } else {
                $run = 1;
            }

            $best = max($best, $run);
            $prev = $date;
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $current = 0;
        $cursor = isset($dateSet[$today]) ? $today : (isset($dateSet[$yesterday]) ? $yesterday : null);

        while ($cursor !== null && isset($dateSet[$cursor])) {
            $current++;
            $cursor = date('Y-m-d', strtotime($cursor.' -1 day'));
        }

        return [
            'current' => $current,
            'best' => max($best, $current),
        ];
    }

    /**
     * @param  list<string>  $activeDates
     * @return array<string, mixed>
     */
    public function buildWeeklyProgress(array $activeDates, int $userId = 0, ?Course $course = null): array
    {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime($monday.' +6 days'));
        $dayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $today = date('Y-m-d');

        if ($userId > 0 && $course === null) {
            $course = CourseMember::activeCourseFor($userId);
        }

        $courseWeek = $course !== null
            ? $this->buildCourseWeeklyProgress($course, $userId, $monday, $sunday, $dayLabels, $today)
            : null;

        if ($courseWeek !== null) {
            return $courseWeek;
        }

        $dateSet = array_fill_keys($activeDates, true);
        $days = [];
        $active = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($monday.' +'.$i.' days'));
            $isActive = isset($dateSet[$date]);

            if ($isActive) {
                $active++;
            }

            $days[] = [
                'date' => $date,
                'label' => $dayLabels[$i],
                'is_active' => $isActive,
                'is_today' => $date === $today,
                'is_scheduled' => false,
                'time_label' => '',
            ];
        }

        $goal = self::WEEKLY_GOAL_DAYS;

        return [
            'monday' => $monday,
            'active_days' => $active,
            'goal_days' => $goal,
            'progress_percent' => (int) round(min(100, ($active / max(1, $goal)) * 100)),
            'days' => $days,
            'schedule_mode' => 'activity',
        ];
    }

    /**
     * @param  list<string>  $dayLabels
     * @return array<string, mixed>|null
     */
    private function buildCourseWeeklyProgress(
        Course $course,
        int $userId,
        string $monday,
        string $sunday,
        array $dayLabels,
        string $today
    ): ?array {
        $courseId = (int) $course->id;

        if ($courseId <= 0 || $userId <= 0) {
            return null;
        }

        $sessionsByDate = [];

        foreach ($this->catalog->forStudentCourse($courseId, $userId) as $session) {
            $date = trim((string) ($session['session_date'] ?? ''));
            $start = trim((string) ($session['start_time'] ?? ''));

            if ($date === '' || $start === '') {
                continue;
            }

            if ($date >= $monday && $date <= $sunday) {
                $sessionsByDate[$date][] = $session;
            }
        }

        $patternWeekdays = $this->schedule->decodeWeekdays($course->weekly_schedule_days);
        $defaultStart = $this->schedule->formatTimeForInput($course->default_start_time);
        $defaultEnd = $this->schedule->formatTimeForInput($course->default_end_time);
        $defaultTime = $defaultStart !== '' && $defaultEnd !== ''
            ? $defaultStart.'–'.$defaultEnd
            : $defaultStart;

        $days = [];
        $scheduledCount = 0;
        $attendedCount = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($monday.' +'.$i.' days'));
            $daySessions = $sessionsByDate[$date] ?? [];
            $isScheduled = $daySessions !== [];

            if (! $isScheduled && $patternWeekdays !== []) {
                $isScheduled = in_array((int) date('w', strtotime($date)), $patternWeekdays, true);
            }

            $hasAttended = false;
            $timeLabels = [];

            foreach ($daySessions as $session) {
                $timeLabels[] = $this->formatSessionTimeLabel($session);

                if (! empty($session['has_attended'])) {
                    $hasAttended = true;
                }
            }

            if ($daySessions === [] && $isScheduled && $defaultTime !== '') {
                $timeLabels[] = $defaultTime;
            }

            if ($isScheduled) {
                $scheduledCount++;

                if ($hasAttended) {
                    $attendedCount++;
                }
            }

            $days[] = [
                'date' => $date,
                'label' => $dayLabels[$i],
                'is_active' => $hasAttended,
                'is_today' => $date === $today,
                'is_scheduled' => $isScheduled,
                'time_label' => implode(', ', array_values(array_filter($timeLabels))),
                'session_count' => count($daySessions),
            ];
        }

        if ($scheduledCount === 0 && $patternWeekdays === []) {
            return null;
        }

        return [
            'monday' => $monday,
            'active_days' => $attendedCount,
            'goal_days' => $scheduledCount,
            'progress_percent' => (int) round(min(100, ($attendedCount / max(1, $scheduledCount)) * 100)),
            'days' => $days,
            'schedule_mode' => 'course',
            'course_title' => trim((string) $course->title),
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function formatSessionTimeLabel(array $session): string
    {
        $start = substr((string) ($session['start_time'] ?? ''), 0, 5);
        $end = substr((string) ($session['end_time'] ?? ''), 0, 5);

        if ($start === '') {
            return '';
        }

        return $end !== '' ? $start.'–'.$end : $start;
    }

    /**
     * @return array{lessons: int, videos: int, mock_exams: int, sessions: int, assessments: int, total: int}
     */
    public function getActivityCounts(int $userId): array
    {
        $empty = [
            'lessons' => 0,
            'videos' => 0,
            'mock_exams' => 0,
            'sessions' => 0,
            'assessments' => 0,
            'total' => 0,
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $monday = date('Y-m-d', strtotime('monday this week'));

        $lessons = Schema::hasTable('lesson_attempts')
            ? (int) DB::table('lesson_attempts')->where('user_id', $userId)->whereDate('created_at', '>=', $monday)->count()
            : 0;
        $videos = Schema::hasTable('video_attempts')
            ? (int) DB::table('video_attempts')->where('user_id', $userId)->whereDate('created_at', '>=', $monday)->count()
            : 0;
        $mockExams = Schema::hasTable('mock_exam_attempts')
            ? (int) DB::table('mock_exam_attempts')->where('user_id', $userId)->whereNotNull('submitted_at')->whereDate('submitted_at', '>=', $monday)->count()
            : 0;

        $sessions = 0;

        if (Schema::hasTable('course_session_quiz_answers')) {
            $sessions = (int) CourseSessionQuizAnswer::query()
                ->where('user_id', $userId)
                ->whereDate('updated_at', '>=', $monday)
                ->selectRaw('COUNT(DISTINCT course_session_id) as cnt')
                ->value('cnt');
        }

        $assessments = Schema::hasTable('course_assessment_submissions')
            ? (int) CourseAssessmentSubmission::query()
                ->where('user_id', $userId)
                ->whereNotNull('submitted_at')
                ->whereDate('submitted_at', '>=', $monday)
                ->count()
            : 0;

        return [
            'lessons' => $lessons,
            'videos' => $videos,
            'mock_exams' => $mockExams,
            'sessions' => $sessions,
            'assessments' => $assessments,
            'total' => $lessons + $videos + $mockExams + $sessions + $assessments,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecentActivities(int $userId, int $limit = 12): array
    {
        if ($userId <= 0 || $limit <= 0) {
            return [];
        }

        $limit = min(30, $limit);
        $parts = [];
        $bindings = [];

        if (Schema::hasTable('course_session_quiz_answers') && Schema::hasTable('course_sessions')) {
            $parts[] = "
                SELECT MAX(a.updated_at) AS activity_at, 'session' AS activity_type,
                       CONCAT('Buổi học: ', COALESCE(NULLIF(cs.title, ''), CONCAT('Buổi #', cs.id))) AS activity_title,
                       NULL AS score_percent,
                       cs.public_code AS public_code,
                       COALESCE(ps.level_id, 0) AS level_id,
                       NULL AS assessment_id
                FROM course_session_quiz_answers a
                INNER JOIN course_sessions cs ON cs.id = a.course_session_id
                LEFT JOIN study_program_sessions ps ON ps.id = cs.program_session_id
                WHERE a.user_id = ?
                GROUP BY a.course_session_id, cs.title, cs.id, cs.public_code, ps.level_id
            ";
            $bindings[] = $userId;
        }

        if (Schema::hasTable('course_assessment_submissions') && Schema::hasTable('course_assessments')) {
            $parts[] = "
                SELECT cas.submitted_at AS activity_at, 'assessment' AS activity_type,
                       CONCAT('Kiểm tra: ', ca.title) AS activity_title,
                       NULL AS score_percent,
                       NULL AS public_code,
                       0 AS level_id,
                       cas.course_assessment_id AS assessment_id
                FROM course_assessment_submissions cas
                INNER JOIN course_assessments ca ON ca.id = cas.course_assessment_id
                WHERE cas.user_id = ? AND cas.submitted_at IS NOT NULL
            ";
            $bindings[] = $userId;
        }

        if ($parts === []) {
            return [];
        }

        $rows = DB::select(
            'SELECT * FROM ('.implode(' UNION ALL ', $parts).') AS activities
             WHERE activity_at IS NOT NULL
             ORDER BY activity_at DESC
             LIMIT '.(int) $limit,
            $bindings
        );

        $typeLabels = [
            'session' => 'Buổi học',
            'assessment' => 'Kiểm tra',
            'lesson' => 'Bài học',
            'video' => 'Video',
            'mock_exam' => 'Thi thử',
        ];
        $typeIcons = [
            'session' => 'fa-chalkboard',
            'assessment' => 'fa-list-check',
            'lesson' => 'fa-book',
            'video' => 'fa-circle-play',
            'mock_exam' => 'fa-certificate',
        ];

        $result = [];
        $fallbackLevel = $this->nav->resolveActiveLevelId($userId);

        foreach ($rows as $row) {
            $type = (string) ($row->activity_type ?? '');
            $url = '';

            if ($type === 'session' && trim((string) ($row->public_code ?? '')) !== '') {
                $url = $this->nav->dashboardSessionUrl(
                    (int) ($row->level_id ?? 0) > 0 ? (int) $row->level_id : $fallbackLevel,
                    (string) $row->public_code
                );
            } elseif ($type === 'assessment' && (int) ($row->assessment_id ?? 0) > 0) {
                $url = route('student.assessment.show', (int) $row->assessment_id);
            }

            $result[] = [
                'type' => $type,
                'type_label' => $typeLabels[$type] ?? 'Hoạt động',
                'icon' => $typeIcons[$type] ?? 'fa-circle-check',
                'title' => (string) ($row->activity_title ?? ''),
                'score' => $row->score_percent !== null ? (int) $row->score_percent : null,
                'at' => (string) ($row->activity_at ?? ''),
                'url' => $url,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEvaluation(?Course $course, int $userId, mixed $thang, mixed $nam): array
    {
        $empty = [
            'monthlyEvaluation' => null,
            'scoreLabels' => CourseMonthlyEvaluation::scoreLabels(),
            'selectedEvalMonth' => date('Y-m'),
            'filledEvalMonths' => [],
            'evalMonthLabel' => '',
            'hasEvalForMonth' => false,
            'showEvaluation' => false,
        ];

        if ($course === null || ! Schema::hasTable('course_monthly_evaluations')) {
            return $empty;
        }

        try {
            $filled = [];

            foreach (CourseMonthlyEvaluation::forStudentInCourse((int) $course->id, $userId) as $row) {
                $period = (string) $row->eval_month;

                if ($row->isFilled() && CourseMonthlyEvaluation::isMonthlyPeriod($period)) {
                    $filled[] = $period;
                }
            }

            $filled = array_values(array_unique(array_filter($filled)));
            $selected = $this->resolveStudentEvalMonth($filled, $thang, $nam);
            $evaluation = CourseMonthlyEvaluation::forStudent((int) $course->id, $userId, $selected);

            return [
                'monthlyEvaluation' => $evaluation,
                'scoreLabels' => CourseMonthlyEvaluation::scoreLabels(),
                'selectedEvalMonth' => $selected,
                'filledEvalMonths' => $filled,
                'evalMonthLabel' => CourseMonthlyEvaluation::formatMonthLabel($selected),
                'hasEvalForMonth' => $evaluation !== null && $evaluation->isFilled(),
                'showEvaluation' => $filled !== [],
            ];
        } catch (\Throwable $e) {
            Log::error('[LearningProgress] eval load: '.$e->getMessage());

            return $empty;
        }
    }

    /**
     * @param  list<string>  $filledMonths
     */
    public function resolveStudentEvalMonth(array $filledMonths, mixed $thang = null, mixed $nam = null): string
    {
        $currentMonth = date('Y-m');
        $thangParam = trim((string) ($thang ?? ''));

        if (preg_match('/^\d{4}-\d{2}$/', $thangParam) === 1 && $thangParam <= $currentMonth) {
            return $thangParam;
        }

        if ($thangParam !== '' || $nam !== null) {
            $month = (int) $thangParam;
            $year = (int) ($nam ?? 0);

            if ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12) {
                $evalMonth = sprintf('%04d-%02d', $year, $month);

                if ($evalMonth <= $currentMonth) {
                    return $evalMonth;
                }
            }
        }

        if ($filledMonths !== []) {
            return $filledMonths[0];
        }

        return $currentMonth;
    }

    /**
     * @return array{lessons_percent: int, videos_percent: int, lessons_done: int, lessons_total: int, videos_done: int, videos_total: int}
     */
    private function libraryProgress(int $userId): array
    {
        $empty = [
            'lessons_percent' => 0,
            'videos_percent' => 0,
            'lessons_done' => 0,
            'lessons_total' => 0,
            'videos_done' => 0,
            'videos_total' => 0,
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $lessonsTotal = 0;
        $lessonsDone = 0;
        $lessonsPercent = 0;

        if (Schema::hasTable('lessons') && Schema::hasTable('lesson_attempts')) {
            $eligible = DB::table('lessons')
                ->where('status', 'active')
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('lesson_questions')
                        ->whereColumn('lesson_questions.lesson_id', 'lessons.id');
                })
                ->pluck('id');

            $lessonsTotal = $eligible->count();

            if ($lessonsTotal > 0) {
                $map = $this->latestProgressMap('lesson_attempts', 'lesson_id', $userId);
                $sum = 0;

                foreach ($eligible as $id) {
                    $pct = (int) ($map[(int) $id] ?? 0);
                    $sum += $pct;

                    if ($pct >= 100) {
                        $lessonsDone++;
                    }
                }

                $lessonsPercent = (int) round($sum / $lessonsTotal);
            }
        }

        $videosTotal = 0;
        $videosDone = 0;
        $videosPercent = 0;

        if (Schema::hasTable('videos') && Schema::hasTable('video_attempts')) {
            $eligible = DB::table('videos')->pluck('id');
            $videosTotal = $eligible->count();

            if ($videosTotal > 0) {
                $map = $this->latestProgressMap('video_attempts', 'video_id', $userId);
                $sum = 0;

                foreach ($eligible as $id) {
                    $pct = (int) ($map[(int) $id] ?? 0);
                    $sum += $pct;

                    if ($pct >= 100) {
                        $videosDone++;
                    }
                }

                $videosPercent = (int) round($sum / $videosTotal);
            }
        }

        return [
            'lessons_percent' => min(100, max(0, $lessonsPercent)),
            'videos_percent' => min(100, max(0, $videosPercent)),
            'lessons_done' => $lessonsDone,
            'lessons_total' => $lessonsTotal,
            'videos_done' => $videosDone,
            'videos_total' => $videosTotal,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function latestProgressMap(string $table, string $foreignKey, int $userId): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'progress')) {
            return [];
        }

        $rows = DB::table($table)
            ->select($foreignKey, 'progress')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $id = (int) ($row->{$foreignKey} ?? 0);

            if ($id > 0 && ! isset($map[$id])) {
                $map[$id] = (int) ($row->progress ?? 0);
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        $weekly = $this->buildWeeklyProgress([]);

        return [
            'progress' => [
                'overall' => [
                    'percent' => 0,
                    'sessions_percent' => 0,
                    'assessments_percent' => 0,
                    'lessons_percent' => 0,
                    'videos_percent' => 0,
                    'sessions_done' => 0,
                    'sessions_total' => 0,
                    'assessments_done' => 0,
                    'assessments_total' => 0,
                    'lessons_done' => 0,
                    'lessons_total' => 0,
                    'videos_done' => 0,
                    'videos_total' => 0,
                ],
                'streak' => ['current' => 0, 'best' => 0],
                'weekly' => $weekly,
                'weekly_progress' => 0,
                'activity_counts' => [
                    'lessons' => 0,
                    'videos' => 0,
                    'mock_exams' => 0,
                    'sessions' => 0,
                    'assessments' => 0,
                    'total' => 0,
                ],
                'recent' => [],
            ],
            'course' => null,
            'monthlyEvaluation' => null,
            'scoreLabels' => CourseMonthlyEvaluation::scoreLabels(),
            'selectedEvalMonth' => date('Y-m'),
            'filledEvalMonths' => [],
            'evalMonthLabel' => '',
            'hasEvalForMonth' => false,
            'showEvaluation' => false,
        ];
    }
}
