@extends('layouts.pwa', ['body_class' => 'is-home is-eval'])

@section('title', 'Nhận xét của giáo viên')

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
    $attended = (int) ($attendanceReport['attended'] ?? 0);
    $sessionTotal = (int) ($attendanceReport['total'] ?? 0);
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-progress.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/student-evaluation.css') }}">
@endpush

@section('content')
<main class="pwa-home pwa-page pwa-eval lp-page se-page">
    @include('pwa.partials.header')

    @if (session('error'))
        <div class="pwa-alert is-error">{{ session('error') }}</div>
    @endif

    <div class="pwa-course-intro">
        <p class="pwa-kicker">Hồ sơ</p>
        <h1>Nhận xét của giáo viên</h1>
        @if ($course === null)
            <p class="pwa-help">Tham gia khóa học để xem nhận xét và điểm đánh giá theo tuần.</p>
        @else
            <p class="pwa-help">{{ $course->title }}{{ $weekLabel !== '' ? ' · '.$weekLabel : '' }}</p>
        @endif
    </div>

    @if ($course === null)
        <section class="pwa-card">
            <p class="pwa-help" style="margin:0">Bạn chưa được ghi danh khóa học nào. Liên hệ giáo viên để tham gia lớp.</p>
        </section>
    @else
        <section class="pwa-card pwa-eval-week">
            <form id="student-eval-week-form" method="get" action="{{ route('pwa.evaluations') }}">
                <label class="pwa-field" for="studentEvalWeekSelect">
                    <span>Tuần đánh giá{{ $evalWeek === $currentWeek ? ' · tuần hiện tại' : '' }}</span>
                    <span class="pwa-field-box">
                        <i class="fa-solid fa-calendar-week" aria-hidden="true"></i>
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
                    </span>
                </label>
            </form>
        </section>

        @include('student.partials.weekly-eval', [
            'evaluation' => $evaluation,
            'course' => $course,
            'scoreLabels' => $scoreLabels,
            'weekLabel' => $weekLabel,
            'evalWeek' => $evalWeek,
            'hasEvalForWeek' => $hasEvalForWeek,
        ])

        @if ($sessionTotal > 0)
            <section class="pwa-eval-attend" aria-label="Điểm danh tuần">
                <div class="pwa-eval-attend-head">
                    <h2>Điểm danh tuần</h2>
                    <p>{{ $attended }}/{{ $sessionTotal }} buổi có mặt</p>
                </div>
                @include('student.partials.week-attendance', [
                    'attendanceReport' => $attendanceReport,
                    'weekLabel' => $weekLabel,
                ])
            </section>
        @endif
    @endif
</main>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/student-evaluation.js') }}"></script>
@endpush
