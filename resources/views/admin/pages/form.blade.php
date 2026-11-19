@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $row ? 'Sửa trang' : 'Thêm trang' }}</h1>
  <form method="post" action="{{ $row ? route('admin.site-pages.update', $row) : route('admin.site-pages.store') }}">
    @csrf
    <x-sunny.input name="name" label="Tên" :value="$row->name ?? ''" :required="true" />
    <x-sunny.input name="slug" label="Slug" :value="$row->slug ?? ''" />
    <x-sunny.input name="meta_title" label="SEO title" :value="$row->meta_title ?? ''" />
    <x-sunny.input name="meta_description" label="SEO description" :value="$row->meta_description ?? ''" />

    <x-sunny.textarea class="js-richtext" name="description" label="Nội dung HTML" :rows="16" :value="$row->description ?? ''" />

    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))>
      <label class="form-check-label">Công khai</label>
    </div>
    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>
@endsection
@section('scripts')
  @include('admin.partials.tinymce')
@endsection
