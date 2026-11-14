<?php

namespace App\Models;

use App\Support\HtmlContent;
use App\Support\Questions\QuestionParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseKnowledgeReviewAnswer extends Model
{
    protected $fillable = [
        'course_knowledge_review_id',
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

    public function knowledgeReview(): BelongsTo
    {
        return $this->belongsTo(CourseKnowledgeReview::class, 'course_knowledge_review_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CourseKnowledgeReviewQuestion::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function mapForUser(int $reviewId, int $userId): array
    {
        if ($reviewId <= 0 || $userId <= 0) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_knowledge_review_id', $reviewId)->where('user_id', $userId)->get() as $row) {
            $map[(int) $row->question_id] = $row->toArray();
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public static function hasSubmitted(int $reviewId, int $userId, array $questions): bool
    {
        if ($reviewId <= 0 || $userId <= 0 || $questions === []) {
            return false;
        }

        $map = self::mapForUser($reviewId, $userId);

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);

            if ($questionId <= 0 || ! isset($map[$questionId])) {
                return false;
            }
        }

        return true;
    }

    public static function findForTeacherReview(int $reviewId, int $userId, int $questionId, int $answerId = 0): ?self
    {
        if ($reviewId <= 0 || $userId <= 0 || $questionId <= 0) {
            return null;
        }

        $query = static::query()
            ->where('course_knowledge_review_id', $reviewId)
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
