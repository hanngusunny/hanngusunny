<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use App\Models\ContentPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $catSlug = trim((string) $request->query('category_slug', 'tin-tuc')) ?: 'tin-tuc';
        $limit = max(1, min(20, (int) $request->query('limit', 20)));
        $category = ContentCategory::query()->inScope('post')->where('slug', $catSlug)->where('is_active', 1)->first();
        if ($category === null) {
            return response()->json(['ok' => false, 'error' => 'Không tìm thấy chuyên mục tin tức hoặc chuyên mục đã tắt.'], 404)
                ->header('Cache-Control', 'private, no-store');
        }
        $posts = ContentPost::query()->inScope('post')->where('category_id', $category->id)->where('status', 'published')
            ->orderByDesc('published_at')->limit($limit)->get()
            ->map(function (ContentPost $row) use ($category) {
                $featured = trim((string) ($row->featured_image ?? ''));
                $ogImg = trim((string) ($row->og_image ?? ''));
                $row->setRelation('category', $category);

                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'excerpt_html' => (string) $row->excerpt,
                    'body_html' => (string) $row->body,
                    'featured_image' => $featured,
                    'featured_image_url' => $featured !== '' ? \App\Support\PublicUrl::to($featured) : '',
                    'og_image' => $ogImg,
                    'og_image_url' => $ogImg !== '' ? \App\Support\PublicUrl::to($ogImg) : '',
                    'published_at' => optional($row->published_at)?->toIso8601String(),
                    'updated_at' => optional($row->updated_at)?->toIso8601String(),
                    'category_id' => $row->category_id,
                    'category_slug' => $category->slug,
                    'category_name' => $category->name,
                    'url' => $row->publicUrl(),
                    'meta_title' => (string) ($row->meta_title ?? ''),
                    'meta_description' => (string) ($row->meta_description ?? ''),
                    'meta_keywords' => (string) ($row->meta_keywords ?? ''),
                    'view_count' => (int) ($row->view_count ?? 0),
                ];
            });

        return response()->json([
            'ok' => true,
            'limit' => $limit,
            'count' => $posts->count(),
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'page_url' => url('/'.$category->slug),
            ],
            'posts' => $posts,
        ])->header('Cache-Control', 'private, no-store');
    }
}
