<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory;

    public const STATUS_UNUSED = 'unused';
    public const STATUS_USED = 'used';

    protected $fillable = [
        'code',
        'student_name',
        'phone',
        'dob',
        'status',
        'image_path',
        'used_at',
        'used_by',
        'created_by',
        'note',
    ];

    protected $casts = [
        'dob' => 'date',
        'used_at' => 'datetime',
    ];

    public function userUsed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isUsed(): bool
    {
        return $this->status === self::STATUS_USED;
    }

    public function isUnused(): bool
    {
        return $this->status === self::STATUS_UNUSED;
    }

    public function statusText(): string
    {
        return $this->isUsed() ? 'ĐÃ SỬ DỤNG' : 'CHƯA SỬ DỤNG';
    }

    public function imageUrl(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://'])) {
            return $this->image_path;
        }

        return asset($this->image_path);
    }

    /**
     * Generate a unique 8-character voucher code e.g. "SN-98765"
     */
    public static function generateUniqueCode(string $prefix = 'SN-'): string
    {
        do {
            // SN- (3 chars) + 5 digits/chars = 8 chars
            // Use uppercase letters and numbers excluding confusing chars (O, 0, I, 1) or 5 digits
            $randomNumber = str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            $code = $prefix . $randomNumber;
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
