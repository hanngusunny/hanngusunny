@extends('layouts.pwa', ['body_class' => 'is-home'])

@section('title', 'Khóa học')

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
        <p class="pwa-kicker">Học tập</p>
        <h1>Khóa học</h1>
        @if (($courseTitle ?? '') !== '')
            <p class="pwa-help">{{ $courseTitle }}</p>
        @endif
    </div>

    @forelse (($levels ?? []) as $level)
        @php
            $tone = (string) ($level['tone'] ?? 'amber');
            $pct = (int) ($level['progress_pct'] ?? 0);
        @endphp
        <a class="pwa-card pwa-level pwa-level--{{ $tone }}" href="{{ $level['href'] }}">
            <div class="pwa-level-top">
                <span class="pwa-level-num">{{ str_pad((string) ($level['display_index'] ?? 1), 2, '0', STR_PAD_LEFT) }}</span>
                <span class="pwa-level-pct">{{ $pct }}%</span>
            </div>
            <h2>{{ $level['display_title'] }}</h2>
            @if (($level['description'] ?? '') !== '')
                <p class="pwa-help">{{ $level['description'] }}</p>
            @endif
            <p class="pwa-meta"><i class="fa-solid fa-layer-group"></i> {{ (int) $level['attended_count'] }}/{{ (int) $level['session_count'] }} buổi đã học</p>
            <div class="pwa-level-bar" aria-hidden="true"><span style="width: {{ $pct }}%"></span></div>
            <span class="pwa-level-go">Xem buổi học <i class="fa-solid fa-chevron-right"></i></span>
        </a>
    @empty
        @forelse (($teacherCourses ?? []) as $course)
            <a class="pwa-card pwa-level" href="{{ $course['href'] }}">
                <div class="pwa-level-top">
                    <span class="pwa-phase">{{ $course['statusLabel'] }}</span>
                </div>
                <h2>{{ $course['title'] }}</h2>
                <span class="pwa-level-go">Xem buổi học <i class="fa-solid fa-chevron-right"></i></span>
            </a>
        @empty
            <section class="pwa-card">
                <h2>Chưa có khóa học</h2>
                <p class="pwa-help">Khi được thêm vào khóa, danh sách sẽ hiện ở đây.</p>
            </section>
        @endforelse
    @endforelse
</main>
@endsection
