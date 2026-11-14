@extends('layouts.pwa', ['body_class' => 'is-home'])

@section('title', 'Trang chủ')

@php
    $next = $next ?? [];
    $phase = (string) ($phase ?? '');
    $phaseLabel = match ($phase) {
        'live' => 'Đang diễn ra',
        'ended' => 'Đã kết thúc',
        'cancelled' => 'Đã hủy',
        default => 'Sắp tới',
    };
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

    @if (! empty($hasSession))
        <section class="pwa-card pwa-session">
            <div class="pwa-session-top">
                <span class="pwa-session-ico" aria-hidden="true"><i class="fa-solid fa-chalkboard"></i></span>
                <div>
                    <p class="pwa-kicker">{{ $next['label'] ?? 'Buổi học sắp tới' }}</p>
                    <span class="pwa-phase pwa-phase--{{ $phase ?: 'upcoming' }}">{{ $phaseLabel }}</span>
                </div>
            </div>
            <h1>{{ $sessionTitle ?: 'Buổi học' }}</h1>
            <div class="pwa-session-meta">
                @if (($next['levelTitle'] ?? '') !== '')
                    <p class="pwa-meta"><i class="fa-solid fa-layer-group"></i> {{ $next['levelTitle'] }}</p>
                @endif
                @if (($next['scheduleWhen'] ?? '') !== '')
                    <p class="pwa-meta"><i class="fa-regular fa-calendar"></i> {{ $next['scheduleWhen'] }}</p>
                @endif
                @if ($teacherName !== '')
                    <p class="pwa-meta"><i class="fa-solid fa-chalkboard-user"></i> {{ $teacherName }}</p>
                @endif
            </div>
            @if (($next['countdownLabel'] ?? '') !== '')
                <p class="pwa-count">{{ $next['countdownLabel'] }}</p>
            @endif
            @if (($next['opensAtLabel'] ?? '') !== '')
                <p class="pwa-hint">{{ $next['opensAtLabel'] }}</p>
            @endif
            @if (! empty($waitingTeacher))
                <p class="pwa-hint">Giáo viên chưa vào lớp. Bạn vẫn có thể vào phòng và đợi.</p>
            @endif
            <a class="pwa-btn" href="{{ route('pwa.zoom') }}">
                <i class="fa-solid fa-video"></i>
                Vào Zoom
            </a>
            @if (($publicCode ?? '') !== '')
                <a class="pwa-btn pwa-btn--ghost" href="{{ route('pwa.courses.session', $publicCode) }}">
                    <i class="fa-solid fa-book-open"></i>
                    Xem chi tiết buổi học
                </a>
            @endif
        </section>
    @else
        <section class="pwa-card pwa-session">
            <div class="pwa-session-top">
                <span class="pwa-session-ico" aria-hidden="true"><i class="fa-regular fa-calendar"></i></span>
                <div>
                    <p class="pwa-kicker">Trang chủ</p>
                    <span class="pwa-phase">Chưa có lịch</span>
                </div>
            </div>
            <h1>Chưa có buổi học</h1>
            <p class="pwa-help">Khi có lịch học, buổi sắp tới sẽ hiện ở đây. Bấm Zoom để vào phòng.</p>
            <a class="pwa-btn" href="{{ route('pwa.zoom') }}">
                <i class="fa-solid fa-video"></i>
                Mở Zoom
            </a>
        </section>
    @endif
</main>
@endsection
