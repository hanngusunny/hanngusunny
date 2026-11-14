@extends('layouts.app', ['active' => 'evaluations'])

@php
    $course = $course ?? null;
    $courseId = (int) ($courseId ?? 0);
    $userId = (int) ($userId ?? 0);
    $evalWeek = (string) ($evalWeek ?? '');
    $weekLabel = (string) ($weekLabel ?? '');
    $members = $members ?? collect();
    $member = $member ?? null;
    $evaluationService = app(\App\Services\TeacherEvaluationService::class);
    $pageSubtitle = trim((string) ($course?->title ?? 'Khóa học')).($weekLabel !== '' ? ' · '.$weekLabel : '');
@endphp

@section('title', 'Đánh giá học viên')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-evaluation.css') }}">
@endpush

@section('content')
<div class="op-stack te-page">
    <x-page-title kicker="Giáo viên" icon="fa-star-half-stroke" title="Đánh giá học viên" :lead="$pageSubtitle">
        <x-slot:aside>
            <a class="op-btn-ghost" href="{{ route('dashboard') }}"><i class="fa-solid fa-house"></i> Tổng quan</a>
        </x-slot:aside>
    </x-page-title>

    <div class="teacher-eval-workspace">
        <aside class="teacher-eval-workspace-students" aria-label="Danh sách học viên">
            @include('teacher.partials.eval-students', [
                'course' => $course,
                'courseId' => $courseId,
                'userId' => $userId,
                'evalWeek' => $evalWeek,
                'members' => $members,
                'evalMap' => $evalMap ?? [],
                'attendanceMap' => $attendanceMap ?? [],
                'weekSessionCount' => (int) ($weekSessionCount ?? 0),
                'evaluatedCount' => (int) ($evaluatedCount ?? 0),
                'evaluationService' => $evaluationService,
            ])
        </aside>

        <section class="teacher-eval-workspace-main" aria-label="Nội dung đánh giá">
            @include('teacher.partials.eval-panel', [
                'courseId' => $courseId,
                'userId' => $userId,
                'evalWeek' => $evalWeek,
                'weekOptions' => $weekOptions ?? [],
                'member' => $member,
                'evaluation' => $evaluation ?? null,
                'canEdit' => (bool) ($canEdit ?? false),
                'scoreLabels' => $scoreLabels ?? [],
                'evaluationService' => $evaluationService,
            ])
        </section>

        <aside class="teacher-eval-workspace-courses" aria-label="Danh sách khóa học">
            @include('teacher.partials.eval-courses', [
                'courseId' => $courseId,
                'evalWeek' => $evalWeek,
                'courseSummaries' => $courseSummaries ?? [],
                'evaluationService' => $evaluationService,
            ])
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/teacher-evaluation.js') }}"></script>
    @if (! empty($canEdit) && $member !== null)
        <script>
            document.body.dataset.tinymceBase = @json(asset('assets/tinymce'));
        </script>
        <script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
        <script src="{{ asset('assets/js/course-eval-form.js') }}"></script>
    @endif
@endpush
