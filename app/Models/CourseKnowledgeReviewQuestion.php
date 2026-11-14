<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseKnowledgeReviewQuestion extends Model
{
    protected $fillable = [
        'course_knowledge_review_id',
        'question_text',
        'question_type',
        'options_json',
        'correct_answer',
        'answer_explanation',
        'sort_order',
    ];

    public function knowledgeReview(): BelongsTo
    {
        return $this->belongsTo(CourseKnowledgeReview::class, 'course_knowledge_review_id');
    }
}
