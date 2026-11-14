<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseKnowledgeReviewAttempt extends Model
{
    protected $fillable = [
        'course_knowledge_review_id',
        'user_id',
        'is_practice',
        'started_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_practice' => 'boolean',
    ];

    public function knowledgeReview(): BelongsTo
    {
        return $this->belongsTo(CourseKnowledgeReview::class, 'course_knowledge_review_id');
    }

    public static function findActive(int $reviewId, int $userId, bool $isPractice): ?self
    {
        if ($reviewId <= 0 || $userId <= 0) {
            return null;
        }

        return static::query()
            ->where('course_knowledge_review_id', $reviewId)
            ->where('user_id', $userId)
            ->where('is_practice', $isPractice ? 1 : 0)
            ->first();
    }

    public static function deleteActive(int $reviewId, int $userId, bool $isPractice): void
    {
        if ($reviewId <= 0 || $userId <= 0) {
            return;
        }

        static::query()
            ->where('course_knowledge_review_id', $reviewId)
            ->where('user_id', $userId)
            ->where('is_practice', $isPractice ? 1 : 0)
            ->delete();
    }
}
