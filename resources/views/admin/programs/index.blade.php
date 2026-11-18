@extends('layouts.app', ['active' => 'programs'])

@section('title', 'Quản lý chương trình học')

@section('subnav')
    <a href="{{ route('admin.programs.index') }}" class="os-tab is-on">Chương trình học</a>
@endsection

@push('styles')
<style>
    .prog-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        background: #9a4700;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 14px;
        border-radius: 10px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(154, 71, 0, 0.25);
    }
    .prog-btn-primary:hover {
        background: #7c3800;
        box-shadow: 0 4px 14px rgba(154, 71, 0, 0.35);
    }
    .prog-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .prog-action-btn:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #0f172a;
    }
    .prog-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
    }
    .prog-status-active {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .prog-status-archived {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    /* Modal Styles */
    .prog-modal-overlay {
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
    .prog-modal-overlay.is-open {
        opacity: 1;
        pointer-events: auto;
    }
    .prog-modal-box {
        background: #ffffff;
        border-radius: 16px;
        width: 92%;
        max-width: 520px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: translateY(16px);
        transition: transform 0.2s ease;
        overflow: hidden;
    }
    .prog-modal-overlay.is-open .prog-modal-box {
        transform: translateY(0);
    }
</style>
@endpush

@section('content')
<div class="op-stack" style="font-family: 'Nunito', sans-serif;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 8px;">
        <x-page-title kicker="Khung chương trình" icon="fa-graduation-cap" title="Quản lý chương trình học" lead="Thiết kế khung chương trình chuẩn, phân chia trình độ, buổi học và ngân hàng câu hỏi." />
        <button type="button" class="prog-btn-primary js-open-create-program">
            <i class="fa-solid fa-plus"></i>
            <span>Tạo chương trình mới</span>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="op-stat-grid is-4" style="margin-bottom: 24px;">
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-blue"><i class="fa-solid fa-layer-group"></i></span>
                <p>Tổng chương trình<small>Tất cả khung mẫu</small></p>
            </span>
            <strong>{{ number_format($stats['total']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-emerald"><i class="fa-solid fa-circle-check"></i></span>
                <p>Đang áp dụng<small>Sẵn sàng cho khóa học</small></p>
            </span>
            <strong>{{ number_format($stats['active']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-sky"><i class="fa-solid fa-chalkboard-user"></i></span>
                <p>Tổng buổi học<small>Trên mọi trình độ</small></p>
            </span>
            <strong>{{ number_format($stats['sessions']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-amber"><i class="fa-solid fa-box-archive"></i></span>
                <p>Đã lưu trữ<small>Chế độ chỉ đọc</small></p>
            </span>
            <strong>{{ number_format($stats['archived']) }}</strong>
        </article>
    </div>

    <!-- Main Container -->
    <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden;">
        <div style="padding: 18px 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="op-panel-ico"><i class="fa-solid fa-graduation-cap" style="color: #9a4700;"></i></span>
                <span class="op-panel-title" style="font-weight: 800; font-size: 16px; color: #0f172a;">Danh sách chương trình học</span>
            </div>

            <form class="flex flex-wrap items-center gap-2" method="get" action="{{ route('admin.programs.index') }}">
                <select name="status" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; background: #ffffff;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected($filters['status'] === 'active')>Đang áp dụng</option>
                    <option value="archived" @selected($filters['status'] === 'archived')>Đã lưu trữ</option>
                </select>

                <div style="display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #ffffff;">
                    <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm tên chương trình..." style="border: none; padding: 8px 14px; font-size: 13.5px; outline: none; width: 220px;">
                    <button type="submit" style="background: none; border: none; padding: 8px 14px; color: #64748b; cursor: pointer;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="op-panel-body is-flush">
            @if ($programs->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm" style="border-collapse: collapse;">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">
                            <tr>
                                <th style="padding: 14px 20px; font-weight: 700;">Tên chương trình</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Số trình độ</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Số buổi học</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Người phụ trách</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Trạng thái</th>
                                <th style="padding: 14px 20px; font-weight: 700; text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody style="divide-y: 1px solid #f1f5f9;">
                            @foreach ($programs as $program)
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                                    <td style="padding: 14px 20px;">
                                        <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels']) }}" style="font-weight: 700; font-size: 15px; color: #0f172a; text-decoration: none; display: block;">
                                            {{ $program->title }}
                                        </a>
                                        @if ($program->description)
                                            <p style="color: #64748b; font-size: 12.5px; margin: 3px 0 0 0; line-height: 1.4; max-width: 380px;" class="line-clamp-1">{{ $program->description }}</p>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 20px; font-weight: 600; color: #334155;">
                                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-layer-group" style="color: #94a3b8; font-size: 12px;"></i>
                                            {{ $program->levels_count }} cấp
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px; font-weight: 600; color: #334155;">
                                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-chalkboard" style="color: #94a3b8; font-size: 12px;"></i>
                                            {{ $program->sessions_count }} buổi
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px; color: #475569;">
                                        {{ $program->teacher?->name ?: '—' }}
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        @if ($program->isArchived())
                                            <span class="prog-status-badge prog-status-archived">
                                                <i class="fa-solid fa-box-archive"></i> Đã lưu trữ
                                            </span>
                                        @else
                                            <span class="prog-status-badge prog-status-active">
                                                <i class="fa-solid fa-circle-check"></i> Đang áp dụng
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 20px; text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                                            <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels']) }}" class="prog-action-btn" title="Mở không gian soạn bài">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                            <button type="button" class="prog-action-btn js-btn-copy-prog"
                                                data-id="{{ $program->id }}"
                                                data-title="{{ $program->title }}"
                                                data-action="{{ route('admin.programs.copy', $program->id) }}"
                                                title="Tạo bản sao chương trình">
                                                <i class="fa-solid fa-copy"></i>
                                            </button>
                                            @if ($program->isActive())
                                                <button type="button" class="prog-action-btn js-btn-archive-prog"
                                                    data-id="{{ $program->id }}"
                                                    data-title="{{ $program->title }}"
                                                    data-action="{{ route('admin.programs.archive', $program->id) }}"
                                                    title="Lưu trữ chương trình">
                                                    <i class="fa-solid fa-box-archive"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($programs->hasPages())
                    <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0;">
                        {{ $programs->onEachSide(1)->links('pagination.office') }}
                    </div>
                @endif
            @else
                <!-- Empty State (Figma Frame 01) -->
                <div style="text-align: center; padding: 70px 20px;">
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: #fff7ed; color: #9a4700; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Chưa có chương trình học nào</h3>
                    <p style="color: #64748b; font-size: 14px; max-width: 460px; margin: 0 auto 24px; line-height: 1.6;">
                        Tạo chương trình học chuẩn để thiết lập lộ trình các buổi học, bài tập trắc nghiệm, bài kiểm tra và học liệu dùng chung.
                    </p>
                    <button type="button" class="prog-btn-primary js-open-create-program">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tạo chương trình đầu tiên</span>
                    </button>
                </div>
            @endif
        </div>
    </section>
</div>

<!-- Modal: Create Program -->
<div class="prog-modal-overlay" id="createProgramModal">
    <div class="prog-modal-box">
        <div style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #fff7ed; color: #9a4700; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="fa-solid fa-folder-plus"></i>
                </div>
                <div>
                    <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">Tạo chương trình học mới</h3>
                    <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Nhập thông tin cơ bản để bắt đầu soạn giáo án</p>
                </div>
            </div>
            <button type="button" class="js-close-prog-modal" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="post" action="{{ route('admin.programs.store') }}" style="padding: 24px;">
            @csrf
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                    Tên chương trình học <span style="color: #e11d48;">*</span>
                </label>
                <input type="text" name="title" required minlength="3" maxlength="255" value="{{ old('title') }}" placeholder="Ví dụ: Tiếng Trung Giao Tiếp HSK 3 Chuẩn" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px;">
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                    Mô tả ngắn gọn
                </label>
                <textarea name="description" rows="4" placeholder="Mục tiêu đầu ra, thời lượng dự kiến, lưu ý cho giáo viên..." style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px;">{{ old('description') }}</textarea>
            </div>
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                <button type="button" class="js-close-prog-modal" style="padding: 10px 18px; border-radius: 10px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer;">
                    Hủy bỏ
                </button>
                <button type="submit" class="prog-btn-primary">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>Tạo và mở Workspace</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Confirm Action (Copy / Archive) -->
<div class="prog-modal-overlay" id="confirmProgModal">
    <div class="prog-modal-box" style="max-width: 440px; text-align: center; padding: 32px 24px;">
        <div id="progConfirmIconWrap" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
            <i id="progConfirmIcon"></i>
        </div>
        <h3 id="progConfirmTitle" style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Xác nhận thao tác</h3>
        <p id="progConfirmDesc" style="font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 24px;"></p>
        
        <form id="progConfirmForm" method="post" action="">
            @csrf
            <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                <button type="button" class="js-close-prog-confirm" style="padding: 10px 20px; border-radius: 10px; background: #f1f5f9; color: #334155; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer;">
                    Hủy bỏ
                </button>
                <button type="submit" id="progConfirmSubmitBtn" style="padding: 10px 22px; border-radius: 10px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; color: #ffffff;">
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
    const createModal = document.getElementById('createProgramModal');
    const confirmModal = document.getElementById('confirmProgModal');
    const confirmForm = document.getElementById('progConfirmForm');
    const confirmTitle = document.getElementById('progConfirmTitle');
    const confirmDesc = document.getElementById('progConfirmDesc');
    const confirmIconWrap = document.getElementById('progConfirmIconWrap');
    const confirmIcon = document.getElementById('progConfirmIcon');
    const confirmSubmitBtn = document.getElementById('progConfirmSubmitBtn');

    document.querySelectorAll('.js-open-create-program').forEach(btn => {
        btn.addEventListener('click', () => {
            createModal.classList.add('is-open');
        });
    });

    document.querySelectorAll('.js-close-prog-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            createModal.classList.remove('is-open');
        });
    });

    document.querySelectorAll('.js-close-prog-confirm').forEach(btn => {
        btn.addEventListener('click', () => {
            confirmModal.classList.remove('is-open');
        });
    });

    // Copy program confirm
    document.querySelectorAll('.js-btn-copy-prog').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            confirmForm.action = d.action;
            confirmTitle.textContent = "Nhân bản chương trình";
            confirmDesc.innerHTML = `Tạo bản sao mới cho chương trình <strong>${d.title}</strong>? Toàn bộ trình độ, buổi học, câu hỏi và bài kiểm tra sẽ được nhân bản.`;
            confirmIconWrap.style.background = '#eff6ff';
            confirmIconWrap.style.color = '#2563eb';
            confirmIcon.className = 'fa-solid fa-copy';
            confirmSubmitBtn.style.background = '#2563eb';
            confirmSubmitBtn.textContent = 'Tạo bản sao ngay';
            confirmModal.classList.add('is-open');
        });
    });

    // Archive program confirm
    document.querySelectorAll('.js-btn-archive-prog').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            confirmForm.action = d.action;
            confirmTitle.textContent = "Lưu trữ chương trình";
            confirmDesc.innerHTML = `Bạn có chắc muốn lưu trữ chương trình <strong>${d.title}</strong>? Sau khi lưu trữ, chương trình sẽ chuyển sang chế độ chỉ đọc.`;
            confirmIconWrap.style.background = '#fff7ed';
            confirmIconWrap.style.color = '#ea580c';
            confirmIcon.className = 'fa-solid fa-box-archive';
            confirmSubmitBtn.style.background = '#ea580c';
            confirmSubmitBtn.textContent = 'Lưu trữ chương trình';
            confirmModal.classList.add('is-open');
        });
    });
})();
</script>
@endpush
