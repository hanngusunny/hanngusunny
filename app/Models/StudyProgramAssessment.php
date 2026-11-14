<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgramAssessment extends Model
{
    protected $fillable = [
        'program_id',
        'title',
        'description',
        'duration_minutes',
        'sort_order',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(StudyProgramAssessmentQuestion::class, 'assessment_id')->orderBy('sort_order')->orderBy('id');
    }

    public function durationLabel(): string
    {
        $minutes = (int) $this->duration_minutes;

        return $minutes > 0 ? $minutes . ' phút' : 'Không giới hạn';
    }
}
