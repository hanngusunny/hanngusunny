@extends('layouts.app', ['active' => 'evaluations'])

@php
    $course = $course ?? null;
    $evaluation = $evaluation ?? null;
    $scoreLabels = $scoreLabels ?? [];
    $evalWeek = (string) ($evalWeek ?? '');
    $weekLabel = (string) ($weekLabel ?? '');
    $weekOptions = $weekOptions ?? [];
    $filledWeeks = $filledWeeks ?? [];
    $hasEvalForWeek = (bool) ($hasEvalForWeek ?? false);
    $attendanceReport = $attendanceReport ?? ['items' => [], 'attended' => 0, 'total' => 0];
    $currentWeek = (string) ($currentWeek ?? '');
    $courseTitle = trim((string) ($course?->title ?? ''));
    $courseUrl = isset($navService) ? $navService->dashboardUrlForStudent((int) auth()->id()) : route('dashboard');
    $attended = (int) ($attendanceReport['attended'] ?? 0);
    $sessionTotal = (int) ($attendanceReport['total'] ?? 0);

    if ($course === null) {
        $pageSubtitle = 'Tham gia khóa học để xem nhận xét và điểm đánh giá theo tuần từ giáo viên.';
    } else {
        $pageSubtitle = 'Nhận xét của giáo viên theo tuần'.($courseTitle !== '' ? ' · '.$courseTitle : '').'.';
    }
@endphp

@section('title', 'Đánh giá')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/student-progress.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/student-evaluation.css') }}">
@endpush

@section('content')
<div class="op-stack lp-page se-page">
    <x-page-title kicker="Học tập" icon="fa-star-half-stroke" title="Đánh giá" :lead="$pageSubtitle" />

    <div class="lp-shell">
        @if ($course === null)
            <section class="lp-recent" aria-label="Chưa có khóa học">
                <div class="lp-empty">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <p>Bạn chưa được ghi danh khóa học nào. Liên hệ giáo viên hoặc quản trị để tham gia lớp.</p>
                    <a class="op-btn-ghost" href="{{ $courseUrl }}">
                        <i class="fa-solid fa-house"></i>
                        <span>Về khóa học</span>
                    </a>
                </div>
            </section>
        @else
            <article class="lp-panel student-eval-week-panel" aria-label="Chọn tuần đánh giá">
                <div class="lp-panel-head">
                    <h3><i class="fa-solid fa-calendar-week"></i> Chọn tuần</h3>
                    @if ($evalWeek === $currentWeek)
                        <span class="lp-panel-pill">Tuần hiện tại</span>
                    @endif
                </div>
                <form id="student-eval-week-form" class="student-eval-week-form" method="get" action="{{ route('student.evaluations') }}">
                    <div class="field">
                        <label for="studentEvalWeekSelect">Tuần đánh giá</label>
                        <select class="input" id="studentEvalWeekSelect" name="tuan">
                            @foreach ($weekOptions as $option)
                                @php
                                    $isFilled = in_array($option, $filledWeeks, true);
                                    $label = \App\Models\CourseMonthlyEvaluation::formatWeekLabel($option);
                                @endphp
                                <option value="{{ $option }}" @selected($option === $evalWeek)>
                                    {{ $label }}{{ $isFilled ? ' · Đã có đánh giá' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </article>

            @include('student.partials.weekly-eval', [
                'evaluation' => $evaluation,
                'course' => $course,
                'scoreLabels' => $scoreLabels,
                'weekLabel' => $weekLabel,
                'evalWeek' => $evalWeek,
                'hasEvalForWeek' => $hasEvalForWeek,
            ])

            @if ($sessionTotal > 0)
                <section aria-label="Điểm danh tuần">
                    <div class="lp-section-head">
                        <div>
                            <h3>Điểm danh tuần</h3>
                            <p>{{ $attended }}/{{ $sessionTotal }} buổi có mặt · {{ $weekLabel }}</p>
                        </div>
                    </div>
                    @include('student.partials.week-attendance', [
                        'attendanceReport' => $attendanceReport,
                        'weekLabel' => $weekLabel,
                    ])
                </section>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/student-evaluation.js') }}"></script>
@endpush
