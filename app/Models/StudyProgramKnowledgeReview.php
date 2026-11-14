<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgramKnowledgeReview extends Model
{
    protected $fillable = [
        'program_id',
        'title',
        'description',
        'practice_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'sort_order',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(StudyProgramKnowledgeReviewQuestion::class, 'knowledge_review_id')->orderBy('sort_order')->orderBy('id');
    }

    public function durationLabel(): string
    {
        $minutes = (int) $this->duration_minutes;

        return $minutes > 0 ? $minutes . ' phút' : 'Không giới hạn';
    }
}
