@extends('layouts.app', ['active' => 'members'])

@section('title', 'Quản lý thành viên')

@section('subnav')
    <a href="{{ route('admin.users.index') }}" class="os-tab is-on">Danh sách thành viên</a>
@endsection

@push('styles')
<style>
    .user-tab-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 9999px;
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .user-tab-item.is-active {
        background: #9a4700;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(154, 71, 0, 0.25);
    }
    .user-tab-item:not(.is-active) {
        background: #f1f5f9;
        color: #475569;
        border-color: #e2e8f0;
    }
    .user-tab-item:not(.is-active):hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .user-tab-badge {
        font-size: 11.5px;
        padding: 2px 7px;
        border-radius: 12px;
        font-weight: 700;
    }
    .user-tab-item.is-active .user-tab-badge {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }
    .user-tab-item:not(.is-active) .user-tab-badge {
        background: #e2e8f0;
        color: #64748b;
    }
    .user-role-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
    }
    .user-role-admin {
        background: #e0e7ff;
        color: #3730a3;
    }
    .user-role-teacher {
        background: #f3e8ff;
        color: #6b21a8;
    }
    .user-role-student {
        background: #ecfdf5;
        color: #047857;
    }
    .user-role-other {
        background: #f1f5f9;
        color: #475569;
    }
    .user-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        font-weight: 600;
    }
    .user-status-pill .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .user-status-active {
        color: #15803d;
    }
    .user-status-active .dot {
        background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
    }
    .user-status-inactive {
        color: #b91c1c;
    }
    .user-status-inactive .dot {
        background: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
    }
    /* Modal Styles */
    .user-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
    .user-modal-overlay.is-open {
        opacity: 1;
        pointer-events: auto;
    }
    .user-modal-card {
        background: #ffffff;
        border-radius: 16px;
        width: 92%;
        max-width: 580px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: translateY(16px);
        transition: transform 0.2s ease;
        overflow: hidden;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }
    .user-modal-overlay.is-open .user-modal-card {
        transform: translateY(0);
    }
</style>
@endpush

