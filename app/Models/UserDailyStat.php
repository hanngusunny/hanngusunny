<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyStat extends Model
{
    protected $fillable = [
        'user_id',
        'stat_date',
        'active_seconds',
        'page_views',
        'action_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'active_seconds' => 'integer',
        'page_views' => 'integer',
        'action_count' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
