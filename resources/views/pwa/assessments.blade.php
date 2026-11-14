@extends('layouts.pwa', ['body_class' => 'is-home'])

@section('title', 'Kiểm tra')

@php
    $phaseCss = [
        'open' => 'live',
        'retake' => 'live',
        'completed' => 'ended',
        'closed' => 'ended',
        'upcoming' => 'upcoming',
    ];
@endphp

@section('content')
<main class="pwa-home pwa-page">
    @include('pwa.partials.header')

    @if (session('error'))
        <div class="pwa-alert is-error">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="pwa-alert is-ok">{{ session('success') }}</div>
    @endif

    <div class="pwa-course-intro">
        <p class="pwa-kicker">Bài tập</p>
        <h1>Kiểm tra</h1>
        @if (($courseTitle ?? '') !== '')
            <p class="pwa-help">{{ $courseTitle }}</p>
        @endif
    </div>

    @forelse (($assessments ?? []) as $item)
        @php
            $phase = (string) ($item['phase'] ?? '');
            $phaseLabel = (string) ($item['phaseLabel'] ?? '');
            $display = $item['display'] ?? [];
            $canOpen = ! empty($item['can_open']);
            $isTakeable = ! empty($item['is_takeable']);
            $href = $canOpen ? (string) ($item['take_url'] ?? '') : '';
            $badge = $phaseCss[$phase] ?? 'upcoming';
            $hasSubmitted = ! empty($item['has_submitted']);
            $pendingReview = $hasSubmitted && empty($item['is_graded']);
            $questionCount = (int) ($item['question_count'] ?? 0);
            $durationMinutes = (int) ($item['duration_minutes'] ?? 0);
            $metaBits = [];
            if ($questionCount > 0) {
                $metaBits[] = $questionCount.' câu hỏi';
            }
            if ($durationMinutes > 0) {
                $metaBits[] = $durationMinutes.' phút';
            }
            $countdown = (string) ($display['countdownLabel'] ?? '');
            $showCountdown = $countdown !== '' && ! $pendingReview && $countdown !== $phaseLabel;
        @endphp
        @if ($href !== '')
            <a class="pwa-card pwa-sess pwa-assess-card{{ $pendingReview ? ' is-review' : '' }}{{ $hasSubmitted ? ' is-submitted' : '' }}" href="{{ $href }}">
        @else
            <div class="pwa-card pwa-sess pwa-assess-card{{ $pendingReview ? ' is-review' : '' }}{{ $hasSubmitted ? ' is-submitted' : '' }}{{ $canOpen ? '' : ' is-locked' }}">
        @endif
            <span class="pwa-sess-date">
                <strong>{{ $display['dateDay'] ?? '—' }}</strong>
                <em>{{ $display['dateMonth'] ?? ($display['dateLabel'] ?? '') }}</em>
            </span>
            <div class="pwa-sess-body">
                <div class="pwa-assess-badges">
                    @if ($hasSubmitted)
                        <span class="pwa-phase pwa-phase--done">Đã nộp</span>
                    @elseif ($phaseLabel !== '')
                        <span class="pwa-phase pwa-phase--{{ $badge }}">{{ $phaseLabel }}</span>
                    @endif
                    @if ($pendingReview)
                        <span class="pwa-phase pwa-phase--review">Chờ giáo viên chấm</span>
                    @endif
                </div>
                <h2>{{ $item['title'] ?? 'Bài kiểm tra' }}</h2>
                @if (! empty($display['hasSchedule']) && ($display['timeLabel'] ?? '') !== '')
                    <p>{{ $display['weekdayShort'] ?? '' }} · {{ $display['timeLabel'] }}</p>
                @elseif (($display['dateLabel'] ?? '') !== '' && ($display['dateLabel'] ?? '') !== 'Chưa lên lịch')
                    <p>{{ $display['dateLabel'] }}</p>
                @else
                    <p>Chưa lên lịch</p>
                @endif
                @if ($metaBits !== [])
                    <p>{{ implode(' · ', $metaBits) }}</p>
                @endif
                @if ($showCountdown)
                    <p class="{{ $isTakeable ? 'pwa-sess-done' : ($canOpen ? '' : 'pwa-sess-lock') }}">{{ $countdown }}</p>
                @endif
            </div>
            <i class="fa-solid {{ $canOpen ? 'fa-chevron-right' : 'fa-lock' }} pwa-sess-go" aria-hidden="true"></i>
        @if ($href !== '')
            </a>
        @else
            </div>
        @endif
    @empty
        <section class="pwa-card">
            <h2>Chưa có bài kiểm tra</h2>
            <p class="pwa-help">Khi giáo viên giao bài, danh sách sẽ hiện ở đây.</p>
        </section>
    @endforelse
</main>
@endsection
