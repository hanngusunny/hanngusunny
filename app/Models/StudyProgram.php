<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    protected $fillable = [
        'title',
        'description',
        'teacher_id',
        'status',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(StudyProgramLevel::class, 'program_id')->orderBy('sort_order')->orderBy('id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(StudyProgramSession::class, 'program_id')->orderBy('sort_order')->orderBy('id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(StudyProgramAssessment::class, 'program_id')->orderBy('sort_order')->orderBy('id');
    }

    public function knowledgeReviews(): HasMany
    {
        return $this->hasMany(StudyProgramKnowledgeReview::class, 'program_id')->orderBy('sort_order')->orderBy('id');
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function scopeForAdmin(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'archived'], true)) {
            $query->where('status', $status);
        }

        return $query;
    }
}
