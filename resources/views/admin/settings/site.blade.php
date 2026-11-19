@extends('admin.layouts.app')

@section('content')
  @if (session('settings_success'))
    <p class="alert alert-success">{{ session('settings_success') }}</p>
  @endif
  <h1 class="admin-page-title">{{ __('admin_settings.settings_heading') }}</h1>
  <form method="post" action="{{ route('admin.settings.update') }}">
    @csrf
    <x-sunny.input name="site_name" :label="__('admin_settings.settings_site_name')" :value="$settings['site_name'] ?? ''" />
    <x-sunny.input name="site_tagline" :label="__('admin_settings.settings_tagline')" :value="$settings['site_tagline'] ?? ''" />
    <x-sunny.input name="contact_email" :label="__('admin_settings.settings_contact_email')" :value="$settings['contact_email'] ?? ''" />
    <x-sunny.input name="contact_phone" :label="__('admin_settings.settings_contact_phone')" :value="$settings['contact_phone'] ?? ''" />

    <x-sunny.select name="timezone" label="Timezone" :selected="$settings['timezone'] ?? ''">
      @foreach ($timezoneOptions as $id => $label)
        <option value="{{ $id }}" @selected(old('timezone', $settings['timezone'] ?? '') === $id)>{{ $label }}</option>
      @endforeach
    </x-sunny.select>

    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $settings['maintenance_mode'] ?? '0') === '1')>
      <label class="form-check-label">{{ __('admin_settings.settings_maintenance') }}</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="coming_soon_enabled" value="1" @checked(old('coming_soon_enabled', $settings['coming_soon_enabled'] ?? '0') === '1')>
      <label class="form-check-label">{{ __('admin_settings.settings_coming_soon') }}</label>
    </div>

    <x-sunny.input name="coming_soon_until" label="Coming soon until" :value="$settings['coming_soon_until'] ?? ''" />
    <x-sunny.input name="google_analytics_id" label="Google Analytics ID" :value="$settings['google_analytics_id'] ?? ''" />
    <x-sunny.input name="google_tag_manager_id" label="Google Tag Manager ID" :value="$settings['google_tag_manager_id'] ?? ''" />

    <x-sunny.select name="home_events_category_id" label="Chuyên mục sự kiện trang chủ" :selected="$settings['home_events_category_id'] ?? ''">
      <option value="">—</option>
      @foreach ($eventCategories ?? [] as $c)
        <option value="{{ $c->id }}" @selected((string) old('home_events_category_id', $settings['home_events_category_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
      @endforeach
    </x-sunny.select>

    <div class="form-check mt-2">
      <input type="hidden" name="public_comments_enabled" value="0">
      <input class="form-check-input" type="checkbox" name="public_comments_enabled" value="1" @checked(old('public_comments_enabled', $settings['public_comments_enabled'] ?? '1') === '1')>
      <label class="form-check-label">Cho phép bình luận công khai</label>
    </div>

    <x-sunny.button type="submit" class="mt-3">{{ __('admin_settings.settings_save') }}</x-sunny.button>
  </form>
@endsection
