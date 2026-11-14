<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CourseKnowledgeReviewAnswerDraft extends Model
{
    protected $fillable = [
        'course_knowledge_review_id',
        'user_id',
        'question_id',
        'selected_answer',
    ];

    /**
     * @return array<int, string>
     */
    public static function rawMapForUser(int $reviewId, int $userId): array
    {
        if ($reviewId <= 0 || $userId <= 0 || ! Schema::hasTable((new static)->getTable())) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('course_knowledge_review_id', $reviewId)->where('user_id', $userId)->get() as $row) {
            $raw = trim((string) ($row->selected_answer ?? ''));

            if ((int) $row->question_id > 0 && $raw !== '') {
                $map[(int) $row->question_id] = $raw;
            }
        }

        return $map;
    }

    public static function clearForStudent(int $reviewId, int $userId): void
    {
        if ($reviewId <= 0 || $userId <= 0 || ! Schema::hasTable((new static)->getTable())) {
            return;
        }

        static::query()->where('course_knowledge_review_id', $reviewId)->where('user_id', $userId)->delete();
    }
}
