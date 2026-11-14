<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'login_session_id',
        'action_type',
        'action_label',
        'http_method',
        'route_path',
        'url',
        'ip_address',
        'user_agent',
        'context_json',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        if (! $this->context_json) {
            return [];
        }

        $decoded = json_decode((string) $this->context_json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
