@php
    $activeTab = $activeTab ?? 'profile';
@endphp

<nav class="pf-nav" aria-label="Tài khoản">
    <a class="op-btn-ghost" href="{{ auth()->user()?->homePath() ?? route('dashboard') }}">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Tổng quan</span>
    </a>
    <div class="pf-nav-tabs">
        <a class="pf-nav-tab{{ $activeTab === 'profile' ? ' is-on' : '' }}" href="{{ route('profile.show') }}">
            <i class="fa-solid fa-user"></i>
            <span>Hồ sơ cá nhân</span>
        </a>
        <a class="pf-nav-tab{{ $activeTab === 'password' ? ' is-on' : '' }}" href="{{ route('profile.password') }}">
            <i class="fa-solid fa-key"></i>
            <span>Đổi mật khẩu</span>
        </a>
    </div>
</nav>
