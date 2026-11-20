<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSessionAttendance extends Model
{
    protected $table = 'course_session_attendance';

    protected $fillable = [
        'session_id',
        'user_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function hasAttended(int $sessionId, int $userId): bool
    {
        if ($sessionId <= 0 || $userId <= 0) {
            return false;
        }

        return static::query()
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Batch check attendance for multiple sessions for a user in 1 query.
     *
     * @param  list<int>  $sessionIds
     * @return array<int, bool> Map of [session_id => bool]
     */
    public static function attendedMapForUser(array $sessionIds, int $userId): array
    {
        $sessionIds = array_values(array_filter(array_map('intval', $sessionIds), static fn ($id) => $id > 0));

        if ($userId <= 0 || $sessionIds === []) {
            return [];
        }

        $attendedIds = static::query()
            ->where('user_id', $userId)
            ->whereIn('session_id', $sessionIds)
            ->pluck('session_id')
            ->map(static fn ($id): int => (int) $id)
            ->flip()
            ->all();

        $map = [];
        foreach ($sessionIds as $id) {
            $map[$id] = isset($attendedIds[$id]);
        }

        return $map;
    }

    /**
     * @return array{first: bool, joined_at: string|null}
     */
    public static function markPresent(int $sessionId, int $userId): array
    {
        $row = static::query()->firstOrCreate(
            ['session_id' => $sessionId, 'user_id' => $userId],
            ['joined_at' => now()]
        );

        if ($row->wasRecentlyCreated === false && $row->joined_at === null) {
            $row->joined_at = now();
            $row->save();
        }

        return [
            'first' => $row->wasRecentlyCreated,
            'joined_at' => optional($row->joined_at)->format('Y-m-d H:i:s'),
        ];
    }

    public static function countBySession(int $sessionId): int
    {
        return static::query()->where('session_id', $sessionId)->count();
    }

    /**
     * @return array<int, string>
     */
    public static function mapBySession(int $sessionId): array
    {
        if ($sessionId <= 0) {
            return [];
        }

        $map = [];

        foreach (static::query()->where('session_id', $sessionId)->get() as $row) {
            $userId = (int) $row->user_id;

            if ($userId > 0) {
                $map[$userId] = optional($row->joined_at)->format('Y-m-d H:i:s') ?: '';
            }
        }

        return $map;
    }

    public static function setManual(int $sessionId, int $userId, string $joinedAt): void
    {
        static::query()->updateOrCreate(
            ['session_id' => $sessionId, 'user_id' => $userId],
            ['joined_at' => $joinedAt]
        );
    }

    public static function clearAttendance(int $sessionId, int $userId): void
    {
        static::query()->where('session_id', $sessionId)->where('user_id', $userId)->delete();
    }

    /**
     * @return array<int, array{attended: int, total: int}>
     */
    public static function summaryMapForCourseWeek(int $courseId, string $evalWeek): array
    {
        $sessions = CourseSession::scheduledForWeek($courseId, $evalWeek);
        $total = $sessions->count();

        if ($total === 0) {
            return [];
        }

        $rows = static::query()
            ->selectRaw('user_id, COUNT(*) as attended')
            ->whereIn('session_id', $sessions->pluck('id'))
            ->groupBy('user_id')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row->user_id] = [
                'attended' => (int) $row->attended,
                'total' => $total,
            ];
        }

        return $map;
    }

    /**
     * @return array{items: list<array<string, mixed>>, attended: int, total: int}
     */
    public static function studentWeekReport(int $courseId, int $userId, string $evalWeek): array
    {
        $sessions = CourseSession::scheduledForWeek($courseId, $evalWeek);
        $attendedMap = [];

        if ($sessions->isNotEmpty()) {
            $rows = static::query()
                ->whereIn('session_id', $sessions->pluck('id'))
                ->where('user_id', $userId)
                ->get();

            foreach ($rows as $row) {
                $attendedMap[(int) $row->session_id] = $row->joined_at;
            }
        }

        $items = [];
        $attendedCount = 0;

        foreach ($sessions as $session) {
            $sessionId = (int) $session->id;
            $attended = array_key_exists($sessionId, $attendedMap);

            if ($attended) {
                $attendedCount++;
                $statusLabel = 'Có mặt';
            } elseif ($session->livePhase() === 'upcoming') {
                $statusLabel = 'Chưa diễn ra';
            } else {
                $statusLabel = 'Vắng';
            }

            $joinedAt = $attendedMap[$sessionId] ?? null;

            $items[] = [
                'session' => [
                    'id' => $sessionId,
                    'title' => (string) ($session->title ?: 'Buổi học'),
                    'session_date' => $session->dateForInput(),
                    'start_time' => $session->timeForInput($session->start_time),
                ],
                'attended' => $attended,
                'joined_at' => $joinedAt instanceof \DateTimeInterface
                    ? $joinedAt->format('Y-m-d H:i:s')
                    : ($joinedAt ? (string) $joinedAt : null),
                'status_label' => $statusLabel,
            ];
        }

        return [
            'items' => $items,
            'attended' => $attendedCount,
            'total' => $sessions->count(),
        ];
    }
}
