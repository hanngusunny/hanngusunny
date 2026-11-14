<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentPost extends Model
{
    protected $table = 'content_post';

    protected $fillable = [
        'scope',
        'category_id',
        'template_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'featured_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'seo_robots',
        'og_title',
        'og_description',
        'og_image',
        'view_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'category_id' => 'integer',
        'template_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContentComment::class, 'post_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContentTag::class, 'content_post_tag', 'post_id', 'tag_id');
    }

    public function scopeInScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function featuredImageUrl(): string
    {
        $path = trim((string) $this->featured_image);
        if ($path === '') {
            return '';
        }
        return \App\Support\PublicUrl::to($path);
    }

    public function publicUrl(): string
    {
        return match ($this->scope) {
            'events' => url('/su-kien/'.$this->slug),
            'major' => url('/nganh/'.$this->slug),
            'school' => $this->category && $this->category->slug
                ? url('/truong/'.$this->category->slug.'/'.$this->slug)
                : url('/truong/'.$this->slug),
            default => $this->category && $this->category->slug
                ? url('/'.$this->category->slug.'/'.$this->slug)
                : url('/post/'.$this->slug),
        };
    }
}
