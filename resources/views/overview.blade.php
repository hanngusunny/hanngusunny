@extends('layouts.app', ['active' => 'home'])

@section('title', 'Tổng quan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-overview.css') }}?v=2">
@endpush

@section('content')
@php
    $levels = $levels ?? [];
    $hasLevels = $levels !== [];
    $activeLevelId = (int) ($nextSchedule['levelId'] ?? 0);
    $course = $course ?? null;
@endphp

<div class="op-stack so-page">
    <x-page-title
        kicker="Tổng quan"
        icon="fa-graduation-cap"
        title="Khóa học của tôi"
        lead="Theo dõi buổi học tiếp theo và chọn trình độ để vào học."
    >
        @if ($course)
            <x-slot:meta>
                <span><i class="fa-solid fa-chalkboard"></i>{{ $course->title }}</span>
                @if ($hasLevels)
                    <span><i class="fa-solid fa-layer-group"></i>{{ count($levels) }} trình độ</span>
                @endif
            </x-slot:meta>
        @endif
    </x-page-title>

    @if (! $course)
        <article class="op-panel so-empty">
            <span class="so-empty-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            <strong>Chưa có khóa học</strong>
            <p>Bạn chưa được thêm vào khóa học nào. Vui lòng liên hệ giáo viên.</p>
        </article>
    @else
        <div class="so-layout{{ $hasLevels ? '' : ' is-session-only' }}">
            <section class="so-main" aria-label="Thông tin buổi học">
                @include('student.partials.overview-next-schedule', ['nextSchedule' => $nextSchedule ?? []])
            </section>

            @if ($hasLevels)
                <aside class="so-aside" aria-label="Trình độ học">
                    <header class="so-levels-head">
                        <div>
                            <h2>Trình độ học</h2>
                            <p>Chọn trình độ để xem buổi và nội dung</p>
                        </div>
                        <span class="so-levels-count">{{ count($levels) }} trình độ</span>
                    </header>

                    <div class="so-level-stack">
                        @foreach ($levels as $level)
                            @php
                                $indexLabel = str_pad((string) (int) ($level['display_index'] ?? 1), 2, '0', STR_PAD_LEFT);
                                $progressPct = min(100, max(0, (int) ($level['progress_pct'] ?? 0)));
                                $description = \App\Support\HtmlContent::plain($level['description'] ?? '');
                                $levelId = (int) ($level['id'] ?? 0);
                                $isActiveLevel = $activeLevelId > 0 && $levelId === $activeLevelId;
                                $isLive = ((int) ($level['live_count'] ?? 0)) > 0;
                            @endphp
                            <a
                                class="so-level{{ $isLive ? ' is-live' : '' }}{{ $isActiveLevel ? ' is-current' : '' }}"
                                href="{{ $level['url'] ?? '#' }}"
                            >
                                <div class="so-level-head">
                                    <span class="so-level-num" aria-hidden="true">{{ $indexLabel }}</span>
                                    <div class="so-level-copy">
                                        <h3>{{ $level['display_title'] ?? 'Trình độ' }}</h3>
                                        @if ($isActiveLevel)
                                            <span class="so-level-badge">Khóa đang học</span>
                                        @endif
                                        @if ($description !== '')
                                            <p>{{ \Illuminate\Support\Str::limit($description, 64) }}</p>
                                        @endif
                                    </div>
                                    @if ($isLive)
                                        <span class="so-level-live"><span></span>Live</span>
                                    @endif
                                </div>

                                <div class="so-level-progress" aria-label="Tiến độ điểm danh {{ $progressPct }} phần trăm">
                                    <div class="so-level-progress-top">
                                        <span>Tiến độ</span>
                                        <strong>{{ $progressPct }}%</strong>
                                    </div>
                                    <div class="so-level-progress-bar">
                                        <span style="width: {{ $progressPct }}%"></span>
                                    </div>
                                </div>

                                <div class="so-level-metrics">
                                    <div>
                                        <strong>{{ (int) ($level['session_count'] ?? 0) }}</strong>
                                        <span>Buổi</span>
                                    </div>
                                    <div>
                                        <strong>{{ (int) ($level['attended_count'] ?? 0) }}</strong>
                                        <span>Điểm danh</span>
                                    </div>
                                    <div>
                                        <strong>{{ (int) ($level['completed_exercises_count'] ?? 0) }}</strong>
                                        <span>Bài tập</span>
                                    </div>
                                </div>

                                <div class="so-level-foot">
                                    <span>Xem buổi học</span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </aside>
            @else
                <aside class="so-aside">
                    <article class="op-panel so-empty">
                        <span class="so-empty-icon"><i class="fa-solid fa-layer-group"></i></span>
                        <p>Khóa <strong>{{ $course->title }}</strong> chưa có trình độ học hoặc chưa gắn chương trình.</p>
                        <a class="op-btn-ghost" href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-book"></i>
                            <span>Xem buổi học trong Khóa học</span>
                        </a>
                    </article>
                </aside>
            @endif
        </div>
    @endif
</div>
@endsection
