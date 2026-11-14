@extends('layouts.app', ['active' => 'members'])

@section('title', 'Thành viên')

@section('subnav')
    <a href="{{ route('admin.users.index') }}" class="os-tab is-on">Thành viên</a>
@endsection

@section('content')
<div class="op-stack">
    <x-page-title kicker="Quản trị" icon="fa-users" title="Thành viên" />

    <div class="op-stat-grid is-4">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-solid fa-users"></i></span><p>Tổng thành viên<small>Toàn bộ tài khoản</small></p></span><strong>{{ $stats['total'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-sky"><i class="fa-solid fa-shield-halved"></i></span><p>Quản trị viên<small>Có quyền admin</small></p></span><strong>{{ $stats['admins'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-user-check"></i></span><p>Đang hoạt động<small>Tài khoản mở</small></p></span><strong>{{ $stats['active'] }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-amber"><i class="fa-solid fa-clock"></i></span><p>Mới trong tháng<small>{{ now()->format('m/Y') }}</small></p></span><strong>{{ $stats['month'] }}</strong></article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-users"></i></span>
            <span class="op-panel-title">Danh sách thành viên</span>
            <div class="op-panel-tools">
                <form class="flex flex-wrap items-center justify-end gap-2" method="get" action="{{ route('admin.users.index') }}">
                    <select name="role" class="px-3 py-2 text-sm sm:w-44" onchange="this.form.submit()">
                        <option value="">Tất cả vai trò</option>
                        @foreach ($roles as $key => $label)
                            <option value="{{ $key }}" @selected($filters['role'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="px-3 py-2 text-sm sm:w-40" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="flex">
                        <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm tên, SĐT, email..." class="rounded-r-none px-3 py-2 text-sm sm:w-52">
                        <button class="op-btn rounded-l-none" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </form>
                <button type="button" class="op-btn js-user-create"><i class="fa-solid fa-plus"></i> Thêm</button>
            </div>
        </div>
        <div class="op-panel-body is-flush">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Thành viên</th>
                            <th class="px-5 py-3 font-medium">Liên hệ</th>
                            <th class="px-5 py-3 font-medium">Vai trò</th>
                            <th class="px-5 py-3 font-medium">Mã HV</th>
                            <th class="px-5 py-3 font-medium">Đăng nhập cuối</th>
                            <th class="px-5 py-3 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-user-avatar
                                            class="h-9 w-9 rounded-full bg-blue-50 text-xs font-semibold text-blue-600"
                                            :url="$user->avatarUrl()"
                                            :name="$user->name"
                                        />
                                        <div>
                                            <p class="font-medium text-slate-900">{{ $user->full_name ?: $user->username }}</p>
                                            <p class="text-xs text-slate-400">{{ '@'.$user->username }}</p>
                                            @if ($user->id === auth()->id())
                                                <p class="text-xs text-blue-600">Bạn</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    <p>{{ $user->phone ?: '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="rounded-full {{ $user->role === 'admin' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700' }} px-2.5 py-1 text-xs font-medium">{{ $user->role_label }}</span>
                                        @if ($user->status === 'inactive')
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">Đã khóa</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->student_code ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->last_login_at?->format('d/m/Y H:i') ?: 'Chưa đăng nhập' }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" class="js-user-edit inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600"
                                            data-id="{{ $user->id }}"
                                            data-full-name="{{ $user->full_name }}"
                                            data-username="{{ $user->username }}"
                                            data-phone="{{ $user->phone }}"
                                            data-email="{{ $user->email }}"
                                            data-role="{{ $user->role }}"
                                            data-status="{{ $user->status }}"
                                            data-student-code="{{ $user->student_code }}"
                                            title="Sửa">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        @if ($user->id !== auth()->id())
                                            <form method="post" action="{{ route('admin.users.toggle-status', $user->id) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border {{ $user->status === 'active' ? 'border-amber-100 text-amber-700' : 'border-emerald-100 text-emerald-700' }}" title="{{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }}">
                                                    <i class="fa-solid {{ $user->status === 'active' ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Xóa tài khoản này? Thao tác không thể hoàn tác.');">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-100 text-red-600" title="Xóa">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Không có tài khoản nào phù hợp.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="os-pager">
                    {{ $users->onEachSide(1)->links('pagination.office') }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@section('drawer')
<div id="user-form-tpl" hidden>
    <form id="user-form" class="space-y-3 p-5 text-sm" method="post" action="{{ route('admin.users.store') }}">
        @csrf
        <label class="block">Họ tên *
            <input name="full_name" required minlength="2" maxlength="120" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Số điện thoại *
            <input name="phone" type="tel" required minlength="9" maxlength="20" placeholder="0912345678" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Tên đăng nhập
            <input name="username" maxlength="50" placeholder="Để trống sẽ tạo từ SĐT" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Email *
            <input name="email" type="email" required maxlength="120" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
        </label>
        <label class="block">Mật khẩu <span data-password-hint>*</span>
            <input name="password" type="password" minlength="6" class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2" placeholder="Tối thiểu 6 ký tự">
        </label>
        <label class="block">Vai trò *
            <select name="role" required class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
                @foreach ($roles as $key => $label)
                    <option value="{{ $key }}" @selected($key === 'student')>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">Trạng thái *
            <select name="status" required class="mt-1 w-full rounded-lg bg-slate-100 px-3 py-2">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block js-student-code hidden">Mã học viên
            <input name="student_code_display" readonly class="mt-1 w-full rounded-lg bg-slate-50 px-3 py-2 text-slate-500">
        </label>
        <button type="submit" class="op-btn">Lưu</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const storeUrl = @json(route('admin.users.store'));
    const updateUrl = @json(url('nguoi-dung/update'));
    const studentRoles = ['student', 'study_abroad_student'];

    function bindUserForm(form, isEdit, data = {}) {
        form.action = isEdit ? `${updateUrl}/${data.id}` : storeUrl;
        form.full_name.value = data.fullName || data.full_name || '';
        form.phone.value = data.phone || '';
        form.username.value = data.username || '';
        form.email.value = data.email || '';
        form.password.value = '';
        form.password.required = !isEdit;
        form.password.placeholder = isEdit ? 'Để trống nếu không đổi' : 'Tối thiểu 6 ký tự';
        form.querySelector('[data-password-hint]').textContent = isEdit ? '(không bắt buộc)' : '*';
        form.role.value = data.role || 'student';
        form.status.value = data.status || 'active';
        form.student_code_display.value = data.studentCode || data.student_code || '';
        toggleStudentCode(form);
        form.role.addEventListener('change', () => toggleStudentCode(form));
    }

    function toggleStudentCode(form) {
        const box = form.querySelector('.js-student-code');
        box.classList.toggle('hidden', !studentRoles.includes(form.role.value));
    }

    document.querySelector('.js-user-create')?.addEventListener('click', () => {
        TemplateUI.openDrawer({ title: 'Thêm thành viên', html: document.getElementById('user-form-tpl').innerHTML });
        bindUserForm(document.querySelector('#drawer-body form'), false);
    });

    document.querySelectorAll('.js-user-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            TemplateUI.openDrawer({ title: 'Sửa thành viên', html: document.getElementById('user-form-tpl').innerHTML });
            bindUserForm(document.querySelector('#drawer-body form'), true, btn.dataset);
        });
    });

    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            TemplateUI.toast('error', @json($errors->first()));
            TemplateUI.openDrawer({ title: 'Thêm / sửa thành viên', html: document.getElementById('user-form-tpl').innerHTML });
            const form = document.querySelector('#drawer-body form');
            bindUserForm(form, false, {
                full_name: @json(old('full_name')),
                phone: @json(old('phone')),
                username: @json(old('username')),
                email: @json(old('email')),
                role: @json(old('role', 'student')),
                status: @json(old('status', 'active')),
            });
        });
    @endif
</script>
@endpush
