<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use App\Models\ContentPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SchoolApiController extends Controller
{
    public function listSchools(Request $request): JsonResponse
    {
        $hubSlug = trim((string) $request->query('category_slug', ''));
        if ($hubSlug === '') {
            return response()->json(['ok' => false, 'error' => 'category_slug bắt buộc.'], 422);
        }
        $hub = ContentCategory::query()->inScope('school')->where('slug', $hubSlug)->where('is_active', 1)->first();
        if ($hub === null) {
            return response()->json(['ok' => false, 'error' => 'Không tìm thấy chuyên mục.'], 404);
        }
        $limit = max(1, min(8000, (int) $request->query('limit', 500)));
        $offset = max(0, (int) $request->query('offset', 0));
        $requireMap = in_array(strtolower((string) $request->query('has_map', '')), ['1', 'true', 'yes'], true);
        $q = ContentCategory::query()->inScope('school')->where('parent_id', $hub->id)->where('is_active', 1);
        if ($requireMap && Schema::hasColumn('content_category', 'map_lat')) {
            $q->whereNotNull('map_lat')->whereNotNull('map_lng');
        }
        $items = $q->orderBy('sort_order')->offset($offset)->limit($limit)->get()
            ->map(fn (ContentCategory $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'url' => url('/'.$row->slug),
                'logo_url' => $row->logo_url,
                'map_lat' => $row->map_lat,
                'map_lng' => $row->map_lng,
                'map_region' => $row->map_region ?? '',
            ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function listPosts(Request $request): JsonResponse
    {
        $limit = max(1, min(8000, (int) $request->query('limit', 500)));
        $offset = max(0, (int) $request->query('offset', 0));
        $slug = trim((string) $request->query('category_slug', ''));
        $includeChildren = in_array(strtolower((string) $request->query('include_children', '')), ['1', 'true', 'yes'], true);
        $requireMap = in_array(strtolower((string) $request->query('has_map', '')), ['1', 'true', 'yes'], true);

        $q = ContentPost::query()->inScope('school')
            ->with(['category.parent'])
            ->where('status', 'published')
            ->orderByDesc('published_at');

        if ($slug !== '') {
            $cat = ContentCategory::query()->inScope('school')->where('slug', $slug)->first();
            if ($includeChildren) {
                $ids = $cat ? $this->schoolCategoryIdsForApi($cat) : [];
                if ($ids === []) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('category_id', $ids);
                }
            } elseif ($cat) {
                $q->where('category_id', $cat->id);
            } else {
                $q->whereHas('category', fn ($c) => $c->where('slug', $slug));
            }
        }

        if ($requireMap && Schema::hasColumn('content_category', 'map_lat')) {
            $q->whereHas('category', function ($c) {
                $c->whereNotNull('map_lat')->whereNotNull('map_lng');
            });
        }

        $total = (clone $q)->count();
        $rows = $q->offset($offset)->limit($limit)->get();
        $schools = $rows->map(fn (ContentPost $row) => $this->mapSchoolPost($row))->values()->all();
        $posts = array_map(static fn (array $item) => [
            'id' => $item['id'],
            'title' => $item['title'],
            'slug' => $item['slug'],
            'url' => $item['url'],
        ], $schools);

        return response()->json([
            'ok' => true,
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($schools),
            'total' => $total,
            'has_more' => $offset + count($schools) < $total,
            'filters' => [
                'category_slug' => $slug !== '' ? $slug : null,
                'include_children' => $slug !== '' && $includeChildren,
                'has_map' => $requireMap,
            ],
            'posts' => $posts,
            'schools' => $schools,
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * @return list<int>
     */
    private function schoolCategoryIdsForApi(ContentCategory $anchor): array
    {
        $ids = $this->subtreeIds($anchor);
        $hubParent = (int) $anchor->parent_id;
        $hasKids = ContentCategory::query()->inScope('school')->where('parent_id', $anchor->id)->where('is_active', 1)->exists();
        $slug = trim((string) $anchor->slug);
        if ($slug !== '' && ($hubParent === 0 || ! $hasKids)) {
            $extra = ContentCategory::query()->inScope('school')->where('is_active', 1)
                ->where('slug', 'like', $slug.'-%')
                ->where('slug', '!=', $slug)
                ->pluck('id')->all();
            $ids = array_values(array_unique(array_merge($ids, $extra)));
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function subtreeIds(ContentCategory $anchor): array
    {
        $ids = [(int) $anchor->id];
        $pending = [(int) $anchor->id];
        $guard = 0;
        while ($pending !== [] && $guard++ < 40) {
            $kids = ContentCategory::query()->inScope('school')->whereIn('parent_id', $pending)->pluck('id')->all();
            $pending = array_values(array_diff($kids, $ids));
            $ids = array_values(array_unique(array_merge($ids, $kids)));
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSchoolPost(ContentPost $row): array
    {
        $cat = $row->category;
        $catSlug = $cat ? trim((string) $cat->slug) : '';
        $featured = trim((string) ($row->featured_image ?? ''));
        $ogImg = trim((string) ($row->og_image ?? ''));
        $lat = $this->floatOrNull($cat?->map_lat);
        $lng = $this->floatOrNull($cat?->map_lng);
        $logoRaw = $cat ? trim((string) $cat->logo_url) : '';
        $coverRaw = $cat ? trim((string) $cat->cover_image) : '';
        $parent = $cat?->parent;
        $parentBlock = null;
        if ($parent && (int) $parent->id > 0) {
            $pSl = trim((string) $parent->slug);
            $parentBlock = [
                'id' => (int) $parent->id,
                'name' => (string) $parent->name,
                'slug' => $pSl,
                'url' => $pSl !== '' ? url('/'.$pSl) : '',
            ];
        }

        return [
            'representation' => 'post',
            'id' => (int) $row->id,
            'title' => (string) $row->title,
            'slug' => (string) $row->slug,
            'excerpt_html' => (string) ($row->excerpt ?? ''),
            'body_html' => (string) ($row->body ?? ''),
            'featured_image' => $featured,
            'featured_image_url' => $this->absUrl($featured),
            'og_image' => $ogImg,
            'og_image_url' => $this->absUrl($ogImg),
            'published_at' => optional($row->published_at)?->toIso8601String(),
            'updated_at' => optional($row->updated_at)?->toIso8601String(),
            'meta_title' => (string) ($row->meta_title ?? ''),
            'meta_description' => (string) ($row->meta_description ?? ''),
            'meta_keywords' => (string) ($row->meta_keywords ?? ''),
            'seo_robots' => (string) ($row->seo_robots ?? ''),
            'og_title' => (string) ($row->og_title ?? ''),
            'og_description' => (string) ($row->og_description ?? ''),
            'view_count' => (int) ($row->view_count ?? 0),
            'template_id' => $row->template_id ? (int) $row->template_id : null,
            'url' => $catSlug !== ''
                ? url('/truong/'.$catSlug.'/'.$row->slug)
                : url('/truong/'.$row->slug),
            'category' => [
                'id' => (int) ($row->category_id ?? 0),
                'slug' => $catSlug,
                'name' => $cat ? (string) $cat->name : '',
                'parent_id' => (int) ($cat->parent_id ?? 0),
                'parent' => $parentBlock,
                'logo_url' => $logoRaw,
                'logo_url_absolute' => $this->absUrl($logoRaw),
                'cover_image' => $coverRaw,
                'cover_image_url' => $this->absUrl($coverRaw),
                'official_website_url' => $cat ? trim((string) $cat->official_website_url) : '',
                'map' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'region' => $cat ? trim((string) ($cat->map_region ?? '')) : '',
                    'has_coordinates' => $lat !== null && $lng !== null,
                ],
            ],
        ];
    }

    private function absUrl(string $raw): string
    {
        if ($raw === '') {
            return '';
        }
        return \App\Support\PublicUrl::to($raw);
    }

    private function floatOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (! is_numeric($v)) {
            return null;
        }

        return (float) $v;
    }
}
