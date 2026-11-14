@extends('layouts.app', ['active' => 'grading'])

@php
    $course = $course ?? null;
    $courseId = (int) ($courseId ?? 0);
    $activeTab = (string) ($activeTab ?? \App\Services\TeacherGradingService::TAB_PRE);
    $selectedKey = (string) ($selectedKey ?? '');
    $pendingTotal = (int) ($pendingTotal ?? 0);
    $gradingService = app(\App\Services\TeacherGradingService::class);
    $pageSubtitle = trim((string) ($course?->title ?? 'Khóa học')).' · '.$pendingTotal.' bài chờ chấm';
@endphp

@section('title', 'Chấm điểm')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-grading.css') }}?v=11">
@endpush

@section('content')
<div class="op-stack te-page tg-page">
    <x-page-title kicker="Giáo viên" icon="fa-pen-to-square" title="Chấm điểm" :lead="$pageSubtitle">
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('dashboard') }}"><i class="fa-solid fa-house"></i> Tổng quan</a>
        </x-slot:aside>
    </x-page-title>

    <div class="teacher-grading-course-toolbar" aria-label="Chọn khóa học">
        @include('teacher.partials.grading-courses', [
            'courseId' => $courseId,
            'activeTab' => $activeTab,
            'selectedKey' => $selectedKey,
            'courseSummaries' => $courseSummaries ?? [],
            'gradingService' => $gradingService,
        ])
    </div>

    <div class="teacher-grading-workspace">
        <aside class="teacher-grading-workspace-queue" aria-label="Danh sách bài chờ chấm">
            @include('teacher.partials.grading-queue', [
                'courseId' => $courseId,
                'activeTab' => $activeTab,
                'selectedKey' => $selectedKey,
                'tabCounts' => $tabCounts ?? [],
                'tabItems' => $tabItems ?? [],
                'gradingService' => $gradingService,
            ])
        </aside>

        <section class="teacher-grading-workspace-main" aria-label="Chấm bài">
            @include('teacher.partials.grading-panel', [
                'courseId' => $courseId,
                'activeTab' => $activeTab,
                'selectedKey' => $selectedKey,
                'selectedItem' => $selectedItem ?? null,
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
    <script src="{{ asset('assets/js/teacher-grading.js') }}?v=3"></script>
@endpush
