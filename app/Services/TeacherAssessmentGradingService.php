<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentAnswerDraft;
use App\Models\CourseAssessmentRetake;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseMember;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TeacherAssessmentGradingService
{
    public function __construct(
        private AssessmentSubmissionService $scoring,
        private StudentAssessmentService $assessments,
        private AssessmentDraftService $drafts,
        private AssessmentFlowService $flow,
        private TeacherAssessmentGradeService $gradeService,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(int $teacherId, int $courseId, int $assessmentId, int $studentId): ?array
    {
        if ($teacherId <= 0) {
            return null;
        }

        $courses = Course::activeForTeacher($teacherId);

        if ($courses->isEmpty()) {
            return null;
        }

        if ($courseId <= 0 || ! $courses->contains('id', $courseId)) {
            $courseId = (int) $courses->first()?->id;
        }

        $course = $courses->firstWhere('id', $courseId);

        if ($course === null) {
            return null;
        }

        if (Schema::hasTable('course_assessment_submissions')) {
            CourseAssessmentSubmission::reconcileForCourse($courseId);
        }

        try {
            $this->flow->finalizeExpiredAttemptsForCourse($courseId);
        } catch (\Throwable $e) {
            Log::error('[Assessment] finalizeExpiredAttemptsForCourse failed: '.$e->getMessage(), [
                'course_id' => $courseId,
            ]);
        }

        $phaseLabels = CourseAssessment::studentPhaseLabels();
        $assessmentOptions = [];

        foreach ($this->assessmentsForCourse($courseId) as $assessment) {
            $id = (int) $assessment->id;
            $questions = $id > 0 ? $this->assessments->readyQuestions($id) : [];

            if ($id <= 0 || $questions === []) {
                continue;
            }

            $submissionMap = $this->scoring->submissionSummariesForAssessment($id);

            if (Schema::hasTable('course_assessment_submissions')) {
                $submissionMap = CourseAssessmentSubmission::mergeIntoSummaries($id, $submissionMap);
            }

            $pendingGrade = 0;
            $submittedCount = 0;

            foreach ($submissionMap as $summary) {
                if (empty($summary['submitted'])) {
                    continue;
                }

                ++$submittedCount;

                $pendingManual = (int) ($summary['pending_manual'] ?? 0);
                $isGraded = ! empty($summary['is_graded']);

                if ($pendingManual > 0 || ! $isGraded) {
                    ++$pendingGrade;
                }
            }

            $row = $assessment->toStudentArray();
            $phase = $assessment->timePhase();

            $assessmentOptions[] = array_merge($row, [
                'phase' => $phase,
                'question_count' => count($questions),
                'submitted_count' => $submittedCount,
                'pending_grade' => $pendingGrade,
                'display' => $this->assessments->studentDisplay(array_merge($row, ['phase' => $phase])),
            ]);
        }

        $courseSummaries = $this->buildCourseSummaries($courses);

        if ($assessmentOptions === []) {
            return [
                'courses' => $courses,
                'courseSummaries' => $courseSummaries,
                'course' => $course,
                'courseId' => $courseId,
                'assessmentOptions' => [],
                'selectedAssessment' => null,
                'assessmentId' => 0,
                'studentId' => 0,
                'studentItems' => [],
                'selectedDetail' => null,
                'phaseLabels' => $phaseLabels,
                'pendingTotal' => $this->pendingTotal($courseSummaries),
            ];
        }

        $assessmentIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $assessmentOptions);

        if ($assessmentId <= 0 || ! in_array($assessmentId, $assessmentIds, true)) {
            $assessmentId = $this->pickDefaultAssessmentId($assessmentOptions);
        }

        $selectedAssessment = null;

        foreach ($assessmentOptions as $option) {
            if ((int) ($option['id'] ?? 0) === $assessmentId) {
                $selectedAssessment = $option;
                break;
            }
        }

        $submissionMap = $this->scoring->submissionSummariesForAssessment($assessmentId);

        if (Schema::hasTable('course_assessment_submissions')) {
            $submissionMap = CourseAssessmentSubmission::mergeIntoSummaries($assessmentId, $submissionMap);
        }

        $draftUserIds = array_fill_keys($this->drafts->userIdsForAssessment($assessmentId), true);
        $draftProgress = $this->drafts->getProgressByUser($assessmentId);
        $retakeUserIds = array_fill_keys(
            CourseAssessmentRetake::query()
                ->where('course_assessment_id', $assessmentId)
                ->pluck('user_id')
                ->map(static fn ($id): int => (int) $id)
                ->all(),
            true
        );
        $questionTotal = (int) ($selectedAssessment['question_count'] ?? 0);
        $studentItems = [];

        foreach (CourseMember::activeMembersForCourse($courseId) as $member) {
            $user = $member->user;
            $uid = (int) $member->user_id;
            $summary = $submissionMap[$uid] ?? [];
            $hasSubmitted = ! empty($summary['submitted']);
            $allowRetake = isset($retakeUserIds[$uid]);
            $isRetaking = $allowRetake && $hasSubmitted;
            $isDraft = ! $hasSubmitted && isset($draftUserIds[$uid]);
            $pendingManual = (int) ($summary['pending_manual'] ?? 0);
            $isGraded = ! empty($summary['is_graded']) && ! $allowRetake;
            $displayName = trim((string) ($user?->name ?? ''));

            if ($displayName === '') {
                $displayName = 'Học viên';
            }

            if (! $hasSubmitted && ! $isDraft) {
                continue;
            }

            $draftUpdated = (string) ($draftProgress[$uid]['updated_at'] ?? '');
            $showDraftMeta = $isDraft || ($isRetaking && isset($draftUserIds[$uid]));

            $studentItems[] = [
                'user_id' => $uid,
                'student_name' => $displayName,
                'student_avatar' => $user?->avatarUrl(),
                'student_initial' => \App\Models\User::initialsFrom($user?->name ?: $displayName),
                'has_submitted' => $hasSubmitted,
                'is_draft' => $isDraft,
                'is_retaking' => $isRetaking,
                'draft_answered' => $showDraftMeta ? (int) ($draftProgress[$uid]['answered'] ?? 0) : 0,
                'draft_total' => $showDraftMeta ? $questionTotal : 0,
                'pending_manual' => ($isDraft || $isRetaking) ? 0 : $pendingManual,
                'is_graded' => ($isDraft || $isRetaking) ? false : $isGraded,
                'needs_grade' => $isDraft ? false : ($isRetaking || $pendingManual > 0 || ! $isGraded),
                'submitted_at' => $showDraftMeta && $draftUpdated !== ''
                    ? $draftUpdated
                    : (string) ($summary['submitted_at'] ?? ''),
                'score' => ($isDraft || $isRetaking) ? 0 : (int) ($summary['score'] ?? 0),
                'points_earned' => ($isDraft || $isRetaking) ? 0.0 : (float) ($summary['points_earned'] ?? 0),
                'points_possible' => ($isDraft || $isRetaking) ? 0.0 : (float) ($summary['points_possible'] ?? 0),
            ];
        }

        usort($studentItems, static function (array $a, array $b): int {
            $rank = static function (array $item): int {
                if (! empty($item['is_retaking'])) {
                    return 0;
                }

                if (! empty($item['needs_grade'])) {
                    return 1;
                }

                if (! empty($item['is_draft'])) {
                    return 2;
                }

                return 3;
            };

            $rankDiff = $rank($a) <=> $rank($b);

            if ($rankDiff !== 0) {
                return $rankDiff;
            }

            return strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
        });

        $studentIds = array_map(static fn (array $row): int => (int) ($row['user_id'] ?? 0), $studentItems);

        if ($studentId <= 0 || ! in_array($studentId, $studentIds, true)) {
            $studentId = $this->pickDefaultStudentId($studentItems);
        }

        $selectedDetail = null;

        if ($studentId > 0) {
            $detail = $this->gradeService->buildStudentDetail($courseId, $assessmentId, $studentId);

            if ($detail !== null) {
                $selectedDetail = array_merge($detail, [
                    'gradeUrl' => route('teacher.assessments.grade'),
                    'publishUrl' => route('teacher.assessments.publish'),
                    'retakeGrantUrl' => route('teacher.assessments.retake.grant'),
                    'retakeRevokeUrl' => route('teacher.assessments.retake.revoke'),
                ]);
            }
        }

        return [
            'courses' => $courses,
            'courseSummaries' => $courseSummaries,
            'course' => $course,
            'courseId' => $courseId,
            'assessmentOptions' => $assessmentOptions,
            'selectedAssessment' => $selectedAssessment,
            'assessmentId' => $assessmentId,
            'studentId' => $studentId,
            'studentItems' => $studentItems,
            'selectedDetail' => $selectedDetail,
            'phaseLabels' => $phaseLabels,
            'pendingTotal' => $this->pendingTotal($courseSummaries),
        ];
    }

    public function pageUrl(int $courseId = 0, int $assessmentId = 0, int $studentId = 0): string
    {
        return route('student.assessments', array_filter([
            'khoa' => $courseId > 0 ? $courseId : null,
            'bai' => $assessmentId > 0 ? $assessmentId : null,
            'hoc-vien' => $studentId > 0 ? $studentId : null,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    public function withQuestionAnchor(string $url, int $questionId): string
    {
        $url = (string) preg_replace('/#.*$/', '', $url);

        if ($questionId <= 0) {
            return $url;
        }

        return $url.'#cau-'.$questionId;
    }

    public function liveRevision(int $courseId, int $assessmentId, int $studentId = 0): string
    {
        if ($courseId <= 0 || $assessmentId <= 0) {
            return '';
        }

        $parts = [
            (string) CourseAssessmentAnswer::query()
                ->where('course_assessment_id', $assessmentId)
                ->count(),
            (string) CourseAssessmentAnswer::query()
                ->where('course_assessment_id', $assessmentId)
                ->max('updated_at'),
            (string) CourseAssessmentAnswerDraft::query()
                ->where('course_assessment_id', $assessmentId)
                ->count(),
            (string) CourseAssessmentAnswerDraft::query()
                ->where('course_assessment_id', $assessmentId)
                ->max('updated_at'),
            CourseAssessmentRetake::query()
                ->where('course_assessment_id', $assessmentId)
                ->orderBy('user_id')
                ->pluck('user_id')
                ->implode(','),
            (string) CourseAssessmentSubmission::query()
                ->where('course_assessment_id', $assessmentId)
                ->orderBy('user_id')
                ->get(['user_id', 'status', 'submitted_at', 'graded_at'])
                ->toJson(),
            (string) $studentId,
        ];

        if ($studentId > 0) {
            $parts[] = (string) CourseAssessmentAnswer::query()
                ->where('course_assessment_id', $assessmentId)
                ->where('user_id', $studentId)
                ->max('updated_at');
            $parts[] = (string) CourseAssessmentAnswerDraft::query()
                ->where('course_assessment_id', $assessmentId)
                ->where('user_id', $studentId)
                ->max('updated_at');
        }

        return hash('sha256', implode('|', $parts));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, CourseAssessment>
     */
    public function assessmentsForCourse(int $courseId)
    {
        return CourseAssessment::query()
            ->with('slots')
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('sort_order')
            ->orderBy('assessment_date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Course>  $courses
     * @return list<array{course: Course, pending_total: int, assessment_count: int}>
     */
    private function buildCourseSummaries($courses): array
    {
        $summaries = [];

        foreach ($courses as $course) {
            $courseId = (int) $course->id;

            if ($courseId <= 0) {
                continue;
            }

            $pendingTotal = 0;
            $assessmentCount = 0;

            foreach ($this->assessmentsForCourse($courseId) as $assessment) {
                $assessmentId = (int) $assessment->id;

                if ($assessmentId <= 0 || $this->assessments->readyQuestions($assessmentId) === []) {
                    continue;
                }

                ++$assessmentCount;
                $submissionMap = $this->scoring->submissionSummariesForAssessment($assessmentId);

                if (Schema::hasTable('course_assessment_submissions')) {
                    $submissionMap = CourseAssessmentSubmission::mergeIntoSummaries($assessmentId, $submissionMap);
                }

                foreach ($submissionMap as $summary) {
                    if (empty($summary['submitted'])) {
                        continue;
                    }

                    $pendingManual = (int) ($summary['pending_manual'] ?? 0);
                    $isGraded = ! empty($summary['is_graded']);

                    if ($pendingManual > 0 || ! $isGraded) {
                        ++$pendingTotal;
                    }
                }
            }

            $summaries[] = [
                'course' => $course,
                'pending_total' => $pendingTotal,
                'assessment_count' => $assessmentCount,
            ];
        }

        return $summaries;
    }

    /**
     * @param  list<array{pending_total?: int}>  $courseSummaries
     */
    private function pendingTotal(array $courseSummaries): int
    {
        $pendingTotal = 0;

        foreach ($courseSummaries as $summary) {
            $pendingTotal += (int) ($summary['pending_total'] ?? 0);
        }

        return $pendingTotal;
    }

    /**
     * @param  list<array<string, mixed>>  $assessmentOptions
     */
    private function pickDefaultAssessmentId(array $assessmentOptions): int
    {
        foreach ($assessmentOptions as $option) {
            if ((int) ($option['pending_grade'] ?? 0) > 0) {
                return (int) ($option['id'] ?? 0);
            }
        }

        foreach ($assessmentOptions as $option) {
            if ((int) ($option['submitted_count'] ?? 0) > 0) {
                return (int) ($option['id'] ?? 0);
            }
        }

        return (int) ($assessmentOptions[0]['id'] ?? 0);
    }

    /**
     * @param  list<array<string, mixed>>  $studentItems
     */
    private function pickDefaultStudentId(array $studentItems): int
    {
        foreach ($studentItems as $item) {
            if (! empty($item['needs_grade'])) {
                return (int) ($item['user_id'] ?? 0);
            }
        }

        return (int) ($studentItems[0]['user_id'] ?? 0);
    }
}
