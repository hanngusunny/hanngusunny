@php
    $active = $active ?? 'home';
    $pwaMenu = $pwaMenu ?? [];
    $menuName = (string) ($pwaMenu['userName'] ?? '');
    $menuRole = (string) ($pwaMenu['roleLabel'] ?? '');
    $menuCourse = (string) ($pwaMenu['courseTitle'] ?? '');
    $menuAvatar = (string) ($pwaMenu['userAvatar'] ?? '');
    $menuInitials = (string) ($pwaMenu['userInitials'] ?? '');
    $isLearner = ! empty($pwaMenu['isLearner']);
    $menuOn = in_array($active, ['profile', 'evaluations'], true);
@endphp
<nav class="pwa-tabbar" aria-label="Menu chính">
    <a class="pwa-tab{{ $active === 'home' ? ' is-on' : '' }}" href="{{ route('pwa.home') }}">
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        <span>Trang chủ</span>
    </a>
    <a class="pwa-tab{{ $active === 'courses' ? ' is-on' : '' }}" href="{{ route('pwa.courses') }}">
        <i class="fa-solid fa-book-open" aria-hidden="true"></i>
        <span>Khóa học</span>
    </a>
    <a
        class="pwa-tab pwa-tab--zoom{{ $active === 'zoom' ? ' is-on' : '' }}{{ ! empty($zoomLive) ? ' is-live' : '' }}"
        href="{{ route('pwa.zoom') }}"
        aria-label="{{ ! empty($zoomLive) ? 'Zoom — đang tới giờ học' : 'Zoom' }}"
    >
        <span class="pwa-tab-zoom">
            <i class="fa-solid fa-video" aria-hidden="true"></i>
        </span>
        <span>Zoom</span>
    </a>
    <a class="pwa-tab{{ $active === 'assessments' ? ' is-on' : '' }}" href="{{ route('pwa.assessments') }}">
        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
        <span>Kiểm tra</span>
    </a>
    <button
        type="button"
        class="pwa-tab{{ $menuOn ? ' is-on' : '' }}"
        data-pwa-menu-open
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="pwaAccountMenu"
    >
        <i class="fa-solid fa-user" aria-hidden="true"></i>
        <span>Hồ sơ</span>
    </button>
</nav>

<div class="pwa-sheet" id="pwaAccountMenu" hidden>
    <button type="button" class="pwa-sheet-backdrop" data-pwa-menu-close aria-label="Đóng menu"></button>
    <div class="pwa-sheet-card" role="dialog" aria-modal="true" aria-labelledby="pwaAccountMenuName">
        <div class="pwa-sheet-who">
            @if ($menuAvatar !== '')
                <img class="pwa-sheet-avatar" src="{{ $menuAvatar }}" alt="" width="48" height="48">
            @else
                <span class="pwa-sheet-avatar is-fallback">{{ $menuInitials }}</span>
            @endif
            <div>
                <strong id="pwaAccountMenuName">{{ $menuName !== '' ? $menuName : 'Thành viên' }}</strong>
                @if ($menuRole !== '' || $menuCourse !== '')
                    <small>{{ $menuRole }}@if ($menuCourse !== '') · {{ $menuCourse }}@endif</small>
                @endif
            </div>
        </div>
        <nav class="pwa-sheet-links" aria-label="Menu hồ sơ">
            @if ($isLearner)
                <a class="pwa-sheet-link{{ $active === 'evaluations' ? ' is-on' : '' }}" href="{{ route('pwa.evaluations') }}">
                    <span class="pwa-sheet-ico"><i class="fa-solid fa-star-half-stroke"></i></span>
                    Nhận xét của giáo viên
                </a>
            @endif
            <a class="pwa-sheet-link{{ $active === 'profile' ? ' is-on' : '' }}" href="{{ route('pwa.profile') }}">
                <span class="pwa-sheet-ico"><i class="fa-solid fa-id-card"></i></span>
                Hồ sơ cá nhân
            </a>
        </nav>
    </div>
</div>
