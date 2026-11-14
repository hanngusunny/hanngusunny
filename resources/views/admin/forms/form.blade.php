@extends('admin.layouts.app')
@section('content')
  <h1 class="admin-page-title">{{ $row ? 'Sửa form' : 'Tạo form' }}</h1>
  <form method="post" action="{{ $row ? route('admin.forms.update', $row) : route('admin.forms.store') }}">
    @csrf
    <input class="form-control" name="name" value="{{ old('name', $row->name ?? '') }}" required>
    <input class="form-control mt-2" name="slug" value="{{ old('slug', $row->slug ?? '') }}" placeholder="slug">
    <textarea class="form-control mt-2" name="description">{{ old('description', $row->description ?? '') }}</textarea>
    <input class="form-control mt-2" name="success_message" value="{{ old('success_message', $row->success_message ?? '') }}">
    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))><label class="form-check-label">Active</label></div>
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
