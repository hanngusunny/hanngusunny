<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lớp học online') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @if (trim($__env->yieldContent('body_class')) === 'page-classroom-room')
        <link rel="stylesheet" href="{{ asset('assets/css/classroom-meeting.css') }}?v=11">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/office-shell.css') }}?v=2">
        <link rel="stylesheet" href="{{ asset('assets/css/classroom.css') }}">
    @endif
    @stack('styles')
</head>
<body class="cr-body @yield('body_class')">
    @yield('content')
    @if (session('success') || session('error') || session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const msg = @json(session('success') ?: session('info') ?: session('error'));
                if (msg) window.alert(msg);
            });
        </script>
    @endif
    <script src="{{ asset('assets/js/form-validation-vi.js') }}"></script>
    @stack('scripts')
</body>
</html>
