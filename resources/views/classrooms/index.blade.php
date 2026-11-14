@extends('layouts.app', ['active' => 'classroom'])

@section('title', 'Lớp học online')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/classrooms-hub.css') }}">
@endpush

@if (! empty($canCreateRoom))
    @section('subnav')
        <a href="{{ route('classroom.index') }}" class="os-tab is-on">Phòng đang mở</a>
        <a href="{{ route('classroom.create') }}" class="os-tab">Tạo phòng học</a>
        @if (auth()->user()?->isAdmin())
            <a href="{{ route('admin.classrooms.index') }}" class="os-tab">Quản lý phòng</a>
        @endif
    @endsection
@endif

@section('content')
<div class="op-stack cr-hub">
    <x-page-title kicker="Học trực tuyến" icon="fa-video" title="Phòng học online" lead="Tham gia lớp học trực tuyến qua video, tương tác như Zoom hoặc Google Meet.">
        @if (! empty($canCreateRoom))
            <x-slot:aside>
                <a class="op-btn" href="{{ route('classroom.create') }}"><i class="fa-solid fa-plus"></i> Tạo phòng học</a>
            </x-slot:aside>
        @endif
    </x-page-title>

    @if (! empty($showJaasWarning) && ! empty($jaasMissingKey))
        <div class="cr-banner is-danger" role="alert">
            <strong>JaaS chưa hoàn tất:</strong> thiếu file private key.
            Vào <a href="https://developer.8x8.com/jaas/docs/jaas-console-api-keys" target="_blank" rel="noopener">JaaS Console → API Keys</a>,
            tải private key và đặt vào <code>storage/app/jaas/private.pk</code>
            (hoặc cấu hình <code>CLASSROOM_JAAS_PRIVATE_KEY_PATH</code>).
            Nếu thiếu file này, hệ thống sẽ dùng <code>meet.jit.si</code> và bị ngắt sau 5 phút.
        </div>
    @elseif (! empty($showJaasWarning) && empty($jaasReady))
        <div class="cr-banner is-warn" role="alert">
            <strong>Lưu ý:</strong> Phòng học đang dùng <code>meet.jit.si</code> — có thể bị ngắt sau ~5 phút khi embed trong app.
            Cấu hình JaaS trong <code>.env</code> để học không giới hạn thời gian.
        </div>
    @endif

    <div class="op-stat-grid is-4">
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-sky"><i class="fa-solid fa-video"></i></span><p>Phòng đang mở<small>Scheduled / live</small></p></span><strong>{{ $rooms->count() }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-emerald"><i class="fa-solid fa-tower-broadcast"></i></span><p>Đang live<small>Phòng đang diễn ra</small></p></span><strong>{{ $liveCount }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-blue"><i class="fa-regular fa-calendar"></i></span><p>Sắp diễn ra<small>Đã lên lịch</small></p></span><strong>{{ $scheduledCount }}</strong></article>
        <article class="op-stat"><span class="op-stat-top"><span class="op-stat-ico is-violet"><i class="fa-solid fa-users"></i></span><p>Online<small>Người trong phòng</small></p></span><strong>{{ $onlineTotal }}</strong></article>
    </div>

    <section class="op-panel">
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-video"></i></span>
            <span class="op-panel-title">Phòng học trực tuyến</span>
            <span class="ml-auto text-sm text-slate-400">Video call nhiều người — tương tự Zoom / Google Meet</span>
        </div>
        <div class="op-panel-body">
            <div class="cr-hub-toolbar" aria-label="Bộ lọc phòng học">
                <label class="cr-hub-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="classroomSearch" type="search" placeholder="Tìm phòng học, giáo viên...">
                </label>
                <div class="cr-hub-filters">
                    <button class="cr-filter is-on" type="button" data-filter="all">Tất cả</button>
                    <button class="cr-filter" type="button" data-filter="live">Đang live</button>
                    <button class="cr-filter" type="button" data-filter="scheduled">Sắp diễn ra</button>
                </div>
            </div>

            <div class="cr-hub-grid" id="classroomList">
                @forelse ($rooms as $room)
                    @php
                        $status = (string) $room->status;
                        $statusLabel = $statuses[$status] ?? $room->statusLabel();
                        $onlineCount = (int) ($room->online_count ?? 0);
                        $searchText = mb_strtolower(trim($room->title.' '.($room->host?->name ?? '').' '.($room->description ?? '')));
                        $startLabel = $room->scheduled_start ? $room->scheduled_start->format('d/m/Y H:i') : '—';
                    @endphp
                    <article
                        class="cr-hub-card is-{{ $status }}"
                        data-status="{{ $status }}"
                        data-search="{{ $searchText }}"
                    >
                        <div class="cr-hub-card-head">
                            <span class="cr-hub-status is-{{ $status }}">
                                @if ($status === 'live')
                                    <span class="cr-hub-live-dot" aria-hidden="true"></span>
                                @endif
                                {{ $statusLabel }}
                            </span>
                            <span class="cr-hub-online"><i class="fa-solid fa-users"></i> {{ $onlineCount }}/{{ (int) $room->max_participants }}</span>
                        </div>
                        <h3>{{ $room->title }}</h3>
                        @if (filled($room->description))
                            <p class="cr-hub-desc">{{ $room->description }}</p>
                        @endif
                        <div class="cr-hub-meta">
                            <span><i class="fa-solid fa-id-badge"></i> {{ $room->host?->name ?: '—' }}</span>
                            <span><i class="fa-regular fa-clock"></i> {{ $startLabel }}</span>
                            @if (! empty($isStaff))
                                <span><i class="fa-solid fa-hashtag"></i> ID: <code>{{ $room->id }}</code></span>
                                @if (filled($room->room_code))
                                    <span><i class="fa-solid fa-key"></i> Mã: <code>{{ $room->room_code }}</code></span>
                                @endif
                            @endif
                        </div>
                        <a class="op-btn w-full" href="{{ route('classroom.lobby', $room->id) }}">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            {{ $status === 'live' ? 'Vào phòng ngay' : 'Vào phòng chờ' }}
                        </a>
                    </article>
                @empty
                    <div class="cr-hub-empty">
                        <div class="cr-hub-empty-ico"><i class="fa-solid fa-video-slash"></i></div>
                        <h3>Chưa có phòng học nào</h3>
                        <p>Giáo viên sẽ tạo phòng khi có lịch học online. Hãy quay lại sau.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/classroom-list.js') }}"></script>
@endpush
