@extends('layouts.app', ['active' => 'progress'])

@php
    $progress = is_array($progress ?? null) ? $progress : [];
    $overall = is_array($progress['overall'] ?? null) ? $progress['overall'] : [];
    $streak = is_array($progress['streak'] ?? null) ? $progress['streak'] : [];
    $weekly = is_array($progress['weekly'] ?? null) ? $progress['weekly'] : [];
    $counts = is_array($progress['activity_counts'] ?? null) ? $progress['activity_counts'] : [];
    $recent = is_array($progress['recent'] ?? null) ? $progress['recent'] : [];
    $weekDays = is_array($weekly['days'] ?? null) ? $weekly['days'] : [];
    $weeklyPct = (int) ($weekly['progress_percent'] ?? 0);
    $overallPct = (int) ($overall['percent'] ?? 0);
    $currentStreak = (int) ($streak['current'] ?? 0);
    $bestStreak = (int) ($streak['best'] ?? 0);
    $activeDays = (int) ($weekly['active_days'] ?? 0);
    $goalDays = (int) ($weekly['goal_days'] ?? 5);
    $weekTotal = (int) ($counts['total'] ?? 0);
    $isCourseSchedule = ($weekly['schedule_mode'] ?? '') === 'course';
    $weeklyUnitLabel = $isCourseSchedule ? 'buổi' : 'ngày';
    $courseScheduleTitle = trim((string) ($weekly['course_title'] ?? ''));
    $showLibraryTracks = ((int) ($overall['lessons_total'] ?? 0) > 0) || ((int) ($overall['videos_total'] ?? 0) > 0);
    $courseUrl = isset($navService) ? $navService->dashboardUrlForStudent((int) auth()->id()) : route('dashboard');

    $activityTiles = [
        ['key' => 'sessions', 'icon' => 'fa-chalkboard', 'label' => 'Buổi học'],
        ['key' => 'assessments', 'icon' => 'fa-list-check', 'label' => 'Kiểm tra'],
        ['key' => 'lessons', 'icon' => 'fa-book', 'label' => 'Bài học'],
        ['key' => 'videos', 'icon' => 'fa-circle-play', 'label' => 'Video'],
        ['key' => 'mock_exams', 'icon' => 'fa-certificate', 'label' => 'Thi thử'],
    ];

    $streakGuides = [
        ['title' => 'Buổi học trên lớp', 'desc' => 'Vào phòng, làm bài trước buổi hoặc bài sau buổi — mỗi ngày có hoạt động được tính streak.'],
        ['title' => 'Ôn tập kiến thức', 'desc' => 'Làm ôn tập gắn với buổi học để giữ nhịp học giữa các buổi.'],
        ['title' => 'Bài kiểm tra khóa', 'desc' => 'Nộp bài kiểm tra trong khóa. Mỗi lần nộp tính một ngày học.'],
    ];

    if ($currentStreak > 0) {
        $pageSubtitle = 'Bạn đang duy trì '.$currentStreak.' ngày liên tiếp. Tuần này đã học '.$activeDays.'/'.$goalDays.' '.$weeklyUnitLabel.'.';
    } elseif ($isCourseSchedule && $goalDays > 0) {
        $pageSubtitle = 'Tuần này có '.$goalDays.' buổi theo lịch khóa'.($courseScheduleTitle !== '' ? ' «'.$courseScheduleTitle.'»' : '').' — đã tham gia '.$activeDays.'/'.$goalDays.' buổi.';
    } elseif ($weekTotal > 0) {
        $pageSubtitle = 'Tuần này bạn đã có '.$weekTotal.' hoạt động học — tiếp tục để tạo streak nhé!';
    } else {
        $pageSubtitle = 'Bắt đầu với một buổi học, ôn tập hoặc bài kiểm tra hôm nay để theo dõi streak và mục tiêu tuần.';
    }
@endphp

@section('title', 'Tiến độ học tập')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/student-progress.css') }}">
@endpush

