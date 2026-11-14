<?php

namespace App\Http\Controllers;

use App\Models\ContentComment;
use App\Models\ContentPost;
use App\Services\Settings\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicCommentController extends Controller
{
    public function storeSchool(Request $request, string $category, string $slug): RedirectResponse
    {
        return $this->save($request, 'school', $slug);
    }

    public function storeSchoolLegacy(Request $request, string $slug): RedirectResponse
    {
        return $this->save($request, 'school', $slug);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        return $this->save($request, 'post', $slug);
    }

    public function storeCategoryPost(Request $request, string $category, string $slug): RedirectResponse
    {
        $post = ContentPost::query()->inScope('post')->with('category')->where('slug', $slug)->where('status', 'published')->firstOrFail();
        abort_unless($post->category && $post->category->slug === $category, 404);

        return $this->save($request, 'post', $slug);
    }

    public function storeEvent(Request $request, string $slug): RedirectResponse
    {
        return $this->save($request, 'events', $slug);
    }

    public function storeMajor(Request $request, string $slug): RedirectResponse
    {
        return $this->save($request, 'major', $slug);
    }

    private function save(Request $request, string $scope, string $slug): RedirectResponse
    {
        if (app(SiteSettingsService::class)->get('public_comments_enabled', '1') !== '1') {
            abort(403);
        }
        $post = ContentPost::query()->inScope($scope)->where('slug', $slug)->where('status', 'published')->firstOrFail();
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:120'],
            'author_email' => ['nullable', 'email', 'max:191'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
        ContentComment::query()->create([
            'post_id' => $post->id,
            'scope' => $scope,
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'] ?? '',
            'body' => $data['body'],
            'status' => ContentComment::STATUS_PENDING,
            'ip' => (string) $request->ip(),
        ]);

        return back()->with('comment_ok', 'Bình luận đang chờ duyệt.');
    }
}
