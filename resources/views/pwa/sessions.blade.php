@extends('layouts.pwa', ['body_class' => 'is-home'])

@section('title', $listTitle ?? 'Buổi học')

@php
    $phaseLabels = $phaseLabels ?? \App\Models\CourseSession::phaseLabels();
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

    <a class="pwa-back" href="{{ $backUrl }}">
        <i class="fa-solid fa-chevron-left"></i>
        {{ $backLabel }}
    </a>

    <div class="pwa-course-intro">
        <p class="pwa-kicker">{{ $listKicker }}</p>
        <h1>{{ $listTitle }}</h1>
        @if (($listHint ?? '') !== '')
            <p class="pwa-help">{{ $listHint }}</p>
        @endif
    </div>

    @forelse (($sessions ?? []) as $session)
        @php
            $phase = (string) ($session['phase'] ?? 'upcoming');
            $display = $session['display'] ?? [];
            $viewable = ! empty($session['is_viewable']);
            $href = $viewable ? route('pwa.courses.session', $session['public_code']) : '';
        @endphp
        @if ($viewable)
            <a class="pwa-card pwa-sess" href="{{ $href }}">
        @else
            <div class="pwa-card pwa-sess is-locked">
        @endif
            <span class="pwa-sess-date">
                <strong>{{ $display['dateDay'] ?? '—' }}</strong>
                <em>{{ $display['dateMonth'] ?? '' }}</em>
            </span>
            <div class="pwa-sess-body">
                <span class="pwa-phase pwa-phase--{{ $phase }}">{{ $phaseLabels[$phase] ?? $phase }}</span>
                <h2>{{ $session['title'] ?: 'Buổi học' }}</h2>
                @if (! empty($display['hasSchedule']))
                    <p>{{ $display['weekdayShort'] ?? '' }}@if (($display['timeLabel'] ?? '') !== '') · {{ $display['timeLabel'] }}@endif</p>
                @else
                    <p>Chưa lên lịch</p>
                @endif
                @if (! $viewable)
                    <p class="pwa-sess-lock"><i class="fa-solid fa-lock"></i> Chưa mở</p>
                @elseif (! empty($session['has_attended']))
                    <p class="pwa-sess-done"><i class="fa-solid fa-check"></i> Đã điểm danh</p>
                @endif
            </div>
            <i class="fa-solid fa-chevron-right pwa-sess-go" aria-hidden="true"></i>
        @if ($viewable)
            </a>
        @else
            </div>
        @endif
    @empty
        <section class="pwa-card">
            <h2>Chưa có buổi học</h2>
            <p class="pwa-help">Khi có lịch, danh sách buổi sẽ hiện ở đây.</p>
        </section>
    @endforelse
</main>
@endsection
