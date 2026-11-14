@extends('layouts.app', ['active' => 'classrooms'])

@section('title', 'Phòng học Zoom')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-classrooms.css') }}">
@endpush

@section('subnav')
    <a href="{{ route('admin.classrooms.index') }}" class="os-tab is-on">Danh sách phòng</a>
    <a href="{{ route('admin.classrooms.create') }}" class="os-tab">Thêm phòng</a>
@endsection

@section('content')
<div class="op-stack cr-page">
    <x-page-title kicker="Lớp học online" icon="fa-video" title="Quản lý lớp học online" lead="Tạo và quản lý phòng học trực tuyến cho giáo viên và học viên.">
        <x-slot:aside>
            <a class="op-btn" href="{{ route('admin.classrooms.create') }}"><i class="fa-solid fa-plus"></i> Thêm phòng học</a>
        </x-slot:aside>
    </x-page-title>

    <div class="op-stat-grid is-4">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-sky"><i class="fa-solid fa-video"></i></span><p>Phòng<small>Tổng số phòng</small></p></span><strong>{{ $rooms->count() }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-tower-broadcast"></i></span><p>Đang live<small>Phòng đang diễn ra</small></p></span><strong>{{ $liveCount }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-solid fa-users"></i></span><p>Đang online<small>Người trong phòng</small></p></span><strong>{{ $onlineTotal }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-amber"><i class="fa-regular fa-calendar"></i></span><p>Không live<small>Nháp / lịch / kết thúc</small></p></span><strong>{{ $rooms->count() - $liveCount }}</strong></article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-video"></i></span>
            <span class="op-panel-title">Danh sách phòng học</span>
            <span class="ml-auto text-sm text-slate-400">{{ $rooms->count() }} phòng</span>
        </div>
        <div class="op-panel-body is-flush">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th>Tên phòng</th>
                            <th>Giáo viên</th>
                            <th>Lịch học</th>
                            <th>Online</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rooms as $room)
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
                            <tr>
                                <td>
                                    <div class="cr-room">
                                        <span class="cr-room-ico"><i class="fa-solid fa-video"></i></span>
                                        <div>
                                            <strong>{{ $room->title }}</strong>
                                            <small><code>{{ $room->room_code }}</code></small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $room->host?->name }}</td>
                                <td>{{ $room->scheduled_start?->format('d/m/Y H:i') ?: '—' }}</td>
                                <td><span class="cr-pill is-online">{{ (int) ($room->online_count ?? 0) }} người</span></td>
                                <td><span class="cr-pill {{ $statusClass }}">{{ $statuses[$status] ?? $status }}</span></td>
                                <td>
                                    <div class="cr-actions">
                                        <a href="{{ route('classroom.lobby', $room->id) }}" class="cr-ico" title="Vào phòng"><i class="fa-solid fa-right-to-bracket"></i></a>
                                        <a href="{{ route('admin.classrooms.edit', $room->id) }}" class="cr-ico" title="Sửa"><i class="fa-solid fa-pen"></i></a>
                                        <form method="post" action="{{ route('admin.classrooms.destroy', $room->id) }}" onsubmit="return confirm('Bạn có chắc muốn xóa phòng học này? Thao tác không thể hoàn tác.');">
                                            @csrf
                                            <button type="submit" class="cr-ico is-danger" title="Xóa"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Chưa có phòng học nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
