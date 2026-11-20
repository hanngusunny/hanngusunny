@extends('layouts.app', ['active' => 'profile'])

@section('title', 'Hồ sơ cá nhân')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
@endpush

@section('content')
@php
    $avatarUrl = $user?->avatarUrl();
@endphp

<div class="sn-profile-wrapper">
    {{-- Toast thông báo thành công chuẩn Figma --}}
    @if (session('success'))
        <div class="sn-profile-toast" role="alert" id="profileToast">
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

    {{-- Khung trắng tổng thể theo Figma Node #427:44365 --}}
    <div class="sn-profile-card">
        {{-- Tiêu đề trang: H2/Bold 24px #171C22 --}}
        <div class="sn-profile-head">
            <h1 class="sn-profile-title">Hồ sơ cá nhân</h1>
        </div>

        {{-- Container xám nhạt #F4F5F7 bo góc 20px chứa 2 cột --}}
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="sn-profile-container" id="profileForm">
            @csrf

            <div class="sn-profile-grid">
                {{-- Cột trái (width: 280px) --}}
                <div class="sn-profile-col-left">
                    {{-- Card Avatar (Figma Node #511:31802) --}}
                    <div class="sn-avatar-card">
                        <div class="sn-avatar-box">
                            <div class="sn-avatar-circle" data-user-avatar>
                                <span class="sn-avatar-fallback" data-avatar-fallback>{{ $user?->initials }}</span>
                                <img
                                    id="avatarPreview"
                                    @if ($avatarUrl) src="{{ $avatarUrl }}" @endif
                                    alt="Ảnh đại diện của {{ $user?->name }}"
                                    @if (! $avatarUrl) hidden @endif
                                    data-user-avatar-img
                                >
                            </div>
                        </div>

                        <div class="sn-avatar-user-info">
                            <h2 class="sn-avatar-user-name">{{ $user?->full_name }}</h2>
                            <span class="sn-avatar-role-badge">{{ $user?->role_label }}</span>
                        </div>

                        <div class="sn-avatar-actions">
                            <input
                                class="sn-avatar-input"
                                type="file"
                                id="avatar"
                                name="avatar"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                hidden
                            >
                            <button type="button" class="sn-btn-choose-avatar" id="btnChooseAvatar">
                                Chọn ảnh mới
                            </button>
                            <p class="sn-avatar-hint">
                                JPG, PNG · Tối đa 5 MB<br>
                                Ảnh vuông sẽ hiển thị đẹp nhất.
                            </p>
                        </div>
                    </div>

                    {{-- Card Thống kê "Tổng thời gian hoạt động" (Figma Node #511:31803) --}}
                    <div class="sn-stat-card">
                        <span class="sn-stat-card-title">Tổng thời gian hoạt động</span>
                        <div class="sn-stat-card-body">
                            <span class="sn-stat-card-value">{{ $activeHoursText ?? '0 phút' }}</span>
                            <span class="sn-stat-card-subtext">{{ $activeAvgDailyText ?? 'Chưa ghi nhận hoạt động' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Cột phải: Form thông tin tài khoản (Figma Node #427:44373) --}}
                <div class="sn-profile-col-right">
                    <div class="sn-account-form-card">
                        <div class="sn-account-form-header">
                            <div class="sn-account-form-title-group">
                                <h3 class="sn-account-form-title">Thông tin tài khoản</h3>
                                <p class="sn-account-form-subtitle">Cập nhật thông tin để trung tâm hỗ trợ bạn tốt hơn.</p>
                            </div>
                        </div>

                        <div class="sn-account-form-body">
                            {{-- 3 trường chỉnh sửa --}}
                            <div class="sn-field-group">
                                <label class="sn-field-label" for="full_name">Họ tên <span>*</span></label>
                                <input
                                    class="sn-field-input"
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    required
                                    value="{{ old('full_name', $user?->full_name) }}"
                                    placeholder="Nhập họ và tên"
                                >
                            </div>

                            <div class="sn-field-group">
                                <label class="sn-field-label" for="email">Email <span>*</span></label>
                                <input
                                    class="sn-field-input"
                                    type="email"
                                    id="email"
                                    name="email"
                                    required
                                    value="{{ old('email', $user?->email) }}"
                                    placeholder="Nhập địa chỉ email"
                                >
                            </div>

                            <div class="sn-field-group">
                                <label class="sn-field-label" for="phone">Số điện thoại</label>
                                <input
                                    class="sn-field-input"
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone', $user?->phone) }}"
                                    placeholder="0912345678"
                                >
                            </div>

                            {{-- 3 trường chỉ đọc chuẩn Figma --}}
                            <div class="sn-readonly-group">
                                <div class="sn-readonly-col-left">
                                    <div class="sn-field-group">
                                        <label class="sn-field-label" for="username">Tên đăng nhập</label>
                                        <input
                                            class="sn-field-input is-disabled"
                                            type="text"
                                            id="username"
                                            value="{{ $user?->username }}"
                                            disabled
                                            readonly
                                        >
                                        <p class="sn-field-hint">Thông tin do trung tâm quản lý, không thể chỉnh sửa.</p>
                                    </div>

                                    <div class="sn-field-group">
                                        <label class="sn-field-label" for="student_code">Mã học viên</label>
                                        <input
                                            class="sn-field-input is-disabled"
                                            type="text"
                                            id="student_code"
                                            value="{{ $user?->student_code ?? ($user?->username ?? '') }}"
                                            disabled
                                            readonly
                                        >
                                        <p class="sn-field-hint">Thông tin do trung tâm quản lý, không thể chỉnh sửa.</p>
                                    </div>
                                </div>

                                <div class="sn-readonly-col-right">
                                    <div class="sn-field-group">
                                        <label class="sn-field-label" for="role">Vai trò</label>
                                        <input
                                            class="sn-field-input is-disabled"
                                            type="text"
                                            id="role"
                                            value="{{ $user?->role_label }}"
                                            disabled
                                            readonly
                                        >
                                        <p class="sn-field-hint">Thông tin do trung tâm quản lý, không thể chỉnh sửa.</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Nút Lưu thay đổi duy nhất, cam thương hiệu 3D --}}
                            <div class="sn-form-actions">
                                <button type="submit" class="sn-btn-save" id="btnSubmitProfile">
                                    Lưu thay đổi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/profile-form.js') }}"></script>
@endpush
