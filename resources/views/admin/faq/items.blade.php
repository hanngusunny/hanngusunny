@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">{{ $category->name }}</h1>
  <form method="post" action="{{ route('admin.faq.items.store', $category) }}" class="mb-4">@csrf
    <input class="form-control" name="question" placeholder="Câu hỏi" required>
    <textarea class="form-control mt-2" name="answer" placeholder="Trả lời"></textarea>
    <button class="btn btn-primary mt-2" type="submit">Thêm</button>
  </form>
  @foreach ($items as $item)
    <form method="post" action="{{ route('admin.faq.items.update', $item) }}" class="mb-3">@csrf
      <input class="form-control" name="question" value="{{ $item->question }}">
      <textarea class="form-control mt-1" name="answer">{{ $item->answer }}</textarea>
      <button class="btn btn-sm btn-primary mt-1" type="submit">Lưu</button>
    </form>
    <form method="post" action="{{ route('admin.faq.items.destroy', $item) }}">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
    <form class="inline" method="post" action="{{ route('admin.faq.items.up', $item) }}">@csrf<button class="btn btn-link" type="submit">↑</button></form>
    <form class="inline" method="post" action="{{ route('admin.faq.items.down', $item) }}">@csrf<button class="btn btn-link" type="submit">↓</button></form>
  @endforeach
@endsection