@section('content')
<div class="op-stack lp-page">
    @if (! empty($showEvaluation) && ($filledEvalMonths ?? []) !== [] && ($course ?? null) !== null)
        @include('student.partials.monthly-eval', [
            'monthlyEvaluation' => $monthlyEvaluation ?? null,
            'course' => $course,
            'scoreLabels' => $scoreLabels ?? [],
            'evalMonthLabel' => $evalMonthLabel ?? '',
            'selectedEvalMonth' => $selectedEvalMonth ?? date('Y-m'),
            'filledEvalMonths' => $filledEvalMonths ?? [],
            'hasEvalForMonth' => $hasEvalForMonth ?? false,
            'evalFormAction' => route('student.progress'),
        ])
    @endif

    <x-page-title kicker="Theo dõi lộ trình khóa học" icon="fa-chart-line" title="Tiến độ học tập" :lead="$pageSubtitle" />

    <div class="lp-shell">
        <section class="lp-hero" aria-label="Tổng quan tiến độ">
            <span class="lp-hero-glow lp-hero-glow--blue" aria-hidden="true"></span>
            <span class="lp-hero-glow lp-hero-glow--orange" aria-hidden="true"></span>

            <div class="lp-hero-head">
                <a class="op-btn" href="{{ $courseUrl }}">
                    <i class="fa-solid fa-chalkboard"></i>
                    <span>Vào khóa học</span>
                </a>
            </div>

            <div class="lp-hero-metrics">
                <article class="lp-metric">
                    <div class="lp-metric-top">
                        <span class="lp-metric-icon lp-metric-icon--blue"><i class="fa-solid fa-chart-pie"></i></span>
                        <div class="lp-metric-ring-wrap">
                            <div class="lp-metric-ring" style="--progress: {{ $overallPct }}; --ring-fill: #2563eb;" aria-hidden="true"></div>
                            <strong>{{ $overallPct }}%</strong>
                        </div>
                    </div>
                    <div class="lp-metric-value">{{ $overallPct }}<small>%</small></div>
                    <div class="lp-metric-label">Lộ trình khóa học</div>
                    <div class="lp-metric-sub">Buổi học &amp; kiểm tra</div>
                </article>

                <article class="lp-metric">
                    <div class="lp-metric-top">
                        <span class="lp-metric-icon lp-metric-icon--orange"><i class="fa-solid fa-fire"></i></span>
                    </div>
                    <div class="lp-metric-value">{{ $currentStreak }}<small> ngày</small></div>
                    <div class="lp-metric-label">Streak hiện tại</div>
                    <div class="lp-metric-sub">Kỷ lục {{ $bestStreak }} ngày</div>
                </article>

                <article class="lp-metric">
                    <div class="lp-metric-top">
                        <span class="lp-metric-icon lp-metric-icon--teal"><i class="fa-solid fa-calendar-week"></i></span>
                        <div class="lp-metric-ring-wrap">
                            <div class="lp-metric-ring" style="--progress: {{ $weeklyPct }}; --ring-fill: #0d9488;" aria-hidden="true"></div>
                            <strong>{{ $weeklyPct }}%</strong>
                        </div>
                    </div>
                    <div class="lp-metric-value">{{ $activeDays }}<small>/{{ $goalDays }}</small></div>
                    <div class="lp-metric-label">{{ $isCourseSchedule ? 'Buổi học tuần' : 'Mục tiêu tuần' }}</div>
                    <div class="lp-metric-sub">{{ $weeklyPct }}% · {{ $isCourseSchedule ? 'theo lịch khóa' : 'ngày có hoạt động' }}</div>
                </article>

                <article class="lp-metric">
                    <div class="lp-metric-top">
                        <span class="lp-metric-icon lp-metric-icon--purple"><i class="fa-solid fa-bolt"></i></span>
                    </div>
                    <div class="lp-metric-value">{{ $weekTotal }}</div>
                    <div class="lp-metric-label">Hoạt động tuần này</div>
                    <div class="lp-metric-sub">Tất cả kênh học tập</div>
                </article>
            </div>
        </section>

        <div class="lp-grid">
            <article class="lp-panel" aria-label="Tiến độ tuần">
                <div class="lp-panel-head">
                    <h3><i class="fa-solid fa-calendar-check"></i> Lịch học tuần này</h3>
                    <span class="lp-panel-pill">{{ $activeDays }}/{{ $goalDays }} {{ $weeklyUnitLabel }}</span>
                </div>
                @if ($isCourseSchedule && $courseScheduleTitle !== '')
                    <p class="lp-week-course-note">{{ $courseScheduleTitle }}</p>
                @endif
                <div class="lp-weekly-bar" role="progressbar" aria-valuenow="{{ $weeklyPct }}" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: {{ $weeklyPct }}%"></span>
                </div>
                <div class="lp-week-days">
                    @foreach ($weekDays as $day)
                        @php
                            $classes = ['lp-week-day'];
                            if (! empty($day['is_scheduled'])) {
                                $classes[] = 'is-scheduled';
                            }
                            if (! empty($day['is_active'])) {
                                $classes[] = 'is-active';
                            }
                            if (! empty($day['is_today'])) {
                                $classes[] = 'is-today';
                            }
                            $dateValue = (string) ($day['date'] ?? '');
                            $dateTs = $dateValue !== '' ? strtotime($dateValue) : false;
                            $dateNum = $dateTs ? date('d', $dateTs) : '';
                            $dateMonth = $dateTs ? date('m', $dateTs) : '';
                            $timeLabel = trim((string) ($day['time_label'] ?? ''));
                        @endphp
                        <div class="{{ implode(' ', $classes) }}">
                            <span class="lp-week-day-dot" aria-hidden="true">
                                @if (! empty($day['is_active']))
                                    <i class="fa-solid fa-check"></i>
                                @elseif (! empty($day['is_scheduled']))
                                    <i class="fa-solid fa-chalkboard"></i>
                                @else
                                    {{ $dateNum }}
                                @endif
                            </span>
                            <span class="lp-week-day-label">{{ $day['label'] ?? '' }}</span>
                            @if ($dateNum !== '')
                                <span class="lp-week-day-date">{{ $dateNum }}/{{ $dateMonth }}</span>
                            @endif
                            @if ($timeLabel !== '')
                                <span class="lp-week-day-time">{{ $timeLabel }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="lp-panel" aria-label="Lộ trình khóa học">
                <div class="lp-panel-head">
                    <h3><i class="fa-solid fa-chart-line"></i> Lộ trình khóa học</h3>
                    <span class="lp-panel-pill">{{ $overallPct }}% tổng</span>
                </div>
                <div class="lp-track-row">
                    <div class="lp-track-top">
                        <span><i class="fa-solid fa-chalkboard"></i> Buổi học</span>
                        <strong>{{ (int) ($overall['sessions_done'] ?? 0) }}/{{ (int) ($overall['sessions_total'] ?? 0) }} · {{ (int) ($overall['sessions_percent'] ?? 0) }}%</strong>
                    </div>
                    <div class="lp-track-bar lp-track-bar--session">
                        <span style="width: {{ (int) ($overall['sessions_percent'] ?? 0) }}%"></span>
                    </div>
                </div>
                <div class="lp-track-row">
                    <div class="lp-track-top">
                        <span><i class="fa-solid fa-list-check"></i> Bài kiểm tra</span>
                        <strong>{{ (int) ($overall['assessments_done'] ?? 0) }}/{{ (int) ($overall['assessments_total'] ?? 0) }} · {{ (int) ($overall['assessments_percent'] ?? 0) }}%</strong>
                    </div>
                    <div class="lp-track-bar lp-track-bar--assessment">
                        <span style="width: {{ (int) ($overall['assessments_percent'] ?? 0) }}%"></span>
                    </div>
                </div>
                @if ($showLibraryTracks)
                    <div class="lp-track-row">
                        <div class="lp-track-top">
                            <span><i class="fa-solid fa-book"></i> Bài học thư viện</span>
                            <strong>{{ (int) ($overall['lessons_done'] ?? 0) }}/{{ (int) ($overall['lessons_total'] ?? 0) }} · {{ (int) ($overall['lessons_percent'] ?? 0) }}%</strong>
                        </div>
                        <div class="lp-track-bar lp-track-bar--lesson">
                            <span style="width: {{ (int) ($overall['lessons_percent'] ?? 0) }}%"></span>
                        </div>
                    </div>
                    <div class="lp-track-row">
                        <div class="lp-track-top">
                            <span><i class="fa-solid fa-circle-play"></i> Video bài giảng</span>
                            <strong>{{ (int) ($overall['videos_done'] ?? 0) }}/{{ (int) ($overall['videos_total'] ?? 0) }} · {{ (int) ($overall['videos_percent'] ?? 0) }}%</strong>
                        </div>
                        <div class="lp-track-bar lp-track-bar--video">
                            <span style="width: {{ (int) ($overall['videos_percent'] ?? 0) }}%"></span>
                        </div>
                    </div>
                @endif
            </article>

            <article class="lp-panel" aria-label="Hoạt động tuần này">
                <div class="lp-panel-head">
                    <h3><i class="fa-solid fa-bolt"></i> Hoạt động tuần này</h3>
                    <span class="lp-panel-pill">{{ $weekTotal }} lượt</span>
                </div>
                <div class="lp-activity-tiles">
                    @foreach ($activityTiles as $tile)
                        @php $tileKey = (string) ($tile['key'] ?? ''); @endphp
                        <div class="lp-activity-tile">
                            <span class="lp-activity-tile-icon lp-activity-tile-icon--{{ $tileKey }}">
                                <i class="fa-solid {{ $tile['icon'] }}"></i>
                            </span>
                            <div class="lp-activity-tile-body">
                                <strong>{{ (int) ($counts[$tileKey] ?? 0) }}</strong>
                                <span>{{ $tile['label'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="lp-panel" aria-label="Cách tính streak">
                <div class="lp-panel-head">
                    <h3><i class="fa-solid fa-fire"></i> Cách duy trì streak</h3>
                    <span class="lp-panel-pill">Mỗi ngày 1 hoạt động</span>
                </div>
                <div class="lp-streak-guide">
                    @foreach ($streakGuides as $index => $guide)
                        <div class="lp-streak-guide-item">
                            <span class="lp-streak-guide-num">{{ $index + 1 }}</span>
                            <div>
                                <strong>{{ $guide['title'] }}</strong>
                                <p>{{ $guide['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>

        <section class="lp-recent" aria-label="Hoạt động gần đây">
            <div class="lp-recent-head">
                <h3>Hoạt động gần đây</h3>
                <span>{{ count($recent) }} mục ghi nhận</span>
            </div>

            @if ($recent === [])
                <div class="lp-empty">
                    <i class="fa-solid fa-inbox"></i>
                    <p>Chưa có hoạt động khóa học. Tham gia buổi học hoặc nộp bài kiểm tra trong khóa của bạn.</p>
                    <a class="op-btn-ghost" href="{{ $courseUrl }}">
                        <i class="fa-solid fa-chalkboard"></i>
                        <span>Vào khóa học</span>
                    </a>
                </div>
            @else
                <ul class="lp-timeline">
                    @foreach ($recent as $row)
                        @php $type = (string) ($row['type'] ?? 'session'); @endphp
                        <li class="lp-timeline-item">
                            <span class="lp-timeline-icon lp-timeline-icon--{{ $type }}">
                                <i class="fa-solid {{ $row['icon'] ?? 'fa-circle-check' }}"></i>
                            </span>
                            <div class="lp-timeline-body">
                                <strong>{{ $row['title'] ?? '' }}</strong>
                                <div class="lp-timeline-meta">
                                    <span class="lp-timeline-type">{{ $row['type_label'] ?? '' }}</span>
                                    @if (! empty($row['at']))
                                        <span>{{ date('d/m/Y H:i', strtotime((string) $row['at'])) }}</span>
                                    @endif
                                    @if (($row['score'] ?? null) !== null)
                                        <span class="lp-timeline-score">{{ (int) $row['score'] }}%</span>
                                    @endif
                                </div>
                            </div>
                            @if (! empty($row['url']))
                                <div class="lp-timeline-action">
                                    <a class="op-btn-ghost" href="{{ $row['url'] }}">Xem</a>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
@endsection
