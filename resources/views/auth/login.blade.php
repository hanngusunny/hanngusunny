@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
<div class="ga-page is-login">
    <div class="ga-wash" aria-hidden="true"></div>
    <div class="ga-blob" aria-hidden="true"></div>

    <section class="ga-hero is-login" aria-label="Giới thiệu">
        <svg class="ga-waves" viewBox="0 0 900 720" preserveAspectRatio="none" aria-hidden="true">
            <path d="M-20 90 C 80 30, 180 150, 280 90 S 480 20, 580 90 780 160, 920 80" fill="none" stroke="#f3e2c8" stroke-width="1.4"/>
            <path d="M-20 150 C 90 90, 190 210, 300 150 S 510 80, 620 150 810 220, 920 140" fill="none" stroke="#f6e6d2" stroke-width="1.4"/>
            <path d="M-20 430 C 70 370, 190 490, 300 430 S 500 360, 620 430 800 500, 920 410" fill="none" stroke="#f2ddc0" stroke-width="1.3"/>
            <path d="M-20 500 C 100 450, 200 560, 320 500 S 530 430, 650 500 820 570, 920 490" fill="none" stroke="#f7ead8" stroke-width="1.3"/>
        </svg>

        <div class="ga-hero-inner">
            <div class="ga-copy" style="margin-top:0">
                <p class="ga-login-lead">Nền tảng Học tập online của</p>
                <h1 class="ga-login-brandline">Du học Hàn Quốc <em>SUNNY</em></h1>
            </div>

            <div class="ga-features">
                <article class="ga-feature">
                    <span class="ga-feature-ico is-orange" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></span>
                    <div>
                        <strong>Lớp học &amp; tiến độ</strong>
                        <small>Học viên theo dõi lớp và kết quả; nhân viên quản lý hồ sơ, điểm danh và cấp độ TOPIK.</small>
                    </div>
                </article>
                <article class="ga-feature">
                    <span class="ga-feature-ico is-blue" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>
                    <div>
                        <strong>Lịch học &amp; nhắc việc</strong>
                        <small>Xem lịch học, lịch dạy và nhận thông báo quan trọng kịp thời.</small>
                    </div>
                </article>
                <article class="ga-feature">
                    <span class="ga-feature-ico is-teal" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
                    <div>
                        <strong>Bài giảng &amp; bài tập</strong>
                        <small>Truy cập tài liệu, nộp bài và cập nhật công việc mọi lúc, mọi nơi.</small>
                    </div>
                </article>
            </div>
        </div>

        <div class="ga-scene" aria-hidden="true">
            <span class="mountain m1"></span>
            <span class="mountain m2"></span>
            <span class="lake"></span>
            <span class="tower"></span>
            <span class="pagoda"></span>
            <span class="flower f1"></span>
            <span class="flower f2"></span>
            <span class="flower f3"></span>
        </div>
    </section>

    <section class="ga-panel is-login">
        <div class="ga-card is-login">
            <div class="ga-login-logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Hàn ngữ SUNNY">
            </div>

            <div class="ga-card-head">
                <h2 class="ga-card-title">Đăng nhập</h2>
                <p class="ga-login-help">Chào mừng bạn! Sử dụng số điện thoại và mật khẩu để vào hệ thống.</p>
            </div>

            @if (session('error'))
                <div class="ga-alert is-error">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="ga-alert is-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="ga-alert is-error">{{ $errors->first() }}</div>
            @endif

            <form class="ga-form" action="{{ route('login.attempt') }}" method="post">
                @csrf
                <div class="ga-field">
                    <label class="ga-label" for="login">Số điện thoại</label>
                    <div class="ga-field-box">
                        <span class="ga-field-icon" aria-hidden="true"><i class="fa-solid fa-mobile-screen-button"></i></span>
                        <input id="login" class="ga-input" type="tel" name="login" value="{{ old('login') }}" autocomplete="tel" inputmode="tel" placeholder="0912345678" required autofocus>
                    </div>
                </div>
                <div class="ga-field">
                    <label class="ga-label" for="password">Mật khẩu</label>
                    <div class="ga-field-box">
                        <span class="ga-field-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                        <input id="password" class="ga-input" type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="ga-field-toggle" data-toggle-password="password" aria-label="Hiện mật khẩu">
                            <i class="fa-regular fa-eye" data-eye></i>
                        </button>
                    </div>
                </div>
                <div class="ga-row">
                    <label class="ga-check">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember', true))>
                        Ghi nhớ đăng nhập
                    </label>
                    <a class="ga-link" href="#">Quên mật khẩu?</a>
                </div>
                <button type="submit" class="ga-submit">
                    Đăng nhập
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <p class="ga-login-foot">
                <a class="ga-link" href="{{ route('pwa.entry') }}">Mở app điện thoại</a>
                · © {{ date('Y') }} Hàn ngữ SUNNY
            </p>
        </div>
    </section>
</div>
@endsection
