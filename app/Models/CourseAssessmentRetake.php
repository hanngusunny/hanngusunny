<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAssessmentRetake extends Model
{
    protected $fillable = [
        'course_assessment_id',
        'user_id',
        'granted_by',
    ];

    public static function isAllowed(int $assessmentId, int $userId): bool
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return false;
        }

        return static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function grant(int $assessmentId, int $userId, int $grantedBy): void
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return;
        }

        $existing = static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->first();

        if ($existing !== null) {
            $existing->granted_by = $grantedBy > 0 ? $grantedBy : null;
            $existing->save();

            return;
        }

        static::query()->create([
            'course_assessment_id' => $assessmentId,
            'user_id' => $userId,
            'granted_by' => $grantedBy > 0 ? $grantedBy : null,
        ]);
    }

    public static function revoke(int $assessmentId, int $userId): void
    {
        static::query()
            ->where('course_assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->delete();
    }
}
