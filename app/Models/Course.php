<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'title',
        'description',
        'teacher_id',
        'program_id',
        'schedule_start_date',
        'default_start_time',
        'default_end_time',
        'weekly_schedule_days',
        'online_room_id',
        'status',
    ];

    protected $casts = [
        'schedule_start_date' => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(OnlineRoom::class, 'online_room_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class, 'course_id')
            ->where('status', '!=', 'cancelled')
            ->inProgramOrder();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CourseAssessment::class, 'course_id')
            ->where('status', '!=', 'cancelled')
            ->orderBy('sort_order')
            ->orderBy('assessment_date')
            ->orderBy('id');
    }

    public function knowledgeReviews(): HasMany
    {
        return $this->hasMany(CourseKnowledgeReview::class, 'course_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CourseMember::class, 'course_id');
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(CourseMember::class, 'course_id')->where('status', CourseMember::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function activeForTeacher(int $teacherId)
    {
        if ($teacherId <= 0) {
            return static::query()->whereRaw('1 = 0')->get();
        }

        return static::query()
            ->where('teacher_id', $teacherId)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('title')
            ->orderBy('id')
            ->get();
    }

    public static function findByOnlineRoomId(int $roomId): ?self
    {
        if ($roomId <= 0) {
            return null;
        }

        return static::query()->where('online_room_id', $roomId)->first();
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function hasNoMembers(): bool
    {
        if (array_key_exists('members_count', $this->getAttributes())) {
            return (int) $this->getAttribute('members_count') === 0;
        }

        return ! $this->members()->exists();
    }

    public function canBeDeleted(): bool
    {
        return $this->isCancelled() || $this->hasNoMembers();
    }

    public function scopeWithoutMembers(Builder $query): Builder
    {
        return $query->whereDoesntHave('members');
    }

    public function statusLabel(): string
    {
        return $this->isCancelled() ? 'Đã hủy' : 'Đang hoạt động';
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

        if (in_array($status, [self::STATUS_ACTIVE, self::STATUS_CANCELLED], true)) {
            $query->where('status', $status);
        }

        return $query;
    }
}
