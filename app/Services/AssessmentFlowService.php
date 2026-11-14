<?php

namespace App\Services;

use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentAttempt;
use Illuminate\Support\Facades\Log;

class AssessmentFlowService
{
    public function __construct(
        private AssessmentDraftService $drafts,
        private AssessmentSubmissionService $submissions,
        private AssessmentAttemptService $attempts,
        private StudentAssessmentService $assessments,
    ) {
    }

    /**
     * @param  array<string, mixed>  $assessment
     * @param  list<array<string, mixed>>  $questions
     */
    public function autoSubmitExpiredIfNeeded(
        array $assessment,
        int $assessmentId,
        int $userId,
        array $questions,
        bool $hasSubmitted
    ): bool {
        if (
            $hasSubmitted
            || $questions === []
            || $assessmentId <= 0
            || $userId <= 0
            || empty($assessment['quiz_started'])
            || empty($assessment['is_expired'])
            || empty($assessment['has_time_limit'])
        ) {
            return false;
        }

        return $this->submitTimedOutAttempt($assessmentId, $userId, $questions);
    }

    public function finalizeExpiredAttemptsForCourse(int $courseId): int
    {
        if ($courseId <= 0) {
            return 0;
        }

        $finalized = 0;

        foreach ($this->findExpiredUnsubmittedAttempts($courseId) as $row) {
            $assessmentId = (int) ($row['course_assessment_id'] ?? 0);
            $userId = (int) ($row['user_id'] ?? 0);

            if ($assessmentId <= 0 || $userId <= 0 || CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
                continue;
            }

            $questions = $this->assessments->readyQuestions($assessmentId);

            if ($questions === []) {
                continue;
            }

            if ($this->submitTimedOutAttempt($assessmentId, $userId, $questions)) {
                ++$finalized;
            }
        }

        return $finalized;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function submitTimedOutAttempt(int $assessmentId, int $userId, array $questions): bool
    {
        if (CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId)) {
            return false;
        }

        try {
            $answers = $this->drafts->mergeIncomingAnswers([], $questions, $assessmentId, $userId);
            $this->submissions->saveSubmission($assessmentId, $userId, $answers, $questions, [
                'allowPartial' => true,
                'fillUnanswered' => true,
            ]);
            $this->attempts->clearAfterSubmit($assessmentId, $userId);
        } catch (\Throwable $e) {
            Log::error('[Assessment] submitTimedOutAttempt failed: '.$e->getMessage(), [
                'assessment_id' => $assessmentId,
                'user_id' => $userId,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findExpiredUnsubmittedAttempts(int $courseId): array
    {
        $ids = CourseAssessment::query()->where('course_id', $courseId)->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        return CourseAssessmentAttempt::query()
            ->whereIn('course_assessment_id', $ids)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('course_assessment_answers as ans')
                    ->whereColumn('ans.course_assessment_id', 'course_assessment_attempts.course_assessment_id')
                    ->whereColumn('ans.user_id', 'course_assessment_attempts.user_id');
            })
            ->orderBy('expires_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (CourseAssessmentAttempt $row): array => $row->toArray())
            ->all();
    }
}
