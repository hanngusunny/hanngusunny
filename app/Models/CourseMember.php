<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMember extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_LEFT = 'left';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'course_id',
        'user_id',
        'status',
        'package_type',
        'absence_adjustment',
        'homework_adjustment',
        'joined_at',
        'left_at',
        'completed_at',
        'active_user_key',
    ];

    protected $casts = [
        'absence_adjustment' => 'integer',
        'homework_adjustment' => 'integer',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Đang học',
            self::STATUS_LEFT => 'Đã rời khóa',
            self::STATUS_COMPLETED => 'Đã hoàn thành',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /** @var array<int, Course|null> */
    private static array $activeCourseCache = [];

    /** @var array<string, bool> */
    private static array $isMemberCache = [];

    public static function clearMemberCache(): void
    {
        self::$activeCourseCache = [];
        self::$isMemberCache = [];
    }

    public static function activeCourseFor(int $userId): ?Course
    {
        if ($userId <= 0) {
            return null;
        }

        if (array_key_exists($userId, self::$activeCourseCache)) {
            return self::$activeCourseCache[$userId];
        }

        $member = static::query()
            ->where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->whereHas('course', static fn ($query) => $query->where('status', Course::STATUS_ACTIVE))
            ->with(['course.program', 'course.teacher', 'course.room'])
            ->latest('id')
            ->first();

        return self::$activeCourseCache[$userId] = $member?->course;
    }

    public static function isMemberOf(int $courseId, int $userId): bool
    {
        if ($courseId <= 0 || $userId <= 0) {
            return false;
        }

        $cacheKey = "{$courseId}:{$userId}";
        if (isset(self::$isMemberCache[$cacheKey])) {
            return self::$isMemberCache[$cacheKey];
        }

        // Fast-path: nếu active course của user chính là courseId này
        if (isset(self::$activeCourseCache[$userId]) && (int) (self::$activeCourseCache[$userId]?->id ?? 0) === $courseId) {
            return self::$isMemberCache[$cacheKey] = true;
        }

        return self::$isMemberCache[$cacheKey] = static::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function activeMembersForCourse(int $courseId)
    {
        if ($courseId <= 0) {
            return collect();
        }

        return static::query()
            ->where('course_id', $courseId)
            ->where('status', self::STATUS_ACTIVE)
            ->with('user')
            ->get()
            ->sortBy(static fn (self $member): string => mb_strtolower((string) ($member->user?->name ?? '')))
            ->values();
    }

    public function applyActiveUserKey(): void
    {
        $this->active_user_key = $this->status === self::STATUS_ACTIVE && (int) $this->user_id > 0
            ? (int) $this->user_id
            : null;
    }
}
