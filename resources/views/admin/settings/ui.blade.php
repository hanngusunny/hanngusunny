@extends('admin.layouts.app')

@section('content')
  @if (session('admin_ui_success'))
    <p class="alert alert-success">{{ session('admin_ui_success') }}</p>
  @endif
  <h1 class="admin-page-title">{{ __('admin_admin_settings.admin_ui_heading') }}</h1>
  <form method="post" action="{{ route('admin.admin-settings.update') }}">
    @csrf
    <label class="form-label">Ngôn ngữ mặc định</label>
    <select class="form-control" name="admin_default_language">
      @foreach ($languages as $code => $meta)
        <option value="{{ $code }}" @selected(old('admin_default_language', $settings['admin_default_language'] ?? '') === $code)>{{ $code }}</option>
      @endforeach
    </select>
    <label class="form-label">Màu accent</label>
    <input class="form-control" name="admin_accent_color" value="{{ old('admin_accent_color', $settings['admin_accent_color'] ?? '#3b82f6') }}">
    <label class="form-label">Login theme</label>
    <select class="form-control" name="admin_login_theme">
      <option value="light" @selected(old('admin_login_theme', $settings['admin_login_theme'] ?? '') === 'light')>light</option>
      <option value="dark" @selected(old('admin_login_theme', $settings['admin_login_theme'] ?? '') === 'dark')>dark</option>
    </select>
    <label class="form-label">Login preset</label>
    <select class="form-control" name="admin_login_color_preset">
      @foreach ($presets as $preset)
        <option value="{{ $preset }}" @selected(old('admin_login_color_preset', $settings['admin_login_color_preset'] ?? '') === $preset)>{{ $preset }}</option>
      @endforeach
    </select>
    <button class="btn btn-primary mt-3" type="submit">{{ __('admin_admin_settings.admin_ui_save') }}</button>
  </form>
@endsection
