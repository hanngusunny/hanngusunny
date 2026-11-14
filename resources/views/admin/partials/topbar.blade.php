<header class="admin-topbar">
  <div class="admin-topbar-search">
    <input type="search" class="form-control" placeholder="{{ __('admin_common.topbar_search_placeholder') }}" disabled>
  </div>
  <div class="admin-topbar-actions">
    <nav class="admin-lang-switch" aria-label="{{ __('admin_common.topbar_language') }}">
      @foreach ($adminLanguageSwitch ?? [] as $row)
        <a class="{{ !empty($row['active']) ? 'is-active' : '' }}" href="{{ $row['url'] }}">{{ $row['label'] }}</a>
      @endforeach
    </nav>
    <div class="admin-topbar-user">
      @if (!empty($adminAvatarUrl))
        <img src="{{ $adminAvatarUrl }}" alt="" width="32" height="32">
      @else
        <span class="admin-avatar-fallback">{{ $adminInitials ?? 'QT' }}</span>
      @endif
      <span>{{ $adminDisplayName ?? '' }}</span>
      <a href="{{ route('admin.logout') }}">{{ __('admin_common.logout') }}</a>
    </div>
  </div>
</header>
