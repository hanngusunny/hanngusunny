<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineRoomPresetQuestion extends Model
{
    protected $fillable = [
        'room_id',
        'question_text',
        'question_type',
        'options_json',
        'sort_order',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(OnlineRoom::class, 'room_id');
    }

    /**
     * @return list<string>
     */
    public function options(): array
    {
        if (! $this->options_json) {
            return [];
        }

        $decoded = json_decode((string) $this->options_json, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded), static fn (string $value): bool => $value !== ''));
    }
}
