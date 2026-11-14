@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $row ? 'Sửa chuyên mục' : 'Thêm chuyên mục' }}</h1>
  <form method="post" action="{{ $row ? route('admin.'.$module.'.categories.update', $row) : route('admin.'.$module.'.categories.store') }}">
    @csrf
    <label class="form-label">Tên</label>
    <input class="form-control" name="name" value="{{ old('name', $row->name ?? '') }}" required>
    <label class="form-label">Slug</label>
    <input class="form-control" name="slug" value="{{ old('slug', $row->slug ?? '') }}">
    <label class="form-label">Chuyên mục cha</label>
    <select class="form-control" name="parent_id">
      <option value="0">— Gốc —</option>
      @foreach ($parents ?? [] as $p)
        <option value="{{ $p->id }}" @selected((string) old('parent_id', $row->parent_id ?? '0') === (string) $p->id)>{{ $p->name }}</option>
      @endforeach
    </select>
    <label class="form-label">Logo URL</label>
    <input class="form-control" name="logo_url" value="{{ old('logo_url', $row->logo_url ?? '') }}">
    <label class="form-label">Cover URL</label>
    <input class="form-control" name="cover_image" value="{{ old('cover_image', $row->cover_image ?? '') }}">
    <label class="form-label">Website chính thức</label>
    <input class="form-control" name="official_website_url" value="{{ old('official_website_url', $row->official_website_url ?? '') }}">
    @if (($cfg['scope'] ?? '') === 'school')
      <h2 class="h5 mt-3">Bản đồ</h2>
      <label class="form-label">Vĩ độ</label>
      <input class="form-control" name="map_lat" value="{{ old('map_lat', $row->map_lat ?? '') }}" inputmode="decimal">
      <label class="form-label">Kinh độ</label>
      <input class="form-control" name="map_lng" value="{{ old('map_lng', $row->map_lng ?? '') }}" inputmode="decimal">
      <label class="form-label">Vùng</label>
      <input class="form-control" name="map_region" value="{{ old('map_region', $row->map_region ?? '') }}">
    @endif
    <label class="form-label">Mô tả / HTML</label>
    <textarea class="form-control js-richtext" name="description" rows="10">{{ old('description', $row->description ?? '') }}</textarea>
    <div class="form-check mt-2">
      <input type="hidden" name="is_active" value="0">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))>
      <label class="form-check-label">Kích hoạt</label>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
@section('scripts')
  @include('admin.partials.tinymce')
@endsection
