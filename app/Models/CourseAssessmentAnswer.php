<?php

namespace App\Models;

use App\Support\HtmlContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssessmentAnswer extends Model
{
    protected $fillable = [
        'course_assessment_id',
        'user_id',
        'question_id',
        'selected_answer',
        'answer_text',
        'is_correct',
        'points_earned',
        'teacher_graded_at',
        'teacher_comment',
    ];

    protected $casts = [
        'teacher_graded_at' => 'datetime',
        'points_earned' => 'float',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessment::class, 'course_assessment_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function mapForUser(int $assessmentId, int $userId): array
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_assessment_id', $assessmentId)->where('user_id', $userId)->get() as $row) {
            $map[(int) $row->question_id] = $row->toArray();
        }

        return $map;
    }

    /**
     * @return array<int, string>
     */
    public static function rawMapForUser(int $assessmentId, int $userId): array
    {
        $map = [];

        foreach (self::mapForUser($assessmentId, $userId) as $questionId => $row) {
            $text = trim((string) ($row['answer_text'] ?? ''));
            $selected = trim((string) ($row['selected_answer'] ?? ''));
            $raw = $text !== '' ? $text : $selected;

            if ($questionId > 0 && $raw !== '') {
                $map[$questionId] = $raw;
            }
        }

        return $map;
    }

    public static function hasSubmitted(int $assessmentId, int $userId): bool
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return false;
        }

        return static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function findForTeacherReview(int $assessmentId, int $userId, int $questionId): ?self
    {
        if ($assessmentId <= 0 || $userId <= 0 || $questionId <= 0) {
            return null;
        }

        return static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first();
    }

    public function saveTeacherPoints(float $pointsEarned, float $maxPoints, ?string $comment): bool
    {
        $pointsEarned = round(max(0, min($maxPoints, $pointsEarned)), 2);
        $sanitized = $comment !== null ? HtmlContent::sanitize($comment) : '';
        $nextComment = HtmlContent::has($sanitized) ? $sanitized : null;
        $existingComment = trim((string) ($this->teacher_comment ?? ''));

        if ($nextComment === null && $existingComment !== '' && str_starts_with(ltrim($existingComment), '{')) {
            $nextComment = $existingComment;
        }

        $this->points_earned = $pointsEarned;
        $this->is_correct = $maxPoints > 0 && $pointsEarned >= $maxPoints ? 1 : 0;
        $this->teacher_graded_at = now();
        $this->teacher_comment = $nextComment;

        return $this->save();
    }

    /**
     * @param  list<array{is_correct: bool, comment?: string}>  $itemGrades
     */
    public function saveTeacherWordTranslateReview(array $itemGrades, int $itemCount, float $maxPoints): bool
    {
        $parser = app(\App\Support\Questions\QuestionParser::class);
        $scoring = app(\App\Services\AssessmentSubmissionService::class);

        if ($itemCount <= 0) {
            return false;
        }

        $complete = $parser->isWordTranslateItemReviewComplete($itemGrades, $itemCount);
        $this->teacher_comment = $parser->encodeWordTranslateItemReview($itemGrades);

        if ($complete) {
            $correctCount = $parser->countWordTranslateCorrectItems($itemGrades);
            $this->is_correct = $parser->computeWordTranslateOverallCorrect($itemGrades) ? 1 : 0;
            $this->points_earned = $scoring->wordTranslateEarnedPoints($maxPoints, $itemCount, $correctCount);
            $this->teacher_graded_at = now();
        }

        return $this->save();
    }

    /**
     * @param  list<array{is_correct: bool, comment?: string}>  $itemGrades
     */
    public function saveTeacherFillBlanksReview(array $itemGrades, int $blankCount, float $maxPoints, float $pointsEarned): bool
    {
        $parser = app(\App\Support\Questions\QuestionParser::class);

        if ($blankCount <= 0) {
            return false;
        }

        $correctCount = $parser->countWordTranslateCorrectItems($itemGrades);
        $pointsEarned = round(max(0, min($maxPoints, $pointsEarned)), 2);

        $this->teacher_comment = $parser->encodeFillBlanksBlankReview($itemGrades);
        $this->points_earned = $pointsEarned;
        $this->is_correct = $blankCount > 0 && $correctCount === $blankCount ? 1 : 0;
        $this->teacher_graded_at = now();

        return $this->save();
    }

    /**
     * @param  array<int, float>  $earnedByIndex
     * @param  array<string, mixed>  $question
     */
    /**
     * @param  array<int, float>  $earnedByIndex
     * @param  array<int, array<int, float>>  $partsByIndex
     * @param  array<string, mixed>  $question
     */
    public function saveTeacherCompositeItemReview(array $question, array $earnedByIndex, float $maxPoints, array $partsByIndex = []): bool
    {
        $parser = app(\App\Support\Questions\QuestionParser::class);
        $pointsEarned = round(max(0, min($maxPoints, array_sum($earnedByIndex))), 2);
        $comment = $parser->encodeCompositeItemPointGrades($earnedByIndex, $partsByIndex);

        if ((string) ($question['question_type'] ?? '') === 'phat_am') {
            $review = $parser->decodePhatAmTeacherReview((string) ($this->teacher_comment ?? ''));
            $comment = $parser->encodePhatAmTeacherReview($review['text'], $review['recording'], $earnedByIndex, $partsByIndex);
        }

        $this->teacher_comment = $comment !== '' ? $comment : $parser->encodeCompositeItemPointGrades($earnedByIndex, $partsByIndex);
        $this->points_earned = $pointsEarned;
        $this->is_correct = $maxPoints > 0 && $pointsEarned >= $maxPoints ? 1 : 0;
        $this->teacher_graded_at = now();

        return $this->save();
    }
}
