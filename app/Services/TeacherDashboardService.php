<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSession;
use App\Models\StudyProgram;
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
        $programsTotal = StudyProgram::query()->where('teacher_id', $teacherId)->count();
        $studentsTotal = 0;
        $courseCards = [];

        foreach ($courses as $course) {
            $memberCount = CourseMember::query()
                ->where('course_id', $course->id)
                ->where('status', CourseMember::STATUS_ACTIVE)
                ->count();
            $sessionCount = CourseSession::query()
                ->where('course_id', $course->id)
                ->where('status', '!=', 'cancelled')
                ->count();
            $studentsTotal += $memberCount;
            $courseCards[] = [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
                'status' => (string) $course->status,
                'member_count' => $memberCount,
                'session_count' => $sessionCount,
                'manage_url' => route('teacher.course.show', ['id' => $course->id]),
            ];

            if ($sessionCount > 0) {
                try {
                    $this->rooms->ensure($course);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $evalMonth = CourseMonthlyEvaluation::currentWeekKey();
        $pendingEvals = $this->countPendingEvaluations($courses, $evalMonth);
        $liveSessions = $this->collectLiveSessions($courses);
        $joinableSessions = array_map(fn (array $session): array => $this->enrichSessionCard($session), $liveSessions);
        $upcomingSessions = [];

        foreach (CourseSession::upcomingForTeacher($teacherId, 12) as $session) {
            $upcomingSessions[] = $this->enrichSessionCard($this->sessionCardBase($session));
        }

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
     * @param  \Illuminate\Support\Collection<int, Course>  $courses
     * @return list<array<string, mixed>>
     */
    private function collectLiveSessions($courses): array
    {
        $live = [];

        foreach ($courses as $course) {
            $sessions = CourseSession::query()
                ->where('course_id', $course->id)
                ->where('status', '!=', 'cancelled')
                ->with(['course.teacher', 'course.room'])
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->orderBy('sort_order')
                ->get();

            foreach ($sessions as $session) {
                if ($session->livePhase() !== 'live') {
                    continue;
                }

                $live[] = $this->sessionCardBase($session);
            }
        }

        usort($live, static function (array $a, array $b): int {
            return strcmp((string) ($a['start_at'] ?? ''), (string) ($b['start_at'] ?? ''));
        });

        return $live;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionCardBase(CourseSession $session): array
    {
        $course = $session->course;
        $display = $session->studentDisplay();
        $row = $this->catalog->toStudentRow($session, 0);

        return [
            'id' => (int) $session->id,
            'course_id' => (int) $session->course_id,
            'public_code' => (string) $session->public_code,
            'title' => (string) ($session->title ?: 'Buổi học'),
            'course_title' => (string) ($course?->title ?? ''),
            'phase' => $session->livePhase(),
            'is_joinable' => $session->isJoinable(),
            'display' => $display,
            'start_at' => $session->dateForInput().' '.$session->timeForInput($session->start_time),
            'waiting_teacher' => ! empty($row['waiting_teacher']),
            'teacher_in_room' => ! empty($row['teacher_in_room']),
            'description' => (string) ($session->description ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function enrichSessionCard(array $session): array
    {
        $courseId = (int) ($session['course_id'] ?? 0);
        $session['manage_url'] = $courseId > 0
            ? route('teacher.course.show', ['id' => $courseId]).'?buoi='.(int) ($session['id'] ?? 0)
            : route('dashboard');
        $session['room_lobby_url'] = null;
        $session['room_id'] = 0;

        if ($courseId > 0) {
            $course = Course::query()->find($courseId);

            if ($course !== null) {
                try {
                    $room = $this->rooms->ensure($course);

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
     * @param  \Illuminate\Support\Collection<int, Course>  $courses
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
