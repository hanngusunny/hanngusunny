<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssessmentQuestion extends Model
{
    protected $fillable = [
        'course_assessment_id',
        'question_text',
        'question_type',
        'options_json',
        'correct_answer',
        'answer_explanation',
        'points',
        'sort_order',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessment::class, 'course_assessment_id');
    }
}
