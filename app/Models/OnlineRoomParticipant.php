<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class OnlineRoomParticipant extends Model
{
    protected $table = 'online_room_participants';

    protected $fillable = [
        'room_id',
        'user_id',
        'participant_role',
        'joined_at',
        'left_at',
        'last_seen_at',
        'is_online',
        'break_out_at',
        'breakout_room_index',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_online' => 'boolean',
        'break_out_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(OnlineRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function joinRoom(int $roomId, int $userId, string $role = 'participant'): self
    {
        $now = now();
        $payload = [
            'participant_role' => $role,
            'joined_at' => $now,
            'left_at' => null,
            'last_seen_at' => $now,
            'is_online' => 1,
        ];

        if (Schema::hasColumn($this->getTable(), 'break_out_at')) {
            $payload['break_out_at'] = null;
        }

        $row = static::query()->firstOrNew([
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);

        if (! $row->exists && Schema::hasColumn($this->getTable(), 'breakout_room_index')) {
            $payload['breakout_room_index'] = null;
        }

        $row->fill($payload);
        $row->save();

        return $row;
    }

    public function leaveRoom(int $roomId, int $userId): void
    {
        $now = now();
        $payload = [
            'is_online' => 0,
            'left_at' => $now,
            'last_seen_at' => $now,
        ];

        if (Schema::hasColumn($this->getTable(), 'break_out_at')) {
            $payload['break_out_at'] = null;
        }

        static::query()->where('room_id', $roomId)->where('user_id', $userId)->update($payload);
    }

    public function touchPresence(int $roomId, int $userId): void
    {
        static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->update([
                'last_seen_at' => now(),
                'is_online' => 1,
            ]);
    }

    public function markStaleOffline(int $roomId, int $timeoutSeconds): void
    {
        $cutoff = now()->subSeconds(max(30, $timeoutSeconds));

        static::query()
            ->where('room_id', $roomId)
            ->where('is_online', 1)
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $cutoff);
            })
            ->update(['is_online' => 0]);
    }

    public function countOnline(int $roomId, int $timeoutSeconds): int
    {
        $this->markStaleOffline($roomId, $timeoutSeconds);
        $cutoff = now()->subSeconds(max(30, $timeoutSeconds));

        return static::query()
            ->where('room_id', $roomId)
            ->where('is_online', 1)
            ->where('last_seen_at', '>=', $cutoff)
            ->count();
    }

    public function onlineParticipant(int $roomId, int $userId, int $timeoutSeconds): ?self
    {
        $this->markStaleOffline($roomId, $timeoutSeconds);
        $cutoff = now()->subSeconds(max(30, $timeoutSeconds));

        return static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('is_online', 1)
            ->where('last_seen_at', '>=', $cutoff)
            ->first();
    }

    public function findForRoom(int $roomId, int $userId): ?self
    {
        return static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function onlineList(int $roomId, int $timeoutSeconds): array
    {
        $this->markStaleOffline($roomId, $timeoutSeconds);
        $cutoff = now()->subSeconds(max(30, $timeoutSeconds));

        return static::query()
            ->where('room_id', $roomId)
            ->where('is_online', 1)
            ->where('last_seen_at', '>=', $cutoff)
            ->with('user:id,full_name,username,role')
            ->orderBy('participant_role')
            ->get()
            ->map(function (self $row): array {
                $user = $row->user;

                return [
                    'userId' => (int) $row->user_id,
                    'name' => (string) ($user?->name ?: $user?->username ?: 'User'),
                    'role' => (string) ($row->participant_role ?? 'participant'),
                    'userRole' => (string) ($user?->role ?? 'student'),
                    'onBreak' => $row->break_out_at !== null,
                    'breakOutAt' => optional($row->break_out_at)?->format('Y-m-d H:i:s'),
                    'breakoutRoom' => (int) ($row->breakout_room_index ?? 0) ?: null,
                ];
            })
            ->all();
    }

    /**
     * @param  list<int>  $candidateIds
     */
    public static function isTeacherPresent(int $roomId, array $candidateIds, int $timeoutSeconds): bool
    {
        if ($roomId <= 0) {
            return false;
        }

        $cutoff = now()->subSeconds(max(30, $timeoutSeconds));
        $candidateIds = array_values(array_filter(array_map('intval', $candidateIds), static fn (int $id): bool => $id > 0));

        $query = static::query()
            ->where('room_id', $roomId)
            ->where('is_online', 1)
            ->where('last_seen_at', '>=', $cutoff);

        if ($candidateIds !== []) {
            $found = (clone $query)->whereIn('user_id', $candidateIds)->exists();

            if ($found) {
                return true;
            }
        }

        return $query->whereIn('participant_role', ['host', 'moderator'])->exists();
    }

    public function setBreakoutRoom(int $roomId, int $userId, ?int $index): void
    {
        if (! Schema::hasColumn($this->getTable(), 'breakout_room_index')) {
            return;
        }

        static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->update(['breakout_room_index' => $index !== null && $index > 0 ? $index : null]);
    }

    public function clearBreakouts(int $roomId): void
    {
        if (! Schema::hasColumn($this->getTable(), 'breakout_room_index')) {
            return;
        }

        static::query()->where('room_id', $roomId)->update(['breakout_room_index' => null]);
    }

    public function requestBreakOut(int $roomId, int $userId): ?string
    {
        if (! Schema::hasColumn($this->getTable(), 'break_out_at')) {
            return null;
        }

        $now = now();
        $updated = static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->update(['break_out_at' => $now, 'last_seen_at' => $now, 'is_online' => 1]);

        return $updated > 0 ? $now->format('Y-m-d H:i:s') : null;
    }

    public function returnFromBreak(int $roomId, int $userId): void
    {
        if (! Schema::hasColumn($this->getTable(), 'break_out_at')) {
            return;
        }

        static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->update(['break_out_at' => null, 'last_seen_at' => now(), 'is_online' => 1]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeBreakouts(int $roomId, int $timeoutSeconds): array
    {
        return array_values(array_filter(
            $this->onlineList($roomId, $timeoutSeconds),
            static fn (array $row): bool => ! empty($row['onBreak'])
        ));
    }

    /**
     * @return array<int, list<array{userId: int, name: string}>>
     */
    public function breakoutRoomGroups(int $roomId, int $roomCount, int $timeoutSeconds): array
    {
        $groups = [];

        for ($i = 1; $i <= $roomCount; $i++) {
            $groups[$i] = [];
        }

        foreach ($this->onlineList($roomId, $timeoutSeconds) as $row) {
            $index = (int) ($row['breakoutRoom'] ?? 0);

            if ($index < 1 || $index > $roomCount) {
                continue;
            }

            $groups[$index][] = [
                'userId' => (int) ($row['userId'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        return $groups;
    }
}
