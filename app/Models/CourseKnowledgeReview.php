<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseKnowledgeReview extends Model
{
    protected $fillable = [
        'course_id',
        'program_knowledge_review_id',
        'title',
        'description',
        'practice_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'sort_order',
        'status',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CourseKnowledgeReviewQuestion::class, 'course_knowledge_review_id')->orderBy('sort_order')->orderBy('id');
    }

    public static function findForCourseByProgramReview(int $courseId, int $programKnowledgeReviewId): ?self
    {
        if ($courseId <= 0 || $programKnowledgeReviewId <= 0) {
            return null;
        }

        return static::query()
            ->where('course_id', $courseId)
            ->where('program_knowledge_review_id', $programKnowledgeReviewId)
            ->first();
    }
}
