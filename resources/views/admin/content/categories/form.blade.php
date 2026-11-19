@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $row ? 'Sửa chuyên mục' : 'Thêm chuyên mục' }}</h1>
  <form method="post" action="{{ $row ? route('admin.'.$module.'.categories.update', $row) : route('admin.'.$module.'.categories.store') }}">
    @csrf
    <x-sunny.input name="name" label="Tên" :value="$row->name ?? ''" :required="true" />
    <x-sunny.input name="slug" label="Slug" :value="$row->slug ?? ''" />

    <x-sunny.select name="parent_id" label="Chuyên mục cha" :selected="$row->parent_id ?? '0'">
      <option value="0">— Gốc —</option>
      @foreach ($parents ?? [] as $p)
        <option value="{{ $p->id }}" @selected((string) old('parent_id', $row->parent_id ?? '0') === (string) $p->id)>{{ $p->name }}</option>
      @endforeach
    </x-sunny.select>

    <x-sunny.input name="logo_url" label="Logo URL" :value="$row->logo_url ?? ''" />
    <x-sunny.input name="cover_image" label="Cover URL" :value="$row->cover_image ?? ''" />
    <x-sunny.input name="official_website_url" label="Website chính thức" :value="$row->official_website_url ?? ''" />

    @if (($cfg['scope'] ?? '') === 'school')
      <h2 class="h5 mt-3">Bản đồ</h2>
      <x-sunny.input name="map_lat" label="Vĩ độ" :value="$row->map_lat ?? ''" inputmode="decimal" />
      <x-sunny.input name="map_lng" label="Kinh độ" :value="$row->map_lng ?? ''" inputmode="decimal" />
      <x-sunny.input name="map_region" label="Vùng" :value="$row->map_region ?? ''" />
    @endif

    <x-sunny.textarea class="js-richtext" name="description" label="Mô tả / HTML" :rows="10" :value="$row->description ?? ''" />

    <div class="form-check mt-2">
      <input type="hidden" name="is_active" value="0">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))>
      <label class="form-check-label">Kích hoạt</label>
    </div>

    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>
@endsection
@section('scripts')
  @include('admin.partials.tinymce')
@endsection
