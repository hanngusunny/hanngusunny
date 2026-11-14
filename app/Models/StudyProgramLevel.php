<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgramLevel extends Model
{
    protected $fillable = [
        'program_id',
        'title',
        'description',
        'sort_order',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(StudyProgramSession::class, 'level_id')->orderBy('sort_order')->orderBy('id');
    }
}
