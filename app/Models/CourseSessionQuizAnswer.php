<?php

namespace App\Models;

use App\Support\HtmlContent;
use App\Support\Questions\QuestionParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSessionQuizAnswer extends Model
{
    protected $fillable = [
        'course_session_id',
        'user_id',
        'question_id',
        'selected_answer',
        'is_correct',
        'teacher_comment',
        'teacher_graded_at',
    ];

    protected $casts = [
        'teacher_graded_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CourseSessionQuestion::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function mapForUser(int $sessionId, int $userId): array
    {
        if ($sessionId <= 0 || $userId <= 0) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_session_id', $sessionId)->where('user_id', $userId)->get() as $row) {
            $map[(int) $row->question_id] = $row->toArray();
        }

        return $map;
    }

    /**
     * Batch count completed exercises for a user across multiple session IDs in 1-2 queries.
     * An exercise group is completed when it has at least 1 question and all questions are submitted.
     *
     * @param  list<int>  $sessionIds
     * @return array<int, int> Map of [course_session_id => completed_count]
     */
    public static function batchCountCompletedExercisesForUser(int $userId, array $sessionIds): array
    {
        $sessionIds = array_values(array_unique(array_filter(array_map('intval', $sessionIds), static fn ($id) => $id > 0)));

        if ($userId <= 0 || $sessionIds === []) {
            return [];
        }

        $result = array_fill_keys($sessionIds, 0);

        $questions = CourseSessionQuestion::query()
            ->whereIn('course_session_id', $sessionIds)
            ->whereIn('question_group', ['pre', 'review', 'post_practice'])
            ->select(['id', 'course_session_id', 'question_group'])
            ->get();

        if ($questions->isEmpty()) {
            return $result;
        }

        $sessionGroupQuestions = [];
        $neededQuestionIds = [];

        foreach ($questions as $q) {
            $sId = (int) $q->course_session_id;
            $grp = (string) $q->question_group;
            $qId = (int) $q->id;

            $sessionGroupQuestions[$sId][$grp][] = $qId;
            $neededQuestionIds[] = $qId;
        }

        $submittedMap = static::query()
            ->where('user_id', $userId)
            ->whereIn('course_session_id', $sessionIds)
            ->whereIn('question_id', $neededQuestionIds)
            ->pluck('question_id')
            ->map(static fn ($id): int => (int) $id)
            ->flip()
            ->all();

        foreach ($sessionIds as $sessionId) {
            $completed = 0;
            $groups = $sessionGroupQuestions[$sessionId] ?? null;

            if ($groups === null) {
                $result[$sessionId] = 0;
                continue;
            }

            foreach (['pre', 'review', 'post_practice'] as $group) {
                $qIds = $groups[$group] ?? [];
                if ($qIds === []) {
                    continue;
                }

                $allDone = true;
                foreach ($qIds as $qId) {
                    if (! isset($submittedMap[$qId])) {
                        $allDone = false;
                        break;
                    }
                }

                if ($allDone) {
                    $completed++;
                }
            }

            $result[$sessionId] = $completed;
        }

        return $result;
    }

    /**
     * @param  list<int>  $sessionIds
     */
    public static function countCompletedExercisesForUser(int $userId, array $sessionIds): int
    {
        $batch = self::batchCountCompletedExercisesForUser($userId, $sessionIds);

        return array_sum($batch);
    }

    public static function hasSubmittedForQuestionIds(int $sessionId, int $userId, array $questionIds): bool
    {
        $questionIds = array_values(array_filter(array_map('intval', $questionIds), static fn ($id) => $id > 0));

        if ($sessionId <= 0 || $userId <= 0 || $questionIds === []) {
            return false;
        }

        $map = self::mapForUser($sessionId, $userId);

        foreach ($questionIds as $questionId) {
            if (! isset($map[$questionId])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public static function submissionSummariesForSession(int $sessionId, array $questions): array
    {
        if ($sessionId <= 0 || $questions === []) {
            return [];
        }

        $questionIds = [];

        foreach ($questions as $question) {
            $id = (int) ($question['id'] ?? 0);

            if ($id > 0) {
                $questionIds[] = $id;
            }
        }

        $grouped = [];

        foreach (static::query()->where('course_session_id', $sessionId)->get() as $row) {
            $grouped[(int) $row->user_id][] = $row;
        }

        $map = [];

        foreach ($grouped as $userId => $answers) {
            if ($userId <= 0) {
                continue;
            }

            $answerMap = [];
            $latest = '';

            foreach ($answers as $row) {
                $answerMap[(int) $row->question_id] = $row->toArray();
                $updated = optional($row->updated_at)->format('Y-m-d H:i:s') ?: '';

                if ($updated !== '' && ($latest === '' || $updated > $latest)) {
                    $latest = $updated;
                }
            }

            $answered = 0;
            $correct = 0;

            foreach ($questionIds as $questionId) {
                if (! isset($answerMap[$questionId])) {
                    continue;
                }

                $answered++;

                if (! empty($answerMap[$questionId]['is_correct'])) {
                    $correct++;
                }
            }

            $total = count($questionIds);
            $submitted = $answered > 0;

            $map[$userId] = [
                'submitted' => $submitted,
                'complete' => $total > 0 && $answered >= $total,
                'correct' => $correct,
                'total' => $total,
                'score' => $submitted && $total > 0 ? (int) round(($correct / $total) * 100) : 0,
                'submitted_at' => $latest,
            ];
        }

        return $map;
    }

    public static function findForTeacherReview(int $sessionId, int $userId, int $questionId, int $answerId = 0): ?self
    {
        if ($sessionId <= 0 || $userId <= 0 || $questionId <= 0) {
            return null;
        }

        $query = static::query()
            ->where('course_session_id', $sessionId)
            ->where('user_id', $userId)
            ->where('question_id', $questionId);

        if ($answerId > 0) {
            $query->where('id', $answerId);
        }

        return $query->first();
    }

    public function saveTeacherEssayReview(bool $isCorrect, string $comment): bool
    {
        $this->is_correct = $isCorrect ? 1 : 0;
        $this->teacher_comment = HtmlContent::sanitize($comment);
        $this->teacher_graded_at = now();

        return $this->save();
    }

    public function saveTeacherPhatAmReview(bool $isCorrect, string $text, string $recordingPath): bool
    {
        $parser = app(QuestionParser::class);

        $this->is_correct = $isCorrect ? 1 : 0;
        $this->teacher_comment = $parser->encodePhatAmTeacherReview(
            HtmlContent::sanitize($text),
            $recordingPath,
            $parser->decodeCompositeItemPointGrades((string) ($this->teacher_comment ?? ''))
        );
        $this->teacher_graded_at = now();

        return $this->save();
    }

    /**
     * @param  list<array{is_correct: bool, comment?: string}>  $itemGrades
     */
    public function saveTeacherWordTranslateReview(array $itemGrades, int $itemCount): bool
    {
        $parser = app(QuestionParser::class);

        if ($itemCount <= 0 || ! $parser->isWordTranslateItemReviewComplete($itemGrades, $itemCount)) {
            return false;
        }

        $this->is_correct = $parser->computeWordTranslateOverallCorrect($itemGrades) ? 1 : 0;
        $this->teacher_comment = $parser->encodeWordTranslateItemReview($itemGrades);
        $this->teacher_graded_at = now();

        return $this->save();
    }

    /**
     * @param  array<string, mixed>  $question
     * @param  array<int, array{is_correct: bool, comment?: string}>  $itemGrades
     */
    public function saveTeacherCompositeFillReview(
        array $question,
        string $rawSelected,
        array $itemGrades,
        string $comment,
        string $recordingPath = ''
    ): bool {
        $parser = app(QuestionParser::class);
        $fillIndexes = $parser->compositeFillItemIndexes($question);

        if ($fillIndexes === []) {
            return false;
        }

        foreach ($fillIndexes as $index) {
            if (! is_array($itemGrades[$index] ?? null) || ! array_key_exists('is_correct', $itemGrades[$index])) {
                return false;
            }
        }

        $flags = $parser->mergeCompositeFillCorrectFlags($question, $rawSelected, $itemGrades);
        $this->is_correct = $parser->compositeFlagsAllCorrect($flags) ? 1 : 0;
        $this->teacher_comment = $parser->encodeCompositeFillItemReviews(
            $itemGrades,
            HtmlContent::plain($comment),
            (string) ($this->teacher_comment ?? ''),
            $recordingPath
        );
        $this->teacher_graded_at = now();

        return $this->save();
    }
}
