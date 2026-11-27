@extends('layouts.app', ['active' => 'password'])

@section('title', 'Đổi mật khẩu')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}?v=2">
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
                        <label class="sn-field-label" for="current_password">Mật khẩu hiện tại</label>
                        <div class="sn-input-wrap @error('current_password') is-invalid @enderror">
                            <span class="sn-input-icon-left" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 10V8C6 4.69 7 2 12 2C17 2 18 4.69 18 8V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14.5 16C14.5 17.3807 13.3807 18.5 12 18.5C10.6193 18.5 9.5 17.3807 9.5 16C9.5 14.6193 10.6193 13.5 12 13.5C13.3807 13.5 14.5 14.6193 14.5 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17 22H7C3 22 2 21 2 17V15C2 11 3 10 7 10H17C21 10 22 11 22 15V17C22 21 21 22 17 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input
                                class="sn-input-control"
                                type="password"
                                id="current_password"
                                name="current_password"
                                required
                                autocomplete="current-password"
                                placeholder="Nhập mật khẩu hiện tại"
                            >
                            <button class="sn-toggle-pw" type="button" data-toggle-password="current_password" aria-label="Hiện mật khẩu" aria-pressed="false">
                                <svg class="sn-eye-icon sn-eye-icon--hidden" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M14.5299 9.47004L9.46992 14.53C8.81992 13.88 8.41992 12.99 8.41992 12C8.41992 10.02 10.0199 8.42004 11.9999 8.42004C12.9899 8.42004 13.8799 8.82004 14.5299 9.47004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17.8198 5.76998C16.0698 4.44998 14.0698 3.72998 11.9998 3.72998C8.46984 3.72998 5.17984 5.80998 2.88984 9.40998C1.98984 10.82 1.98984 13.19 2.88984 14.6C3.67984 15.84 4.59984 16.91 5.59984 17.77" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8.41992 19.5301C9.55992 20.0101 10.7699 20.2701 11.9999 20.2701C15.5299 20.2701 18.8199 18.1901 21.1099 14.5901C22.0099 13.1801 22.0099 10.8101 21.1099 9.40005C20.7799 8.88005 20.4199 8.39005 20.0499 7.93005" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15.5104 12.7C15.2504 14.11 14.1004 15.26 12.6904 15.52" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9.47 14.53L2 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22.0003 2L14.5303 9.47" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg class="sn-eye-icon sn-eye-icon--visible" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden>
                                    <path d="M15.58 12C15.58 13.98 13.98 15.58 12 15.58C10.02 15.58 8.42 13.98 8.42 12C8.42 10.02 10.02 8.42 12 8.42C13.98 8.42 15.58 10.02 15.58 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 20.27C15.53 20.27 18.82 18.19 21.11 14.59C22.01 13.18 22.01 10.81 21.11 9.4C18.82 5.8 15.53 3.72 12 3.72C8.47 3.72 5.18 5.8 2.89 9.4C1.99 10.81 1.99 13.18 2.89 14.59C5.18 18.19 8.47 20.27 12 20.27Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="sn-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 2. Mật khẩu mới --}}
                    <div class="sn-field-group">
                        <label class="sn-field-label" for="password">Mật khẩu mới</label>
                        <div class="sn-input-wrap @error('password') is-invalid @enderror">
                            <span class="sn-input-icon-left" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 10V8C6 4.69 7 2 12 2C17 2 18 4.69 18 8V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14.5 16C14.5 17.3807 13.3807 18.5 12 18.5C10.6193 18.5 9.5 17.3807 9.5 16C9.5 14.6193 10.6193 13.5 12 13.5C13.3807 13.5 14.5 14.6193 14.5 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17 22H7C3 22 2 21 2 17V15C2 11 3 10 7 10H17C21 10 22 11 22 15V17C22 21 21 22 17 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
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
                            <button class="sn-toggle-pw" type="button" data-toggle-password="password" aria-label="Hiện mật khẩu" aria-pressed="false">
                                <svg class="sn-eye-icon sn-eye-icon--hidden" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M14.5299 9.47004L9.46992 14.53C8.81992 13.88 8.41992 12.99 8.41992 12C8.41992 10.02 10.0199 8.42004 11.9999 8.42004C12.9899 8.42004 13.8799 8.82004 14.5299 9.47004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17.8198 5.76998C16.0698 4.44998 14.0698 3.72998 11.9998 3.72998C8.46984 3.72998 5.17984 5.80998 2.88984 9.40998C1.98984 10.82 1.98984 13.19 2.88984 14.6C3.67984 15.84 4.59984 16.91 5.59984 17.77" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8.41992 19.5301C9.55992 20.0101 10.7699 20.2701 11.9999 20.2701C15.5299 20.2701 18.8199 18.1901 21.1099 14.5901C22.0099 13.1801 22.0099 10.8101 21.1099 9.40005C20.7799 8.88005 20.4199 8.39005 20.0499 7.93005" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15.5104 12.7C15.2504 14.11 14.1004 15.26 12.6904 15.52" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9.47 14.53L2 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22.0003 2L14.5303 9.47" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg class="sn-eye-icon sn-eye-icon--visible" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden>
                                    <path d="M15.58 12C15.58 13.98 13.98 15.58 12 15.58C10.02 15.58 8.42 13.98 8.42 12C8.42 10.02 10.02 8.42 12 8.42C13.98 8.42 15.58 10.02 15.58 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 20.27C15.53 20.27 18.82 18.19 21.11 14.59C22.01 13.18 22.01 10.81 21.11 9.4C18.82 5.8 15.53 3.72 12 3.72C8.47 3.72 5.18 5.8 2.89 9.4C1.99 10.81 1.99 13.18 2.89 14.59C5.18 18.19 8.47 20.27 12 20.27Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
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
                        <label class="sn-field-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                        <div class="sn-input-wrap" id="confirmInputWrap">
                            <span class="sn-input-icon-left" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 10V8C6 4.69 7 2 12 2C17 2 18 4.69 18 8V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14.5 16C14.5 17.3807 13.3807 18.5 12 18.5C10.6193 18.5 9.5 17.3807 9.5 16C9.5 14.6193 10.6193 13.5 12 13.5C13.3807 13.5 14.5 14.6193 14.5 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17 22H7C3 22 2 21 2 17V15C2 11 3 10 7 10H17C21 10 22 11 22 15V17C22 21 21 22 17 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
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
                            <button class="sn-toggle-pw" type="button" data-toggle-password="password_confirmation" aria-label="Hiện mật khẩu" aria-pressed="false">
                                <svg class="sn-eye-icon sn-eye-icon--hidden" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M14.5299 9.47004L9.46992 14.53C8.81992 13.88 8.41992 12.99 8.41992 12C8.41992 10.02 10.0199 8.42004 11.9999 8.42004C12.9899 8.42004 13.8799 8.82004 14.5299 9.47004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17.8198 5.76998C16.0698 4.44998 14.0698 3.72998 11.9998 3.72998C8.46984 3.72998 5.17984 5.80998 2.88984 9.40998C1.98984 10.82 1.98984 13.19 2.88984 14.6C3.67984 15.84 4.59984 16.91 5.59984 17.77" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8.41992 19.5301C9.55992 20.0101 10.7699 20.2701 11.9999 20.2701C15.5299 20.2701 18.8199 18.1901 21.1099 14.5901C22.0099 13.1801 22.0099 10.8101 21.1099 9.40005C20.7799 8.88005 20.4199 8.39005 20.0499 7.93005" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15.5104 12.7C15.2504 14.11 14.1004 15.26 12.6904 15.52" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9.47 14.53L2 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22.0003 2L14.5303 9.47" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg class="sn-eye-icon sn-eye-icon--visible" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden>
                                    <path d="M15.58 12C15.58 13.98 13.98 15.58 12 15.58C10.02 15.58 8.42 13.98 8.42 12C8.42 10.02 10.02 8.42 12 8.42C13.98 8.42 15.58 10.02 15.58 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 20.27C15.53 20.27 18.82 18.19 21.11 14.59C22.01 13.18 22.01 10.81 21.11 9.4C18.82 5.8 15.53 3.72 12 3.72C8.47 3.72 5.18 5.8 2.89 9.4C1.99 10.81 1.99 13.18 2.89 14.59C5.18 18.19 8.47 20.27 12 20.27Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
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
    <script src="{{ asset('assets/js/profile-password.js') }}?v=2"></script>
@endpush
