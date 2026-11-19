@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">{{ $row->name }}</h1>
  <form method="post" action="{{ route('admin.templates.update', $row) }}">@csrf
    <x-sunny.input name="name" label="Tên" :value="$row->name" :required="true" />
    <x-sunny.textarea name="content" label="Nội dung (lưu DB, không execute)" :rows="16" :value="$row->content" />
    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>
@endsection