@section('content')
<div class="op-stack" style="font-family: 'Nunito', sans-serif;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 8px;">
        <x-page-title kicker="Quản trị hệ thống" icon="fa-users" title="Quản lý thành viên" lead="Quản lý tài khoản quản trị viên, giáo viên và học viên tại Hán ngữ Sunny." />
        <button type="button" class="op-btn js-open-create-modal" style="background: #9a4700; color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(154,71,0,0.25);">
            <i class="fa-solid fa-user-plus"></i>
            <span>Thêm thành viên</span>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="op-stat-grid is-4" style="margin-bottom: 24px;">
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-blue"><i class="fa-solid fa-users"></i></span>
                <p>Tổng thành viên<small>Tất cả tài khoản</small></p>
            </span>
            <strong>{{ number_format($stats['total']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-sky"><i class="fa-solid fa-shield-halved"></i></span>
                <p>Quản trị viên<small>Có quyền admin</small></p>
            </span>
            <strong>{{ number_format($stats['admins']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-emerald"><i class="fa-solid fa-chalkboard-user"></i></span>
                <p>Giáo viên<small>Giảng dạy tại trung tâm</small></p>
            </span>
            <strong>{{ number_format($stats['teachers']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-amber"><i class="fa-solid fa-graduation-cap"></i></span>
                <p>Học viên<small>Học viên Sunny & du học</small></p>
            </span>
            <strong>{{ number_format($stats['students']) }}</strong>
        </article>
    </div>

    <!-- Main Container -->
    <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden;">
        <!-- Top Toolbar with Role Tabs & Search -->
        <div style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">
            <!-- Role Tabs (Figma Frame 03) -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ route('admin.users.index', array_merge($filters, ['role' => ''])) }}"
                       class="user-tab-item {{ empty($filters['role']) ? 'is-active' : '' }}">
                        <i class="fa-solid fa-users"></i>
                        <span>Tất cả</span>
                        <span class="user-tab-badge">{{ $stats['total'] }}</span>
                    </a>
                    <a href="{{ route('admin.users.index', array_merge($filters, ['role' => 'admin'])) }}"
                       class="user-tab-item {{ $filters['role'] === 'admin' ? 'is-active' : '' }}">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Quản trị viên</span>
                        <span class="user-tab-badge">{{ $stats['admins'] }}</span>
                    </a>
                    <a href="{{ route('admin.users.index', array_merge($filters, ['role' => 'teacher'])) }}"
                       class="user-tab-item {{ $filters['role'] === 'teacher' ? 'is-active' : '' }}">
                        <i class="fa-solid fa-chalkboard-user"></i>
                        <span>Giáo viên</span>
                        <span class="user-tab-badge">{{ $stats['teachers'] }}</span>
                    </a>
                    <a href="{{ route('admin.users.index', array_merge($filters, ['role' => 'student'])) }}"
                       class="user-tab-item {{ $filters['role'] === 'student' ? 'is-active' : '' }}">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Học viên</span>
                        <span class="user-tab-badge">{{ $stats['students'] }}</span>
                    </a>
                </div>

                <!-- Status Filter & Search Form -->
                <form method="get" action="{{ route('admin.users.index') }}" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    @if (!empty($filters['role']))
                        <input type="hidden" name="role" value="{{ $filters['role'] }}">
                    @endif
                    <select name="status" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; background: #ffffff;">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $k => $lbl)
                            <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $lbl }}</option>
                        @endforeach
                    </select>

                    <div style="display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #ffffff;">
                        <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm tên, SĐT, email..." style="border: none; padding: 8px 14px; font-size: 13.5px; outline: none; width: 220px;">
                        <button type="submit" style="background: none; border: none; padding: 8px 14px; color: #64748b; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    @if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['role']))
                        <a href="{{ route('admin.users.index') }}" style="padding: 8px 12px; border-radius: 10px; background: #f1f5f9; color: #64748b; font-size: 13px; text-decoration: none; font-weight: 600;" title="Xóa bộ lọc">
                            <i class="fa-solid fa-xmark"></i> Xóa lọc
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Table View or Empty State -->
        <div class="op-panel-body is-flush">
            @if ($users->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm" style="border-collapse: collapse;">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">
                            <tr>
                                <th style="padding: 14px 20px; font-weight: 700;">Thành viên</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Liên hệ</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Vai trò</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Trạng thái</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Mã HV</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Đăng nhập cuối</th>
                                <th style="padding: 14px 20px; font-weight: 700; text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody style="divide-y: 1px solid #f1f5f9;">
                            @foreach ($users as $user)
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                                    <td style="padding: 14px 20px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <x-user-avatar
                                                class="h-10 w-10 rounded-full bg-slate-100 text-xs font-bold text-slate-700 shadow-sm"
                                                :url="$user->avatarUrl()"
                                                :name="$user->name"
                                            />
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 14.5px;">{{ $user->full_name ?: $user->username }}</div>
                                                <div style="color: #64748b; font-size: 12.5px;">{{ '@'.$user->username }}</div>
                                                @if ($user->id === auth()->id())
                                                    <span style="display: inline-block; font-size: 11px; font-weight: 700; color: #9a4700; background: #fff7ed; padding: 1px 6px; border-radius: 4px; margin-top: 2px;">Bạn</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 20px; color: #334155;">
                                        <div style="font-weight: 600;">{{ $user->phone ?: '—' }}</div>
                                        <div style="color: #64748b; font-size: 12.5px;">{{ $user->email }}</div>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        @php
                                            $roleClass = match($user->role) {
                                                'admin' => 'user-role-admin',
                                                'teacher' => 'user-role-teacher',
                                                'student', 'study_abroad_student' => 'user-role-student',
                                                default => 'user-role-other',
                                            };
                                        @endphp
                                        <span class="user-role-badge {{ $roleClass }}">
                                            @if ($user->role === 'admin') <i class="fa-solid fa-shield-halved"></i>
                                            @elseif ($user->role === 'teacher') <i class="fa-solid fa-chalkboard-user"></i>
                                            @else <i class="fa-solid fa-graduation-cap"></i>
                                            @endif
                                            {{ $user->role_label }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span class="user-status-pill {{ $user->status === 'active' ? 'user-status-active' : 'user-status-inactive' }}">
                                            <span class="dot"></span>
                                            <span>{{ $user->status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</span>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px; color: #475569; font-weight: 600;">
                                        {{ $user->student_code ?: '—' }}
                                    </td>
                                    <td style="padding: 14px 20px; color: #64748b; font-size: 13px;">
                                        {{ $user->last_login_at?->format('d/m/Y H:i') ?: 'Chưa đăng nhập' }}
                                    </td>
                                    <td style="padding: 14px 20px; text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                                            <button type="button" class="js-edit-user-btn" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;"
                                                data-id="{{ $user->id }}"
                                                data-full-name="{{ $user->full_name }}"
                                                data-username="{{ $user->username }}"
                                                data-phone="{{ $user->phone }}"
                                                data-email="{{ $user->email }}"
                                                data-role="{{ $user->role }}"
                                                data-status="{{ $user->status }}"
                                                data-student-code="{{ $user->student_code }}"
                                                title="Chỉnh sửa">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            @if ($user->id !== auth()->id())
                                                <button type="button" class="js-trigger-toggle-btn" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid {{ $user->status === 'active' ? '#fed7aa' : '#bbf7d0' }}; background: {{ $user->status === 'active' ? '#fff7ed' : '#f0fdf4' }}; color: {{ $user->status === 'active' ? '#c2410c' : '#15803d' }}; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->full_name ?: $user->username }}"
                                                    data-action-url="{{ route('admin.users.toggle-status', $user->id) }}"
                                                    data-status="{{ $user->status }}"
                                                    title="{{ $user->status === 'active' ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}">
                                                    <i class="fa-solid {{ $user->status === 'active' ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                                </button>

                                                <button type="button" class="js-trigger-delete-btn" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->full_name ?: $user->username }}"
                                                    data-action-url="{{ route('admin.users.destroy', $user->id) }}"
                                                    title="Xóa tài khoản">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0;">
                        {{ $users->onEachSide(1)->links('pagination.office') }}
                    </div>
                @endif
            @else
                <!-- Empty State (Figma Frame 01) -->
                <div style="text-align: center; padding: 70px 20px;">
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-users-slash"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 8px;">Không tìm thấy thành viên nào</h3>
                    <p style="color: #64748b; font-size: 14px; max-width: 460px; margin: 0 auto 20px; line-height: 1.6;">
                        Không có dữ liệu thành viên nào phù hợp với điều kiện tìm kiếm hoặc bộ lọc hiện tại. Thử xóa bộ lọc hoặc tạo mới thành viên.
                    </p>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                        @if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['role']))
                            <a href="{{ route('admin.users.index') }}" style="padding: 9px 18px; border-radius: 10px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 14px; text-decoration: none; border: 1px solid #cbd5e1;">
                                <i class="fa-solid fa-arrow-rotate-left"></i> Xóa bộ lọc
                            </a>
                        @endif
                        <button type="button" class="js-open-create-modal" style="padding: 10px 20px; border-radius: 10px; background: #9a4700; color: #ffffff; font-weight: 600; font-size: 14px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-user-plus"></i> Thêm thành viên ngay
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>

<!-- Modal: Create / Edit User (Figma Frame 04) -->
<div class="user-modal-overlay" id="userFormModal">
    <div class="user-modal-card">
        <div style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #fff7ed; color: #9a4700; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="fa-solid fa-user" id="userModalIcon"></i>
                </div>
                <div>
                    <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;" id="userModalTitle">Thêm thành viên</h3>
                    <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Điền thông tin tài khoản bên dưới</p>
                </div>
            </div>
            <button type="button" class="js-close-user-modal" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer; padding: 4px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div style="padding: 24px; overflow-y: auto;">
            @include('admin.users.form')
        </div>
    </div>
</div>

<!-- Modal: Confirmation for Lock / Delete (Figma Frame 05) -->
<div class="user-modal-overlay" id="confirmActionModal">
    <div class="user-modal-card" style="max-width: 440px; text-align: center; padding: 32px 24px;">
        <div id="confirmModalIconWrap" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
            <i id="confirmModalIcon"></i>
        </div>
        <h3 id="confirmModalTitle" style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Xác nhận thao tác</h3>
        <p id="confirmModalDesc" style="font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 24px;"></p>
        
        <form id="confirmActionForm" method="post" action="">
            @csrf
            <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                <button type="button" class="js-close-confirm-modal" style="padding: 10px 20px; border-radius: 10px; background: #f1f5f9; color: #334155; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer;">
                    Hủy bỏ
                </button>
                <button type="submit" id="confirmModalSubmitBtn" style="padding: 10px 22px; border-radius: 10px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; color: #ffffff;">
                    Xác nhận
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const userModal = document.getElementById('userFormModal');
    const userForm = document.getElementById('userModalForm');
    const userModalTitle = document.getElementById('userModalTitle');
    const userModalIcon = document.getElementById('userModalIcon');
    const phoneInput = document.getElementById('userPhoneInput');
    const usernameInput = document.getElementById('userUsernameInput');

    const confirmModal = document.getElementById('confirmActionModal');
    const confirmForm = document.getElementById('confirmActionForm');
    const confirmTitle = document.getElementById('confirmModalTitle');
    const confirmDesc = document.getElementById('confirmModalDesc');
    const confirmIconWrap = document.getElementById('confirmModalIconWrap');
    const confirmIcon = document.getElementById('confirmModalIcon');
    const confirmSubmitBtn = document.getElementById('confirmModalSubmitBtn');

    const storeUrl = @json(route('admin.users.store'));
    const updateUrlBase = @json(url('nguoi-dung/update'));

    // Phone auto username helper
    if (phoneInput && usernameInput) {
        phoneInput.addEventListener('input', () => {
            if (!usernameInput.dataset.manualEdited && !userForm.dataset.isEdit) {
                const cleanPhone = phoneInput.value.replace(/[^0-9]/g, '');
                if (cleanPhone) {
                    usernameInput.value = 'u' + cleanPhone;
                }
            }
        });
        usernameInput.addEventListener('input', () => {
            usernameInput.dataset.manualEdited = "true";
        });
    }

    // Open Create Modal
    document.querySelectorAll('.js-open-create-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            userForm.reset();
            delete userForm.dataset.isEdit;
            delete usernameInput.dataset.manualEdited;
            userForm.action = storeUrl;
            userModalTitle.textContent = "Thêm thành viên mới";
            userModalIcon.className = "fa-solid fa-user-plus";
            userModal.classList.add('is-open');
        });
    });

    // Open Edit Modal
    document.querySelectorAll('.js-edit-user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            userForm.reset();
            userForm.dataset.isEdit = "true";
            userForm.action = `${updateUrlBase}/${d.id}`;
            userModalTitle.textContent = "Chỉnh sửa thành viên: " + (d.fullName || d.username);
            userModalIcon.className = "fa-solid fa-user-pen";

            userForm.querySelector('[name="full_name"]').value = d.fullName || '';
            userForm.querySelector('[name="phone"]').value = d.phone || '';
            userForm.querySelector('[name="username"]').value = d.username || '';
            userForm.querySelector('[name="email"]').value = d.email || '';
            userForm.querySelector('[name="role"]').value = d.role || 'student';
            userForm.querySelector('[name="status"]').value = d.status || 'active';
            userForm.querySelector('[name="password"]').value = '';

            userModal.classList.add('is-open');
        });
    });

    // Close Modals
    document.querySelectorAll('.js-close-user-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            userModal.classList.remove('is-open');
        });
    });

    document.querySelectorAll('.js-close-confirm-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            confirmModal.classList.remove('is-open');
        });
    });

    // Toggle Status Confirm Modal
    document.querySelectorAll('.js-trigger-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            const isLocking = d.status === 'active';
            confirmForm.action = d.actionUrl;
            confirmTitle.textContent = isLocking ? 'Khóa tài khoản' : 'Mở khóa tài khoản';
            confirmDesc.innerHTML = `Bạn có chắc chắn muốn ${isLocking ? '<strong>khóa</strong>' : '<strong>mở khóa</strong>'} tài khoản của <strong>${d.name}</strong>?`;
            
            if (isLocking) {
                confirmIconWrap.style.background = '#fff7ed';
                confirmIconWrap.style.color = '#c2410c';
                confirmIcon.className = 'fa-solid fa-lock';
                confirmSubmitBtn.style.background = '#ea580c';
                confirmSubmitBtn.textContent = 'Khóa tài khoản';
            } else {
                confirmIconWrap.style.background = '#f0fdf4';
                confirmIconWrap.style.color = '#15803d';
                confirmIcon.className = 'fa-solid fa-lock-open';
                confirmSubmitBtn.style.background = '#16a34a';
                confirmSubmitBtn.textContent = 'Mở khóa ngay';
            }

            confirmModal.classList.add('is-open');
        });
    });

    // Delete Confirm Modal
    document.querySelectorAll('.js-trigger-delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            confirmForm.action = d.actionUrl;
            confirmTitle.textContent = 'Xóa tài khoản vĩnh viễn';
            confirmDesc.innerHTML = `Hành động này sẽ xóa hoàn toàn tài khoản của <strong>${d.name}</strong>. Thao tác này không thể hoàn tác!`;
            
            confirmIconWrap.style.background = '#fef2f2';
            confirmIconWrap.style.color = '#dc2626';
            confirmIcon.className = 'fa-solid fa-trash-can';
            confirmSubmitBtn.style.background = '#dc2626';
            confirmSubmitBtn.textContent = 'Xóa vĩnh viễn';

            confirmModal.classList.add('is-open');
        });
    });
})();
</script>
@endpush
