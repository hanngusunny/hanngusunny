@extends('layouts.pwa', ['body_class' => 'is-login'])

@section('title', 'Đăng nhập')

@section('content')
<main class="pwa-login">
    <div class="pwa-login-bg" aria-hidden="true"></div>

    <header class="pwa-login-brand">
        <img class="pwa-mark" src="{{ asset('assets/images/pwa-icon.png') }}" alt="Hàn Ngữ SUNNY" width="64" height="64">
        <p>Hàn ngữ SUNNY</p>
        <h1>Chào mừng trở lại</h1>
        <span class="pwa-login-lead">Tài khoản, email hoặc số điện thoại</span>
    </header>

    <section class="pwa-card pwa-login-card">
        <h2>Đăng nhập</h2>
        <p class="pwa-help">Tài khoản, email hoặc số điện thoại</p>

        @if (session('error'))
            <div class="pwa-alert is-error">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="pwa-alert is-ok">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="pwa-alert is-error">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('pwa.login.attempt') }}" class="pwa-form" data-pwa-wait="Đang đăng nhập…">
            @csrf
            <label class="pwa-field" for="pwa-login">
                <span>Tài khoản</span>
                <span class="pwa-field-box">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    <input
                        id="pwa-login"
                        type="text"
                        name="login"
                        value="{{ old('login') }}"
                        autocomplete="username"
                        inputmode="text"
                        autocapitalize="none"
                        autocorrect="off"
                        spellcheck="false"
                        placeholder="Tên đăng nhập hoặc SĐT"
                        required
                        autofocus
                    >
                </span>
            </label>
            <label class="pwa-field" for="pwa-password">
                <span>Mật khẩu</span>
                <span class="pwa-field-box">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input
                        id="pwa-password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        inputmode="text"
                        autocapitalize="none"
                        spellcheck="false"
                        placeholder="Nhập mật khẩu"
                        required
                    >
                    <button type="button" class="pwa-field-toggle" id="pwaPasswordToggle" aria-label="Hiện mật khẩu">
                        <i class="fa-regular fa-eye" data-eye></i>
                    </button>
                </span>
            </label>
            <label class="pwa-check">
                <input type="checkbox" name="remember" value="1" @checked(old('remember', true))>
                Ghi nhớ đăng nhập
            </label>
            <button type="submit" class="pwa-btn pwa-login-submit">
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                Vào ứng dụng
            </button>
        </form>

        <button type="button" id="pwa-install-btn" class="pwa-btn pwa-btn--ghost" hidden>
            <i class="fa-solid fa-download" aria-hidden="true"></i>
            Cài ứng dụng
        </button>
    </section>
</main>

<div id="pwa-install-sheet" class="pwa-poll" hidden>
    <div class="pwa-poll-card" role="dialog" aria-labelledby="pwa-install-title">
        <span class="pwa-poll-tag">PWA</span>
        <h2 id="pwa-install-title">Cài ứng dụng</h2>
        <p id="pwa-install-body" class="pwa-help"></p>
        <div class="pwa-poll-foot">
            <button type="button" id="pwa-install-close" class="pwa-btn">Đã hiểu</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/pwa-install.js') }}?v=1"></script>
<script>
    (function () {
        var btn = document.getElementById('pwaPasswordToggle');
        var input = document.getElementById('pwa-password');
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            input.setAttribute('inputmode', 'text');
            var icon = btn.querySelector('[data-eye]');
            if (icon) icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            btn.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        });
    })();
</script>
@endpush
