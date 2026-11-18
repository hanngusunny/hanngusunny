@extends('layouts.app', ['active' => 'schedule'])

@php
    $payload = is_array($payload ?? null) ? $payload : [];
    $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];
    $stats = is_array($payload['stats'] ?? null) ? $payload['stats'] : [];
    $todaySessions = is_array($payload['todaySessions'] ?? null) ? $payload['todaySessions'] : [];
    $reminders = is_array($payload['reminders'] ?? null) ? $payload['reminders'] : [];
    $todayCount = (int) ($stats['today'] ?? 0);
    $liveCount = (int) ($stats['live'] ?? 0);
    $monthCount = (int) ($stats['month'] ?? 0);
    $sessionCount = count($sessions);
    $weekdays = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    $todayDateLabel = ($weekdays[(int) date('w')] ?? '').', '.date('d/m/Y');

    $sessionsJson = json_encode($sessions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    if ($sessionsJson === false) {
        $sessionsJson = '[]';
    }

    if ($sessionCount === 0) {
        $pageSubtitle = 'Chưa có buổi dạy được lên lịch trên các khóa bạn phụ trách.';
    } elseif ($todayCount > 0) {
        $pageSubtitle = 'Hôm nay bạn có '.$todayCount.' buổi dạy'.($liveCount > 0 ? ', '.$liveCount.' buổi đang diễn ra' : '').'.';
    } else {
        $pageSubtitle = 'Xem lịch buổi dạy theo ngày, tuần và tháng. Tháng này có '.$monthCount.' buổi đã lên lịch.';
    }
@endphp

@section('title', 'Lịch dạy')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-schedule.css') }}">
@endpush

@section('content')
<div class="op-stack ts-page">
    <x-page-title kicker="Giáo viên" icon="fa-calendar-days" title="Lịch dạy" :lead="$pageSubtitle" />

    <div class="op-stat-grid is-3">
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-amber"><i class="fa-solid fa-calendar-day"></i></span>
                <p>Hôm nay<small>Buổi dạy trong ngày</small></p>
            </span>
            <strong>{{ $todayCount }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-rose"><i class="fa-solid fa-tower-broadcast"></i></span>
                <p>Đang dạy<small>Buổi đang diễn ra</small></p>
            </span>
            <strong>{{ $liveCount }}</strong>
        </article>
        <article class="op-stat">
            <span class="op-stat-top">
                <span class="op-stat-ico is-blue"><i class="fa-solid fa-calendar-days"></i></span>
                <p>Tháng này<small>Buổi đã lên lịch</small></p>
            </span>
            <strong>{{ $monthCount }}</strong>
        </article>
    </div>

    @if ($todaySessions !== [] || $reminders !== [])
        <div class="ts-side-grid">
            @if ($todaySessions !== [])
                <section class="op-panel ts-today-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-solid fa-sun"></i></span>
                        <span class="op-panel-title">Buổi dạy hôm nay</span>
                        <span class="ts-side-date">{{ $todayDateLabel }}</span>
                    </div>
                    <div class="op-panel-body ts-today-list">
                        @foreach ($todaySessions as $item)
                            @php
                                $phase = (string) ($item['phase'] ?? 'upcoming');
                                $pill = $phase === 'live' ? 'red' : ($phase === 'ended' || $phase === 'cancelled' ? 'gray' : 'purple');
                            @endphp
                            <a class="ts-today-card{{ $phase === 'live' ? ' is-live' : '' }}{{ $phase === 'ended' ? ' is-ended' : '' }}" href="{{ $item['url'] ?? route('dashboard') }}">
                                <div class="ts-today-time">
                                    <b>{{ $item['start_time'] ?? '' }}</b>
                                    @if (($item['end_time'] ?? '') !== '')
                                        <span>{{ $item['end_time'] }}</span>
                                    @endif
                                </div>
                                <div class="ts-today-main">
                                    <div class="ts-today-badges">
                                        @if ($phase === 'live')
                                            <span class="ts-live-badge">LIVE</span>
                                        @endif
                                        <span class="pill {{ $pill }}">{{ $item['phase_label'] ?? $phase }}</span>
                                    </div>
                                    <strong>{{ $item['title'] ?? 'Buổi học' }}</strong>
                                    @if (($item['course_title'] ?? '') !== '')
                                        <small>{{ $item['course_title'] }}</small>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($reminders !== [])
                <section class="op-panel ts-reminder-panel">
                    <div class="op-panel-head is-static">
                        <span class="op-panel-ico"><i class="fa-solid fa-bell"></i></span>
                        <span class="op-panel-title">Nhắc lịch 48 giờ</span>
                    </div>
                    <div class="op-panel-body ts-reminder-list">
                        @foreach ($reminders as $item)
                            <a class="ts-reminder-item" href="{{ $item['url'] ?? route('dashboard') }}">
                                <span class="ts-reminder-ico is-{{ $item['reminder_icon'] ?? 'blue' }}">
                                    <i class="fa-solid {{ $item['reminder_icon_fa'] ?? 'fa-circle-info' }}"></i>
                                </span>
                                <span class="ts-reminder-copy">
                                    <strong>{{ $item['title'] ?? 'Buổi học' }}</strong>
                                    <small>{{ $item['time_label'] ?? '' }}@if (($item['course_title'] ?? '') !== '') · {{ $item['course_title'] }}@endif</small>
                                </span>
                                <span class="pill {{ $item['reminder_pill'] ?? 'orange' }}">{{ $item['reminder_hint'] ?? 'Sắp tới' }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    <div class="student-schedule-layout student-schedule-layout--full">
        <div class="student-schedule-main">
            <div
                id="studentScheduleApp"
                class="student-schedule-app"
                data-sessions="{{ $sessionsJson }}"
                data-session-url-base="{{ url('/dashboard') }}"
                data-empty-day="Không có buổi dạy trong ngày này."
            >
                <div class="calendar-shell">
                    <div class="calendar-toolbar">
                        <div class="segmented student-schedule-views" role="tablist" aria-label="Chế độ xem lịch">
                            <button type="button" data-view="day" role="tab" aria-selected="false">Ngày</button>
                            <button type="button" data-view="week" role="tab" aria-selected="false">Tuần</button>
                            <button type="button" data-view="month" class="active" role="tab" aria-selected="true">Tháng</button>
                        </div>
                        <div class="student-schedule-nav">
                            <button type="button" class="btn btn-outline student-schedule-prev" aria-label="Lùi">‹</button>
                            <strong class="student-schedule-label" aria-live="polite"></strong>
                            <button type="button" class="btn btn-outline student-schedule-next" aria-label="Tiến">›</button>
                            <button type="button" class="btn btn-outline student-schedule-today">Hôm nay</button>
                        </div>
                    </div>
                    <div class="student-schedule-body" aria-live="polite"></div>
                </div>
            </div>
        </div>
    </div>

    @include('teacher.schedule.partials.attendance-drawer')
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/teacher-schedule.js') }}"></script>
    <script>
    (() => {
        const drawer = document.getElementById('attendanceDrawer');
        const drawerPanel = drawer ? drawer.querySelector('.attendance-drawer-panel') : null;
        const btnClose = document.getElementById('btnCloseAttendanceDrawer');
        const btnDone = document.getElementById('btnDoneAttendance');

        function closeDrawer() {
            if (!drawer || !drawerPanel) return;
            drawer.style.opacity = '0';
            drawer.style.pointerEvents = 'none';
            drawerPanel.style.transform = 'translateX(100%)';
        }

        if (btnClose) btnClose.addEventListener('click', closeDrawer);
        if (btnDone) btnDone.addEventListener('click', closeDrawer);
        if (drawer) {
            drawer.addEventListener('click', (e) => {
                if (e.target === drawer) closeDrawer();
            });
        }
    })();
    </script>
@endpush
