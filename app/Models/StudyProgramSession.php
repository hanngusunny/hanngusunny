<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgramSession extends Model
{
    protected $fillable = [
        'program_id',
        'level_id',
        'title',
        'description',
        'pre_session_materials',
        'pre_session_videos',
        'pre_session_video_content',
        'pre_session_video_url',
        'materials',
        'session_summary',
        'program_knowledge_review_id',
        'sort_order',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(StudyProgramLevel::class, 'level_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(StudyProgramSessionQuestion::class, 'session_id')->orderBy('sort_order')->orderBy('id');
    }

    public function knowledgeReview(): BelongsTo
    {
        return $this->belongsTo(StudyProgramKnowledgeReview::class, 'program_knowledge_review_id');
    }
}
