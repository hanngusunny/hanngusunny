@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">{{ $row->name }}</h1>
  <form method="post" action="{{ route('admin.templates.update', $row) }}">@csrf
    <label class="form-label">Tên</label>
    <input class="form-control" name="name" value="{{ $row->name }}" required>
    <label class="form-label">Nội dung (lưu DB, không execute)</label>
    <textarea class="form-control" name="content" rows="16">{{ $row->content }}</textarea>
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
