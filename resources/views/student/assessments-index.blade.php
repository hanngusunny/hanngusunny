@extends('layouts.app', ['active' => 'assessments'])

@php
    $assessments = $assessments ?? [];
    $levelTitle = trim((string) ($activeLevel->title ?? ''));
@endphp

@section('title', 'Bài thi'.($levelTitle !== '' ? ' · '.$levelTitle : ''))

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/student-assessment.css') }}?v=2">
@endpush

@section('content')
<div class="sn-exam-page sn-exam-list-page">
    <section class="sn-exam-surface sn-exam-list-surface" aria-labelledby="snExamListHeading">
        <header class="sn-exam-list-head">
            <h1 class="sn-exam-heading" id="snExamListHeading">Bài thi</h1>
        </header>

        @if (session('error'))
            <div class="sn-exam-flash sn-exam-flash--error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if (session('success') || session('info'))
            <div class="sn-exam-flash sn-exam-flash--info" role="status">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>{{ session('success') ?: session('info') }}</span>
            </div>
        @endif

        <div class="sn-exam-list-well">
            @if ($assessments === [])
                <div class="sn-exam-empty-card">
                    <img
                        src="{{ asset('assets/images/figma-mascot-fighting.png') }}"
                        alt="Chưa có bài thi"
                        width="148"
                        height="148"
                        loading="lazy"
                    >
                    <h2 class="sn-exam-empty-title">Chưa có bài thi nào</h2>
                    <p class="sn-exam-empty-desc">Bài thi sẽ hiển thị tại đây khi giáo viên lên lịch trong khóa học của bạn.</p>
                </div>
            @else
                @foreach ($assessments as $assessment)
                    @php
                        $display = is_array($assessment['display'] ?? null) ? $assessment['display'] : [];
                        $phase = (string) ($display['phase'] ?? ($assessment['phase'] ?? 'upcoming'));
                        $hasSubmitted = ! empty($assessment['has_submitted']);
                        $resultsVisible = ! empty($assessment['results_visible']);
                        $allowRetake = ! empty($assessment['allow_retake']);
                        $isTakeable = ! empty($assessment['is_takeable']);
                        $canOpen = ! empty($assessment['can_open']);
                        $takeUrl = (string) ($assessment['take_url'] ?? '#');
                        $title = trim((string) ($assessment['title'] ?? 'Bài thi'));
                        $duration = (int) ($assessment['duration_minutes'] ?? 0);
                        $questionCount = (int) ($assessment['question_count'] ?? 0);
                        $hasSchedule = ! empty($display['hasSchedule']);
                        $dateLabel = trim((string) ($display['dateLabel'] ?? ''));
                        $timeLabel = trim((string) ($display['timeLabel'] ?? ''));

                        $startClock = '';
                        $endClock = '';
                        if ($timeLabel !== '') {
                            $parts = preg_split('/\s*[–-]\s*/u', $timeLabel);
                            $startClock = trim((string) ($parts[0] ?? ''));
                            $endClock = trim((string) ($parts[1] ?? ''));
                        }
                        if ($startClock === '' && ! empty($assessment['start_time'])) {
                            $startClock = substr(trim((string) $assessment['start_time']), 0, 5);
                        }
                        if ($endClock === '' && ! empty($assessment['end_time'])) {
                            $endClock = substr(trim((string) $assessment['end_time']), 0, 5);
                        }

                        if ($isTakeable && $allowRetake && $hasSubmitted) {
                            $statusKey = 'retake';
                            $statusLabel = 'Được phép làm lại';
                            $scheduleIcon = 'open';
                            $scheduleText = ($hasSchedule && $endClock !== '' && $dateLabel !== '')
                                ? 'Mở đến '.$endClock.' · '.$dateLabel
                                : ($hasSchedule ? trim($timeLabel.' · '.$dateLabel, ' ·') : 'Đang mở làm lại');
                            $ctaKind = 'primary';
                            $ctaLabel = 'Làm lại';
                        } elseif ($isTakeable) {
                            $statusKey = 'open';
                            $statusLabel = 'Đang mở';
                            $scheduleIcon = 'open';
                            $scheduleText = ($hasSchedule && $endClock !== '' && $dateLabel !== '')
                                ? 'Mở đến '.$endClock.' · '.$dateLabel
                                : ($hasSchedule ? trim($timeLabel.' · '.$dateLabel, ' ·') : 'Đang mở làm bài');
                            $ctaKind = 'primary';
                            $ctaLabel = ! empty($assessment['quiz_started']) ? 'Tiếp tục làm' : 'Bắt đầu';
                        } elseif ($hasSubmitted && $resultsVisible) {
                            $statusKey = 'graded';
                            $statusLabel = 'Đã có kết quả';
                            $scheduleIcon = 'graded';
                            $scheduleText = $hasSchedule
                                ? trim($timeLabel.' · '.$dateLabel, ' ·')
                                : 'Đã công bố kết quả';
                            $ctaKind = 'white';
                            $ctaLabel = 'Xem kết quả';
                        } elseif ($hasSubmitted) {
                            $statusKey = 'pending';
                            $statusLabel = 'Chờ chấm điểm';
                            $scheduleIcon = 'pending';
                            $scheduleText = $hasSchedule && $dateLabel !== ''
                                ? 'Đã nộp bài · '.$dateLabel
                                : 'Đã nộp · Chờ giáo viên chấm';
                            $ctaKind = 'white';
                            $ctaLabel = 'Xem trạng thái';
                        } elseif ($phase === 'closed') {
                            $statusKey = 'closed';
                            $statusLabel = 'Đã kết thúc';
                            $scheduleIcon = 'calendar';
                            $scheduleText = ($hasSchedule && $endClock !== '' && $dateLabel !== '')
                                ? 'Đã đóng lúc '.$endClock.' · '.$dateLabel
                                : ($hasSchedule ? trim($timeLabel.' · '.$dateLabel, ' ·') : 'Bài thi đã kết thúc');
                            $ctaKind = $canOpen ? 'white' : 'disabled';
                            $ctaLabel = $canOpen ? 'Xem chi tiết' : 'Đã kết thúc';
                        } else {
                            $statusKey = 'upcoming';
                            $statusLabel = 'Chưa mở';
                            $scheduleIcon = 'calendar';
                            $scheduleText = ($hasSchedule && $startClock !== '' && $dateLabel !== '')
                                ? 'Mở lúc '.$startClock.' · '.$dateLabel
                                : ($hasSchedule ? trim($timeLabel.' · '.$dateLabel, ' ·') : 'Chưa lên lịch');
                            $ctaKind = 'disabled-peach';
                            $ctaLabel = 'Bắt đầu';
                        }
                    @endphp

                    <article class="sn-exam-card">
                        <div class="sn-exam-card-info">
                            <span class="sn-exam-status sn-exam-status--{{ $statusKey }}">{{ $statusLabel }}</span>

                            <div class="sn-exam-card-copy">
                                <h2 class="sn-exam-card-title">
                                    @if ($canOpen)
                                        <a href="{{ $takeUrl }}">{{ $title }}</a>
                                    @else
                                        <span>{{ $title }}</span>
                                    @endif
                                </h2>

                                <div class="sn-exam-card-meta">
                                    <div class="sn-exam-meta-row">
                                        @if ($scheduleIcon === 'open')
                                            <svg class="sn-exam-meta-ico sn-exam-meta-ico--green" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10" fill="currentColor"/>
                                                <path d="M8.5 12.2L10.8 14.5L15.8 9.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @elseif ($scheduleIcon === 'graded')
                                            <svg class="sn-exam-meta-ico sn-exam-meta-ico--blue" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10" fill="currentColor"/>
                                                <path d="M8.5 12.2L10.8 14.5L15.8 9.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @elseif ($scheduleIcon === 'pending')
                                            <svg class="sn-exam-meta-ico sn-exam-meta-ico--purple" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="M12 7.5V12L15 13.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @else
                                            <svg class="sn-exam-meta-ico" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <rect x="3.5" y="4.5" width="17" height="16" rx="3" stroke="currentColor" stroke-width="1.7"/>
                                                <path d="M8 2.8V6M16 2.8V6M3.5 9.5H20.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                                <circle cx="8.5" cy="13.5" r="1" fill="currentColor"/>
                                                <circle cx="12" cy="13.5" r="1" fill="currentColor"/>
                                                <circle cx="15.5" cy="13.5" r="1" fill="currentColor"/>
                                                <circle cx="8.5" cy="16.8" r="1" fill="currentColor"/>
                                                <circle cx="12" cy="16.8" r="1" fill="currentColor"/>
                                            </svg>
                                        @endif
                                        <span>{{ $scheduleText }}</span>
                                    </div>

                                    <div class="sn-exam-meta-row">
                                        <svg class="sn-exam-meta-ico" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.7"/>
                                            <path d="M12 7.5V12.2L15.2 14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span>
                                            @if ($duration > 0)
                                                {{ $duration }} phút
                                            @elseif ($questionCount > 0)
                                                {{ $questionCount }} câu hỏi
                                            @else
                                                Không giới hạn thời gian
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sn-exam-card-cta">
                            @if ($ctaKind === 'primary')
                                <a class="sn-exam-btn sn-exam-btn--primary" href="{{ $takeUrl }}">{{ $ctaLabel }}</a>
                            @elseif ($ctaKind === 'white')
                                <a class="sn-exam-btn sn-exam-btn--white" href="{{ $takeUrl }}">{{ $ctaLabel }}</a>
                            @elseif ($ctaKind === 'disabled-peach')
                                <button type="button" class="sn-exam-btn sn-exam-btn--peach-disabled" disabled>{{ $ctaLabel }}</button>
                            @else
                                <button type="button" class="sn-exam-btn sn-exam-btn--muted-disabled" disabled>{{ $ctaLabel }}</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            @endif
        </div>
    </section>
</div>
@endsection
