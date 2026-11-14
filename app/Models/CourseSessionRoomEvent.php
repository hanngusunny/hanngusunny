<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSessionRoomEvent extends Model
{
    public const TYPE_JOIN = 'join';

    public const TYPE_LEAVE = 'leave';

    public const TYPE_BREAK_OUT = 'break_out';

    public const TYPE_BREAK_RETURN = 'break_return';

    protected $fillable = [
        'session_id',
        'room_id',
        'user_id',
        'event_type',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_JOIN => 'Vào lớp học',
            self::TYPE_LEAVE => 'Rời phòng',
            self::TYPE_BREAK_OUT => 'Xin ra ngoài',
            self::TYPE_BREAK_RETURN => 'Đã quay lại lớp',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function timelineForSession(int $sessionId): array
    {
        if ($sessionId <= 0) {
            return [];
        }

        $rows = [];

        foreach (static::query()
            ->with('user')
            ->where('session_id', $sessionId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get() as $event
        ) {
            $user = $event->user;
            $role = (string) ($user?->role ?? 'student');

            $rows[] = [
                'id' => (int) $event->id,
                'user_id' => (int) $event->user_id,
                'name' => (string) ($user?->name ?: 'Thành viên'),
                'role' => $role,
                'roleLabel' => $user?->role_label ?: 'Thành viên',
                'event_type' => (string) $event->event_type,
                'label' => self::typeLabels()[$event->event_type] ?? $event->event_type,
                'occurred_at' => optional($event->occurred_at)->format('Y-m-d H:i:s'),
                'timeLabel' => optional($event->occurred_at)->format('H:i') ?: '—',
                'dateLabel' => optional($event->occurred_at)->format('d/m/Y') ?: '',
            ];
        }

        return $rows;
    }
}
