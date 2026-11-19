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
    <x-sunny.input name="name" placeholder="Tên" :value="$row->name ?? ''" :required="true" />
    <x-sunny.input name="slug" placeholder="Slug" :value="$row->slug ?? ''" class="mt-2" />
    <x-sunny.input name="block_type" :value="$row->block_type ?? 'intro'" class="mt-2" />

    <x-sunny.textarea name="html" label="HTML hiển thị" :rows="8" :value="$htmlVal" class="mt-2" />
    <x-sunny.textarea name="config_json" label="JSON cấu hình (tùy chọn)" :rows="6" :value="$row->config_json ?? ''" class="mt-2" />

    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))>
      <label class="form-check-label">Active</label>
    </div>
    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>
@endsection
