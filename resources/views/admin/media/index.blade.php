@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">Thư viện ảnh</h1>
  <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">@csrf
    <input type="file" name="file" required>
    <button class="btn btn-primary" type="submit">Tải lên</button>
  </form>
  <form method="post" action="{{ route('admin.media.store-url') }}" class="mt-3">@csrf
    <input class="form-control" name="url" placeholder="https://… ảnh từ URL">
    <button class="btn btn-outline-secondary mt-1" type="submit">Nhập từ URL</button>
  </form>
  <p class="text-sm mt-2">JSON picker (admin): <code>{{ url('/admin/media/json') }}</code></p>
  <div class="flex flex-wrap gap-3 mt-4">
    @foreach ($items as $m)
      <div>
        <img src="{{ asset($m->path) }}" alt="" width="120">
        <form method="post" action="{{ route('admin.media.destroy', $m) }}">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
      </div>
    @endforeach
  </div>
  {{ $items->links() }}
@endsection
