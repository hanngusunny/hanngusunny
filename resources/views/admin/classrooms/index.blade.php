@extends('layouts.app', ['active' => 'classrooms'])

@section('title', 'Quản lý lớp học Online')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-classrooms.css') }}">
    <style>
        .cr-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
        }
        .cr-pill.is-live {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .cr-pill.is-scheduled {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .cr-pill.is-ended {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .cr-pill.is-cancelled {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .cr-pill.is-draft {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }
        .cr-pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: crPulse 1.6s infinite;
        }
        @keyframes crPulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 7px rgba(34, 197, 94, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
        }
        .cr-btn-primary {
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
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(154, 71, 0, 0.25);
        }
        .cr-btn-primary:hover {
            background: #7c3800;
            box-shadow: 0 4px 14px rgba(154, 71, 0, 0.35);
        }
        .cr-action-icon {
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
        .cr-action-icon:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }
        .cr-action-icon.is-enter {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #9a4700;
        }
        .cr-action-icon.is-enter:hover {
            background: #ffedd5;
            color: #7c3800;
        }
        .cr-action-icon.is-danger {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }
        .cr-action-icon.is-danger:hover {
            background: #fee2e2;
        }
    </style>
@endpush

@section('subnav')
    <a href="{{ route('admin.classrooms.index') }}" class="os-tab is-on">Danh sách phòng</a>
    <a href="{{ route('admin.classrooms.create') }}" class="os-tab">Thêm phòng mới</a>
@endsection

@section('content')
<div class="op-stack cr-page" style="font-family: 'Nunito', sans-serif;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 8px;">
        <x-page-title kicker="Lớp học trực tuyến" icon="fa-video" title="Quản lý lớp học Online" lead="Tạo và quản lý các phòng học video call tương tác thời gian thực." />
        <a class="cr-btn-primary" href="{{ route('admin.classrooms.create') }}">
            <i class="fa-solid fa-plus"></i>
            <span>Tạo phòng học mới</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="op-stat-grid is-4" style="margin-bottom: 24px;">
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-sky"><i class="fa-solid fa-video"></i></span>
                <p>Tổng số phòng<small>Tất cả phòng học</small></p>
            </span>
            <strong>{{ $rooms->count() }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-emerald"><i class="fa-solid fa-tower-broadcast"></i></span>
                <p>Đang Live<small>Phòng đang diễn ra</small></p>
            </span>
            <strong style="color: #16a34a;">{{ $liveCount }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-blue"><i class="fa-solid fa-users"></i></span>
                <p>Học viên online<small>Đang trong các phòng</small></p>
            </span>
            <strong>{{ $onlineTotal }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-amber"><i class="fa-regular fa-calendar-check"></i></span>
                <p>Lên lịch / Chờ<small>Chưa phát trực tiếp</small></p>
            </span>
            <strong>{{ $rooms->count() - $liveCount }}</strong>
        </article>
    </div>

    <!-- Main Table Container -->
    <section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden;">
        <div class="op-panel-head is-static" style="padding: 18px 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
            <span class="op-panel-ico"><i class="fa-solid fa-video" style="color: #9a4700;"></i></span>
            <span class="op-panel-title" style="font-weight: 800; font-size: 16px; color: #0f172a;">Danh sách phòng học trực tuyến</span>
            <span class="ml-auto text-sm text-slate-500 font-medium">{{ $rooms->count() }} phòng</span>
        </div>

        <div class="op-panel-body is-flush">
            @if ($rooms->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm" style="border-collapse: collapse;">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">
                            <tr>
                                <th style="padding: 14px 20px; font-weight: 700;">Tên phòng / Mã phòng</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Giáo viên chủ trì</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Thời gian bắt đầu</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Đang có mặt</th>
                                <th style="padding: 14px 20px; font-weight: 700;">Trạng thái</th>
                                <th style="padding: 14px 20px; font-weight: 700; text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody style="divide-y: 1px solid #f1f5f9;">
                            @foreach ($rooms as $room)
                                @php
                                    $status = (string) $room->status;
                                    $statusClass = match ($status) {
                                        'live' => 'is-live',
                                        'ended' => 'is-ended',
                                        'cancelled' => 'is-cancelled',
                                        'draft' => 'is-draft',
                                        default => 'is-scheduled',
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                                    <td style="padding: 14px 20px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 38px; height: 38px; border-radius: 10px; background: {{ $status === 'live' ? '#dcfce7' : '#f1f5f9' }}; color: {{ $status === 'live' ? '#15803d' : '#64748b' }}; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                                <i class="fa-solid fa-video"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 14.5px;">{{ $room->title }}</div>
                                                <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px;">
                                                    <code style="font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #475569;">{{ $room->room_code }}</code>
                                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $room->room_code }}'); alert('Đã sao chép mã phòng: {{ $room->room_code }}');" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0 4px; font-size: 11.5px;" title="Sao chép mã phòng">
                                                        <i class="fa-regular fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 20px; color: #334155; font-weight: 600;">
                                        <i class="fa-solid fa-chalkboard-user" style="color: #94a3b8; margin-right: 6px;"></i>
                                        {{ $room->host?->name ?: '—' }}
                                    </td>
                                    <td style="padding: 14px 20px; color: #475569; font-size: 13.5px;">
                                        {{ $room->scheduled_start?->format('d/m/Y H:i') ?: 'Chưa đặt lịch' }}
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 13px; color: {{ (int) ($room->online_count ?? 0) > 0 ? '#15803d' : '#64748b' }};">
                                            <i class="fa-solid fa-users" style="font-size: 12px;"></i>
                                            {{ (int) ($room->online_count ?? 0) }} người
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span class="cr-pill {{ $statusClass }}">
                                            @if ($status === 'live')
                                                <span class="cr-pulse-dot"></span>
                                            @endif
                                            <span>{{ $statuses[$status] ?? $status }}</span>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px; text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                                            <a href="{{ route('classroom.lobby', $room->id) }}" class="cr-action-icon is-enter" title="Vào sảnh phòng học">
                                                <i class="fa-solid fa-right-to-bracket"></i>
                                            </a>
                                            <a href="{{ route('admin.classrooms.edit', $room->id) }}" class="cr-action-icon" title="Chỉnh sửa cấu hình">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <button type="button" class="cr-action-icon is-danger js-btn-delete-room"
                                                data-id="{{ $room->id }}"
                                                data-title="{{ $room->title }}"
                                                data-action="{{ route('admin.classrooms.destroy', $room->id) }}"
                                                title="Xóa phòng học">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State (Figma Frame 01) -->
                <div style="text-align: center; padding: 70px 20px;">
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: #fff7ed; color: #9a4700; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-video-slash"></i>
                    </div>
                    <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Chưa có phòng học online nào</h3>
                    <p style="color: #64748b; font-size: 14px; max-width: 460px; margin: 0 auto 24px; line-height: 1.6;">
                        Tạo phòng học trực tuyến để giáo viên và học viên có thể trao đổi, học tập và tương tác qua video call và bộ câu hỏi trực tiếp.
                    </p>
                    <a class="cr-btn-primary" href="{{ route('admin.classrooms.create') }}">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tạo phòng học đầu tiên</span>
                    </a>
                </div>
            @endif
        </div>
    </section>
</div>

<!-- Modal: Confirm Delete Room -->
<div class="user-modal-overlay" id="deleteRoomModal" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.2s ease;">
    <div style="background: #ffffff; border-radius: 16px; width: 92%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; padding: 32px 24px;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">Xác nhận xóa phòng học</h3>
        <p id="deleteRoomDesc" style="font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 24px;"></p>
        
        <form id="deleteRoomForm" method="post" action="">
            @csrf
            <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                <button type="button" id="btnCancelDeleteRoom" style="padding: 10px 20px; border-radius: 10px; background: #f1f5f9; color: #334155; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer;">
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
    const modal = document.getElementById('deleteRoomModal');
    const form = document.getElementById('deleteRoomForm');
    const desc = document.getElementById('deleteRoomDesc');
    const cancelBtn = document.getElementById('btnCancelDeleteRoom');

    document.querySelectorAll('.js-btn-delete-room').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            form.action = d.action;
            desc.innerHTML = `Bạn có chắc muốn xóa phòng học <strong>${d.title}</strong>? Toàn bộ dữ liệu câu hỏi soạn sẵn và người tham gia sẽ bị xóa vĩnh viễn.`;
            modal.style.opacity = '1';
            modal.style.pointerEvents = 'auto';
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            modal.style.opacity = '0';
            modal.style.pointerEvents = 'none';
        });
    }
})();
</script>
@endpush
