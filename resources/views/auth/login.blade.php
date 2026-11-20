@extends('layouts.guest')

@section('title', 'Đăng nhập')

@section('content')
<main class="sn-login-page">
    <div class="sn-login-container">
        <!-- Cột trái: Giới thiệu & Mascot Anh Cam (Figma #188:5662) -->
        <section class="sn-login-hero" aria-label="Giới thiệu Hàn ngữ Sunny">
            <div class="sn-login-brandmark">
                <p class="sn-login-lead">Nền tảng học tập online của</p>
                <h1 class="sn-login-title">Hàn ngữ <span>Sunny</span></h1>
            </div>

            <div class="sn-mascot-stage">
                <!-- Speech Bubble (#375:14718) -->
                <div class="sn-speech-bubble" role="note">
                    <p class="sn-speech-bubble-text">Chào các vợ iu, sẵn sàng học tiếng Hàn cùng anh Cam chưa nào?!</p>
                </div>

                <!-- White Elliptical Podium Base (#223:10979) -->
                <div class="sn-mascot-podium" aria-hidden="true"></div>

                <!-- Mascot Anh Cam PNG (#398:24751) -->
                <img
                    class="sn-mascot-img"
                    src="{{ asset('assets/images/anh-cam-mascot.png') }}"
                    alt="Anh Cam - Hàn ngữ Sunny"
                    width="619"
                    height="325"
                    loading="eager"
                    fetchpriority="high"
                >
            </div>
        </section>

        <!-- Cột phải: Khung Đăng nhập (Figma #222:9579) -->
        <section class="sn-login-card" aria-label="Khung đăng nhập">
            <!-- Top: Logo và Tên thương hiệu (#188:5671) -->
            <div class="sn-login-card-top">
                <div class="sn-logo-box" aria-hidden="true">
                    <img src="{{ asset('assets/images/sunny-logo-icon.svg') }}" alt="Logo Sunny" width="54" height="50">
                </div>
                <p class="sn-card-brand-name">HÀN NGỮ SUNNY</p>
            </div>

            <!-- Body: Tiêu đề H2, Thông báo lỗi & Form nhập liệu (#188:5863) -->
            <div class="sn-login-card-body">
                <h2 class="sn-form-heading">Đăng nhập</h2>

                @if (session('error'))
                    <div class="ga-alert is-error" role="alert">{{ session('error') }}</div>
                @endif
                @if (session('success'))
                    <div class="ga-alert is-success" role="status">{{ session('success') }}</div>
                @endif
                @if (isset($errors) && $errors->any())
                    <div class="ga-alert is-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form class="sn-login-form" action="{{ route('login.attempt') }}" method="post">
                    @csrf
                    <div class="sn-login-fields">
                        <!-- Trường Số điện thoại (#188:5697) -->
                        <div class="sn-login-field">
                            <label class="sn-login-label" for="login">Số điện thoại</label>
                            <div class="sn-login-input-wrap">
                                <span class="sn-login-field-icon" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.97 18.33C21.97 18.69 21.89 19.06 21.72 19.42C21.55 19.78 21.33 20.12 21.04 20.44C20.55 20.98 20.01 21.37 19.4 21.62C18.8 21.87 18.15 22 17.45 22C16.43 22 15.34 21.76 14.19 21.27C13.04 20.78 11.89 20.12 10.75 19.29C9.6 18.45 8.51 17.52 7.47 16.49C6.44 15.45 5.51 14.36 4.68 13.22C3.86 12.08 3.2 10.94 2.72 9.81C2.24 8.67 2 7.58 2 6.54C2 5.86 2.12 5.21 2.36 4.61C2.6 4 2.98 3.44 3.51 2.94C4.15 2.31 4.85 2 5.59 2C5.87 2 6.15 2.06 6.4 2.18C6.66 2.3 6.89 2.48 7.07 2.74L9.39 6.01C9.57 6.26 9.7 6.49 9.79 6.71C9.88 6.92 9.93 7.13 9.93 7.32C9.93 7.56 9.86 7.8 9.72 8.03C9.59 8.26 9.4 8.5 9.16 8.74L8.4 9.53C8.29 9.64 8.24 9.77 8.24 9.93C8.24 10.01 8.25 10.08 8.27 10.16C8.3 10.24 8.33 10.3 8.35 10.36C8.53 10.69 8.84 11.12 9.28 11.64C9.73 12.16 10.21 12.69 10.73 13.22C11.27 13.75 11.79 14.24 12.32 14.69C12.84 15.13 13.27 15.43 13.61 15.61C13.66 15.63 13.72 15.66 13.79 15.69C13.87 15.72 13.95 15.73 14.04 15.73C14.21 15.73 14.34 15.67 14.45 15.56L15.21 14.81C15.46 14.56 15.7 14.37 15.93 14.25C16.16 14.11 16.39 14.04 16.64 14.04C16.83 14.04 17.03 14.08 17.25 14.17C17.47 14.26 17.7 14.39 17.95 14.56L21.26 16.91C21.52 17.09 21.7 17.3 21.81 17.55C21.91 17.8 21.97 18.05 21.97 18.33Z" stroke-width="1.5" stroke-miterlimit="10" stroke="currentColor"/>
                                    </svg>
                                </span>
                                <input
                                    id="login"
                                    class="sn-login-input"
                                    type="text"
                                    name="login"
                                    value="{{ old('login') }}"
                                    autocomplete="username"
                                    inputmode="text"
                                    placeholder="Nhập số điện thoại"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <!-- Trường Mật khẩu (#188:5724) -->
                        <div class="sn-login-field">
                            <label class="sn-login-label" for="password">Mật khẩu</label>
                            <div class="sn-login-input-wrap">
                                <span class="sn-login-field-icon" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6 10V8C6 4.69 7 2 12 2C17 2 18 4.69 18 8V10" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
                                        <path d="M14.5 16C14.5 17.3807 13.3807 18.5 12 18.5C10.6193 18.5 9.5 17.3807 9.5 16C9.5 14.6193 10.6193 13.5 12 13.5C13.3807 13.5 14.5 14.6193 14.5 16Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
                                        <path d="M17 22H7C3 22 2 21 2 17V15C2 11 3 10 7 10H17C21 10 22 11 22 15V17C22 21 21 22 17 22Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
                                    </svg>
                                </span>
                                <input
                                    id="password"
                                    class="sn-login-input"
                                    type="password"
                                    name="password"
                                    placeholder="Nhập mật khẩu của bạn"
                                    autocomplete="current-password"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Ghi nhớ & Quên mật khẩu (#188:5836) -->
                    <div class="sn-login-options">
                        <label class="sn-login-check">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember', true))>
                            <span>Ghi nhớ đăng nhập</span>
                        </label>
                        <a class="sn-login-forgot" href="#" onclick="alert('Vui lòng liên hệ giáo viên hoặc quản trị viên để đặt lại mật khẩu.'); return false;">Quên mật khẩu?</a>
                    </div>

                    <!-- Nút Submit (#188:5838) sử dụng Base Button Component -->
                    <x-sunny.button type="submit" variant="primary" size="m" class="sn-login-submit-btn w-full">
                        Đăng nhập
                    </x-sunny.button>
                </form>
            </div>

            <!-- Footer: Mở app điện thoại & Bản quyền (#188:5862) -->
            <footer class="sn-login-card-foot">
                <a class="sn-login-app-link" href="{{ route('pwa.entry') }}">Mở app điện thoại</a>
                <span class="sn-login-copyright">· © {{ date('Y') }} Hàn ngữ SUNNY</span>
            </footer>
        </section>
    </div>
</main>
@endsection
