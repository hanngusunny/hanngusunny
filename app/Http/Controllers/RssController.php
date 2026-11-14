<?php

namespace App\Http\Controllers;

use App\Models\ContentPost;
use Illuminate\Http\Response;

class RssController extends Controller
{
    public function index(): Response
    {
        $posts = ContentPost::query()->with('category')->inScope('post')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0"><channel>';
        $xml .= '<title>'.e(config('app.name')).'</title>';
        $xml .= '<link>'.e(url('/')).'</link>';
        $xml .= '<description>Tin tức</description>';
        foreach ($posts as $post) {
            $xml .= '<item>';
            $xml .= '<title>'.e($post->title).'</title>';
            $xml .= '<link>'.e($post->publicUrl()).'</link>';
            $xml .= '<guid>'.e($post->publicUrl()).'</guid>';
            if ($post->published_at) {
                $xml .= '<pubDate>'.e($post->published_at->toRfc2822String()).'</pubDate>';
            }
            $xml .= '<description>'.e(strip_tags((string) $post->excerpt)).'</description>';
            $xml .= '</item>';
        }
        $xml .= '</channel></rss>';

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
