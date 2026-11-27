<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSession;
use App\Models\StudyProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherDashboardService
{
    public function __construct(
        private OnlineRoomService $rooms,
        private ClassroomService $classroom,
        private StudentSessionCatalogService $catalog,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(int $teacherId): array
    {
        $courses = Course::activeForTeacher($teacherId);
        $courses->load([
            'teacher',
            'room',
            'program.levels' => static fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);
        $courses->loadCount([
            'activeMembers as member_count',
            'sessions as session_count',
        ]);

        $courseIds = $courses->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $courseById = $courses->keyBy('id');
        $programsTotal = StudyProgram::query()->where('teacher_id', $teacherId)->count();

        // Single batched load of all non-cancelled sessions in program order for teacher's active courses
        $sessionsByCourse = $this->loadCourseSessionsGrouped($courseIds, $courseById);
        $pendingGradingByCourse = $this->countPendingGradingByCourse($courseIds);

        $sessionOrdinals = [];
        $liveSessions = [];
        $upcomingSessions = [];
        $courseCards = [];
        $studentsTotal = 0;

        foreach ($courses as $course) {
            $courseId = (int) $course->id;
            $memberCount = (int) ($course->member_count ?? 0);
            $sessionCount = (int) ($course->session_count ?? 0);
            $studentsTotal += $memberCount;

            if ($sessionCount > 0) {
                try {
                    $room = $this->rooms->ensure($course);
                    if ($room !== null) {
                        $course->setRelation('room', $room);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            /** @var Collection<int, CourseSession> $courseSessions */
            $courseSessions = $sessionsByCourse->get($courseId, collect());
            $currentSessionNo = 0;
            $endedCount = 0;
            $courseLevelTitle = '';

            foreach ($courseSessions->values() as $idx => $session) {
                $ordinal = $idx + 1;
                $sessionOrdinals[(int) $session->id] = $ordinal;
                $phase = $session->livePhase();

                if ($phase === 'ended') {
                    $endedCount++;
                } elseif ($currentSessionNo === 0 && ($phase === 'live' || $phase === 'upcoming')) {
                    $currentSessionNo = $ordinal;
                    $courseLevelTitle = trim((string) ($session->programSession?->level?->title ?? ''));
                }

                if ($phase === 'live') {
                    $liveSessions[] = $this->sessionCardBase(
                        $session,
                        $ordinal,
                        $memberCount,
                        (int) ($pendingGradingByCourse[$courseId] ?? 0)
                    );
                } elseif ($phase === 'upcoming' && $session->isTimed()) {
                    $upcomingSessions[] = $this->sessionCardBase(
                        $session,
                        $ordinal,
                        $memberCount,
                        (int) ($pendingGradingByCourse[$courseId] ?? 0)
                    );
                }
            }

            if ($currentSessionNo === 0 && $sessionCount > 0) {
                $currentSessionNo = min($sessionCount, max(1, $endedCount));
            }

            if ($courseLevelTitle === '') {
                $courseLevelTitle = trim((string) ($course->program?->levels?->first()?->title ?? ''));
            }

            $courseCards[] = [
                'id' => $courseId,
                'title' => (string) $course->title,
                'status' => (string) $course->status,
                'level_title' => $courseLevelTitle,
                'member_count' => $memberCount,
                'session_count' => $sessionCount,
                'current_session_number' => $currentSessionNo,
                'pending_grading_count' => (int) ($pendingGradingByCourse[$courseId] ?? 0),
                'manage_url' => route('teacher.course.show', ['id' => $courseId]),
            ];
        }

        usort($liveSessions, static fn (array $a, array $b): int => strcmp((string) ($a['start_at'] ?? ''), (string) ($b['start_at'] ?? '')));
        usort($upcomingSessions, static fn (array $a, array $b): int => strcmp((string) ($a['start_at'] ?? ''), (string) ($b['start_at'] ?? '')));
        $upcomingSessions = array_slice($upcomingSessions, 0, 12);

        $joinableSessions = array_map(
            fn (array $session): array => $this->enrichSessionCard($session, $courseById),
            $liveSessions
        );
        $upcomingSessions = array_map(
            fn (array $session): array => $this->enrichSessionCard($session, $courseById),
            $upcomingSessions
        );

        $evalMonth = CourseMonthlyEvaluation::currentWeekKey();
        $pendingEvals = $this->countPendingEvaluations($courses, $evalMonth);

        return [
            'stats' => [
                'courses_total' => count($courseCards),
                'courses_active' => count($courseCards),
                'students_total' => $studentsTotal,
                'programs_total' => $programsTotal,
                'sessions_this_week' => CourseSession::countScheduledThisWeekForTeacher($teacherId),
                'upcoming_sessions' => count($upcomingSessions),
                'live_sessions' => count($liveSessions),
                'pending_evaluations' => $pendingEvals,
            ],
            'courses' => $courseCards,
            'joinableSessions' => $joinableSessions,
            'featureLiveSession' => $joinableSessions[0] ?? null,
            'nearestUpcomingSession' => $upcomingSessions[0] ?? null,
            'otherLiveSessions' => array_slice($joinableSessions, 1),
            'upcomingSessions' => $upcomingSessions,
            'evalMonth' => $evalMonth,
            'evalMonthLabel' => CourseMonthlyEvaluation::formatPeriodLabel($evalMonth),
            'phaseLabels' => CourseSession::phaseLabels(),
        ];
    }

    /**
     * @param  list<int>  $courseIds
     * @param  Collection<int, Course>  $courseById
     * @return Collection<int, Collection<int, CourseSession>>
     */
    private function loadCourseSessionsGrouped(array $courseIds, Collection $courseById): Collection
    {
        if ($courseIds === []) {
            return collect();
        }

        $sessions = CourseSession::query()
            ->whereIn('course_sessions.course_id', $courseIds)
            ->where('course_sessions.status', '!=', 'cancelled')
            ->with(['programSession.level'])
            ->inProgramOrder()
            ->get();

        foreach ($sessions as $session) {
            $course = $courseById->get((int) $session->course_id);
            if ($course !== null) {
                $session->setRelation('course', $course);
            }
        }

        return $sessions->groupBy('course_id');
    }

    /**
     * Count ungraded subjective homework answers per course (safe for MySQL strict mode).
     *
     * @param  list<int>  $courseIds
     * @return array<int, int>
     */
    private function countPendingGradingByCourse(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $counts = [];

        try {
            $quizRows = DB::table('course_session_quiz_answers as a')
                ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
                ->join('course_sessions as s', 's.id', '=', 'a.course_session_id')
                ->whereIn('s.course_id', $courseIds)
                ->whereIn('q.question_group', ['pre', 'post_practice'])
                ->where(function ($inner): void {
                    $inner->whereIn('q.question_type', ['fill', 'listening', 'word_translate', 'phat_am'])
                        ->orWhere('q.options_json', 'like', '%"phat_am"%')
                        ->orWhere('q.options_json', 'like', '%"word_translate"%')
                        ->orWhere('q.options_json', 'like', '%"listening"%')
                        ->orWhere('a.selected_answer', 'like', '%"recording":%');
                })
                ->whereNotNull('a.selected_answer')
                ->whereRaw('TRIM(a.selected_answer) != ?', [''])
                ->whereNull('a.teacher_graded_at')
                ->groupBy('s.course_id')
                ->selectRaw('s.course_id, COUNT(*) as total')
                ->get();

            foreach ($quizRows as $row) {
                $cid = (int) $row->course_id;
                $counts[$cid] = ($counts[$cid] ?? 0) + (int) $row->total;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if (Schema::hasTable('course_knowledge_review_answers') && Schema::hasColumn('course_knowledge_review_answers', 'teacher_graded_at')) {
                $krRows = DB::table('course_knowledge_review_answers as a')
                    ->join('course_knowledge_review_questions as q', 'q.id', '=', 'a.question_id')
                    ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'a.course_knowledge_review_id')
                    ->whereIn('kr.course_id', $courseIds)
                    ->where(function ($inner): void {
                        $inner->whereIn('q.question_type', ['fill', 'essay', 'listening', 'word_translate', 'phat_am'])
                            ->orWhere('q.options_json', 'like', '%"phat_am"%')
                            ->orWhere('q.options_json', 'like', '%"word_translate"%')
                            ->orWhere('q.options_json', 'like', '%"listening"%')
                            ->orWhere('a.selected_answer', 'like', '%"recording":%');
                    })
                    ->whereNotNull('a.selected_answer')
                    ->whereRaw('TRIM(a.selected_answer) != ?', [''])
                    ->whereNull('a.teacher_graded_at')
                    ->groupBy('kr.course_id')
                    ->selectRaw('kr.course_id, COUNT(*) as total')
                    ->get();

                foreach ($krRows as $row) {
                    $cid = (int) $row->course_id;
                    $counts[$cid] = ($counts[$cid] ?? 0) + (int) $row->total;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionCardBase(CourseSession $session, int $ordinal, int $memberCount, int $pendingGradingCount): array
    {
        $course = $session->course;
        $display = $session->studentDisplay();
        $row = $this->catalog->toStudentRow($session, 0);
        $rawTitle = trim((string) ($session->title ?: 'Buổi học'));
        $sessionNumber = $ordinal;
        $lessonTitle = $rawTitle;

        if (preg_match('/^Buổi\s+(\d+)\s*[:\-·–]\s*(.+)$/u', $rawTitle, $matches) === 1) {
            $sessionNumber = (int) $matches[1];
            $lessonTitle = trim($matches[2]);
        }

        $levelTitle = trim((string) ($session->programSession?->level?->title ?? ''));
        if ($levelTitle === '') {
            $levelTitle = trim((string) ($course?->program?->levels?->first()?->title ?? ''));
        }

        $phase = $session->livePhase();
        $isOngoing = false;
        $startsInMinutes = null;

        if ($session->isTimed()) {
            $window = $session->window();
            $now = time();
            $isOngoing = $phase === 'live' && $now >= $window['start'] && $now <= $window['end'];
            if ($phase === 'live' && $now < $window['start']) {
                $startsInMinutes = max(1, (int) ceil(($window['start'] - $now) / 60));
            }
        }

        return [
            'id' => (int) $session->id,
            'course_id' => (int) $session->course_id,
            'public_code' => (string) $session->public_code,
            'title' => $rawTitle,
            'lesson_title' => $lessonTitle,
            'session_number' => $sessionNumber,
            'level_title' => $levelTitle,
            'course_title' => (string) ($course?->title ?? ''),
            'member_count' => $memberCount,
            'pending_grading_count' => $pendingGradingCount,
            'phase' => $phase,
            'is_joinable' => $session->isJoinable(),
            'is_ongoing' => $isOngoing,
            'starts_in_minutes' => $startsInMinutes,
            'display' => $display,
            'start_at' => $session->dateForInput().' '.$session->timeForInput($session->start_time),
            'waiting_teacher' => ! empty($row['waiting_teacher']),
            'teacher_in_room' => ! empty($row['teacher_in_room']),
            'description' => (string) ($session->description ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  Collection<int, Course>  $courseById
     * @return array<string, mixed>
     */
    private function enrichSessionCard(array $session, Collection $courseById): array
    {
        $courseId = (int) ($session['course_id'] ?? 0);
        $session['manage_url'] = $courseId > 0
            ? route('teacher.course.show', ['id' => $courseId]).'?buoi='.(int) ($session['id'] ?? 0)
            : route('dashboard');
        $session['grading_url'] = $courseId > 0
            ? route('teacher.grading', ['khoa' => $courseId])
            : route('teacher.grading');
        $session['room_lobby_url'] = null;
        $session['room_id'] = 0;

        if ($courseId > 0) {
            /** @var Course|null $course */
            $course = $courseById->get($courseId) ?? Course::query()->find($courseId);

            if ($course !== null) {
                try {
                    $room = $course->relationLoaded('room') && $course->room !== null
                        ? $course->room
                        : $this->rooms->ensure($course);

                    if ($room !== null) {
                        $session['room_lobby_url'] = $this->classroom->lobbyUrl((int) $room->id);
                        $session['room_id'] = (int) $room->id;
                        $session['room_code'] = (string) $room->room_code;
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return $session;
    }

    public function pendingEvaluationCount(int $teacherId): int
    {
        if ($teacherId <= 0) {
            return 0;
        }

        try {
            return $this->countPendingEvaluations(
                Course::activeForTeacher($teacherId),
                CourseMonthlyEvaluation::currentWeekKey()
            );
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    private function countPendingEvaluations($courses, string $evalWeek): int
    {
        if (! Schema::hasTable('course_monthly_evaluations')) {
            return 0;
        }

        $pending = 0;

        foreach ($courses as $course) {
            $courseId = (int) $course->id;
            $evalMap = CourseMonthlyEvaluation::mapForCoursePeriod($courseId, $evalWeek);

            foreach (CourseMember::activeMembersForCourse($courseId) as $member) {
                $eval = $evalMap[(int) $member->user_id] ?? null;

                if ($eval === null || ! $eval->isFilled()) {
                    $pending++;
                }
            }
        }

        return $pending;
    }
}
