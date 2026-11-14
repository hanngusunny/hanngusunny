@extends('layouts.pwa', ['body_class' => 'is-home is-session'])

@php
    use App\Services\PwaCourseService;

    $session = $sessionRow ?? ($sessionDetail['session'] ?? []);
    $phase = (string) ($session['phase'] ?? 'upcoming');
    $phaseLabels = $phaseLabels ?? \App\Models\CourseSession::phaseLabels();
    $display = $session['display'] ?? [];
    $publicCode = trim((string) ($session['public_code'] ?? ''));
    $activeMuc = (string) ($activeMuc ?? PwaCourseService::MUC_INFO);
    $showWork = $activeMuc !== PwaCourseService::MUC_INFO && ! empty($isLearner);
@endphp

@section('title', ($session['title'] ?? 'Buổi học'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pwa-session.css') }}?v=4">
@endpush

@section('content')
<main class="pwa-home pwa-page pwa-session-page">
    @include('pwa.partials.header')

    @if (session('error'))
        <div class="pwa-alert is-error">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="pwa-alert is-ok">{{ session('success') }}</div>
    @endif

    @if (! empty($isLearner) && ! empty($sessionMenu))
        @include('pwa.partials.session-menu')
    @else
        <a class="pwa-back" href="{{ $backUrl }}">
            <i class="fa-solid fa-chevron-left"></i>
            {{ $backLabel }}
        </a>
    @endif

    <section class="pwa-card pwa-session-hero">
        <span class="pwa-phase pwa-phase--{{ $phase }}">{{ $phaseLabels[$phase] ?? $phase }}</span>
        <h1>{{ $session['title'] ?: 'Buổi học' }}</h1>
        @if (! empty($display['hasSchedule']))
            <p class="pwa-help">{{ $display['weekdayFull'] ?? '' }}@if (($display['dateLabel'] ?? '') !== '') · {{ $display['dateLabel'] }}@endif @if (($display['timeLabel'] ?? '') !== '')· {{ $display['timeLabel'] }}@endif</p>
        @endif
    </section>

    @if (count($tabs ?? []) > 1)
        <nav class="pwa-muc" aria-label="Nội dung buổi học">
            @foreach ($tabs as $tab)
                <a
                    class="pwa-muc-item{{ $activeMuc === $tab['id'] ? ' is-on' : '' }}"
                    href="{{ route('pwa.courses.session', ['code' => $publicCode, 'muc' => $tab['id']]) }}"
                >{{ $tab['label'] }}</a>
            @endforeach
        </nav>
    @endif

    @if ($activeMuc === PwaCourseService::MUC_INFO)
        <div class="pwa-card pwa-session-panel">
            @include('pwa.partials.session-info')
        </div>
    @elseif ($showWork)
        <div class="pwa-session-work">
            @include('student.partials.session-detail', [
                'sessionDetail' => $sessionDetail,
                'contentTab' => $contentTab,
                'pwaEmbed' => true,
                'pwaReturn' => true,
            ])
        </div>
    @endif
</main>
@endsection

@push('scripts')
    @if ($showWork)
        @include('student.partials.quiz-scripts')
    @endif
    <script>
        (function () {
            var active = document.getElementById('pwa-course-session-active');
            var list = document.getElementById('pwa-course-session-list');
            if (!active || !list) return;
            var top = active.offsetTop - list.offsetTop - (list.clientHeight / 2) + (active.clientHeight / 2);
            list.scrollTop = Math.max(0, top);
        })();
    </script>
@endpush
