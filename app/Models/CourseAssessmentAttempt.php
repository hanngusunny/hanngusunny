<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAssessmentAttempt extends Model
{
    protected $fillable = [
        'course_assessment_id',
        'user_id',
        'started_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function findActive(int $assessmentId, int $userId): ?self
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return null;
        }

        return static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->first();
    }

    public static function deleteActive(int $assessmentId, int $userId): void
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return;
        }

        static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->delete();
    }
}
