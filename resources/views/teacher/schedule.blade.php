@extends('layouts.app', ['active' => 'schedule'])

@php
    $payload = is_array($payload ?? null) ? $payload : [];
    $courses = is_array($payload['courses'] ?? null) ? $payload['courses'] : [];
    $sessions = is_array($payload['sessions'] ?? null) ? $payload['sessions'] : [];

    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
    $sessionsJson = json_encode($sessions, $jsonFlags);
    if ($sessionsJson === false) {
        $sessionsJson = '[]';
    }
    $coursesJson = json_encode($courses, $jsonFlags);
    if ($coursesJson === false) {
        $coursesJson = '[]';
    }
@endphp

@section('title', 'Lịch dạy')

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-schedule.css') }}?v=2">
@endpush

@section('content')
<div class="sn-ts-page">
    <section
        id="teacherScheduleApp"
        class="sn-ts-surface"
        data-sessions="{{ $sessionsJson }}"
        data-courses="{{ $coursesJson }}"
        data-today="{{ date('Y-m-d') }}"
        data-default-view="day"
        data-empty-day="Không có buổi dạy nào trong ngày này."
    >
        {{-- Tiêu đề trang (#602:68774) --}}
        <header class="sn-ts-header">
            <h1 class="sn-ts-title">Lịch dạy</h1>
            <p class="sn-ts-subtitle">Tất cả buổi dạy của các khóa bạn phụ trách</p>
        </header>

        {{-- Thanh công cụ lịch (#602:68777) --}}
        <div class="sn-ts-toolbar">
            <div class="sn-ts-views" role="tablist" aria-label="Chế độ xem lịch">
                <button type="button" class="sn-ts-view-btn is-active" data-view="day" role="tab" aria-selected="true">Ngày</button>
                <button type="button" class="sn-ts-view-btn" data-view="week" role="tab" aria-selected="false">Tuần</button>
                <button type="button" class="sn-ts-view-btn" data-view="month" role="tab" aria-selected="false">Tháng</button>
            </div>

            <div class="sn-ts-nav">
                <button type="button" class="sn-ts-nav-btn sn-ts-prev" aria-label="Lùi thời gian">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <strong class="sn-ts-date-label" aria-live="polite"></strong>
                <button type="button" class="sn-ts-nav-btn sn-ts-next" aria-label="Tiến thời gian">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <button type="button" class="sn-ts-today-btn">Hôm nay</button>
        </div>

        {{-- Chú giải & bộ lọc khóa học (#602:68787 / #549:2765) --}}
        <div class="sn-ts-chips" role="group" aria-label="Lọc lịch dạy theo khóa học">
            <button
                type="button"
                class="sn-ts-chip sn-ts-chip--all is-selected"
                data-course-filter="all"
                aria-pressed="true"
            >
                <span class="sn-ts-chip-dot" aria-hidden="true"></span>
                <span>Tất cả khóa học</span>
            </button>

            @foreach ($courses as $course)
                @php
                    $cId = (int) ($course['id'] ?? 0);
                    $cColor = (string) ($course['color'] ?? 'orange');
                    $cTitle = (string) ($course['title'] ?? 'Khóa học');
                @endphp
                <button
                    type="button"
                    class="sn-ts-chip sn-ts-chip--{{ $cColor }} is-selected"
                    data-course-filter="{{ $cId }}"
                    aria-pressed="true"
                    title="{{ $cTitle }}"
                >
                    <span class="sn-ts-chip-dot" aria-hidden="true"></span>
                    <span>{{ $cTitle }}</span>
                </button>
            @endforeach
        </div>

        {{-- Khung lịch chính (#602:68793) --}}
        <div class="sn-ts-calendar-wrap">
            <div class="sn-ts-calendar-body" aria-live="polite"></div>
        </div>
    </section>

    {{-- Backdrop mobile & Popover chi tiết buổi dạy (#602:68840 / #549:2897) --}}
    <div class="sn-ts-popover-backdrop" id="teacherScheduleBackdrop" hidden></div>
    <aside
        class="sn-ts-popover"
        id="teacherSchedulePopover"
        role="dialog"
        aria-modal="false"
        aria-label="Chi tiết buổi dạy"
        hidden
    ></aside>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/teacher-schedule.js') }}?v=2"></script>
@endpush
