<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolRule extends Model
{
    protected $fillable = [
        'title',
        'content',
        'updated_by',
    ];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function editorName(): string
    {
        $editor = $this->editor;

        if ($editor === null) {
            return '';
        }

        return trim((string) ($editor->full_name ?: $editor->username));
    }
}
