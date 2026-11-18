@php
    $editUser = $editUser ?? null;
    $roles = $roles ?? \App\Support\UserRole::labels();
    $statuses = $statuses ?? \App\Support\UserRole::statuses();
@endphp

<form id="userModalForm" method="post" action="{{ $editUser ? route('admin.users.update', $editUser->id) : route('admin.users.store') }}" style="font-family: 'Nunito', sans-serif;">
    @csrf
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div style="grid-column: span 2;">
            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                Họ và tên <span style="color: #e11d48;">*</span>
            </label>
            <input type="text" name="full_name" required minlength="2" maxlength="120"
                value="{{ old('full_name', $editUser?->full_name ?? $editUser?->name ?? '') }}"
                placeholder="Ví dụ: Nguyễn Văn A"
                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px;">
        </div>

        <div>
            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                Số điện thoại <span style="color: #e11d48;">*</span>
            </label>
            <input type="tel" name="phone" id="userPhoneInput" required minlength="9" maxlength="20"
                value="{{ old('phone', $editUser?->phone ?? '') }}"
                placeholder="0912345678"
                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px;">
        </div>

        <div>
            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                Tên đăng nhập <small style="color: #64748b; font-weight: 400;">(tự sinh từ SĐT)</small>
            </label>
            <input type="text" name="username" id="userUsernameInput" maxlength="50"
                value="{{ old('username', $editUser?->username ?? '') }}"
                placeholder="Tự động nếu để trống"
                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px;">
        </div>

        <div style="grid-column: span 2;">
            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                Địa chỉ Email <span style="color: #e11d48;">*</span>
            </label>
            <input type="email" name="email" required maxlength="120"
                value="{{ old('email', $editUser?->email ?? '') }}"
                placeholder="email@example.com"
                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px;">
        </div>

        <div style="grid-column: span 2;">
            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                Mật khẩu <span style="color: #e11d48;">{{ $editUser ? '(để trống nếu không đổi)' : '*' }}</span>
            </label>
            <input type="password" name="password" minlength="6" {{ $editUser ? '' : 'required' }}
                placeholder="{{ $editUser ? 'Để trống nếu giữ nguyên mật khẩu cũ' : 'Tối thiểu 6 ký tự' }}"
                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px;">
        </div>

        <div>
            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                Vai trò hệ thống <span style="color: #e11d48;">*</span>
            </label>
            <select name="role" id="userRoleSelect" required style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px; background: #fff;">
                @foreach ($roles as $key => $label)
                    <option value="{{ $key }}" @selected(old('role', $editUser?->role ?? 'student') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                Trạng thái hoạt động <span style="color: #e11d48;">*</span>
            </label>
            <select name="status" required style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px; background: #fff;">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $editUser?->status ?? 'active') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if ($editUser?->student_code)
            <div style="grid-column: span 2;">
                <label style="display: block; font-size: 13.5px; font-weight: 700; color: #64748b; margin-bottom: 6px;">
                    Mã học viên (Hệ thống cấp)
                </label>
                <input type="text" readonly value="{{ $editUser->student_code }}" style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14.5px; background: #f8fafc; color: #64748b;">
            </div>
        @endif
    </div>

    <div style="margin-top: 24px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
        <button type="button" class="js-close-user-modal" style="padding: 10px 18px; border-radius: 10px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer;">
            Hủy bỏ
        </button>
        <button type="submit" style="padding: 10px 22px; border-radius: 10px; background: #9a4700; color: #ffffff; font-weight: 600; font-size: 14px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-check"></i>
            <span>{{ $editUser ? 'Cập nhật thành viên' : 'Thêm mới thành viên' }}</span>
        </button>
    </div>
</form>
