@php
  $nav = $adminNavActive ?? '';
  $postsOpen = str_starts_with((string) $nav, 'post_');
  $eventsOpen = str_starts_with((string) $nav, 'event_');
  $schoolOpen = str_starts_with((string) $nav, 'school_');
  $majorOpen = str_starts_with((string) $nav, 'major_');
  $settingsOpen = in_array($nav, ['site_settings', 'admin_ui_settings', 'site_customize', 'nav_menus', 'frontend_appearance', 'sql_updates', 'content_templates'], true);
@endphp
<aside class="admin-sidebar" id="adminSidebar" aria-label="{{ __('admin_sidebar.sidebar_aria_nav') }}">
  <div class="admin-sidebar-brand">
    <div class="admin-sidebar-logo" aria-hidden="true"><i class="bi bi-grid-1x2-fill"></i></div>
    <div>
      <div class="admin-sidebar-title">{{ __('admin_sidebar.sidebar_brand_title') }}</div>
      <div class="admin-sidebar-sub">{{ __('admin_sidebar.sidebar_brand_sub') }}</div>
    </div>
  </div>
  <nav class="admin-nav">
    <div class="admin-nav-label">{{ __('admin_sidebar.sidebar_nav_main') }}</div>
    <a class="admin-nav-link{{ $nav === 'overview' ? ' active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> {{ __('admin_sidebar.sidebar_nav_overview') }}</a>

    @foreach (config('content.modules') as $modKey => $cfg)
      @php
        $open = $nav === $cfg['nav_posts'] || $nav === $cfg['nav_categories'] || $nav === $cfg['nav_tags'];
        $cid = 'navMod'.ucfirst($modKey);
      @endphp
      <div class="admin-nav-group">
        <button type="button" class="admin-nav-link admin-nav-toggle{{ $open ? '' : ' collapsed' }}" data-tw-toggle="collapse" data-tw-target="#{{ $cid }}" aria-expanded="{{ $open ? 'true' : 'false' }}">
          <span class="admin-nav-toggle-label"><i class="bi bi-journal-text"></i> {{ $cfg['label'] }}</span>
          <i class="bi bi-chevron-down admin-nav-chevron"></i>
        </button>
        <div class="collapse admin-nav-subwrap{{ $open ? ' show' : '' }}" id="{{ $cid }}">
          <a class="admin-nav-sublink{{ $nav === $cfg['nav_posts'] ? ' active' : '' }}" href="{{ route('admin.'.$modKey.'.posts.index') }}"><i class="bi bi-file-text"></i> Danh sách</a>
          <a class="admin-nav-sublink{{ $nav === $cfg['nav_categories'] ? ' active' : '' }}" href="{{ route('admin.'.$modKey.'.categories.index') }}"><i class="bi bi-folder2-open"></i> Chuyên mục</a>
          <a class="admin-nav-sublink{{ $nav === $cfg['nav_tags'] ? ' active' : '' }}" href="{{ route('admin.'.$modKey.'.tags.index') }}"><i class="bi bi-bookmarks"></i> Tags</a>
        </div>
      </div>
    @endforeach

    <a class="admin-nav-link{{ str_starts_with((string) $nav, 'site_pages') ? ' active' : '' }}" href="{{ route('admin.site-pages.index') }}"><i class="bi bi-file-earmark-text"></i> {{ __('admin_sidebar.sidebar_nav_site_pages') }}</a>
    <a class="admin-nav-link{{ str_starts_with((string) $nav, 'faq') ? ' active' : '' }}" href="{{ route('admin.faq.index') }}"><i class="bi bi-question-circle"></i> FAQ</a>
    <a class="admin-nav-link{{ str_starts_with((string) $nav, 'blocks') ? ' active' : '' }}" href="{{ route('admin.blocks.index') }}"><i class="bi bi-layout-text-window-reverse"></i> Blocks</a>
    <a class="admin-nav-link{{ $nav === 'slider' ? ' active' : '' }}" href="{{ route('admin.slider.index') }}"><i class="bi bi-sliders"></i> Slider</a>
    <a class="admin-nav-link{{ $nav === 'media' ? ' active' : '' }}" href="{{ route('admin.media.index') }}"><i class="bi bi-images"></i> Media</a>
    <a class="admin-nav-link{{ $nav === 'post_comments' ? ' active' : '' }}" href="{{ route('admin.comments.index') }}"><i class="bi bi-chat-left-text"></i> Bình luận</a>
    <a class="admin-nav-link{{ str_starts_with((string) $nav, 'custom_forms') ? ' active' : '' }}" href="{{ route('admin.forms.index') }}"><i class="bi bi-ui-checks"></i> Form</a>
    <a class="admin-nav-link{{ $nav === 'consult_leads' ? ' active' : '' }}" href="{{ route('admin.consult-leads.index') }}"><i class="bi bi-envelope-paper"></i> {{ __('admin_sidebar.sidebar_nav_contacts') }}</a>
    <a class="admin-nav-link{{ $nav === 'analytics_reports' ? ' active' : '' }}" href="{{ route('admin.analytics.index') }}"><i class="bi bi-graph-up"></i> Báo cáo</a>

    <div class="admin-nav-group">
      <button type="button" class="admin-nav-link admin-nav-toggle{{ $settingsOpen ? '' : ' collapsed' }}" data-tw-toggle="collapse" data-tw-target="#navSettings" aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}">
        <span class="admin-nav-toggle-label"><i class="bi bi-gear"></i> {{ __('admin_sidebar.sidebar_nav_settings') }}</span>
        <i class="bi bi-chevron-down admin-nav-chevron"></i>
      </button>
      <div class="collapse admin-nav-subwrap{{ $settingsOpen ? ' show' : '' }}" id="navSettings">
        <a class="admin-nav-sublink{{ $nav === 'site_settings' ? ' active' : '' }}" href="{{ route('admin.settings.edit') }}"><i class="bi bi-globe2"></i> {{ __('admin_sidebar.sidebar_nav_site_settings') }}</a>
        <a class="admin-nav-sublink{{ $nav === 'site_customize' ? ' active' : '' }}" href="{{ route('admin.customize.identity') }}"><i class="bi bi-palette"></i> Nhận diện / Topbar</a>
        <a class="admin-nav-sublink{{ $nav === 'frontend_appearance' ? ' active' : '' }}" href="{{ route('admin.appearance.edit') }}"><i class="bi bi-code-slash"></i> Giao diện Blade</a>
        <a class="admin-nav-sublink{{ $nav === 'nav_menus' ? ' active' : '' }}" href="{{ route('admin.menus.index') }}"><i class="bi bi-list-nested"></i> Menu</a>
        <a class="admin-nav-sublink{{ $nav === 'content_templates' ? ' active' : '' }}" href="{{ route('admin.templates.index') }}"><i class="bi bi-layout-wtf"></i> Template CMS</a>
        @if ($adminIsSuper ?? false)
          <a class="admin-nav-sublink{{ $nav === 'sql_updates' ? ' active' : '' }}" href="{{ route('admin.sql.index') }}"><i class="bi bi-database"></i> Cập nhật CSDL</a>
        @endif
      </div>
    </div>

    <div class="admin-nav-group">
      <button type="button" class="admin-nav-link admin-nav-toggle{{ in_array($nav, ['users_list','users_add','users_edit'], true) ? '' : ' collapsed' }}" data-tw-toggle="collapse" data-tw-target="#navUsers">
        <span class="admin-nav-toggle-label"><i class="bi bi-people"></i> {{ __('admin_sidebar.sidebar_nav_users') }}</span>
        <i class="bi bi-chevron-down admin-nav-chevron"></i>
      </button>
      <div class="collapse admin-nav-subwrap{{ in_array($nav, ['users_list','users_add','users_edit'], true) ? ' show' : '' }}" id="navUsers">
        <a class="admin-nav-sublink{{ $nav === 'users_list' ? ' active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i> {{ __('admin_sidebar.sidebar_nav_user_list') }}</a>
        <a class="admin-nav-sublink{{ $nav === 'users_add' ? ' active' : '' }}" href="{{ route('admin.users.create') }}"><i class="bi bi-person-plus"></i> {{ __('admin_sidebar.sidebar_nav_user_add') }}</a>
      </div>
    </div>
    <a class="admin-nav-link{{ $nav === 'profile' ? ' active' : '' }}" href="{{ route('admin.profile.edit') }}"><i class="bi bi-person-circle"></i> Hồ sơ</a>
  </nav>
</aside>
