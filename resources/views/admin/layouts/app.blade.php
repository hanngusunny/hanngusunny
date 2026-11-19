<!DOCTYPE html>
<html lang="{{ $adminHtmlLang ?? 'vi' }}">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $pageTitle ?? config('app.name') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:opsz,wght@8..60,1..1000&family=Nunito:wght@400;500;600;700;800;900&family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ admin_asset('css/tailwind.css') }}" rel="stylesheet">
  <link href="{{ admin_asset('css/main.css') }}" rel="stylesheet">
  <link href="{{ admin_asset('css/toasts.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/sunny-ds.css') }}?v=1" rel="stylesheet">
  {!! $adminThemeInline ?? '' !!}
</head>
<body>
  <div class="admin-backdrop" data-sidebar-backdrop aria-hidden="true"></div>
  <div class="admin-shell">
    @include('admin.partials.sidebar')
    <div class="admin-main">
      @include('admin.partials.topbar')
      <main class="admin-content">
        @yield('content')
      </main>
    </div>
  </div>
  <script src="{{ admin_asset('js/tw-ui.js') }}"></script>
  @yield('scripts')
</body>
</html>
