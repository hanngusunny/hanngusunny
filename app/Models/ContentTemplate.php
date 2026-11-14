<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTemplate extends Model
{
    protected $table = 'content_template';

    protected $fillable = [
        'scope', 'page_type', 'slug', 'name', 'rel_path', 'content', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
