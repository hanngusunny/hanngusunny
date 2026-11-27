@extends('layouts.app', ['active' => 'evaluations'])

@php
    $activeTab = ($activeTab ?? 'nhan-xet') === 'ket-qua' ? 'ket-qua' : 'nhan-xet';
    $course = $course ?? null;
    $resultDetail = $resultDetail ?? null;
    $courseUrl = isset($navService) ? $navService->dashboardUrlForStudent((int) auth()->id()) : route('dashboard');
    $isDetailView = $activeTab === 'ket-qua' && is_array($resultDetail);
@endphp

@section('title', $isDetailView ? ($resultDetail['title'].' · Kết quả') : ($activeTab === 'ket-qua' ? 'Kết quả · Nhận xét & Kết quả' : 'Nhận xét & Kết quả'))

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/student-evaluation-hub.css') }}?v=4">
@endpush

@section('content')
<div class="sn-eval-page">
    <section class="sn-eval-surface">
        @if ($course === null)
            <div class="sn-eval-empty-state">
                <div class="sn-eval-empty-ico" aria-hidden="true">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                        <circle cx="40" cy="40" r="40" fill="#E8EFF9"/>
                        <path d="M27 25C27 22.8 28.8 21 31 21H45L53 29V55C53 57.2 51.2 59 49 59H31C28.8 59 27 57.2 27 55V25Z" fill="#FFFFFF"/>
                        <circle cx="40" cy="40" r="10" fill="#0D99FF"/>
                        <path d="M36.5 36.5L43.5 43.5M43.5 36.5L36.5 43.5" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </div>
                <h1 class="sn-eval-empty-title">Bạn chưa tham gia khóa học nào</h1>
                <p class="sn-eval-empty-desc">Khi được ghi danh vào khóa học, nhận xét theo tuần và kết quả bài làm sẽ hiển thị tại đây.</p>
                <a class="sn-eval-btn-primary" href="{{ $courseUrl }}">Về khóa học</a>
            </div>
        @elseif ($isDetailView)
            @include('student.partials.evaluation-result-detail', [
                'detail' => $resultDetail,
                'activeFilter' => $activeFilter ?? 'all',
            ])
        @else
            <div class="sn-eval-tabs-bar">
                <nav class="sn-eval-tabs" role="tablist" aria-label="Chuyển mục Nhận xét và Kết quả">
                    <a
                        class="sn-eval-tab{{ $activeTab === 'nhan-xet' ? ' is-active' : '' }}"
                        href="{{ route('student.evaluations', ['tab' => 'nhan-xet']) }}"
                        role="tab"
                        aria-selected="{{ $activeTab === 'nhan-xet' ? 'true' : 'false' }}"
                    >Nhận xét</a>
                    <a
                        class="sn-eval-tab{{ $activeTab === 'ket-qua' ? ' is-active' : '' }}"
                        href="{{ route('student.evaluations', ['tab' => 'ket-qua']) }}"
                        role="tab"
                        aria-selected="{{ $activeTab === 'ket-qua' ? 'true' : 'false' }}"
                    >Kết quả</a>
                </nav>
            </div>

            @if ($activeTab === 'ket-qua')
                @include('student.partials.evaluation-results-list', [
                    'resultsData' => $resultsData ?? ['items' => [], 'counts' => ['all' => 0, 'pre' => 0, 'post' => 0, 'kr' => 0, 'exam' => 0]],
                    'activeFilter' => $activeFilter ?? 'all',
                ])
            @else
                @include('student.partials.evaluation-weekly-figma', [
                    'course' => $course,
                    'evaluation' => $evaluation ?? null,
                    'scoreLabels' => $scoreLabels ?? [],
                    'evalWeek' => $evalWeek ?? '',
                    'weekOptions' => $weekOptions ?? [],
                    'filledWeeks' => $filledWeeks ?? [],
                    'hasEvalForWeek' => $hasEvalForWeek ?? false,
                    'attendanceReport' => $attendanceReport ?? ['items' => [], 'attended' => 0, 'total' => 0],
                    'currentWeek' => $currentWeek ?? '',
                ])
            @endif
        @endif
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/student-evaluation-hub.js') }}?v=1"></script>
@endpush
