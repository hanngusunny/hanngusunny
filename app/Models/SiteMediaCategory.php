<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteMediaCategory extends Model
{
    public $timestamps = false;

    protected $table = 'site_media_category';

    protected $fillable = ['parent_id', 'name', 'slug', 'sort_order', 'created_at'];

    public function media(): HasMany
    {
        return $this->hasMany(SiteMedia::class, 'category_id');
    }
}
