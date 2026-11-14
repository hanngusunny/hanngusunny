<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CourseSessionQuizAnswerDraft extends Model
{
    protected $fillable = [
        'course_session_id',
        'user_id',
        'question_id',
        'selected_answer',
    ];

    /**
     * @return array<int, string>
     */
    public static function rawMapForUser(int $sessionId, int $userId): array
    {
        if ($sessionId <= 0 || $userId <= 0 || ! Schema::hasTable((new static)->getTable())) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_session_id', $sessionId)->where('user_id', $userId)->get() as $row) {
            $raw = trim((string) ($row->selected_answer ?? ''));

            if ((int) $row->question_id > 0 && $raw !== '') {
                $map[(int) $row->question_id] = $raw;
            }
        }

        return $map;
    }

    public static function clearForStudent(int $sessionId, int $userId, array $questionIds = []): void
    {
        if ($sessionId <= 0 || $userId <= 0 || ! Schema::hasTable((new static)->getTable())) {
            return;
        }

        $query = static::query()->where('course_session_id', $sessionId)->where('user_id', $userId);
        $ids = array_values(array_filter(array_map('intval', $questionIds), static fn (int $id): bool => $id > 0));

        if ($ids !== []) {
            $query->whereIn('question_id', $ids);
        }

        $query->delete();
    }
}
