@extends('layouts.app', ['active' => 'password'])

@section('title', 'Đổi mật khẩu')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
@endpush

@section('content')
<div class="op-stack pf-page">
    <x-page-title kicker="Tài khoản" icon="fa-lock" title="Đổi mật khẩu" lead="Cập nhật mật khẩu đăng nhập để bảo mật tài khoản." />

    @include('profile.partials.nav', ['activeTab' => $activeTab ?? 'password'])

    <div class="pf-password-layout">
        <aside class="op-panel pf-tips">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-shield-halved"></i></span>
                <span class="op-panel-title">Bảo vệ tài khoản</span>
            </div>
            <div class="op-panel-body">
                <ul class="pf-tip-list">
                    <li><i class="fa-solid fa-check"></i> Dùng ít nhất <strong>6 ký tự</strong> — nên từ 8 ký tự trở lên</li>
                    <li><i class="fa-solid fa-check"></i> Kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt</li>
                    <li><i class="fa-solid fa-check"></i> Không dùng lại mật khẩu cũ hoặc mật khẩu dễ đoán</li>
                    <li><i class="fa-solid fa-check"></i> Đăng xuất thiết bị công cộng sau khi dùng xong</li>
                </ul>
                <p class="pf-hint">Sau khi đổi mật khẩu, bạn vẫn giữ phiên đăng nhập hiện tại trên trình duyệt này.</p>
            </div>
        </aside>

        <section class="op-panel">
            <div class="op-panel-head is-static">
                <span class="op-panel-ico"><i class="fa-solid fa-key"></i></span>
                <span class="op-panel-title">Thiết lập mật khẩu mới</span>
            </div>
            <div class="op-panel-body">
                @if (isset($errors) && $errors->any())
                    <div class="pf-alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="post" action="{{ route('profile.password.update') }}" class="pf-password-form" id="passwordForm">
                    @csrf
                    <div class="pf-field pf-field--full">
                        <label for="current_password">Mật khẩu hiện tại</label>
                        <div class="pf-input-wrap">
                            <input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password" placeholder="Nhập mật khẩu đang dùng">
                            <button class="pf-toggle-pw" type="button" data-toggle-password="current_password" aria-label="Hiện mật khẩu">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="pf-field">
                        <label for="password">Mật khẩu mới</label>
                        <div class="pf-input-wrap">
                            <input class="input" type="password" id="password" name="password" required minlength="6" autocomplete="new-password" placeholder="Tối thiểu 6 ký tự">
                            <button class="pf-toggle-pw" type="button" data-toggle-password="password" aria-label="Hiện mật khẩu">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="pf-strength" id="passwordStrength" aria-live="polite">
                            <div class="pf-strength-bar"><span></span></div>
                            <small class="pf-strength-label">Nhập mật khẩu để kiểm tra độ mạnh</small>
                        </div>
                    </div>
                    <div class="pf-field">
                        <label for="password_confirmation">Nhập lại mật khẩu mới</label>
                        <div class="pf-input-wrap">
                            <input class="input" type="password" id="password_confirmation" name="password_confirmation" required minlength="6" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới">
                            <button class="pf-toggle-pw" type="button" data-toggle-password="password_confirmation" aria-label="Hiện mật khẩu">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="pf-match" id="passwordMatchHint" hidden>
                            <i class="fa-solid fa-circle-check"></i> Mật khẩu xác nhận khớp
                        </small>
                    </div>
                    <div class="pf-actions">
                        <x-button variant="white" href="{{ route('profile.show') }}" icon="fa-solid fa-arrow-left">Quay lại hồ sơ</x-button>
                        <x-button type="submit" icon="fa-solid fa-shield">Lưu mật khẩu mới</x-button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/profile-password.js') }}"></script>
@endpush
