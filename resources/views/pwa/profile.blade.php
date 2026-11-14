@extends('layouts.pwa', ['body_class' => 'is-home is-profile'])

@section('title', 'Hồ sơ')

@php
    $user = $user ?? auth()->user();
    $avatarUrl = $userAvatar ?? $user?->avatarUrl();
@endphp

@section('content')
<main class="pwa-home pwa-page">
    @include('pwa.partials.header')

    @if (session('error'))
        <div class="pwa-alert is-error">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="pwa-alert is-ok">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="pwa-alert is-error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="pwa-card pwa-profile-hero">
        <div class="pwa-profile-photo{{ $avatarUrl ? '' : ' is-fallback' }}" data-user-avatar>
            <span class="pwa-avatar pwa-profile-fallback" data-avatar-fallback>{{ $userInitials }}</span>
            <img
                id="avatarPreview"
                class="pwa-profile-photo-img"
                @if ($avatarUrl) src="{{ $avatarUrl }}" @endif
                alt="{{ $userName }}"
                @if (! $avatarUrl) hidden @endif
                data-user-avatar-img
            >
            <label class="pwa-profile-camera" for="avatar" aria-label="Chọn ảnh đại diện">
                <i class="fa-solid fa-camera"></i>
            </label>
        </div>
        <strong>{{ $userName }}</strong>
        <small>{{ $roleLabel }}@if (($courseTitle ?? '') !== '') · {{ $courseTitle }}@endif</small>
        <div class="pwa-profile-chips">
            @if ($userUsername !== '')
                <span><i class="fa-regular fa-id-badge"></i> {{ $userUsername }}</span>
            @endif
            @if ($userStudentCode !== '')
                <span><i class="fa-solid fa-hashtag"></i> {{ $userStudentCode }}</span>
            @endif
            @if ($userStatusLabel !== '')
                <span><i class="fa-solid fa-shield"></i> {{ $userStatusLabel }}</span>
            @endif
        </div>
    </section>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="pwa-card pwa-profile-card" id="thong-tin" data-pwa-wait="Đang lưu hồ sơ…">
        @csrf
        @include('pwa.partials.return-field', ['pwaReturn' => true])
        <p class="pwa-kicker">Tài khoản</p>
        <h2>Thông tin cá nhân</h2>

        <input
            class="pwa-avatar-input"
            type="file"
            id="avatar"
            name="avatar"
            accept="image/jpeg,image/png,image/webp,image/gif"
        >

        @if ($avatarUrl)
            <label class="pwa-profile-remove">
                <input type="checkbox" name="remove_avatar" value="1">
                Xóa ảnh đại diện hiện tại
            </label>
        @endif

        <label class="pwa-field" for="full_name">
            <span>Họ và tên</span>
            <span class="pwa-field-box">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <input id="full_name" type="text" name="full_name" required minlength="2" maxlength="120" value="{{ old('full_name', $user->full_name) }}" autocomplete="name">
            </span>
        </label>
        <label class="pwa-field" for="email">
            <span>Email</span>
            <span class="pwa-field-box">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <input id="email" type="email" name="email" required maxlength="120" value="{{ old('email', $user->email) }}" autocomplete="email" inputmode="email">
            </span>
        </label>
        <label class="pwa-field" for="phone">
            <span>Số điện thoại</span>
            <span class="pwa-field-box">
                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0912345678" autocomplete="tel" inputmode="tel">
            </span>
        </label>
        <label class="pwa-field" for="username">
            <span>Tên đăng nhập</span>
            <span class="pwa-field-box is-locked">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input id="username" type="text" value="{{ $userUsername }}" disabled readonly>
            </span>
        </label>

        @if ($joinedLabel !== '' || $lastLoginLabel !== '')
            <p class="pwa-profile-meta">
                @if ($joinedLabel !== '')
                    <span>Tham gia {{ $joinedLabel }}</span>
                @endif
                @if ($lastLoginLabel !== '')
                    <span>Đăng nhập {{ $lastLoginLabel }}</span>
                @endif
            </p>
        @endif

        <button type="submit" class="pwa-btn">
            <i class="fa-solid fa-floppy-disk"></i>
            Lưu hồ sơ
        </button>
    </form>

    <form method="post" action="{{ route('profile.password.update') }}" class="pwa-card pwa-profile-card" id="mat-khau" data-pwa-wait="Đang đổi mật khẩu…">
        @csrf
        @include('pwa.partials.return-field', ['pwaReturn' => true])
        <p class="pwa-kicker">Bảo mật</p>
        <h2>Đổi mật khẩu</h2>
        <p class="pwa-help">Ít nhất 6 ký tự. Nên có chữ hoa, số và ký tự đặc biệt.</p>

        <label class="pwa-field" for="current_password">
            <span>Mật khẩu hiện tại</span>
            <span class="pwa-field-box">
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password" placeholder="Mật khẩu đang dùng">
                <button type="button" class="pwa-field-toggle" data-toggle-password="current_password" aria-label="Hiện mật khẩu">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </span>
        </label>
        <label class="pwa-field" for="password">
            <span>Mật khẩu mới</span>
            <span class="pwa-field-box">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input id="password" type="password" name="password" required minlength="6" autocomplete="new-password" placeholder="Tối thiểu 6 ký tự">
                <button type="button" class="pwa-field-toggle" data-toggle-password="password" aria-label="Hiện mật khẩu">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </span>
        </label>
        <div class="pf-strength" id="passwordStrength">
            <div class="pf-strength-bar"><span></span></div>
            <small class="pf-strength-label">Nhập mật khẩu để kiểm tra độ mạnh</small>
        </div>
        <label class="pwa-field" for="password_confirmation">
            <span>Nhập lại mật khẩu mới</span>
            <span class="pwa-field-box">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="6" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới">
                <button type="button" class="pwa-field-toggle" data-toggle-password="password_confirmation" aria-label="Hiện mật khẩu">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </span>
        </label>
        <small class="pf-match" id="passwordMatchHint" hidden>
            <i class="fa-solid fa-circle-check"></i> Mật khẩu xác nhận khớp
        </small>

        <button type="submit" class="pwa-btn">
            <i class="fa-solid fa-shield-halved"></i>
            Đổi mật khẩu
        </button>
    </form>

    <form method="post" action="{{ route('pwa.logout') }}" data-pwa-wait="Đang đăng xuất…">
        @csrf
        <button type="submit" class="pwa-btn pwa-btn--ghost">
            <i class="fa-solid fa-right-from-bracket"></i>
            Đăng xuất
        </button>
    </form>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/profile-form.js') }}"></script>
    <script src="{{ asset('assets/js/profile-password.js') }}"></script>
@endpush
