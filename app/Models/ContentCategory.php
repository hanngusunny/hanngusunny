<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentCategory extends Model
{
    protected $table = 'content_category';

    protected $fillable = [
        'scope',
        'parent_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'seo_robots',
        'og_title',
        'og_description',
        'og_image',
        'logo_url',
        'cover_image',
        'official_website_url',
        'map_lat',
        'map_lng',
        'map_region',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ContentPost::class, 'category_id');
    }

    public function scopeInScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    public function coverImageUrl(): string
    {
        $path = trim((string) $this->cover_image);
        if ($path === '') {
            return '';
        }
        return \App\Support\PublicUrl::to($path);
    }
}
