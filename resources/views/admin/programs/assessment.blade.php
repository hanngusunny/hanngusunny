@extends('layouts.app', ['active' => 'programs'])

@php
    $questions = $assessment->questions;
    $questionCount = $questions->count();
    $totalPoints = (float) $questions->sum('points');
    $readyCount = $questions->filter(
        static fn ($question): bool => \App\Support\Questions\QuestionParser::plainTextFromHtml($question->question_text) !== ''
    )->count();
    $draftCount = max(0, $questionCount - $readyCount);
    $lead = \App\Support\HtmlContent::plain((string) $assessment->description);
    $formatPoints = static function (float $points): string {
        if (fmod($points, 1.0) === 0.0) {
            return (string) (int) $points;
        }

        return rtrim(rtrim(number_format($points, 2, ',', ''), '0'), ',');
    };
@endphp

@section('title', $assessment->title)

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/program-workspace.css') }}?v=6">
@endpush

@section('subnav')
    <a href="{{ route('admin.programs.index') }}" class="os-tab">Danh sách</a>
    <a href="{{ route('admin.programs.show', ['id' => $program->id, 'tab' => 'assessments']) }}" class="os-tab">Bài kiểm tra</a>
    <a href="{{ route('admin.programs.assessments.show', [$program->id, $assessment->id]) }}" class="os-tab is-on">Soạn đề</a>
@endsection

@section('content')
<div class="pw-page">
    <header class="pw-hero">
        <div class="pw-hero-top">
            <div>
                <p class="pw-kicker"><i class="fa-solid fa-file-pen"></i> {{ $program->title }} · Bài kiểm tra</p>
                <h1>{{ $assessment->title }}</h1>
                <p class="pw-lead">{{ $lead !== '' ? $lead : 'Soạn câu hỏi, kéo để đổi thứ tự và bấm vào câu để sửa nội dung.' }}</p>
                @if (\App\Support\HtmlContent::has((string) $assessment->description))
                    <details class="pw-assess-guide">
                        <summary><i class="fa-solid fa-circle-info"></i> Xem hướng dẫn đầy đủ</summary>
                        <div class="course-rich-content">{!! \App\Support\HtmlContent::render($assessment->description) !!}</div>
                    </details>
                @endif
                <div class="pw-meta">
                    <span><i class="fa-regular fa-clock"></i>{{ $assessment->durationLabel() }}</span>
                    <span><i class="fa-solid fa-circle-question"></i>{{ $questionCount }} câu hỏi</span>
                    <span><i class="fa-solid fa-star"></i>{{ $formatPoints($totalPoints) }} điểm</span>
                    @if ($program->isArchived())
                        <span><i class="fa-solid fa-box-archive"></i>Chỉ xem</span>
                    @endif
                </div>
            </div>
            @if ($program->isArchived())
                <span class="pw-pill is-off"><i class="fa-solid fa-box-archive"></i> Đã lưu trữ — chỉ xem</span>
            @else
                <span class="pw-pill is-on"><i class="fa-solid fa-pen-to-square"></i> Đang soạn đề</span>
            @endif
        </div>

        <div class="pw-stats">
            <div class="pw-stat">
                <span class="pw-stat-ico"><i class="fa-solid fa-list-ol"></i></span>
                <span>
                    <strong>{{ $questionCount }}</strong>
                    <small>Câu hỏi</small>
                </span>
            </div>
            <div class="pw-stat">
                <span class="pw-stat-ico"><i class="fa-solid fa-star"></i></span>
                <span>
                    <strong>{{ $formatPoints($totalPoints) }}</strong>
                    <small>Tổng điểm</small>
                </span>
            </div>
            <div class="pw-stat">
                <span class="pw-stat-ico"><i class="fa-regular fa-clock"></i></span>
                <span>
                    <strong>{{ (int) $assessment->duration_minutes > 0 ? (int) $assessment->duration_minutes : '—' }}</strong>
                    <small>{{ (int) $assessment->duration_minutes > 0 ? 'Phút làm bài' : 'Không giới hạn' }}</small>
                </span>
            </div>
            <div class="pw-stat">
                <span class="pw-stat-ico"><i class="fa-solid fa-pen"></i></span>
                <span>
                    <strong>{{ $draftCount }}</strong>
                    <small>Chưa soạn</small>
                </span>
            </div>
        </div>
    </header>

    @include('admin.programs.partials.assessment-question-list', [
        'questions' => $questions,
        'formatPoints' => $formatPoints,
    ])
</div>
@endsection
