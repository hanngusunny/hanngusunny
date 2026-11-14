@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">{{ $category->name }}</h1>
  <form method="post" action="{{ route('admin.slider.slides.store', $category) }}">@csrf
    <input class="form-control" name="image_desktop" placeholder="URL ảnh desktop">
    <input class="form-control mt-1" name="image_mobile" placeholder="URL ảnh mobile">
    <input class="form-control mt-1" name="link_url" placeholder="Link">
    <textarea class="form-control mt-1" name="content" placeholder="Nội dung"></textarea>
    <button class="btn btn-primary mt-2" type="submit">Thêm slide</button>
  </form>
  @foreach ($slides as $s)
    <form method="post" action="{{ route('admin.slider.slides.update', $s) }}" class="mt-3">@csrf
      <input class="form-control" name="image_desktop" value="{{ $s->image_desktop }}">
      <input class="form-control mt-1" name="image_mobile" value="{{ $s->image_mobile }}">
      <input class="form-control mt-1" name="link_url" value="{{ $s->link_url }}">
      <textarea class="form-control mt-1" name="content">{{ $s->content }}</textarea>
      <button class="btn btn-sm btn-primary mt-1" type="submit">Lưu</button>
    </form>
    <form method="post" action="{{ route('admin.slider.slides.up', $s) }}" class="inline">@csrf<button class="btn btn-link" type="submit">↑</button></form>
    <form method="post" action="{{ route('admin.slider.slides.down', $s) }}" class="inline">@csrf<button class="btn btn-link" type="submit">↓</button></form>
    <form method="post" action="{{ route('admin.slider.slides.destroy', $s) }}">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
  @endforeach
@endsection
