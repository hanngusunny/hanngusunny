<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostCommentController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', ContentComment::STATUS_PENDING);
        $allowed = ['all', ContentComment::STATUS_PENDING, ContentComment::STATUS_APPROVED, ContentComment::STATUS_SPAM, ContentComment::STATUS_TRASH];
        if (! in_array($status, $allowed, true)) {
            $status = ContentComment::STATUS_PENDING;
        }
        $q = ContentComment::query()->with('post')->orderByDesc('id');
        if ($status !== 'all') {
            $q->where('status', $status);
        }

        return view('admin.comments.index', [
            'pageTitle' => 'Bình luận',
            'adminNavActive' => 'post_comments',
            'comments' => $q->paginate(30),
            'status' => $status,
            'counts' => [
                'pending' => ContentComment::query()->where('status', ContentComment::STATUS_PENDING)->count(),
                'approved' => ContentComment::query()->where('status', ContentComment::STATUS_APPROVED)->count(),
                'spam' => ContentComment::query()->where('status', ContentComment::STATUS_SPAM)->count(),
                'trash' => ContentComment::query()->where('status', ContentComment::STATUS_TRASH)->count(),
            ],
        ]);
    }

    public function setStatus(ContentComment $post_comment, string $status): RedirectResponse
    {
        $allowed = [ContentComment::STATUS_APPROVED, ContentComment::STATUS_SPAM, ContentComment::STATUS_TRASH, ContentComment::STATUS_PENDING];
        abort_unless(in_array($status, $allowed, true), 404);
        $post_comment->update(['status' => $status]);

        return back()->with('ok', 'Đã cập nhật trạng thái.');
    }

    public function destroy(ContentComment $post_comment): RedirectResponse
    {
        $post_comment->delete();

        return back()->with('ok', 'Đã xóa.');
    }
}
