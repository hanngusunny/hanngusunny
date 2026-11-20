<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Đăng nhập') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400..900;1,400..900&family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/guest-auth.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/guest-login.css') }}?v=5">
    <link rel="stylesheet" href="{{ asset('assets/css/sunny-ds.css') }}?v=1">
    <style>
        :root { --app-font: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        html, body, button, input, select, textarea { font-family: var(--app-font); }
        .ga-alert { margin: 0 0 16px; padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; line-height: 1.45; }
        .ga-alert.is-error { background: #fdecec; color: #b42318; border: 1px solid #fecdca; }
        .ga-alert.is-success { background: #e8f8ef; color: #027a48; border: 1px solid #a6f4c5; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    </style>
</head>
<body class="ga-body">
    @yield('content')
    <script src="{{ asset('assets/js/form-validation-vi.js') }}"></script>
    <script src="{{ asset('assets/js/guest-auth.js') }}"></script>
</body>
</html>
