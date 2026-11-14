<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ApiToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'last_used_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array{token: string, expires_at: string}
     */
    public static function issueForUser(int $userId, int $ttlDays = 30): array
    {
        $plain = bin2hex(random_bytes(32));
        $expiresAt = Carbon::now()->addDays(max(1, $ttlDays));

        static::query()->create([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
            'created_at' => Carbon::now(),
        ]);

        return [
            'token' => $plain,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ];
    }

    public static function findValidByPlainToken(string $plainToken): ?self
    {
        $plainToken = trim($plainToken);

        if ($plainToken === '') {
            return null;
        }

        $row = static::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($row === null) {
            return null;
        }

        $row->last_used_at = Carbon::now();
        $row->save();

        return $row;
    }

    public static function revokePlainToken(string $plainToken): bool
    {
        $plainToken = trim($plainToken);

        if ($plainToken === '') {
            return false;
        }

        return static::query()->where('token_hash', hash('sha256', $plainToken))->delete() > 0;
    }
}
