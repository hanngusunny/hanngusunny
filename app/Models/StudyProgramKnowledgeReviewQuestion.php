<?php

namespace App\Models;

use App\Support\Questions\QuestionParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyProgramKnowledgeReviewQuestion extends Model
{
    protected $fillable = [
        'knowledge_review_id',
        'question_text',
        'question_type',
        'options_json',
        'correct_answer',
        'answer_explanation',
        'sort_order',
    ];

    public function knowledgeReview(): BelongsTo
    {
        return $this->belongsTo(StudyProgramKnowledgeReview::class, 'knowledge_review_id');
    }

    public static function createEmpty(int $reviewId, ?string $questionType = null): self
    {
        $type = in_array($questionType, QuestionParser::TYPES, true) ? $questionType : 'fill';
        $sort = (int) static::query()->where('knowledge_review_id', $reviewId)->max('sort_order');

        return static::query()->create([
            'knowledge_review_id' => $reviewId,
            'question_text' => '',
            'question_type' => $type,
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toEditArray(): array
    {
        return app(QuestionParser::class)->normalizeQuestionRowForEdit($this->toArray());
    }
}
