<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineRoom extends Model
{
    protected $fillable = [
        'title',
        'description',
        'room_code',
        'host_id',
        'scheduled_start',
        'scheduled_end',
        'status',
        'access_type',
        'max_participants',
        'waiting_room',
        'room_password',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'waiting_room' => 'boolean',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'online_room_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OnlineRoomParticipant::class, 'room_id');
    }

    public function presetQuestions(): HasMany
    {
        return $this->hasMany(OnlineRoomPresetQuestion::class, 'room_id');
    }

    public static function generateRoomCode(int $id): string
    {
        return 'sunny-'.$id.'-'.bin2hex(random_bytes(4));
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'draft' => 'Nháp',
            'scheduled' => 'Đã lên lịch',
            'live' => 'Đang diễn ra',
            'ended' => 'Đã kết thúc',
            'cancelled' => 'Đã hủy',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /**
     * @return array<string, string>
     */
    public static function accessTypeLabels(): array
    {
        return [
            'all' => 'Tất cả người dùng',
            'students' => 'Học viên & giáo viên',
            'teachers' => 'Chỉ giáo viên',
        ];
    }

    public function accessTypeLabel(): string
    {
        return self::accessTypeLabels()[$this->access_type] ?? $this->access_type;
    }
}
