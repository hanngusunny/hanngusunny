<?php

namespace App\Services;

use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentAnswerDraft;
use App\Models\CourseAssessmentQuestion;
use App\Models\StudyProgramAssessmentQuestion;

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

        $orphanMap = [];

        foreach ($map as $oldQuestionId => $value) {
            $oldQuestionId = (int) $oldQuestionId;

            if (! in_array($oldQuestionId, $ids, true)) {
                $orphanMap[$oldQuestionId] = $value;
            }
        }

        if ($orphanMap === []) {
            return $aligned;
        }

        // Try to match orphans by looking up the old question record
        $oldQuestions = CourseAssessmentQuestion::query()
            ->whereIn('id', array_keys($orphanMap))
            ->get()
            ->keyBy('id');

        $missingOldIds = array_diff(array_keys($orphanMap), $oldQuestions->keys()->all());

        if ($missingOldIds !== []) {
            $programQuestions = StudyProgramAssessmentQuestion::query()
                ->whereIn('id', $missingOldIds)
                ->get()
                ->keyBy('id');

            foreach ($programQuestions as $pId => $pQ) {
                $oldQuestions->put($pId, $pQ);
            }
        }

        $remainingOrphans = [];

        foreach ($orphanMap as $oldQuestionId => $value) {
            $oldQ = $oldQuestions->get($oldQuestionId);

            if ($oldQ !== null) {
                $matchedId = null;

                foreach ($questions as $q) {
                    $qId = (int) ($q['id'] ?? 0);

                    if (! isset($aligned[$qId])
                        && (int) ($q['sort_order'] ?? -1) === (int) $oldQ->sort_order
                        && (string) ($q['question_type'] ?? '') === (string) $oldQ->question_type
                    ) {
                        $matchedId = $qId;
                        break;
                    }
                }

                if ($matchedId !== null) {
                    $aligned[$matchedId] = $value;
                    continue;
                }
            }

            $remainingOrphans[$oldQuestionId] = $value;
        }

        // Only do full 1:1 positional alignment if ALL questions were orphans and counts match exactly
        if ($aligned === [] && count($remainingOrphans) === count($ids)) {
            $orphanValues = array_values($remainingOrphans);

            foreach ($ids as $index => $questionId) {
                $aligned[$questionId] = $orphanValues[$index];
            }
        }

        return $aligned;
    }
}
