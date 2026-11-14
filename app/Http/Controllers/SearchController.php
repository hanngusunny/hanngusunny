<?php

namespace App\Http\Controllers;

use App\Models\ContentPost;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings)
    {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $posts = collect();
        if (mb_strlen($q) >= 2) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $posts = ContentPost::query()->with('category')->where('status', 'published')
                ->where(function ($w) use ($like) {
                    $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like);
                })
                ->orderByDesc('published_at')
                ->paginate(15)
                ->withQueryString();
        }

        return view('public.search', [
            'site' => $this->settings->all(),
            'q' => $q,
            'posts' => $posts,
            'title' => 'Tìm kiếm',
            'canonicalUrl' => url('/tim-kiem'),
            'breadcrumbs' => [['label' => 'Tìm kiếm']],
            'loadNewsCardFit' => true,
        ]);
    }
}
