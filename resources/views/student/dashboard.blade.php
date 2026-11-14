@extends('layouts.app', ['active' => ($workspaceTab ?? '') === \App\Services\StudentCourseNavService::TAB_ASSESSMENTS ? 'assessments' : 'course'])

@php
    use App\Services\StudentCourseNavService;
    $levelTitle = trim((string) ($activeLevel->title ?? 'Trình độ học'));
    $courseTitle = trim((string) ($course->title ?? ''));
    $pageTitle = 'Bài kiểm tra';
    if (($workspaceTab ?? '') !== StudentCourseNavService::TAB_ASSESSMENTS) {
        $pageTitle = 'Buổi học';
        $sessionRow = is_array($sessionDetail['session'] ?? null) ? $sessionDetail['session'] : null;
        if ($sessionRow !== null) {
            $pageTitle = trim((string) ($sessionRow['title'] ?? '')) ?: 'Buổi học';
        } elseif (($selectedPublicCode ?? '') !== '') {
            foreach ($sessions as $row) {
                if ((string) ($row['public_code'] ?? '') === (string) $selectedPublicCode) {
                    $pageTitle = trim((string) ($row['title'] ?? '')) ?: 'Buổi học';
                    break;
                }
            }
        }
    }
    $pageSubtitle = implode(' · ', array_values(array_filter([$levelTitle, $courseTitle])));
@endphp

@section('title', $levelTitle.' · Khóa học')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/student-quiz-office.css') }}?v=2">
@endpush

@section('content')
<div class="op-stack sw-page">
    <x-page-title :kicker="$pageSubtitle" icon="fa-chalkboard" :title="$pageTitle">
        @if (($workspaceTab ?? '') !== StudentCourseNavService::TAB_ASSESSMENTS)
            <x-slot:aside>
                @include('student.partials.session-dropdown')
            </x-slot:aside>
        @endif
    </x-page-title>

    <div class="sw-workspace">
        <aside class="sw-nav" aria-label="Menu buổi học">
            @include('student.partials.course-menu')
        </aside>
        <section class="sw-main" aria-label="Nội dung buổi học">
            @include('student.partials.course-panel')
        </section>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/student-workspace.js') }}"></script>
    @include('student.partials.quiz-scripts')
@endpush
