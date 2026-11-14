@extends('admin.layouts.app')

@section('content')
  @if (session('settings_success'))
    <p class="alert alert-success">{{ session('settings_success') }}</p>
  @endif
  <h1 class="admin-page-title">{{ __('admin_settings.settings_heading') }}</h1>
  <form method="post" action="{{ route('admin.settings.update') }}">
    @csrf
    <label class="form-label">{{ __('admin_settings.settings_site_name') }}</label>
    <input class="form-control" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}">
    <label class="form-label">{{ __('admin_settings.settings_tagline') }}</label>
    <input class="form-control" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}">
    <label class="form-label">{{ __('admin_settings.settings_contact_email') }}</label>
    <input class="form-control" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
    <label class="form-label">{{ __('admin_settings.settings_contact_phone') }}</label>
    <input class="form-control" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}">
    <label class="form-label">Timezone</label>
    <select class="form-control" name="timezone">
      @foreach ($timezoneOptions as $id => $label)
        <option value="{{ $id }}" @selected(old('timezone', $settings['timezone'] ?? '') === $id)>{{ $label }}</option>
      @endforeach
    </select>
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $settings['maintenance_mode'] ?? '0') === '1')>
      <label class="form-check-label">{{ __('admin_settings.settings_maintenance') }}</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="coming_soon_enabled" value="1" @checked(old('coming_soon_enabled', $settings['coming_soon_enabled'] ?? '0') === '1')>
      <label class="form-check-label">{{ __('admin_settings.settings_coming_soon') }}</label>
    </div>
    <label class="form-label">Coming soon until</label>
    <input class="form-control" name="coming_soon_until" value="{{ old('coming_soon_until', $settings['coming_soon_until'] ?? '') }}">
    <label class="form-label">Google Analytics ID</label>
    <input class="form-control" name="google_analytics_id" value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}">
    <label class="form-label">Google Tag Manager ID</label>
    <input class="form-control" name="google_tag_manager_id" value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id'] ?? '') }}">
    <label class="form-label">Chuyên mục sự kiện trang chủ</label>
    <select class="form-control" name="home_events_category_id">
      <option value="">—</option>
      @foreach ($eventCategories ?? [] as $c)
        <option value="{{ $c->id }}" @selected((string) old('home_events_category_id', $settings['home_events_category_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
    <div class="form-check mt-2">
      <input type="hidden" name="public_comments_enabled" value="0">
      <input class="form-check-input" type="checkbox" name="public_comments_enabled" value="1" @checked(old('public_comments_enabled', $settings['public_comments_enabled'] ?? '1') === '1')>
      <label class="form-check-label">Cho phép bình luận công khai</label>
    </div>
    <button class="btn btn-primary mt-3" type="submit">{{ __('admin_settings.settings_save') }}</button>
  </form>
@endsection
