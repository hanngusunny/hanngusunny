<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSessionQuestion extends Model
{
    protected $fillable = [
        'course_session_id',
        'question_group',
        'question_text',
        'question_type',
        'options_json',
        'correct_answer',
        'answer_explanation',
        'sort_order',
    ];

    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function forSessionGroup(int $sessionId, string $group)
    {
        if ($sessionId <= 0 || $group === '') {
            return collect();
        }

        return static::query()
            ->where('course_session_id', $sessionId)
            ->where('question_group', $group)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
