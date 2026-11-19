@extends('admin.layouts.app')

@section('content')
  @if (session('admin_ui_success'))
    <p class="alert alert-success">{{ session('admin_ui_success') }}</p>
  @endif
  <h1 class="admin-page-title">{{ __('admin_admin_settings.admin_ui_heading') }}</h1>
  <form method="post" action="{{ route('admin.admin-settings.update') }}">
    @csrf
    <x-sunny.select name="admin_default_language" label="Ngôn ngữ mặc định" :selected="$settings['admin_default_language'] ?? ''">
      @foreach ($languages as $code => $meta)
        <option value="{{ $code }}" @selected(old('admin_default_language', $settings['admin_default_language'] ?? '') === $code)>{{ $code }}</option>
      @endforeach
    </x-sunny.select>

    <x-sunny.input name="admin_accent_color" label="Màu accent" :value="$settings['admin_accent_color'] ?? '#3b82f6'" />

    <x-sunny.select name="admin_login_theme" label="Login theme" :selected="$settings['admin_login_theme'] ?? ''">
      <option value="light" @selected(old('admin_login_theme', $settings['admin_login_theme'] ?? '') === 'light')>light</option>
      <option value="dark" @selected(old('admin_login_theme', $settings['admin_login_theme'] ?? '') === 'dark')>dark</option>
    </x-sunny.select>

    <x-sunny.select name="admin_login_color_preset" label="Login preset" :selected="$settings['admin_login_color_preset'] ?? ''">
      @foreach ($presets as $preset)
        <option value="{{ $preset }}" @selected(old('admin_login_color_preset', $settings['admin_login_color_preset'] ?? '') === $preset)>{{ $preset }}</option>
      @endforeach
    </x-sunny.select>

    <x-sunny.button type="submit" class="mt-3">{{ __('admin_admin_settings.admin_ui_save') }}</x-sunny.button>
  </form>
@endsection
