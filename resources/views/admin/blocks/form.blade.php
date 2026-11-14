@extends('admin.layouts.app')
@section('content')
  @php
    $htmlVal = old('html', '');
    if ($htmlVal === '' && !empty($row->config_json)) {
      $decoded = json_decode((string) $row->config_json, true);
      $htmlVal = is_array($decoded) ? (string) ($decoded['html'] ?? '') : '';
    }
  @endphp
  <h1 class="admin-page-title">{{ $row ? 'Sửa' : 'Thêm' }} block</h1>
  <form method="post" action="{{ $row ? route('admin.blocks.update', $row) : route('admin.blocks.store') }}">
    @csrf
    <input class="form-control" name="name" value="{{ old('name', $row->name ?? '') }}" placeholder="Tên" required>
    <input class="form-control mt-2" name="slug" value="{{ old('slug', $row->slug ?? '') }}" placeholder="Slug">
    <input class="form-control mt-2" name="block_type" value="{{ old('block_type', $row->block_type ?? 'intro') }}">
    <label class="form-label mt-2">HTML hiển thị</label>
    <textarea class="form-control" name="html" rows="8">{{ $htmlVal }}</textarea>
    <label class="form-label mt-2">JSON cấu hình (tùy chọn)</label>
    <textarea class="form-control" name="config_json" rows="6">{{ old('config_json', $row->config_json ?? '') }}</textarea>
    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))><label class="form-check-label">Active</label></div>
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
