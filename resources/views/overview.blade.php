@extends('layouts.app', ['active' => 'home'])

@section('title', 'Tổng quan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-overview.css') }}?v=4">
@endpush

@section('content')
@php
    $levels = $levels ?? [];
    $hasLevels = $levels !== [];
    $activeLevelId = (int) ($nextSchedule['levelId'] ?? 0);
    $course = $course ?? null;
    $hasPendingHomework = ! empty($hasPendingHomework);
@endphp

<div class="so-page">
    @if (! $course)
        <article class="sn-next-session-box text-center py-12">
            <div class="w-16 h-16 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Chưa có khóa học</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto">Bạn chưa được phân vào khóa học nào. Vui lòng liên hệ giáo viên hoặc bộ phận đào tạo SUNNY để được kích hoạt khóa học.</p>
        </article>
    @else
        <!-- 1. Banner nhắc việc bài tập (Figma Node #135:1583) -->
        @if ($hasPendingHomework)
            <section class="sn-homework-reminder" aria-label="Nhắc nhở bài tập">
                <div class="sn-reminder-content">
                    <div class="sn-reminder-mascot-box">
                        <img
                            src="{{ asset('assets/images/mascot-reminder.png') }}"
                            alt="Anh Cam nhắc bài"
                            class="sn-reminder-mascot-img"
                            width="140"
                            height="85"
                            loading="eager"
                        >
                    </div>
                    <div class="sn-reminder-text">
                        <h2 class="sn-reminder-title">Bạn còn bài tập chưa làm!!!</h2>
                        <p class="sn-reminder-desc">Hoàn thành bài tập để sẵn sàng cho buổi học sắp tới nhé!</p>
                    </div>
                </div>
                <div class="sn-reminder-action">
                    <x-sunny.button
                        href="{{ $homeworkUrl ?? route('student.assessments') }}"
                        variant="primary"
                        iconRight="fa-solid fa-arrow-right"
                    >
                        Làm bài tập
                    </x-sunny.button>
                </div>
            </section>
        @endif

        <!-- 2. Header Tổng quan & Tabs chuyển đổi (Figma Node #317:12112) -->
        <header class="sn-overview-header">
            <h1 class="sn-overview-title">Tổng quan</h1>
            <div class="sn-overview-tabs" role="tablist" id="overviewTabs">
                <button
                    type="button"
                    class="sn-pill-tab is-active"
                    id="tabScheduleBtn"
                    data-target="paneSchedule"
                    role="tab"
                    aria-selected="true"
                    aria-controls="paneSchedule"
                >
                    Lịch học sắp tới
                </button>
                <button
                    type="button"
                    class="sn-pill-tab"
                    id="tabLevelsBtn"
                    data-target="paneLevels"
                    role="tab"
                    aria-selected="false"
                    aria-controls="paneLevels"
                >
                    Danh sách cấp độ
                </button>
            </div>
        </header>

        <!-- 3. Nội dung Tab 1: Lịch học sắp tới -->
        <div id="paneSchedule" role="tabpanel" aria-labelledby="tabScheduleBtn" class="space-y-6">
            @include('student.partials.overview-next-schedule', ['nextSchedule' => $nextSchedule ?? []])

            @if ($hasLevels)
                <div class="pt-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-800">Các trình độ trong khóa học</h3>
                        <button type="button" class="text-sm font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1" onclick="document.getElementById('tabLevelsBtn').click();">
                            <span>Xem toàn bộ</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>

                    <div class="sn-levels-grid">
                        @foreach (array_slice($levels, 0, 3) as $level)
                            @php
                                $indexLabel = str_pad((string) (int) ($level['display_index'] ?? 1), 2, '0', STR_PAD_LEFT);
                                $progressPct = min(100, max(0, (int) ($level['progress_pct'] ?? 0)));
                                $levelId = (int) ($level['id'] ?? 0);
                                $isActiveLevel = $activeLevelId > 0 && $levelId === $activeLevelId;
                            @endphp
                            <a
                                class="sn-level-card{{ $isActiveLevel ? ' is-active-level' : '' }}"
                                href="{{ $level['url'] ?? '#' }}"
                            >
                                <div class="sn-level-card-head">
                                    <span class="sn-level-index-badge">{{ $indexLabel }}</span>
                                    <div class="sn-level-card-title-wrap">
                                        <h4 class="sn-level-card-title">{{ $level['display_title'] ?? 'Trình độ' }}</h4>
                                        @if ($isActiveLevel)
                                            <span class="text-xs font-bold text-blue-600">Đang học</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="sn-level-progress-wrap">
                                    <div class="sn-level-progress-labels">
                                        <span>Tiến độ</span>
                                        <strong>{{ $progressPct }}%</strong>
                                    </div>
                                    <div class="sn-level-progress-track">
                                        <div class="sn-level-progress-fill" style="width: {{ $progressPct }}%"></div>
                                    </div>
                                </div>

                                <div class="sn-level-metrics-row">
                                    <div class="sn-level-metric-item">
                                        <strong>{{ (int) ($level['session_count'] ?? 0) }}</strong>
                                        <small>Buổi</small>
                                    </div>
                                    <div class="sn-level-metric-item">
                                        <strong>{{ (int) ($level['attended_count'] ?? 0) }}</strong>
                                        <small>Điểm danh</small>
                                    </div>
                                    <div class="sn-level-metric-item">
                                        <strong>{{ (int) ($level['completed_exercises_count'] ?? 0) }}</strong>
                                        <small>Bài tập</small>
                                    </div>
                                </div>

                                <div class="sn-level-card-foot">
                                    <span>Vào học trình độ này</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- 4. Nội dung Tab 2: Danh sách cấp độ đầy đủ (Figma Node #488:26450 / #488:26455) -->
        <div id="paneLevels" role="tabpanel" aria-labelledby="tabLevelsBtn" class="sn-level-list-container" hidden>
            @if ($hasLevels)
                @foreach ($levels as $level)
                    @php
                        $isUnlocked = ! empty($level['is_unlocked']);
                        $thumbPath = $level['thumbnail_path'] ?? 'assets/images/figma-level-socap1.png';
                    @endphp
                    <div class="sn-course-card">
                        <div class="sn-course-card-thumb-wrap">
                            <img
                                src="{{ asset($thumbPath) }}"
                                alt="{{ $level['display_title'] ?? 'Cấp độ' }}"
                                class="sn-course-card-thumb"
                                width="222"
                                height="182"
                                loading="lazy"
                            >
                        </div>
                        <div class="sn-course-card-body">
                            <div class="sn-course-card-top">
                                <div class="sn-course-card-header">
                                    <h3 class="sn-course-card-title">{{ $level['display_title'] ?? 'Trình độ' }}</h3>
                                    @if ($isUnlocked)
                                        <span class="sn-course-card-badge is-unlocked">Đã mở khoá</span>
                                    @else
                                        <span class="sn-course-card-badge is-locked">Chưa mở khoá</span>
                                    @endif
                                </div>

                                <div class="sn-course-card-stat">
                                    <div class="sn-course-card-stat-icon {{ $isUnlocked ? 'is-unlocked' : 'is-locked' }}" aria-hidden="true">
                                        <img src="{{ asset('assets/images/figma-book.svg') }}" width="20" height="20" alt="">
                                    </div>
                                    <span class="sn-course-card-stat-text">{{ (int) ($level['session_count'] ?? 0) }} Buổi</span>
                                </div>
                            </div>

                            <div class="sn-course-card-bottom">
                                @if ($isUnlocked)
                                    <a href="{{ $level['url'] ?? '#' }}" class="sn-course-card-btn is-unlocked">
                                        <span>Xem khoá học</span>
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <button type="button" class="sn-course-card-btn is-locked" disabled aria-disabled="true">
                                        <span>Xem khoá học</span>
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="sn-next-session-box text-center py-8">
                    <p class="text-slate-500">Chưa có dữ liệu trình độ học.</p>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var tabs = document.querySelectorAll('#overviewTabs [data-target]');
        var panes = {
            paneSchedule: document.getElementById('paneSchedule'),
            paneLevels: document.getElementById('paneLevels')
        };

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                tabs.forEach(function (b) {
                    var isCurrent = b === btn;
                    b.classList.toggle('is-active', isCurrent);
                    b.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
                });

                Object.keys(panes).forEach(function (id) {
                    if (panes[id]) {
                        panes[id].hidden = (id !== targetId);
                    }
                });
            });
        });
    })();
</script>
@endpush
