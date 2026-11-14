<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnlineRoomJoinRequest extends Model
{
    protected $table = 'online_room_join_requests';

    protected $fillable = [
        'room_id',
        'user_id',
        'guest_name',
        'guest_token',
        'is_guest',
        'status',
        'requested_at',
        'reviewed_at',
        'reviewed_by',
        'review_note',
    ];

    protected $casts = [
        'is_guest' => 'boolean',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public static function ready(): bool
    {
        return Schema::hasTable('online_room_join_requests');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(OnlineRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function createPending(int $roomId, int $userId, bool $isGuest = false, ?string $guestName = null): ?self
    {
        if (! self::ready() || $roomId <= 0 || $userId <= 0) {
            return null;
        }

        $now = now();

        static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return static::query()->create([
            'room_id' => $roomId,
            'user_id' => $userId,
            'guest_name' => $guestName,
            'guest_token' => bin2hex(random_bytes(24)),
            'is_guest' => $isGuest ? 1 : 0,
            'status' => 'pending',
            'requested_at' => $now,
        ]);
    }

    public static function getActiveForUser(int $roomId, int $userId): ?self
    {
        if (! self::ready() || $roomId <= 0 || $userId <= 0) {
            return null;
        }

        return static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->orderByDesc('id')
            ->first();
    }

    public static function hasApprovedAccess(int $roomId, int $userId): bool
    {
        if (! self::ready() || $roomId <= 0 || $userId <= 0) {
            return false;
        }

        return static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->exists();
    }

    public static function hasLobbyAccess(int $roomId, int $userId): bool
    {
        if (! self::ready() || $roomId <= 0 || $userId <= 0) {
            return false;
        }

        return static::query()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function pendingForModerator(int $userId, string $role, int $limit = 30): array
    {
        if (! self::ready() || ! Schema::hasTable('online_rooms')) {
            return [];
        }

        $limit = max(1, $limit);
        $query = DB::table('online_room_join_requests as j')
            ->join('online_rooms as r', 'r.id', '=', 'j.room_id')
            ->leftJoin('users as u', 'u.id', '=', 'j.user_id')
            ->select([
                'j.id',
                'j.room_id',
                'j.user_id',
                'j.guest_name',
                'j.is_guest',
                'j.status',
                'j.requested_at',
                'r.title as room_title',
                'u.full_name',
                'u.username',
                'u.role as user_role',
            ])
            ->where('j.status', 'pending')
            ->orderByDesc('j.requested_at')
            ->limit($limit);

        if ($role === 'admin') {
            return $query->get()->map(static fn ($row): array => (array) $row)->all();
        }

        if ($role === 'teacher' && $userId > 0) {
            $query->leftJoin('courses as c', 'c.online_room_id', '=', 'r.id')
                ->where(static function ($inner) use ($userId): void {
                    $inner->where('r.host_id', $userId)
                        ->orWhere('c.teacher_id', $userId);
                });

            return $query->get()->map(static fn ($row): array => (array) $row)->all();
        }

        return [];
    }

    /**
     * @return list<self>
     */
    public static function pendingForRoom(int $roomId): array
    {
        if (! self::ready() || $roomId <= 0) {
            return [];
        }

        return static::query()
            ->with('user:id,full_name,username,role')
            ->where('room_id', $roomId)
            ->where('status', 'pending')
            ->orderBy('requested_at')
            ->get()
            ->all();
    }

    public function approve(int $reviewerId): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId > 0 ? $reviewerId : null,
        ]);
    }

    public function reject(int $reviewerId, ?string $note = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $note = trim((string) $note);

        return $this->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId > 0 ? $reviewerId : null,
            'review_note' => $note !== '' ? $note : null,
        ]);
    }

    public function displayName(): string
    {
        if ($this->is_guest && filled($this->guest_name)) {
            return (string) $this->guest_name;
        }

        return (string) ($this->user?->name ?: $this->user?->username ?: 'Người tham gia');
    }
}
