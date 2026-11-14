<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMedia extends Model
{
    public $timestamps = false;

    protected $table = 'site_media';

    protected $fillable = [
        'category_id', 'path', 'original_name', 'title', 'alt_text', 'mime', 'file_size', 'created_at',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SiteMediaCategory::class, 'category_id');
    }
}
