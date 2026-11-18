@extends('layouts.app', ['active' => 'courses'])

@section('title', 'Quản lý khóa học')

@section('subnav')
    <a href="{{ route('admin.courses.index') }}" class="os-tab is-on">Danh sách khóa học</a>
    <a href="{{ route('admin.courses.create') }}" class="os-tab">Tạo khóa học mới</a>
@endsection

@push('styles')
<style>
    .course-btn-primary {
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
    .course-btn-primary:hover {
        background: #7c3800;
        box-shadow: 0 4px 14px rgba(154, 71, 0, 0.35);
    }
    .course-action-btn {
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
    .course-action-btn:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #0f172a;
    }
    .course-action-btn.is-danger {
        border-color: #fecaca;
        background: #fef2f2;
        color: #dc2626;
    }
    .course-action-btn.is-danger:hover {
        background: #fee2e2;
    }
    .course-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
    }
    .course-status-active {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .course-status-cancelled {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    /* Confirm Modal */
    .course-modal-overlay {
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
    .course-modal-overlay.is-open {
        opacity: 1;
        pointer-events: auto;
    }
    .course-modal-box {
        background: #ffffff;
        border-radius: 16px;
        width: 92%;
        max-width: 450px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        text-align: center;
        padding: 32px 24px;
        transform: translateY(16px);
        transition: transform 0.2s ease;
    }
    .course-modal-overlay.is-open .course-modal-box {
        transform: translateY(0);
    }
</style>
@endpush

@section('content')
<div class="op-stack" style="font-family: 'Nunito', sans-serif;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 8px;">
        <x-page-title kicker="Đào tạo & Lớp học" icon="fa-chalkboard" title="Quản lý khóa học" lead="Mở lớp, phân công giáo viên, xếp lịch học và quản lý học viên theo khóa." />
        <a href="{{ route('admin.courses.create') }}" class="course-btn-primary">
            <i class="fa-solid fa-plus"></i>
            <span>Tạo khóa học mới</span>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="op-stat-grid is-4" style="margin-bottom: 24px;">
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-blue"><i class="fa-solid fa-chalkboard"></i></span>
                <p>Tổng khóa học<small>Tất cả các lớp</small></p>
            </span>
            <strong>{{ number_format($stats['total']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-emerald"><i class="fa-solid fa-circle-check"></i></span>
                <p>Đang mở<small>Lớp đang diễn ra</small></p>
            </span>
            <strong style="color: #16a34a;">{{ number_format($stats['active']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-sky"><i class="fa-solid fa-users"></i></span>
                <p>Học viên đang học<small>Sĩ số lớp thực tế</small></p>
            </span>
            <strong>{{ number_format($stats['members']) }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-amber"><i class="fa-solid fa-clock"></i></span>
                <p>Tổng buổi học<small>Theo thời khóa biểu</small></p>
            </span>
            <strong>{{ number_format($stats['sessions']) }}</strong>
        </article>
    </div>

    <!-- Main Table Container -->
    <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden;">
        <div style="padding: 18px 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="op-panel-ico"><i class="fa-solid fa-chalkboard" style="color: #9a4700;"></i></span>
                <span class="op-panel-title" style="font-weight: 800; font-size: 16px; color: #0f172a;">Danh sách khóa học</span>
                <span class="text-sm text-slate-500 font-medium ml-2">{{ $courses->total() }} lớp</span>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <form class="flex flex-wrap items-center gap-2" method="get" action="{{ route('admin.courses.index') }}">
                    <select name="status" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; background: #ffffff;">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected($filters['status'] === 'active')>Đang mở</option>
                        <option value="cancelled" @selected($filters['status'] === 'cancelled')>Đã hủy</option>
                    </select>

                    <div style="display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #ffffff;">
                        <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm tên khóa học..." style="border: none; padding: 8px 14px; font-size: 13.5px; outline: none; width: 220px;">
                        <button type="submit" style="background: none; border: none; padding: 8px 14px; color: #64748b; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>

                @if (($emptyCourseCount ?? 0) > 0)
                    <form method="post" action="{{ route('admin.courses.destroy-empty') }}" onsubmit="return confirm('Xóa {{ $emptyCourseCount }} khóa học không có học viên?');">
                        @csrf
                        <button type="submit" style="padding: 8px 14px; border-radius: 10px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; font-size: 13px; font-weight: 600; cursor: pointer;">
                            <i class="fa-solid fa-trash-can"></i> Dọn khóa trống ({{ $emptyCourseCount }})
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="op-panel-body is-flush">
            @if ($courses->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm" style="border-collapse: collapse;">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">
                            <tr>
                                <th style="padding: 14px 20px; font-weight: 700;">Khóa học</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Giáo viên chủ nhiệm</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Chương trình đào tạo</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Sĩ số học viên</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Số buổi</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Trạng thái</th>
                                <th style="padding: 14px 20px; font-weight: 700; text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody style="divide-y: 1px solid #f1f5f9;">
                            @foreach ($courses as $course)
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                                    <td style="padding: 14px 20px;">
                                        <a href="{{ route('admin.courses.show', ['id' => $course->id, 'tab' => $course->isActive() ? 'members' : 'info']) }}" style="font-weight: 700; font-size: 15px; color: #0f172a; text-decoration: none; display: block;">
                                            {{ $course->title }}
                                        </a>
                                        @if ($course->description)
                                            <p style="color: #64748b; font-size: 12.5px; margin: 3px 0 0 0; line-height: 1.4; max-width: 360px;" class="line-clamp-1">{{ $course->description }}</p>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 20px; color: #334155; font-weight: 600;">
                                        <i class="fa-solid fa-chalkboard-user" style="color: #94a3b8; margin-right: 6px;"></i>
                                        {{ $course->teacher?->name ?: '—' }}
                                    </td>
                                    <td style="padding: 14px 20px; color: #475569;">
                                        {{ $course->program?->title ?: '—' }}
                                    </td>
                                    <td style="padding: 14px 20px; font-weight: 700; color: #15803d;">
                                        <i class="fa-solid fa-user-group" style="font-size: 12px; margin-right: 4px;"></i>
                                        {{ $course->active_members_count }} học viên
                                    </td>
                                    <td style="padding: 14px 20px; font-weight: 600; color: #475569;">
                                        {{ $course->sessions_count }} buổi
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        @if ($course->isCancelled())
                                            <span class="course-status-badge course-status-cancelled">
                                                <i class="fa-solid fa-ban"></i> Đã hủy
                                            </span>
                                        @else
                                            <span class="course-status-badge course-status-active">
                                                <i class="fa-solid fa-circle-check"></i> Đang mở
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 20px; text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                                            <a href="{{ route('admin.courses.show', ['id' => $course->id, 'tab' => $course->isActive() ? 'members' : 'info']) }}" class="course-action-btn" title="Mở lớp học">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                            @if ($course->canBeDeleted())
                                                <button type="button" class="course-action-btn is-danger js-btn-delete-course"
                                                    data-id="{{ $course->id }}"
                                                    data-title="{{ $course->title }}"
                                                    data-action="{{ route('admin.courses.destroy', $course->id) }}"
                                                    title="Xóa khóa học">
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

                @if ($courses->hasPages())
                    <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0;">
                        {{ $courses->onEachSide(1)->links('pagination.office') }}
                    </div>
                @endif
            @else
                <!-- Empty State (Figma Frame 01) -->
                <div style="text-align: center; padding: 70px 20px;">
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: #fff7ed; color: #9a4700; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Chưa có khóa học nào</h3>
                    <p style="color: #64748b; font-size: 14px; max-width: 460px; margin: 0 auto 24px; line-height: 1.6;">
                        Tạo khóa học để phân công giáo viên, xếp lịch học theo tuần và thêm học viên vào lớp.
                    </p>
                    <a href="{{ route('admin.courses.create') }}" class="course-btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tạo khóa học đầu tiên</span>
                    </a>
                </div>
            @endif
        </div>
    </section>
</div>

<!-- Modal: Confirm Delete Course -->
<div class="course-modal-overlay" id="confirmCourseModal">
    <div class="course-modal-box">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Xác nhận xóa khóa học</h3>
        <p id="courseConfirmDesc" style="font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 24px;"></p>
        
        <form id="courseConfirmForm" method="post" action="">
            @csrf
            <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                <button type="button" class="js-close-course-modal" style="padding: 10px 20px; border-radius: 10px; background: #f1f5f9; color: #334155; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer;">
                    Hủy bỏ
                </button>
                <button type="submit" style="padding: 10px 22px; border-radius: 10px; background: #dc2626; color: #ffffff; font-weight: 600; font-size: 14px; border: none; cursor: pointer;">
                    Xác nhận xóa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = document.getElementById('confirmCourseModal');
    const form = document.getElementById('courseConfirmForm');
    const desc = document.getElementById('courseConfirmDesc');

    document.querySelectorAll('.js-btn-delete-course').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            form.action = d.action;
            desc.innerHTML = `Bạn có chắc muốn xóa vĩnh viễn khóa học <strong>${d.title}</strong>? Toàn bộ buổi học và dữ liệu liên quan sẽ bị xóa hoàn toàn.`;
            modal.classList.add('is-open');
        });
    });

    document.querySelectorAll('.js-close-course-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.remove('is-open');
        });
    });
})();
</script>
@endpush
