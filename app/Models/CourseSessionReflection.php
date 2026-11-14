<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSessionReflection extends Model
{
    protected $fillable = [
        'course_session_id',
        'user_id',
        'session_rating',
        'student_comment',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getForUser(int $sessionId, int $userId): ?self
    {
        if ($sessionId <= 0 || $userId <= 0) {
            return null;
        }

        return static::query()
            ->where('course_session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();
    }

    public static function upsertComment(int $sessionId, int $userId, string $comment, ?int $rating = null): self
    {
        $data = [
            'student_comment' => trim($comment),
        ];

        if ($rating !== null && $rating >= 1 && $rating <= 5) {
            $data['session_rating'] = $rating;
        }

        return static::query()->updateOrCreate(
            [
                'course_session_id' => $sessionId,
                'user_id' => $userId,
            ],
            $data
        );
    }
}
