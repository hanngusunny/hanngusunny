<?php

namespace App\Services;

use App\Models\CourseKnowledgeReviewAnswerDraft;
use App\Models\CourseSessionQuizAnswerDraft;
use Illuminate\Support\Facades\Schema;

class SessionQuizDraftService
{
    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, string>
     */
    public function mapForSession(int $sessionId, int $userId, array $questions = []): array
    {
        return $this->alignMap(CourseSessionQuizAnswerDraft::rawMapForUser($sessionId, $userId), $questions);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, mixed>  $submittedMap
     * @return array<int, string>
     */
    public function answersForSessionForm(
        int $sessionId,
        int $userId,
        array $questions = [],
        array $submittedMap = [],
        bool $fillFromSubmitted = false
    ): array {
        $drafts = $this->mapForSession($sessionId, $userId, $questions);

        return $fillFromSubmitted
            ? $this->mergeDraftsWithSubmitted($drafts, $submittedMap, $questions)
            : $drafts;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, string>
     */
    public function mapForKnowledgeReview(int $reviewId, int $userId, array $questions = []): array
    {
        return $this->alignMap(CourseKnowledgeReviewAnswerDraft::rawMapForUser($reviewId, $userId), $questions);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  array<int, mixed>  $submittedMap
     * @return array<int, string>
     */
    public function answersForKnowledgeReviewForm(
        int $reviewId,
        int $userId,
        array $questions = [],
        array $submittedMap = [],
        bool $fillFromSubmitted = false
    ): array {
        $drafts = $this->mapForKnowledgeReview($reviewId, $userId, $questions);

        return $fillFromSubmitted
            ? $this->mergeDraftsWithSubmitted($drafts, $submittedMap, $questions)
            : $drafts;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function saveSessionQuestion(int $sessionId, int $userId, int $questionId, string $value, array $questions): bool
    {
        if (! Schema::hasTable('course_session_quiz_answer_drafts')) {
            return false;
        }

        $value = trim($value);

        if ($sessionId <= 0 || $userId <= 0 || $questionId <= 0 || $value === '' || ! $this->hasQuestion($questions, $questionId)) {
            return false;
        }

        $row = CourseSessionQuizAnswerDraft::query()->firstOrNew([
            'course_session_id' => $sessionId,
            'user_id' => $userId,
            'question_id' => $questionId,
        ]);
        $row->selected_answer = $value;

        return $row->save();
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function saveKnowledgeQuestion(int $reviewId, int $userId, int $questionId, string $value, array $questions): bool
    {
        if (! Schema::hasTable('course_knowledge_review_answer_drafts')) {
            return false;
        }

        $value = trim($value);

        if ($reviewId <= 0 || $userId <= 0 || $questionId <= 0 || $value === '' || ! $this->hasQuestion($questions, $questionId)) {
            return false;
        }

        $row = CourseKnowledgeReviewAnswerDraft::query()->firstOrNew([
            'course_knowledge_review_id' => $reviewId,
            'user_id' => $userId,
            'question_id' => $questionId,
        ]);
        $row->selected_answer = $value;

        return $row->save();
    }

    /**
     * @param  array<int, mixed>  $incoming
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, mixed>
     */
    public function mergeIncoming(array $incoming, array $drafts, array $questions): array
    {
        $alignedIncoming = $this->alignMap($incoming, $questions);
        $alignedDrafts = $this->alignMap($drafts, $questions);

        foreach ($alignedDrafts as $questionId => $value) {
            if (! isset($alignedIncoming[$questionId]) || trim((string) $alignedIncoming[$questionId]) === '') {
                $alignedIncoming[$questionId] = $value;
            }
        }

        return $alignedIncoming;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function clearSession(int $sessionId, int $userId, array $questions = []): void
    {
        $ids = [];

        foreach ($questions as $question) {
            $id = (int) ($question['id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        CourseSessionQuizAnswerDraft::clearForStudent($sessionId, $userId, $ids);
    }

    public function clearKnowledgeReview(int $reviewId, int $userId): void
    {
        CourseKnowledgeReviewAnswerDraft::clearForStudent($reviewId, $userId);
    }

    /**
     * @param  array<int, mixed>  $map
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, mixed>
     */
    public function alignMap(array $map, array $questions): array
    {
        if ($questions === []) {
            return $map;
        }

        $aligned = [];

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId > 0 && isset($map[$questionId]) && trim((string) $map[$questionId]) !== '') {
                $aligned[$questionId] = $map[$questionId];
            }
        }

        return $aligned;
    }

    /**
     * @param  array<int, string>  $drafts
     * @param  array<int, mixed>  $submittedMap
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, string>
     */
    private function mergeDraftsWithSubmitted(array $drafts, array $submittedMap, array $questions): array
    {
        $submitted = $this->rawValues($submittedMap);
        $merged = [];

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId <= 0) {
                continue;
            }

            $draftValue = trim((string) ($drafts[$questionId] ?? ''));
            $submittedValue = trim((string) ($submitted[$questionId] ?? ''));
            $value = $draftValue !== '' ? $draftValue : $submittedValue;

            if ($value !== '') {
                $merged[$questionId] = $value;
            }
        }

        return $merged;
    }

    /**
     * @param  array<int, mixed>  $map
     * @return array<int, string>
     */
    private function rawValues(array $map): array
    {
        $out = [];

        foreach ($map as $id => $value) {
            $questionId = (int) $id;

            if ($questionId <= 0) {
                continue;
            }

            $raw = is_array($value)
                ? trim((string) ($value['selected_answer'] ?? $value['answer_text'] ?? ''))
                : trim((string) $value);

            if ($raw !== '') {
                $out[$questionId] = $raw;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function hasQuestion(array $questions, int $questionId): bool
    {
        foreach ($questions as $question) {
            if ((int) ($question['id'] ?? 0) === $questionId) {
                return true;
            }
        }

        return false;
    }
}
