<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f26b21">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="application-name" content="Hàn Ngữ SUNNY">
    <meta name="apple-mobile-web-app-title" content="Hàn Ngữ SUNNY">
    <meta name="mobile-web-app-capable" content="yes">
    <title>@yield('title', 'SUNNY') · Lớp học</title>
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/images/pwa-icon-192.png') }}?v=3">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('assets/images/pwa-icon-512.png') }}?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/pwa-icon-180.png') }}?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Inter+Tight:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/pwa.css') }}?v=22">
    <style>
        .pwa-loading{position:fixed;inset:0;z-index:90;display:grid;place-items:center;background:#fffaf6}
        .pwa-loading[hidden]{display:none!important}
        .pwa-loading-brand{display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center}
        .pwa-loading-brand img{width:56px;height:56px;border-radius:14px;object-fit:cover}
        .pwa-loading-brand strong{font:800 16px/1.3 Inter,"Inter Tight",sans-serif;color:#2b2a33}
    </style>
    @stack('styles')
</head>
<body class="pwa-body {{ $body_class ?? '' }}">
    @yield('content')
    @if (! empty($pwaTab))
        @include('pwa.partials.nav', ['active' => $pwaTab])
    @endif
    <div id="pwaLoading" class="pwa-loading">
        <div class="pwa-loading-brand" role="status" aria-live="polite" aria-label="Hàn Ngữ SUNNY">
            <img src="{{ asset('assets/images/pwa-icon.png') }}" alt="" width="56" height="56">
            <strong>Hàn Ngữ SUNNY</strong>
            <span id="pwaLoadingText" class="pwa-loading-status" hidden>Đang tải…</span>
        </div>
    </div>
    <script src="{{ asset('assets/js/pwa-ui.js') }}?v=4"></script>
    @stack('scripts')
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(@json(route('pwa.sw')), { scope: @json(url('/app')) }).catch(function () {});
        }
    </script>
</body>
</html>
