@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">{{ $category->name }}</h1>
  <form method="post" action="{{ route('admin.slider.slides.store', $category) }}">@csrf
    <x-sunny.input name="image_desktop" placeholder="URL ảnh desktop" />
    <x-sunny.input name="image_mobile" placeholder="URL ảnh mobile" class="mt-1" />
    <x-sunny.input name="link_url" placeholder="Link" class="mt-1" />
    <textarea class="sn-textarea mt-1" name="content" placeholder="Nội dung"></textarea>
    <x-sunny.button type="submit" class="mt-2">Thêm slide</x-sunny.button>
  </form>
  @foreach ($slides as $s)
    <form method="post" action="{{ route('admin.slider.slides.update', $s) }}" class="mt-3">@csrf
      <input class="sn-input" name="image_desktop" value="{{ $s->image_desktop }}">
      <input class="sn-input mt-1" name="image_mobile" value="{{ $s->image_mobile }}">
      <input class="sn-input mt-1" name="link_url" value="{{ $s->link_url }}">
      <textarea class="sn-textarea mt-1" name="content">{{ $s->content }}</textarea>
      <x-sunny.button size="sm" type="submit" class="mt-1">Lưu</x-sunny.button>
    </form>
    <form method="post" action="{{ route('admin.slider.slides.up', $s) }}" class="inline">@csrf<x-sunny.button variant="text" type="submit">↑</x-sunny.button></form>
    <form method="post" action="{{ route('admin.slider.slides.down', $s) }}" class="inline">@csrf<x-sunny.button variant="text" type="submit">↓</x-sunny.button></form>
    <form method="post" action="{{ route('admin.slider.slides.destroy', $s) }}">@csrf<x-sunny.button variant="text" type="submit">Xóa</x-sunny.button></form>
  @endforeach
@endsection
