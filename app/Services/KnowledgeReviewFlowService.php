<?php

namespace App\Services;

use App\Models\CourseKnowledgeReviewAnswer;
use App\Support\StudentQuizGrader;

class KnowledgeReviewFlowService
{
    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function autoSubmitExpiredIfNeeded(
        array $knowledgeReview,
        int $courseKrId,
        int $userId,
        array $questions,
        bool $hasSubmitted,
        bool $isPracticeMode
    ): bool {
        if (
            $hasSubmitted
            || $isPracticeMode
            || $questions === []
            || $courseKrId <= 0
            || $userId <= 0
            || empty($knowledgeReview['quiz_started'])
            || empty($knowledgeReview['is_expired'])
        ) {
            return false;
        }

        if (CourseKnowledgeReviewAnswer::hasSubmitted($courseKrId, $userId, $questions)) {
            return false;
        }

        try {
            app(StudentQuizGrader::class)->persistKnowledgeReviewAnswers($courseKrId, $userId, [], $questions);
            app(KnowledgeReviewAttemptService::class)->clearAfterSubmit($courseKrId, $userId, false, $knowledgeReview);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}
