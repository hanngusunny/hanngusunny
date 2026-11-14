@extends('admin.layouts.app')

@section('content')
  @if (session('ok'))
    <p class="alert alert-success">{{ session('ok') }}</p>
  @endif
  <h1 class="admin-page-title">Topbar frontend</h1>
  <p><a href="{{ route('admin.customize.identity') }}">Nhận diện</a></p>
  <form method="post" action="{{ route('admin.customize.topbar.save') }}">
    @csrf
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="frontend_topbar_enabled" value="1" @checked(old('frontend_topbar_enabled', $settings['frontend_topbar_enabled'] ?? '1') === '1')>
      <label class="form-check-label">Bật topbar</label>
    </div>
    <label class="form-label">Chiều cao (px)</label>
    <input class="form-control" name="frontend_topbar_height_px" value="{{ old('frontend_topbar_height_px', $settings['frontend_topbar_height_px'] ?? '56') }}">
    <label class="form-label">Màu nền</label>
    <input class="form-control" name="frontend_topbar_bg_color" value="{{ old('frontend_topbar_bg_color', $settings['frontend_topbar_bg_color'] ?? '') }}">
    <label class="form-label">Hotline</label>
    <input class="form-control" name="frontend_topbar_hotline" value="{{ old('frontend_topbar_hotline', $settings['frontend_topbar_hotline'] ?? '') }}">
    <label class="form-label">Tagline</label>
    <input class="form-control" name="frontend_topbar_tagline" value="{{ old('frontend_topbar_tagline', $settings['frontend_topbar_tagline'] ?? '') }}">
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
