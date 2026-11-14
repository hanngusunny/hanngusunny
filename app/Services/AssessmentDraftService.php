<?php

namespace App\Services;

use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentAnswerDraft;

class AssessmentDraftService
{
    /**
     * @return array<int, string>
     */
    public function getMap(int $assessmentId, int $userId): array
    {
        return CourseAssessmentAnswerDraft::rawMapForUser($assessmentId, $userId);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function saveQuestion(int $assessmentId, int $userId, int $questionId, string $value, array $questions = []): bool
    {
        $value = trim($value);

        if ($assessmentId <= 0 || $userId <= 0 || $questionId <= 0 || $value === '') {
            return false;
        }

        $question = null;

        foreach ($questions as $row) {
            if ((int) ($row['id'] ?? 0) === $questionId) {
                $question = $row;
                break;
            }
        }

        if ($question === null) {
            return false;
        }

        $fields = app(AssessmentSubmissionService::class)->storedAnswerFields($question, $value);
        $fields['course_assessment_id'] = $assessmentId;
        $fields['user_id'] = $userId;

        $existing = CourseAssessmentAnswerDraft::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first();

        if ($existing !== null) {
            $existing->fill($fields)->save();

            return true;
        }

        CourseAssessmentAnswerDraft::query()->create($fields);

        return true;
    }

    public function clear(int $assessmentId, int $userId): void
    {
        CourseAssessmentAnswerDraft::clearForStudent($assessmentId, $userId);
    }

    /**
     * @return list<int>
     */
    public function userIdsForAssessment(int $assessmentId): array
    {
        return CourseAssessmentAnswerDraft::userIdsForAssessment($assessmentId);
    }

    /**
     * @return array<int, array{answered: int, updated_at: string}>
     */
    public function getProgressByUser(int $assessmentId): array
    {
        return CourseAssessmentAnswerDraft::progressByUser($assessmentId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAnswerMap(int $assessmentId, int $userId): array
    {
        return CourseAssessmentAnswerDraft::mapForUser($assessmentId, $userId);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, string>
     */
    public function answersForTakeForm(
        int $assessmentId,
        int $userId,
        array $questions,
        bool $hasSubmitted,
        bool $allowRetake
    ): array {
        if ($assessmentId <= 0 || $userId <= 0 || ($hasSubmitted && ! $allowRetake)) {
            return [];
        }

        if ($allowRetake) {
            return $this->seedForRetake($assessmentId, $userId, $questions);
        }

        return $this->alignMapToQuestions($this->getMap($assessmentId, $userId), $questions);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, string>
     */
    public function seedForRetake(int $assessmentId, int $userId, array $questions): array
    {
        $drafts = $this->alignMapToQuestions($this->getMap($assessmentId, $userId), $questions);
        $submitted = $this->alignMapToQuestions(CourseAssessmentAnswer::rawMapForUser($assessmentId, $userId), $questions);
        $merged = [];

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId <= 0) {
                continue;
            }

            $draftValue = trim((string) ($drafts[$questionId] ?? ''));
            $submittedValue = trim((string) ($submitted[$questionId] ?? ''));
            $value = $draftValue !== '' ? $draftValue : $submittedValue;

            if ($value === '') {
                continue;
            }

            $merged[$questionId] = $value;

            if ($draftValue !== $value) {
                $this->saveQuestion($assessmentId, $userId, $questionId, $value, $questions);
            }
        }

        return $merged;
    }

    /**
     * @param  array<int, mixed>  $incoming
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, mixed>
     */
    public function mergeIncomingAnswers(array $incoming, array $questions, int $assessmentId, int $userId): array
    {
        $alignedIncoming = $this->alignMapToQuestions($incoming, $questions);
        $drafts = $this->alignMapToQuestions($this->getMap($assessmentId, $userId), $questions);

        foreach ($drafts as $questionId => $value) {
            if (! isset($alignedIncoming[$questionId]) || trim((string) $alignedIncoming[$questionId]) === '') {
                $alignedIncoming[$questionId] = $value;
            }
        }

        return $alignedIncoming;
    }

    /**
     * @param  array<int, mixed>  $map
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, mixed>
     */
    public function alignMapToQuestions(array $map, array $questions): array
    {
        if ($map === [] || $questions === []) {
            return $map;
        }

        $ids = [];

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId > 0) {
                $ids[] = $questionId;
            }
        }

        $aligned = [];

        foreach ($ids as $questionId) {
            if (isset($map[$questionId])) {
                $aligned[$questionId] = $map[$questionId];
            }
        }

        $orphanValues = [];

        foreach ($map as $oldQuestionId => $value) {
            if (! in_array((int) $oldQuestionId, $ids, true)) {
                $orphanValues[] = $value;
            }
        }

        $emptySlots = [];

        foreach ($ids as $questionId) {
            if (! isset($aligned[$questionId])) {
                $emptySlots[] = $questionId;
            }
        }

        foreach ($emptySlots as $index => $questionId) {
            if (! isset($orphanValues[$index])) {
                break;
            }

            $aligned[$questionId] = $orphanValues[$index];
        }

        return $aligned;
    }
}
