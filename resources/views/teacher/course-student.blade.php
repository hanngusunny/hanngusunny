@extends('layouts.app', ['active' => 'schedule'])

@php
    $course = $course ?? null;
    $session = $session ?? [];
    $member = $member ?? null;
    $activeTab = $activeTab ?? 'tong-quan';
    $name = $member?->user?->name ?: 'Học viên';
    $sessionTitle = trim((string) ($session['title'] ?? 'Buổi học'));
    $pageSubtitle = implode(' · ', array_values(array_filter([
        $name,
        trim((string) ($course?->title ?? '')),
    ])));
@endphp

@section('title', $name.' · '.$course?->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/teacher-course.css') }}?v=5">
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/student-quiz-office.css') }}?v=2">
@endpush

@section('content')
<div class="op-stack sw-page tc-page">
    <nav class="sw-back" aria-label="Điều hướng">
        <a href="{{ $backUrl ?? route('teacher.course.show', ['id' => $course->id, 'buoi' => $sessionId]) }}"><i class="fa-solid fa-arrow-left"></i> {{ $course?->title }}</a>
        <span>/</span>
        <span>{{ $name }}</span>
        <span>/</span>
        <a href="{{ route('teacher.evaluations', ['khoa' => $course->id, 'hoc-vien' => $userId]) }}">Đánh giá tuần</a>
    </nav>

    <x-page-title :kicker="$pageSubtitle" icon="fa-chalkboard" :title="$sessionTitle">
        <x-slot:aside>
            @include('teacher.partials.student-session-dropdown')
        </x-slot:aside>
    </x-page-title>

    <div class="sw-workspace sw-workspace--teacher-student">
        <aside class="sw-nav" aria-label="Menu buổi học">
            @include('teacher.partials.student-session-menu')
        </aside>
        <section class="sw-main" aria-label="Nội dung buổi học">
            @if ($activeTab === 'tong-quan')
                @include('teacher.partials.student-overview')
            @elseif ($activeTab === 'truoc-buoi')
                @include('teacher.partials.student-work', [
                    'title' => 'Bài trước buổi',
                    'questions' => $preQuestions,
                    'answerMap' => $preAnswerMap,
                    'submitted' => $preSubmitted,
                    'result' => $preResult,
                    'tab' => 'truoc-buoi',
                ])
            @elseif ($activeTab === 'sau-buoi')
                @include('teacher.partials.student-work', [
                    'title' => 'Ôn tập sau buổi',
                    'questions' => $postQuestions,
                    'answerMap' => $postAnswerMap,
                    'submitted' => $postSubmitted,
                    'result' => $postResult,
                    'tab' => 'sau-buoi',
                ])
            @else
                @include('teacher.partials.student-work', [
                    'title' => 'Ôn tập kiến thức',
                    'questions' => $reviewQuestions,
                    'answerMap' => $reviewAnswerMap,
                    'submitted' => $reviewSubmitted,
                    'result' => $reviewResult,
                    'tab' => 'kiem-tra',
                ])
            @endif
        </section>
        @include('teacher.partials.student-roster')
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/student-workspace.js') }}"></script>
    <script>
        (function () {
            const active = document.getElementById('teacher-student-roster-active');
            const list = document.getElementById('teacher-student-roster-list');
            if (!active || !list) return;
            const top = active.offsetTop - list.offsetTop - (list.clientHeight / 2) + (active.clientHeight / 2);
            list.scrollTop = Math.max(0, top);
        })();
    </script>
@endpush
