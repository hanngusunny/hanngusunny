<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentTag extends Model
{
    protected $table = 'content_tag';

    protected $fillable = [
        'scope',
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
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(ContentPost::class, 'content_post_tag', 'tag_id', 'post_id');
    }

    public function scopeInScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }
}
