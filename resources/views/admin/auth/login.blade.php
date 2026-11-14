<!DOCTYPE html>
<html lang="{{ $htmlLang }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ __('admin_login.login_page_title') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:opsz,wght@8..60,1..1000&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ admin_asset('css/tailwind.css') }}" rel="stylesheet">
  <link href="{{ admin_asset('css/toasts.css') }}" rel="stylesheet">
  <link href="{{ admin_asset('css/login.css') }}" rel="stylesheet">
  <link href="{{ admin_asset('css/login-presets.css') }}" rel="stylesheet">
</head>
<body class="login-body login-preset-{{ $loginColorPreset }}{{ $loginDefaultTheme === 'light' ? ' login-theme-light' : '' }}">
  <div class="login-lang-bar" role="navigation" aria-label="{{ __('admin_common.topbar_language') }}">
    @foreach ($languageSwitch as $row)
      <a class="login-lang-link{{ $row['active'] ? ' is-active' : '' }}" href="{{ $row['url'] }}" @if($row['active']) aria-current="true" @endif>{{ $row['label'] }}</a>
    @endforeach
  </div>
  <button type="button" class="login-theme-toggle" id="login-theme-toggle" aria-label="{{ __('admin_login.login_theme_aria_light') }}" title="{{ __('admin_login.login_theme_aria_light') }}">
    <i class="bi bi-sun-fill" aria-hidden="true"></i>
  </button>
  <div class="login-ambient" aria-hidden="true">
    <div class="login-ambient__blob login-ambient__blob--1"></div>
    <div class="login-ambient__blob login-ambient__blob--2"></div>
    <div class="login-ambient__blob login-ambient__blob--3"></div>
    <div class="login-ambient__grid"></div>
    <div class="login-ambient__vignette"></div>
  </div>

  <div class="login-page">
    <aside class="login-brand" aria-label="{{ __('admin_login.login_brand_aria') }}">
      <div class="login-brand__inner">
        <a href="{{ route('admin.login') }}" class="login-brand__mark">
          <span class="login-brand__mark-icon" aria-hidden="true"><i class="bi bi-grid-1x2-fill"></i></span>
          <span class="login-brand__mark-text">{{ __('admin_login.login_brand_mark') }}</span>
        </a>
        <div class="login-brand__content">
          <p class="login-brand__eyebrow">{{ __('admin_login.login_brand_eyebrow') }}</p>
          <h1 class="login-brand__title">{{ __('admin_login.login_brand_title') }}</h1>
          <p class="login-brand__lead">{{ __('admin_login.login_brand_lead') }}</p>
        </div>
        <ul class="login-brand__highlights" role="list">
          <li>
            <span class="login-brand__hi-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
            <span>{{ __('admin_login.login_highlight_1') }}</span>
          </li>
          <li>
            <span class="login-brand__hi-icon" aria-hidden="true"><i class="bi bi-lightning-charge"></i></span>
            <span>{{ __('admin_login.login_highlight_2') }}</span>
          </li>
          <li>
            <span class="login-brand__hi-icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
            <span>{{ __('admin_login.login_highlight_3') }}</span>
          </li>
        </ul>
        <div class="login-brand__metrics">
          <div class="login-brand__metric">
            <span class="login-brand__metric-value">24k+</span>
            <span class="login-brand__metric-label">{{ __('admin_login.login_metric_tx') }}</span>
          </div>
          <div class="login-brand__metric">
            <span class="login-brand__metric-value">99.9%</span>
            <span class="login-brand__metric-label">{{ __('admin_login.login_metric_uptime') }}</span>
          </div>
        </div>
      </div>
    </aside>

    <main class="login-main" id="main">
      <div class="login-main__shell">
        <div class="login-card">
          <div class="login-card__glow" aria-hidden="true"></div>
          <header class="login-intro">
            <p class="login-eyebrow">{{ __('admin_login.login_welcome') }}</p>
            <h2 class="login-title">{{ __('admin_login.login_title') }}</h2>
            <p class="login-subtitle">{{ __('admin_login.login_subtitle') }}</p>
          </header>
          <form class="login-form" action="{{ route('admin.login.auth') }}" method="post" accept-charset="utf-8" novalidate>
            @csrf
            <div class="login-field">
              <label for="username" class="form-label">{{ __('admin_login.login_label_username') }}</label>
              <div class="login-input-wrap">
                <i class="bi bi-person login-input-wrap__icon" aria-hidden="true"></i>
                <input type="text" class="form-control login-input-wrap__control" id="username" name="username" autocomplete="username" required>
              </div>
            </div>
            <div class="login-field">
              <label for="password" class="form-label">{{ __('admin_login.login_label_password') }}</label>
              <div class="login-input-wrap">
                <i class="bi bi-lock login-input-wrap__icon" aria-hidden="true"></i>
                <input type="password" class="form-control login-input-wrap__control" id="password" name="password" autocomplete="current-password" required>
                <button type="button" class="login-input-wrap__toggle" id="toggle-password" aria-label="{{ __('admin_login.login_password_show') }}" title="{{ __('admin_login.login_password_toggle') }}">
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
              </div>
            </div>
            <div class="login-options">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                <label class="form-check-label" for="remember">{{ __('admin_login.login_remember') }}</label>
              </div>
              <a href="#" class="login-link">{{ __('admin_login.login_forgot') }}</a>
            </div>
            <button type="submit" class="btn btn-login">
              <span>{{ __('admin_login.login_submit') }}</span>
              <i class="bi bi-arrow-right-short login-btn-icon" aria-hidden="true"></i>
            </button>
          </form>
          <p class="login-divider"><span>{{ __('admin_login.login_or') }}</span></p>
          <div class="login-alt">
            <button type="button" class="login-alt__btn" disabled title="{{ __('admin_login.login_oauth_demo') }}">
              <i class="bi bi-google" aria-hidden="true"></i>
              Google
            </button>
            <button type="button" class="login-alt__btn" disabled title="{{ __('admin_login.login_oauth_demo') }}">
              <i class="bi bi-microsoft" aria-hidden="true"></i>
              Microsoft
            </button>
          </div>
          <p class="login-footer">
            {{ __('admin_login.login_footer') }} <a href="#" class="login-link">{{ __('admin_login.login_contact') }}</a>
          </p>
        </div>
        <p class="login-legal">{{ __('admin_login.login_legal') }} <a href="#" class="login-link login-link--muted">{{ __('admin_login.login_terms') }}</a></p>
      </div>
    </main>
  </div>

  <div class="toast-container fixed top-0 end-0 p-3 login-toast-container" aria-live="polite" aria-atomic="true">
    <div id="loginErrorToast" class="toast fade border-0 admin-app-toast admin-app-toast--error" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="admin-app-toast-inner">
        <span class="admin-app-toast-accent" aria-hidden="true"></span>
        <div class="admin-app-toast-icon-wrap"><i class="bi bi-x-lg" aria-hidden="true"></i></div>
        <div class="admin-app-toast-main">
          <div class="admin-app-toast-row">
            <p class="admin-app-toast-msg"></p>
            <button type="button" class="btn-close admin-app-toast-close" data-tw-dismiss="toast" aria-label="{{ __('admin_login.login_toast_close') }}"></button>
          </div>
        </div>
      </div>
      <div class="admin-app-toast-progress" aria-hidden="true" style="animation-duration: 5200ms"></div>
    </div>
  </div>

  <script>
    window.__loginError = @json($loginError ?: null);
    window.__loginThemeLight = @json(__('admin_login.login_theme_aria_light'));
    window.__loginThemeDark = @json(__('admin_login.login_theme_aria_dark'));
    window.__loginPwShow = @json(__('admin_login.login_password_show'));
    window.__loginPwHide = @json(__('admin_login.login_password_hide'));
  </script>
  <script src="{{ admin_asset('js/tw-ui.js') }}"></script>
  <script src="{{ asset('js/admin-login.js') }}"></script>
</body>
</html>
