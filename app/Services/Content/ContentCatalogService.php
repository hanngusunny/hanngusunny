<?php

namespace App\Services\Content;

use App\Models\ContentCategory;
use App\Models\ContentPost;
use App\Models\ContentTag;
use Illuminate\Support\Str;

class ContentCatalogService
{
    public function slugify(string $value): string
    {
        $slug = Str::slug($value);
        if ($slug === '') {
            $slug = 'item-'.substr(sha1($value.microtime()), 0, 8);
        }

        return substr($slug, 0, 159);
    }

    public function uniquePostSlug(string $scope, string $slug, ?int $ignoreId = null): string
    {
        $base = $this->slugify($slug);
        $try = $base;
        $i = 2;
        while (
            ContentPost::query()
                ->inScope($scope)
                ->where('slug', $try)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $try = substr($base, 0, 150).'-'.$i;
            $i++;
        }

        return $try;
    }

    public function uniqueCategorySlug(string $scope, string $slug, ?int $ignoreId = null): string
    {
        $base = $this->slugify($slug);
        $try = $base;
        $i = 2;
        while (
            ContentCategory::query()
                ->inScope($scope)
                ->where('slug', $try)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $try = substr($base, 0, 150).'-'.$i;
            $i++;
        }

        return $try;
    }

    public function uniqueTagSlug(string $scope, string $slug, ?int $ignoreId = null): string
    {
        $base = $this->slugify($slug);
        $try = $base;
        $i = 2;
        while (
            ContentTag::query()
                ->inScope($scope)
                ->where('slug', $try)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $try = substr($base, 0, 150).'-'.$i;
            $i++;
        }

        return $try;
    }
}
