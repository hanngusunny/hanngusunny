@extends('layouts.app', ['active' => 'password'])

@section('title', 'Đổi mật khẩu')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
@endpush

@section('content')
@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp
<div class="sn-profile-wrapper">
    {{-- Toast thông báo thành công chuẩn Figma --}}
    @if (session('success'))
        <div class="sn-profile-toast" role="alert" id="passwordToast">
            <div class="sn-profile-toast-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="sn-profile-toast-text">
                {{ session('success') }}
            </div>
            <button type="button" class="sn-profile-toast-close" onclick="this.closest('.sn-profile-toast').remove()" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    {{-- Khung lỗi nếu có --}}
    @if (isset($errors) && $errors->any())
        <div class="sn-profile-alert" role="alert">
            <div class="sn-profile-alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="sn-profile-alert-content">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Khung trắng tổng thể theo Figma Node #569:44476 --}}
    <div class="sn-profile-card">
        {{-- Tiêu đề trang: H2/Bold 24px #171C22 --}}
        <div class="sn-profile-head">
            <h1 class="sn-profile-title">Đổi mật khẩu</h1>
        </div>

        {{-- Container xám nhạt #F4F5F7 bo góc 20px chứa Card đổi mật khẩu --}}
        <div class="sn-security-container">
            <form method="post" action="{{ route('profile.password.update') }}" class="sn-password-form-card" id="passwordForm">
                @csrf

                <div class="sn-password-form-header">
                    <div class="sn-password-form-title-group">
                        <h3 class="sn-password-form-title">Thay đổi mật khẩu</h3>
                        <p class="sn-password-form-subtitle">Để bảo vệ tài khoản, hãy chọn mật khẩu khác với mật khẩu hiện tại và không chia sẻ với người khác.</p>
                    </div>
                </div>

                <div class="sn-password-form-body">
                    {{-- 1. Mật khẩu hiện tại --}}
                    <div class="sn-field-group">
                        <label class="sn-field-label" for="current_password">Mật khẩu hiện tại <span>*</span></label>
                        <div class="sn-input-wrap @error('current_password') is-invalid @enderror">
                            <span class="sn-input-icon-left" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                            <input
                                class="sn-input-control"
                                type="password"
                                id="current_password"
                                name="current_password"
                                required
                                autocomplete="current-password"
                                placeholder="Nhập mật khẩu hiện tại"
                            >
                            <button class="sn-toggle-pw" type="button" data-toggle-password="current_password" aria-label="Hiện mật khẩu">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="sn-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 2. Mật khẩu mới --}}
                    <div class="sn-field-group">
                        <label class="sn-field-label" for="password">Mật khẩu mới <span>*</span></label>
                        <div class="sn-input-wrap @error('password') is-invalid @enderror">
                            <span class="sn-input-icon-left" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                            <input
                                class="sn-input-control"
                                type="password"
                                id="password"
                                name="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Nhập mật khẩu mới"
                            >
                            <button class="sn-toggle-pw" type="button" data-toggle-password="password" aria-label="Hiện mật khẩu">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="sn-field-error">{{ $message }}</p>
                        @enderror

                        {{-- Cụm 4 tiêu chí bảo mật sống động chuẩn Figma Node #406:26819 --}}
                        <div class="sn-password-rules" id="passwordRules" aria-live="polite">
                            <div class="sn-rule-item" data-rule="length">
                                <i class="fa-solid fa-circle-check sn-rule-icon"></i>
                                <span class="sn-rule-text">Ít nhất 8 ký tự</span>
                            </div>
                            <div class="sn-rule-item" data-rule="uppercase">
                                <i class="fa-solid fa-circle-check sn-rule-icon"></i>
                                <span class="sn-rule-text">Có chữ hoa (A–Z)</span>
                            </div>
                            <div class="sn-rule-item" data-rule="lowercase">
                                <i class="fa-solid fa-circle-check sn-rule-icon"></i>
                                <span class="sn-rule-text">Có chữ thường (a–z)</span>
                            </div>
                            <div class="sn-rule-item" data-rule="number">
                                <i class="fa-solid fa-circle-check sn-rule-icon"></i>
                                <span class="sn-rule-text">Có chữ số (0–9)</span>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Xác nhận mật khẩu mới --}}
                    <div class="sn-field-group">
                        <label class="sn-field-label" for="password_confirmation">Xác nhận mật khẩu mới <span>*</span></label>
                        <div class="sn-input-wrap" id="confirmInputWrap">
                            <span class="sn-input-icon-left" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                            <input
                                class="sn-input-control"
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Nhập lại mật khẩu mới"
                            >
                            <button class="sn-toggle-pw" type="button" data-toggle-password="password_confirmation" aria-label="Hiện mật khẩu">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <p class="sn-field-error" id="confirmMismatchError" hidden>Mật khẩu xác nhận không khớp với mật khẩu mới.</p>
                        <p class="sn-field-match" id="confirmMatchSuccess" hidden>
                            <i class="fa-solid fa-circle-check"></i> Mật khẩu xác nhận khớp
                        </p>
                    </div>

                    {{-- Thao tác bảo mật: Nút Hủy và Nút Cập nhật mật khẩu --}}
                    <div class="sn-password-actions">
                        <a href="{{ route('profile.show') }}" class="sn-btn-cancel">
                            Hủy
                        </a>
                        <button type="submit" class="sn-btn-submit-password" id="btnSubmitPassword">
                            Cập nhật mật khẩu
                        </button>
                    </div>
                </div>
            </form>

            {{-- Khối Hướng dẫn bảo mật dưới form chuẩn Figma (Node #569:44491) --}}
            <div class="sn-security-guide">
                <div class="sn-security-guide-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <p class="sn-security-guide-text">
                    Không dùng ngày sinh hoặc số điện thoại làm mật khẩu. Bạn có thể dùng chức năng Quên mật khẩu nếu không nhớ mật khẩu hiện tại.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/profile-password.js') }}"></script>
@endpush
