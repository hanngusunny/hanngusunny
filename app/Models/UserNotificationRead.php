<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UserNotificationRead extends Model
{
    protected $table = 'user_notification_reads';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'notification_key',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public static function ready(): bool
    {
        return Schema::hasTable('user_notification_reads');
    }

    /**
     * @return list<string>
     */
    public static function getKeysForUser(int $userId): array
    {
        if ($userId <= 0 || ! self::ready()) {
            return [];
        }

        try {
            return static::query()
                ->where('user_id', $userId)
                ->pluck('notification_key')
                ->map(static fn ($key): string => (string) $key)
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    public static function isRead(int $userId, string $notificationKey): bool
    {
        if ($userId <= 0 || $notificationKey === '' || ! self::ready()) {
            return false;
        }

        return static::query()
            ->where('user_id', $userId)
            ->where('notification_key', $notificationKey)
            ->exists();
    }

    public static function markRead(int $userId, string $notificationKey): void
    {
        if ($userId <= 0 || $notificationKey === '' || ! self::ready()) {
            return;
        }

        if (self::isRead($userId, $notificationKey)) {
            return;
        }

        try {
            static::query()->create([
                'user_id' => $userId,
                'notification_key' => $notificationKey,
                'read_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  list<string>  $notificationKeys
     */
    public static function markManyRead(int $userId, array $notificationKeys): void
    {
        if ($userId <= 0 || empty($notificationKeys) || ! self::ready()) {
            return;
        }

        try {
            $existingKeys = static::query()
                ->where('user_id', $userId)
                ->whereIn('notification_key', $notificationKeys)
                ->pluck('notification_key')
                ->all();

            $existingSet = array_flip($existingKeys);
            $now = now();
            $rowsToInsert = [];

            foreach ($notificationKeys as $key) {
                $trimmedKey = trim((string) $key);
                if ($trimmedKey !== '' && ! isset($existingSet[$trimmedKey])) {
                    $rowsToInsert[] = [
                        'user_id' => $userId,
                        'notification_key' => $trimmedKey,
                        'read_at' => $now,
                    ];
                    $existingSet[$trimmedKey] = true;
                }
            }

            if ($rowsToInsert !== []) {
                static::query()->insert($rowsToInsert);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
