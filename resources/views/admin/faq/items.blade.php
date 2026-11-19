@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">{{ $category->name }}</h1>
  <form method="post" action="{{ route('admin.faq.items.store', $category) }}" class="mb-4">@csrf
    <x-sunny.input name="question" placeholder="Câu hỏi" :required="true" />
    <textarea class="sn-textarea mt-2" name="answer" placeholder="Trả lời"></textarea>
    <x-sunny.button type="submit" class="mt-2">Thêm</x-sunny.button>
  </form>
  @foreach ($items as $item)
    <form method="post" action="{{ route('admin.faq.items.update', $item) }}" class="mb-3">@csrf
      <input class="sn-input" name="question" value="{{ $item->question }}">
      <textarea class="sn-textarea mt-1" name="answer">{{ $item->answer }}</textarea>
      <x-sunny.button size="sm" type="submit" class="mt-1">Lưu</x-sunny.button>
    </form>
    <form method="post" action="{{ route('admin.faq.items.destroy', $item) }}">@csrf<x-sunny.button variant="text" type="submit">Xóa</x-sunny.button></form>
    <form class="inline" method="post" action="{{ route('admin.faq.items.up', $item) }}">@csrf<x-sunny.button variant="text" type="submit">↑</x-sunny.button></form>
    <form class="inline" method="post" action="{{ route('admin.faq.items.down', $item) }}">@csrf<x-sunny.button variant="text" type="submit">↓</x-sunny.button></form>
  @endforeach
@endsection
