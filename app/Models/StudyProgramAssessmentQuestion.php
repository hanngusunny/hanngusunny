<?php

namespace App\Models;

use App\Support\Questions\QuestionParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyProgramAssessmentQuestion extends Model
{
    protected $fillable = [
        'assessment_id',
        'question_text',
        'question_type',
        'options_json',
        'correct_answer',
        'answer_explanation',
        'points',
        'sort_order',
    ];

    protected $casts = [
        'points' => 'float',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(StudyProgramAssessment::class, 'assessment_id');
    }

    public static function createEmpty(int $assessmentId, ?string $questionType = null): self
    {
        $type = in_array($questionType, QuestionParser::TYPES, true) ? $questionType : 'fill';
        $sort = (int) static::query()->where('assessment_id', $assessmentId)->max('sort_order');

        return static::query()->create([
            'assessment_id' => $assessmentId,
            'question_text' => '',
            'question_type' => $type,
            'points' => 1,
            'sort_order' => $sort + 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function updateFromPost(array $item): bool
    {
        $parser = app(QuestionParser::class);
        $existing = $parser->normalizeQuestionRowForEdit($this->toArray());
        $parsed = $parser->resolveQuestionFromPost($item, 'post_practice', $existing);

        return $this->update([
            'question_text' => $parsed['question_text'],
            'question_type' => $parsed['question_type'],
            'options_json' => $parsed['options_json'],
            'correct_answer' => $parsed['correct_answer'],
            'answer_explanation' => $parsed['answer_explanation'],
            'points' => $this->pointsFromParsed($item, $parsed),
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $parsed
     */
    public function pointsFromParsed(array $item, array $parsed): float
    {
        $posted = $this->normalizePoints($item['points'] ?? null);
        $type = (string) ($parsed['question_type'] ?? '');

        if (! in_array($type, ['listening', 'phat_am'], true)) {
            return $posted;
        }

        $row = $parsed;
        $row['points'] = $posted;

        return app(QuestionParser::class)->resolveCompositeParentPoints($row, $posted);
    }

    public function normalizePoints(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 1.0;
        }

        $points = (float) str_replace(',', '.', (string) $value);

        if ($points <= 0) {
            return 1.0;
        }

        return round(min(1000, $points), 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function toEditArray(): array
    {
        $row = app(QuestionParser::class)->normalizeQuestionRowForEdit($this->toArray());
        $row['points'] = $this->points;

        return $row;
    }
}
