<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use App\Models\CourseSessionQuestion;
use App\Models\CourseSessionQuizAnswer;
use App\Support\StudentQuizGrader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourseAdminExtrasService
{
    public function __construct(
        private StudentQuizGrader $grader,
        private AssessmentSubmissionService $scoring,
        private AssessmentDraftService $drafts,
        private AssessmentFlowService $flow,
        private StudentAssessmentService $assessments,
    ) {
    }

    /**
     * @return list<string>
     */
    public function extraTabs(): array
    {
        return ['evaluations', 'practice', 'assessments', 'leave-requests', 'homework'];
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluations(Course $course, string $evalWeek): array
    {
        $evalWeek = CourseMonthlyEvaluation::normalizePeriod($evalWeek);
        $members = CourseMember::activeMembersForCourse((int) $course->id);
        $evalMap = CourseMonthlyEvaluation::mapForCoursePeriod((int) $course->id, $evalWeek);
        $attendanceMap = CourseSessionAttendance::summaryMapForCourseWeek((int) $course->id, $evalWeek);
        $weekSessionCount = CourseSession::scheduledForWeek((int) $course->id, $evalWeek)->count();
        $evaluated = 0;

        foreach ($members as $member) {
            $eval = $evalMap[(int) $member->user_id] ?? null;

            if ($eval !== null && $eval->isFilled()) {
                $evaluated++;
            }
        }

        return [
            'evalWeek' => $evalWeek,
            'weekLabel' => CourseMonthlyEvaluation::formatPeriodLabel($evalWeek),
            'weekOptions' => CourseMonthlyEvaluation::weekOptions(20),
            'members' => $members,
            'evalMap' => $evalMap,
            'attendanceMap' => $attendanceMap,
            'weekSessionCount' => $weekSessionCount,
            'evaluatedCount' => $evaluated,
            'scoreLabels' => CourseMonthlyEvaluation::scoreLabels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function practiceStats(Course $course, int $selectedSessionId): array
    {
        $sessionOptions = [];

        foreach (CourseSession::query()->where('course_id', $course->id)->where('status', '!=', 'cancelled')->inProgramOrder()->get() as $session) {
            $questions = $this->grader->readyQuestions(CourseSessionQuestion::forSessionGroup((int) $session->id, 'post_practice'));

            if ($questions === []) {
                continue;
            }

            $sessionOptions[] = [
                'id' => (int) $session->id,
                'title' => (string) ($session->title ?: 'Buổi học'),
                'phase' => $session->livePhase(),
                'question_count' => count($questions),
                'session_date' => $session->dateForInput(),
            ];
        }

        if ($selectedSessionId <= 0 && $sessionOptions !== []) {
            foreach (array_reverse($sessionOptions) as $option) {
                if (($option['phase'] ?? '') === 'ended') {
                    $selectedSessionId = (int) $option['id'];
                    break;
                }
            }

            if ($selectedSessionId <= 0) {
                $selectedSessionId = (int) ($sessionOptions[array_key_last($sessionOptions)]['id'] ?? 0);
            }
        }

        $selectedSession = null;
        $questions = [];
        $submissionMap = [];

        foreach ($sessionOptions as $option) {
            if ((int) $option['id'] === $selectedSessionId) {
                $selectedSession = $option;
                $questions = $this->grader->readyQuestions(CourseSessionQuestion::forSessionGroup($selectedSessionId, 'post_practice'));
                $submissionMap = CourseSessionQuizAnswer::submissionSummariesForSession($selectedSessionId, $questions);
                break;
            }
        }

        $members = CourseMember::activeMembersForCourse((int) $course->id);
        $doneCount = 0;

        foreach ($members as $member) {
            if (! empty($submissionMap[(int) $member->user_id]['submitted'])) {
                $doneCount++;
            }
        }

        return [
            'sessionOptions' => $sessionOptions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $selectedSessionId,
            'submissionMap' => $submissionMap,
            'members' => $members,
            'doneCount' => $doneCount,
            'pendingCount' => $members->count() - $doneCount,
            'phaseLabels' => CourseSession::phaseLabels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assessmentResults(Course $course, int $selectedAssessmentId): array
    {
        try {
            $this->flow->finalizeExpiredAttemptsForCourse((int) $course->id);
        } catch (\Throwable $e) {
            report($e);
        }

        $assessmentOptions = [];

        foreach ($course->assessments()->get() as $assessment) {
            $questions = $this->assessments->readyQuestions((int) $assessment->id);

            if ($questions === []) {
                continue;
            }

            $assessmentOptions[] = [
                'id' => (int) $assessment->id,
                'title' => (string) ($assessment->title ?: 'Bài kiểm tra'),
                'phase' => $assessment->livePhase(),
                'question_count' => count($questions),
            ];
        }

        if ($selectedAssessmentId <= 0 && $assessmentOptions !== []) {
            $selectedAssessmentId = (int) ($assessmentOptions[0]['id'] ?? 0);
        }

        $selectedAssessment = null;
        $submissionMap = [];
        $draftUserIds = [];

        foreach ($assessmentOptions as $option) {
            if ((int) $option['id'] === $selectedAssessmentId) {
                $selectedAssessment = $option;
                $submissionMap = $this->scoring->submissionSummariesForAssessment($selectedAssessmentId);

                if (Schema::hasTable('course_assessment_submissions')) {
                    $submissionMap = CourseAssessmentSubmission::mergeIntoSummaries($selectedAssessmentId, $submissionMap);
                }

                $draftUserIds = $this->drafts->userIdsForAssessment($selectedAssessmentId);
                break;
            }
        }

        $members = CourseMember::activeMembersForCourse((int) $course->id);
        $doneCount = 0;

        foreach ($members as $member) {
            if (! empty($submissionMap[(int) $member->user_id]['submitted'])) {
                $doneCount++;
            }
        }

        return [
            'assessmentOptions' => $assessmentOptions,
            'selectedAssessment' => $selectedAssessment,
            'selectedAssessmentId' => $selectedAssessmentId,
            'submissionMap' => $submissionMap,
            'draftUserIds' => array_fill_keys($draftUserIds, true),
            'members' => $members,
            'doneCount' => $doneCount,
            'pendingCount' => $members->count() - $doneCount,
            'phaseLabels' => CourseAssessment::studentPhaseLabels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leaveRequests(Course $course): array
    {
        if (! Schema::hasTable('course_leave_requests')) {
            return ['ready' => false, 'rows' => [], 'statusLabels' => []];
        }

        $rows = DB::table('course_leave_requests as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('course_sessions as s', 's.id', '=', 'r.course_session_id')
            ->where('r.course_id', $course->id)
            ->orderByDesc('r.created_at')
            ->limit(100)
            ->get([
                'r.id',
                'r.leave_date',
                'r.reason',
                'r.status',
                'r.teacher_note',
                'r.created_at',
                'u.full_name',
                'u.username',
                's.title as session_title',
            ]);

        return [
            'ready' => true,
            'rows' => $rows,
            'statusLabels' => [
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'rejected' => 'Từ chối',
                'cancelled' => 'Đã hủy',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function homework(Course $course): array
    {
        if (! Schema::hasTable('homework_assignments')) {
            return ['ready' => false, 'rows' => [], 'statusLabels' => []];
        }

        $rows = DB::table('homework_assignments')
            ->where('course_id', $course->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return [
            'ready' => true,
            'rows' => $rows,
            'statusLabels' => [
                'draft' => 'Nháp',
                'open' => 'Đang mở',
                'closed' => 'Đã đóng',
            ],
        ];
    }
}
