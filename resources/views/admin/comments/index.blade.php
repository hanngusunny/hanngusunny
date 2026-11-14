@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">Bình luận</h1>
  <p>
    <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}">Chờ ({{ $counts['pending'] }})</a> ·
    <a href="{{ route('admin.comments.index', ['status' => 'approved']) }}">Duyệt ({{ $counts['approved'] }})</a> ·
    <a href="{{ route('admin.comments.index', ['status' => 'spam']) }}">Spam</a>
  </p>
  @foreach ($comments as $c)
    <div class="mb-3">
      <strong>{{ $c->author_name }}</strong> — {{ $c->status }}
      <p>{{ $c->body }}</p>
      <form method="post" action="{{ route('admin.comments.approve', $c) }}" class="inline">@csrf<button class="btn btn-sm" type="submit">Duyệt</button></form>
      <form method="post" action="{{ route('admin.comments.spam', $c) }}" class="inline">@csrf<button class="btn btn-link" type="submit">Spam</button></form>
      <form method="post" action="{{ route('admin.comments.destroy', $c) }}" class="inline">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
    </div>
  @endforeach
  {{ $comments->links() }}
@endsection
