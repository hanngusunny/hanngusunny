<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteBlock extends Model
{
    protected $table = 'site_block';

    protected $fillable = ['name', 'slug', 'block_type', 'config_json', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
