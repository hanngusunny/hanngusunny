<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tổng quan') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ephesis&family=Inter+Tight:ital,wght@0,100..900;1,100..900&family=Noto+Sans+KR:wght@400;500;700&family=Nunito:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <style>
        :root { --app-font: "Inter Tight", ui-sans-serif, system-ui, sans-serif; --app-font-display: "Ephesis", cursive; }
        html, body, button, input, select, textarea { font-family: var(--app-font); }
        body.os-body { font-family: var(--app-font); }
        main :is(.text-slate-300, .text-slate-400, .text-slate-500),
        #drawer :is(.text-slate-300, .text-slate-400, .text-slate-500),
        #toast-root :is(.text-slate-300, .text-slate-400, .text-slate-500) { color: #0f172a; }
        .os-pager { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: flex-end; padding: 16px 20px; }
        .os-pager a, .os-pager span { display: inline-flex; min-width: 34px; height: 34px; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #ececec; font-size: 14px; color: #334155; background: #fff; }
        .os-pager .is-on { background: #f26b21; border-color: #f26b21; color: #fff; }
    </style>
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Inter Tight"', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } } };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/office-shell.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('assets/css/office-page.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/sunny-ds.css') }}?v=1">
    @stack('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .os-main.is-pad { font-size: 15px; }
        .os-main .text-sm { font-size: 16px; line-height: 1.5; }
        .os-main .text-xs,
        .os-main small { font-size: 14px; line-height: 1.45; }
        .os-main table,
        .os-main table.text-sm { font-size: 17px; }
        .os-main table thead,
        .os-main table thead.text-xs,
        .os-main table th { font-size: 15px; line-height: 1.4; }
        .os-main table td { font-size: 17px; line-height: 1.5; }
        .os-main table .text-sm { font-size: 17px; }
        .os-main table .text-xs { font-size: 15px; }
        .os-main input:not([type="checkbox"]):not([type="radio"]),
        .os-main select,
        .os-main textarea { font-size: 16px; }
    </style>
    @stack('styles-late')
</head>
<body class="os-body antialiased">
    @php
        $authUser = auth()->user();
        $active = $active ?? 'home';
        $nav = [
            ['id' => 'home', 'href' => $authUser?->homePath() ?? route('dashboard'), 'icon' => 'fa-house', 'label' => 'Tổng quan'],
        ];
        if ($authUser?->isGuest()) {
            $nav = [
                ['id' => 'home', 'href' => $authUser->homePath(), 'icon' => 'fa-video', 'label' => 'Phòng chờ'],
            ];
        } elseif ($authUser?->isLearner()) {
            $studentNav = app(\App\Services\StudentCourseNavService::class);
            $nav[] = [
                'id' => 'course',
                'href' => $studentNav->dashboardUrlForStudent((int) $authUser->id),
                'icon' => 'fa-chalkboard',
                'label' => 'Khóa học',
            ];
            $nav[] = [
                'id' => 'assessments',
                'href' => $studentNav->assessmentsUrlForStudent((int) $authUser->id),
                'icon' => 'fa-list-check',
                'label' => 'Bài kiểm tra',
            ];
            $nav[] = [
                'id' => 'progress',
                'href' => $studentNav->progressUrl(),
                'icon' => 'fa-chart-line',
                'label' => 'Tiến độ học tập',
            ];
            $nav[] = [
                'id' => 'evaluations',
                'href' => $studentNav->evaluationsUrl(),
                'icon' => 'fa-star-half-stroke',
                'label' => 'Đánh giá',
            ];
            $nav[] = [
                'id' => 'videos',
                'href' => $studentNav->videoLibraryUrl(),
                'icon' => 'fa-circle-play',
                'label' => 'Thư viện video',
            ];
            $nav[] = [
                'id' => 'rules',
                'href' => route('school-rules.show'),
                'icon' => 'fa-book',
                'label' => 'Nội quy',
            ];
        }
        if ($authUser?->isTeacher()) {
            $nav[] = [
                'id' => 'schedule',
                'href' => route('teacher.schedule'),
                'icon' => 'fa-calendar-days',
                'label' => 'Lịch dạy',
            ];
            $nav[] = [
                'id' => 'evaluations',
                'href' => route('teacher.evaluations'),
                'icon' => 'fa-star-half-stroke',
                'label' => 'Đánh giá',
            ];
            $nav[] = [
                'id' => 'grading',
                'href' => route('teacher.grading'),
                'icon' => 'fa-pen-to-square',
                'label' => 'Chấm điểm',
            ];
            $nav[] = [
                'id' => 'assessments',
                'href' => route('student.assessments'),
                'icon' => 'fa-list-check',
                'label' => 'Bài kiểm tra',
            ];
            $nav[] = [
                'id' => 'vouchers_check',
                'href' => route('staff.vouchers.check'),
                'icon' => 'fa-ticket',
                'label' => 'Kiểm tra Voucher',
            ];
            $nav[] = [
                'id' => 'rules',
                'href' => route('school-rules.edit'),
                'icon' => 'fa-book',
                'label' => 'Nội quy',
            ];
            $nav[] = [
                'id' => 'activity',
                'href' => route('activity-logs.index'),
                'icon' => 'fa-clock-rotate-left',
                'label' => 'Lịch sử hoạt động',
            ];
        }
        if ($authUser?->isAdmin()) {
            $nav[] = ['id' => 'programs', 'href' => route('admin.programs.index'), 'icon' => 'fa-graduation-cap', 'label' => 'Chương trình'];
            $nav[] = ['id' => 'courses', 'href' => route('admin.courses.index'), 'icon' => 'fa-chalkboard', 'label' => 'Khóa học'];
            $nav[] = ['id' => 'members', 'href' => route('admin.users.index'), 'icon' => 'fa-users', 'label' => 'Thành viên'];
            $nav[] = ['id' => 'vouchers', 'href' => route('admin.vouchers.index'), 'icon' => 'fa-ticket', 'label' => 'Voucher'];
            $nav[] = ['id' => 'activity', 'href' => route('activity-logs.index'), 'icon' => 'fa-clock-rotate-left', 'label' => 'Lịch sử hoạt động'];
            $nav[] = ['id' => 'rules', 'href' => route('school-rules.edit'), 'icon' => 'fa-book', 'label' => 'Nội quy'];
            $nav[] = ['id' => 'classroom', 'href' => route('classroom.index'), 'icon' => 'fa-video', 'label' => 'Lớp học online'];
            $nav[] = ['id' => 'classrooms', 'href' => route('admin.classrooms.index'), 'icon' => 'fa-gear', 'label' => 'Phòng học Zoom'];
            $nav[] = ['id' => 'database', 'href' => route('admin.database.index'), 'icon' => 'fa-database', 'label' => 'Cập nhật CSDL'];
        }
    @endphp

    <aside class="os-sidebar" aria-label="Menu chính">
        <a class="os-brand" href="{{ $authUser?->homePath() ?? route('dashboard') }}" title="{{ config('app.name') }}">
            <img src="{{ asset('assets/images/logo.png') }}" alt="SUNNY" class="h-9 w-9 shrink-0 rounded-lg object-cover">
        </a>
        <nav class="os-side-nav">
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}" class="os-side-item{{ $item['id'] === $active ? ' is-on' : '' }}" title="{{ $item['label'] }}">
                    <span class="os-side-ico"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                    <span class="os-side-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="os-wrap">
        <header class="os-top">
            <a class="os-who" href="{{ route('profile.show') }}" title="Hồ sơ của bạn">
                <x-user-avatar class="os-who-avatar" :url="$authUser?->avatarUrl()" :name="$authUser?->name" />
                <span class="os-who-copy">
                    <strong>{{ $authUser?->name }}</strong>
                    <small>{{ $authUser?->role_label }}</small>
                </span>
                <span class="os-who-gear"><i class="fa-solid fa-gear"></i></span>
            </a>
            <div class="os-tools">
                @if ($authUser && ! $authUser->isGuest())
                    @include('partials.notifications-bell')
                @endif
                <details class="os-drop group relative">
                    <summary class="os-account" title="Tài khoản">
                        <x-user-avatar class="os-avatar" :url="$authUser?->avatarUrl()" :name="$authUser?->name" />
                        <i class="fa-solid fa-chevron-down"></i>
                    </summary>
                    <div class="nav-dropcard os-account-menu">
                        <div class="os-account-head">
                            <x-user-avatar class="os-who-avatar" :url="$authUser?->avatarUrl()" :name="$authUser?->name" />
                            <div class="os-account-id">
                                <strong>{{ $authUser?->name }}</strong>
                                <small>{{ $authUser?->role_label }}</small>
                            </div>
                        </div>
                        <nav class="os-account-links">
                            <a class="os-account-link{{ ($active ?? '') === 'profile' ? ' is-on' : '' }}" href="{{ route('profile.show') }}">
                                <span class="os-account-ico"><i class="fa-solid fa-user"></i></span>Hồ sơ cá nhân
                            </a>
                            <a class="os-account-link{{ ($active ?? '') === 'password' ? ' is-on' : '' }}" href="{{ route('profile.password') }}">
                                <span class="os-account-ico"><i class="fa-solid fa-key"></i></span>Đổi mật khẩu
                            </a>
                        </nav>
                        <div class="os-account-foot">
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="os-account-link is-out">
                                    <span class="os-account-ico"><i class="fa-solid fa-right-from-bracket"></i></span>Đăng xuất
                                </button>
                            </form>
                        </div>
                    </div>
                </details>
            </div>
        </header>

        @hasSection('subnav')
            <nav class="os-sub" aria-label="Menu chuyên mục">@yield('subnav')</nav>
        @endif

        <main class="os-main is-pad" id="os-main">
            @yield('content')
        </main>
    </div>

    <div id="toast-root" class="pointer-events-none fixed top-5 right-5 z-[90] flex w-[min(92vw,380px)] flex-col gap-3"></div>
    <div id="drawer" class="fixed inset-0 z-[80] hidden">
        <div id="drawer-backdrop" class="absolute inset-0 z-0 bg-slate-900/40"></div>
        <aside id="drawer-panel" class="absolute inset-y-0 right-0 z-10 flex w-full max-w-md translate-x-full flex-col overflow-hidden bg-white shadow-2xl transition-transform duration-200">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <h2 id="drawer-title" class="text-lg font-semibold text-slate-900">Chi tiết</h2>
                </div>
                <button type="button" class="js-drawer-close flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="drawer-body" class="flex min-h-0 flex-1 flex-col overflow-y-auto"></div>
        </aside>
    </div>

    @yield('drawer')

    <script src="{{ asset('assets/js/form-validation-vi.js') }}"></script>
    <script src="{{ asset('assets/js/template-ui.js') }}"></script>
    @auth
        <script>
            window.ACTIVITY_PULSE_URL = @json(route('activity.pulse'));
            window.ACTIVITY_PULSE_SECONDS = {{ (int) config('activity.pulse_interval_seconds', 60) }};
        </script>
        <script src="{{ asset('assets/js/activity-tracker.js') }}"></script>
        @if ($authUser && ! $authUser->isGuest())
            <script src="{{ asset('assets/js/notification-read.js') }}"></script>
        @endif
    @endauth
    @if (session('success') || session('error') || session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @if (session('success'))
                    TemplateUI.toast('success', @json(session('success')));
                @endif
                @if (session('info'))
                    TemplateUI.toast('info', @json(session('info')));
                @endif
                @if (session('error'))
                    TemplateUI.toast('error', @json(session('error')));
                @endif
            });
        </script>
    @endif
    @stack('scripts')
</body>
</html>
