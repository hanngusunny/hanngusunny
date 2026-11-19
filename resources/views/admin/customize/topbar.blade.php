@extends('admin.layouts.app')

@section('content')
  @if (session('ok'))
    <p class="alert alert-success">{{ session('ok') }}</p>
  @endif
  <h1 class="admin-page-title">Topbar frontend</h1>
  <p><a href="{{ route('admin.customize.identity') }}">Nhận diện</a></p>
  <form method="post" action="{{ route('admin.customize.topbar.save') }}">
    @csrf
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="frontend_topbar_enabled" value="1" @checked(old('frontend_topbar_enabled', $settings['frontend_topbar_enabled'] ?? '1') === '1')>
      <label class="form-check-label">Bật topbar</label>
    </div>
    <x-sunny.input name="frontend_topbar_height_px" label="Chiều cao (px)" :value="$settings['frontend_topbar_height_px'] ?? '56'" />
    <x-sunny.input name="frontend_topbar_bg_color" label="Màu nền" :value="$settings['frontend_topbar_bg_color'] ?? ''" />
    <x-sunny.input name="frontend_topbar_hotline" label="Hotline" :value="$settings['frontend_topbar_hotline'] ?? ''" />
    <x-sunny.input name="frontend_topbar_tagline" label="Tagline" :value="$settings['frontend_topbar_tagline'] ?? ''" />
    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>
@endsection
