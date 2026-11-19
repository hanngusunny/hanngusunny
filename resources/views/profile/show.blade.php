@extends('layouts.app', ['active' => 'profile'])

@section('title', 'Hồ sơ cá nhân')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
@endpush

@section('content')
@php
    $avatarUrl = $user->avatarUrl();
@endphp
<div class="op-stack pf-page">
    <x-page-title kicker="Tài khoản" icon="fa-user" title="Hồ sơ cá nhân" lead="Cập nhật thông tin và ảnh đại diện của bạn." />

    @include('profile.partials.nav', ['activeTab' => $activeTab ?? 'profile'])

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="pf-form">
        @csrf

        @if (isset($errors) && $errors->any())
            <div class="pf-alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="pf-layout">
            <section class="op-panel">
                <div class="op-panel-head is-static">
                    <span class="op-panel-ico"><i class="fa-solid fa-image"></i></span>
                    <span class="op-panel-title">Ảnh đại diện</span>
                </div>
                <div class="op-panel-body pf-avatar-body">
                    <div class="pf-avatar-preview{{ $avatarUrl ? '' : ' is-fallback' }}" data-user-avatar>
                        <span class="pf-avatar-fallback" data-avatar-fallback>{{ $user->initials }}</span>
                        <img
                            id="avatarPreview"
                            @if ($avatarUrl) src="{{ $avatarUrl }}" @endif
                            alt="Ảnh đại diện của {{ $user->name }}"
                            @if (! $avatarUrl) hidden @endif
                            data-user-avatar-img
                        >
                    </div>
                    <div class="pf-avatar-actions">
                        <label class="op-btn-ghost" for="avatar">
                            <i class="fa-solid fa-camera"></i>
                            <span>Chọn ảnh mới</span>
                        </label>
                        <input
                            class="pf-avatar-input"
                            type="file"
                            id="avatar"
                            name="avatar"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                        >
                        @if ($avatarUrl)
                            <label class="pf-remove-avatar">
                                <input type="checkbox" name="remove_avatar" value="1">
                                <span>Xóa ảnh hiện tại</span>
                            </label>
                        @endif
                        <p class="pf-hint">JPG, PNG, WEBP hoặc GIF — tối đa 2MB.</p>
                    </div>
                </div>
            </section>

            <section class="op-panel">
                <div class="op-panel-head is-static">
                    <span class="op-panel-ico"><i class="fa-solid fa-id-card"></i></span>
                    <span class="op-panel-title">Thông tin tài khoản</span>
                </div>
                <div class="op-panel-body pf-fields">
                    <div class="pf-field pf-field--full">
                        <label for="full_name">Họ và tên <span>*</span></label>
                        <input class="input" type="text" id="full_name" name="full_name" required value="{{ old('full_name', $user->full_name) }}">
                    </div>
                    <div class="pf-field">
                        <label for="email">Email <span>*</span></label>
                        <input class="input" type="email" id="email" name="email" required value="{{ old('email', $user->email) }}">
                    </div>
                    <div class="pf-field">
                        <label for="phone">Số điện thoại</label>
                        <input class="input" type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0912345678">
                    </div>
                    <div class="pf-field">
                        <label for="username">Tên đăng nhập</label>
                        <input class="input" type="text" id="username" value="{{ $user->username }}" disabled readonly>
                        <small class="pf-hint">Tên đăng nhập không thể thay đổi.</small>
                    </div>
                    @if ($user->isLearner() && filled($user->student_code))
                        <div class="pf-field">
                            <label for="student_code">Mã học viên</label>
                            <input class="input" type="text" id="student_code" value="{{ $user->student_code }}" disabled readonly>
                        </div>
                    @endif
                    <div class="pf-field">
                        <label for="role">Vai trò</label>
                        <input class="input" type="text" id="role" value="{{ $user->role_label }}" disabled readonly>
                    </div>
                    <div class="pf-meta">
                        <span><i class="fa-solid fa-shield"></i> {{ $user->status_label }}</span>
                        @if ($user->created_at)
                            <span><i class="fa-regular fa-calendar"></i> Tham gia {{ $user->created_at->format('d/m/Y') }}</span>
                        @endif
                        @if ($user->last_login_at)
                            <span><i class="fa-solid fa-right-to-bracket"></i> Đăng nhập {{ $user->last_login_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                    <div class="pf-actions">
                        <x-button variant="white" href="{{ $user->homePath() }}">Hủy</x-button>
                        <x-button type="submit" icon="fa-solid fa-floppy-disk">Lưu thay đổi</x-button>
                    </div>
                </div>
            </section>
        </div>
    </form>

    @include('profile.partials.activity-summary', [
        'user' => $user,
        'activitySummary' => $activitySummary ?? [],
        'activityHelper' => $activityHelper ?? app(\App\Services\UserActivityService::class),
        'activityDetailUrl' => $activityDetailUrl ?? null,
    ])
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/profile-form.js') }}"></script>
@endpush
