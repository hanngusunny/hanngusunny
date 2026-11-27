@extends('layouts.app', ['active' => 'course'])

@php
    use App\Services\StudentCourseNavService;
    $levelTitle = trim((string) ($activeLevel->title ?? 'Trình độ học'));
    $courseTitle = trim((string) ($course->title ?? ''));
    $sessionRow = is_array($sessionDetail['session'] ?? null) ? $sessionDetail['session'] : null;
    $sessionDisplay = is_array($sessionDetail['display'] ?? null)
        ? $sessionDetail['display']
        : (is_array($sessionRow['display'] ?? null) ? $sessionRow['display'] : []);

    $selectedSessionTitle = '';
    if ($sessionRow !== null) {
        $selectedSessionTitle = trim((string) ($sessionRow['title'] ?? ''));
    } elseif (($selectedPublicCode ?? '') !== '') {
        foreach ($sessions as $row) {
            if ((string) ($row['public_code'] ?? '') === (string) $selectedPublicCode) {
                $selectedSessionTitle = trim((string) ($row['title'] ?? ''));
                if ($sessionDisplay === [] && is_array($row['display'] ?? null)) {
                    $sessionDisplay = $row['display'];
                }
                break;
            }
        }
    }

    $pageTitle = $selectedSessionTitle !== '' ? $selectedSessionTitle : 'Buổi học';

    $scheduleText = '';
    if (! empty($sessionDisplay['hasSchedule'])) {
        $dateLabel = trim((string) ($sessionDisplay['dateLabel'] ?? ''));
        $timeLabel = trim((string) ($sessionDisplay['timeLabel'] ?? ''));
        $scheduleText = trim(implode(' · ', array_filter([$dateLabel, $timeLabel])));
    }

    $headerSubline = $courseTitle;
    if ($scheduleText !== '') {
        $headerSubline = $headerSubline !== '' ? ($headerSubline.' · '.$scheduleText) : $scheduleText;
    }
@endphp

@section('title', $levelTitle.' · Khóa học')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/student-quiz-office.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}?v=3">
@endpush

@section('content')
<div
    class="sn-course-page sw-page"
    data-course-level-title="{{ $levelTitle }}"
    data-course-session-title="{{ $pageTitle }}"
    data-course-subline="{{ $headerSubline }}"
>
    {{-- Header Buổi Học chuẩn Figma (#317:12180) --}}
    <header class="sn-course-header-card">
        <div class="sn-course-header-info">
            <span class="sn-course-level-pill">{{ $levelTitle }}</span>
            <div class="sn-course-header-titles">
                <h1 class="sn-course-session-title">{{ $pageTitle }}</h1>
                @if ($headerSubline !== '')
                    <p class="sn-course-subline">{{ $headerSubline }}</p>
                @endif
            </div>
        </div>
        @if (($sessions ?? []) !== [])
            <div class="sn-course-header-action">
                @include('student.partials.session-dropdown')
            </div>
        @endif
    </header>

    {{-- Khung 2 cột: Cột điều hướng trái (231px) & Cột nội dung chính (#317:12187) --}}
    <div class="sn-course-layout sw-workspace">
        <aside class="sn-course-sidebar sw-nav" aria-label="Menu buổi học">
            @include('student.partials.course-menu')
        </aside>
        <section class="sn-course-main sw-main" aria-label="Nội dung buổi học">
            @include('student.partials.course-panel')
        </section>
    </div>

    {{-- Modal Xác Nhận Bài Tập Chuẩn Figma (#317:12508, #317:12537, #317:12566) --}}
    <div
        class="sn-quiz-modal-backdrop"
        id="snQuizModalBackdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="snQuizModalTitle"
        hidden
        data-img-pause="{{ asset('assets/images/figma-mascot-pause.png') }}"
        data-img-incomplete="{{ asset('assets/images/figma-mascot-incomplete.png') }}"
        data-img-submit="{{ asset('assets/images/figma-mascot-submit.png') }}"
    >
        <div class="sn-quiz-modal-dialog">
            <h2 class="sn-quiz-modal-title" id="snQuizModalTitle">Xác nhận nộp bài?</h2>
            <p class="sn-quiz-modal-desc" id="snQuizModalDesc"></p>
            <div class="sn-quiz-modal-mascot">
                <img id="snQuizModalImg" src="{{ asset('assets/images/figma-mascot-submit.png') }}" alt="" width="152" height="152" loading="lazy">
            </div>
            <div class="sn-quiz-modal-actions">
                <button type="button" class="sn-course-btn sn-course-btn--primary sn-course-btn--full" id="snQuizModalPrimaryBtn">
                    <span id="snQuizModalPrimaryLabel">Tiếp tục làm</span>
                    <span class="sn-course-btn-icon" id="snQuizModalPrimaryIcon" aria-hidden="true"></span>
                </button>
                <button type="button" class="sn-course-btn sn-course-btn--white sn-course-btn--full" id="snQuizModalSecondaryBtn">
                    <span id="snQuizModalSecondaryLabel">Lưu &amp; thoát</span>
                    <span class="sn-course-btn-icon" id="snQuizModalSecondaryIcon" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('student.partials.quiz-scripts')
    <script src="{{ asset('assets/js/student-workspace.js') }}?v=3"></script>
@endpush
