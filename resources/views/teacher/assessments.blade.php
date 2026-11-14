@extends('layouts.app', ['active' => 'assessments'])

@php
    $course = $course ?? null;
    $courseId = (int) ($courseId ?? 0);
    $assessmentId = (int) ($assessmentId ?? 0);
    $studentId = (int) ($studentId ?? 0);
    $pendingTotal = (int) ($pendingTotal ?? 0);
    $gradingService = app(\App\Services\TeacherAssessmentGradingService::class);
    $pageSubtitle = trim((string) ($course?->title ?? 'Khóa học')).' · '.$pendingTotal.' bài chờ chấm';
    $liveStatusQuery = array_filter([
        'khoa' => $courseId > 0 ? $courseId : null,
        'bai' => $assessmentId > 0 ? $assessmentId : null,
        'hoc-vien' => $studentId > 0 ? $studentId : null,
    ]);
    $liveStatusUrl = route('teacher.assessments.live', $liveStatusQuery);
@endphp

@section('title', 'Bài kiểm tra')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-grading.css') }}?v=11">
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-assessments.css') }}?v=3">
@endpush

@section('content')
<div class="op-stack te-page ta-page tg-page">
    <x-page-title kicker="Giáo viên" icon="fa-file-pen" title="Bài kiểm tra" :lead="$pageSubtitle">
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('dashboard') }}"><i class="fa-solid fa-house"></i> Tổng quan</a>
        </x-slot:aside>
    </x-page-title>

    <div class="teacher-assessment-toolbar">
        <div class="teacher-assessment-toolbar-start">
            @include('teacher.partials.assessment-courses', [
                'courseId' => $courseId,
                'assessmentId' => $assessmentId,
                'studentId' => $studentId,
                'courseSummaries' => $courseSummaries ?? [],
                'gradingService' => $gradingService,
            ])
        </div>
        <div class="teacher-assessment-toolbar-end">
            @include('teacher.partials.assessment-dropdown', [
                'courseId' => $courseId,
                'assessmentId' => $assessmentId,
                'studentId' => $studentId,
                'assessmentOptions' => $assessmentOptions ?? [],
                'selectedAssessment' => $selectedAssessment ?? null,
                'phaseLabels' => $phaseLabels ?? [],
                'gradingService' => $gradingService,
            ])
        </div>
    </div>

    <div class="teacher-assessment-workspace">
        <aside class="teacher-assessment-workspace-queue" aria-label="Danh sách học viên">
            @include('teacher.partials.assessment-queue', [
                'courseId' => $courseId,
                'assessmentId' => $assessmentId,
                'studentId' => $studentId,
                'assessmentOptions' => $assessmentOptions ?? [],
                'selectedAssessment' => $selectedAssessment ?? null,
                'studentItems' => $studentItems ?? [],
                'gradingService' => $gradingService,
            ])
        </aside>

        <section class="teacher-assessment-workspace-main" aria-label="Chấm bài kiểm tra">
            @include('teacher.partials.assessment-panel', [
                'courseId' => $courseId,
                'assessmentId' => $assessmentId,
                'studentId' => $studentId,
                'selectedDetail' => $selectedDetail ?? null,
                'selectedAssessment' => $selectedAssessment ?? null,
            ])
        </section>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
    </script>
    <script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('assets/js/assessment-comment-editor.js') }}?v=2"></script>
    <script src="{{ asset('assets/js/session-phat-am.js') }}?v=5"></script>
    <script src="{{ asset('assets/js/teacher-assessments.js') }}?v=5"></script>
    <script src="{{ asset('assets/js/teacher-grading.js') }}?v=2"></script>
    <script>
        (function () {
            const active = document.querySelector('.teacher-assessment-student-item.is-active');
            const list = document.querySelector('.teacher-assessment-student-list');

            if (active && list) {
                const top = active.offsetTop - list.offsetTop - (list.clientHeight / 2) + (active.clientHeight / 2);
                list.scrollTop = Math.max(0, top);
            }

            const liveUrl = @json($liveStatusUrl);
            let revision = '';
            const poll = async () => {
                try {
                    const res = await fetch(liveUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok || !data.revision) return;
                    if (revision === '') {
                        revision = data.revision;
                        return;
                    }
                    if (data.revision !== revision) {
                        window.location.reload();
                    }
                } catch (e) {}
            };
            poll();
            window.setInterval(poll, 8000);
        })();
    </script>
@endpush
