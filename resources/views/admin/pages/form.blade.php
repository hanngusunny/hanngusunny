@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $row ? 'Sửa trang' : 'Thêm trang' }}</h1>
  <form method="post" action="{{ $row ? route('admin.site-pages.update', $row) : route('admin.site-pages.store') }}">
    @csrf
    <label class="form-label">Tên</label>
    <input class="form-control" name="name" value="{{ old('name', $row->name ?? '') }}" required>
    <label class="form-label">Slug</label>
    <input class="form-control" name="slug" value="{{ old('slug', $row->slug ?? '') }}">
    <label class="form-label">SEO title</label>
    <input class="form-control" name="meta_title" value="{{ old('meta_title', $row->meta_title ?? '') }}">
    <label class="form-label">SEO description</label>
    <input class="form-control" name="meta_description" value="{{ old('meta_description', $row->meta_description ?? '') }}">
    <label class="form-label">Nội dung HTML</label>
    <textarea class="form-control js-richtext" name="description" rows="16">{{ old('description', $row->description ?? '') }}</textarea>
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))>
      <label class="form-check-label">Công khai</label>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
@section('scripts')
  @include('admin.partials.tinymce')
@endsection
