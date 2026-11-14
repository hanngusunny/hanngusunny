@extends('layouts.app', ['active' => 'assessments'])

@php
    $phase = (string) ($assessment['phase'] ?? 'upcoming');
    $phaseLabel = $phaseLabels[$phase] ?? $phase;
    $hasSchedule = ! empty($display['hasSchedule']);
    $isOpen = $phase === 'open';
    $isCompleted = $phase === 'completed';
    $isClosed = $phase === 'closed';
    $pendingReview = ! empty($pendingReview);
    $assessmentId = (int) ($assessment['id'] ?? 0);
    $canTakeQuiz = ! empty($assessment['is_takeable']) && (empty($hasSubmitted) || ! empty($allowRetake));
    $quizHasTimeLimit = ! empty($assessment['has_time_limit']);
    $quizDurationMinutes = (int) ($assessment['duration_minutes'] ?? 0);
    $quizAttemptStarted = ! empty($assessment['quiz_started']);
    $quizIsExpired = ! empty($assessment['is_expired']);
    $showQuizStart = $canTakeQuiz && ! $quizAttemptStarted;
    $canShowQuizForm = $canTakeQuiz && $quizAttemptStarted && (! $quizHasTimeLimit || ! $quizIsExpired);
    $enableQuizTimer = $canShowQuizForm && $quizHasTimeLimit;
    $totalQuestions = count($questions ?? []);
    $choiceQCount = 0;
    $essayQCount = 0;
    $submissionService = app(\App\Services\AssessmentSubmissionService::class);

    foreach ($questions ?? [] as $qItem) {
        $qType = (string) ($qItem['question_type'] ?? 'choice');
        if ($submissionService->needsManualGrading($qType)) {
            $essayQCount++;
        } else {
            $choiceQCount++;
        }
    }

    $questionSummary = [];
    if ($choiceQCount > 0) {
        $questionSummary[] = $choiceQCount.' trắc nghiệm';
    }
    if ($essayQCount > 0) {
        $questionSummary[] = $essayQCount.' tự luận';
    }

    $scorePct = ($submitted && $result !== null) ? (int) $result['score'] : 0;
    $pointsEarned = ($submitted && $result !== null) ? (float) ($result['points_earned'] ?? 0) : 0;
    $pointsPossible = ($submitted && $result !== null) ? (float) ($result['points_possible'] ?? 0) : 0;
    $essayCount = ($submitted && $result !== null) ? (int) ($result['essay_count'] ?? 0) : 0;
    $title = trim((string) ($assessment['title'] ?? 'Bài kiểm tra'));
    $description = trim((string) ($assessment['description'] ?? ''));
    $isRetake = ! empty($allowRetake) && ! empty($hasSubmitted);
@endphp

@section('title', $title.' · Bài kiểm tra')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/student-quiz-office.css') }}?v=4">
@endpush

@section('content')
<div class="op-stack sw-page sw-assessment-page">
    <nav class="sw-back" aria-label="Điều hướng">
        <a href="{{ $hubUrl }}"><i class="fa-solid fa-arrow-left"></i> Bài kiểm tra</a>
        <span aria-hidden="true">/</span>
        <span>{{ $title }}</span>
    </nav>

    @if ($showQuizStart)
        <section class="op-panel sw-assess-start" id="assessment-quiz">
            <div class="op-panel-body" style="padding:24px">
                <div class="sw-assess-start-head">
                    <span class="sw-phase sw-phase--{{ $phase }}">{{ $phaseLabel }}</span>
                    @if ($hasSchedule && ! empty($display['dateLabel']))
                        <span class="text-sm text-slate-500">{{ $display['weekdayShort'] ?? '' }}{{ ($display['weekdayShort'] ?? '') !== '' ? ', ' : '' }}{{ $display['dateLabel'] }}</span>
                    @endif
                </div>
                <h1 class="sw-assess-start-title">{{ $title }}</h1>
                @if (trim((string) ($course->title ?? '')) !== '')
                    <p class="sw-assess-start-course"><i class="fa-solid fa-book"></i> {{ $course->title }}</p>
                @endif

                <div class="sw-assess-chips">
                    <span><i class="fa-solid fa-list"></i> {{ $totalQuestions }} câu{{ $questionSummary !== [] ? ' · '.implode(' · ', $questionSummary) : '' }}</span>
                    @if ($quizHasTimeLimit)
                        <span><i class="fa-solid fa-hourglass-half"></i> {{ $quizDurationMinutes }} phút</span>
                    @endif
                    @if ($hasSchedule && ! empty($display['timeLabel']))
                        <span><i class="fa-regular fa-clock"></i> {{ $display['timeLabel'] }}</span>
                    @endif
                </div>

                @if ($isRetake)
                    <p class="sw-assess-rule is-retake"><i class="fa-solid fa-rotate"></i> Giáo viên cho phép làm lại. Câu đã điền ở bản nháp hoặc bài nộp sẽ được điền sẵn. Kết quả mới sẽ thay thế bài cũ.</p>
                @else
                    <p class="sw-assess-rule"><i class="fa-solid fa-shield-halved"></i> Chỉ làm <strong>một lần</strong> — bấm bắt đầu trong khung giờ quy định.</p>
                @endif

                @if ($description !== '')
                    <details class="sw-assess-guide">
                        <summary><i class="fa-solid fa-circle-info"></i> Hướng dẫn</summary>
                        <div class="course-rich-content">{!! \App\Support\HtmlContent::render($description) !!}</div>
                    </details>
                @endif

                <form method="post" action="{{ $startAction }}" class="sw-assess-start-form">
                    @csrf
                    <button type="submit" class="op-btn">
                        <i class="fa-solid fa-play"></i>
                        {{ $isRetake ? 'Bắt đầu làm lại' : 'Bắt đầu làm bài' }}
                    </button>
                    @if ($quizHasTimeLimit)
                        <p class="text-sm text-slate-500">Đồng hồ chạy ngay khi bấm bắt đầu · Hết giờ tự nộp bài</p>
                    @endif
                </form>
            </div>
        </section>
    @else
        <x-page-title kicker="Bài kiểm tra" icon="fa-file-pen" :title="$title" :lead="($description !== '' && ! $canShowQuizForm) ? \App\Support\HtmlContent::plain($description) : ''">
            <x-slot:meta>
                <span class="sw-phase sw-phase--{{ $phase }}">
                    @if ($pendingReview)
                        Chờ chấm
                    @elseif ($isCompleted)
                        Đã có kết quả
                    @elseif ($isClosed)
                        Đã kết thúc
                    @else
                        {{ $phaseLabel }}
                    @endif
                </span>
                @if ($hasSchedule && ! empty($display['dateLabel']))
                    <span><i class="fa-regular fa-calendar"></i> {{ $display['weekdayShort'] ?? '' }}{{ ($display['weekdayShort'] ?? '') !== '' ? ', ' : '' }}{{ $display['dateLabel'] }}</span>
                @endif
                @if (trim((string) ($course->title ?? '')) !== '')
                    <span><i class="fa-solid fa-book"></i> {{ $course->title }}</span>
                @endif
                @if ($hasSchedule && ! empty($display['timeLabel']))
                    <span><i class="fa-regular fa-clock"></i> {{ $display['timeLabel'] }}</span>
                @endif
                <span><i class="fa-solid fa-list"></i> {{ $totalQuestions }} câu{{ $questionSummary !== [] ? ' · '.implode(' · ', $questionSummary) : '' }}</span>
                @if ($quizHasTimeLimit)
                    <span><i class="fa-solid fa-hourglass-half"></i> {{ $quizDurationMinutes }} phút</span>
                @endif
            </x-slot:meta>
            @if ($submitted && $result !== null && $pointsPossible > 0)
                <x-slot:aside>
                    <div class="sw-score sw-score--compact">
                        <strong>{{ $submissionService->formatPoints($pointsEarned) }}/{{ $submissionService->formatPoints($pointsPossible) }}</strong>
                        <span>{{ $scorePct }}%</span>
                    </div>
                </x-slot:aside>
            @endif
        </x-page-title>

        @if ($canTakeQuiz && ! $canShowQuizForm && $isRetake)
            <p class="sw-inline-note"><i class="fa-solid fa-rotate"></i> Giáo viên cho phép làm lại.</p>
        @elseif ($phase === 'upcoming')
            <p class="sw-inline-note"><i class="fa-solid fa-circle-info"></i> Bài kiểm tra sẽ mở đúng giờ theo lịch khóa học.</p>
        @elseif ($pendingReview && ! $canShowQuizForm)
            <p class="sw-inline-note"><i class="fa-solid fa-hourglass-half"></i> Đã nộp bài — chờ giáo viên chấm và công bố kết quả.</p>
        @endif

        <article class="op-panel sw-quiz sunny-app page-session-show page-assessment-show sw-quiz-sunny" id="assessment-quiz">
            <div class="op-panel-body" style="padding:20px">
                @if (($questions ?? []) === [])
                    <div class="sw-empty">
                        <span class="sw-empty-ico"><i class="fa-solid fa-clipboard"></i></span>
                        <p>Giáo viên chưa thêm câu hỏi cho bài kiểm tra này.</p>
                    </div>
                @elseif ($quizHasTimeLimit && $quizIsExpired && empty($hasSubmitted) && $quizAttemptStarted)
                    <div class="sw-inline-note is-warn">
                        <i class="fa-solid fa-hourglass-end"></i>
                        <div>
                            <strong>Đã hết thời gian làm bài</strong>
                            <p>Bạn đã hết {{ $quizDurationMinutes }} phút làm bài. Hệ thống sẽ tự nộp bài nếu chưa nộp.</p>
                        </div>
                    </div>
                @endif

                @if ($canShowQuizForm)
                    @if ($enableQuizTimer)
                        <div class="sw-timer" data-quiz-timer data-expires-ts="{{ (int) ($assessment['expires_ts'] ?? 0) }}" data-remaining="{{ (int) ($assessment['remaining_seconds'] ?? 0) }}">
                            Thời gian còn lại <strong data-timer-label>—</strong>
                            <span class="sw-timer-meta">{{ $quizDurationMinutes }} phút</span>
                        </div>
                    @endif
                    <form
                        method="post"
                        action="{{ $submitAction }}"
                        class="js-student-quiz session-quiz-form"
                        novalidate
                        data-timed="{{ $enableQuizTimer ? '1' : '0' }}"
                        data-quiz-autosave
                        data-autosave-url="{{ $autosaveUrl }}"
                        data-assessment-leave-guard
                        data-assessment-answer-autosave
                        data-assessment-id="{{ $assessmentId }}"
                        data-user-id="{{ (int) auth()->id() }}"
                        data-attempt-key="{{ $assessment['offline_attempt_key'] ?? '' }}"
                        data-image-upload-url="{{ route('student.media.image') }}"
                        data-recording-upload-url="{{ route('student.media.recording') }}"
                    >
                        @csrf
                        <input type="hidden" name="timed_out" value="0" class="js-timed-out">
                        <div class="sw-assess-toolbar">
                            <span><i class="fa-solid fa-pen-to-square"></i> Đang làm bài</span>
                            <span>{{ $totalQuestions }} câu</span>
                        </div>
                        @include('student.partials.quiz-form', [
                            'quizQuestions' => $questions,
                            'savedAnswers' => $draftAnswers ?? [],
                            'showPoints' => true,
                        ])
                        <div class="sw-quiz-actions">
                            <p class="sw-assess-submit-hint">
                                <i class="fa-solid fa-shield-halved"></i>
                                Sau khi nộp, giáo viên sẽ chấm bài rồi công bố kết quả. Bạn {{ ! empty($allowRetake) ? 'có thể được giáo viên cho làm lại' : 'không thể sửa bài' }}.
                            </p>
                            <button type="submit" class="op-btn">
                                {{ $isRetake ? 'Nộp bài làm lại' : 'Nộp bài kiểm tra' }}
                            </button>
                        </div>
                    </form>
                @elseif ($pendingReview)
                    <div class="sw-empty">
                        <span class="sw-empty-ico"><i class="fa-solid fa-hourglass-half"></i></span>
                        <h3>Đã nộp bài — chờ chấm</h3>
                        <p>Bài làm của bạn đã được gửi tới giáo viên. Kết quả sẽ hiển thị sau khi giáo viên chấm và công bố.</p>
                        @if (! empty($submissionMeta?->submitted_at))
                            <p class="text-sm text-slate-500">Nộp lúc <strong>{{ $submissionMeta->submitted_at->format('H:i d/m/Y') }}</strong></p>
                        @endif
                    </div>
                @elseif ($submitted && $result !== null)
                    @include('student.partials.quiz-result', [
                        'title' => 'Kết quả bài kiểm tra',
                        'questions' => $questions,
                        'answerMap' => $answerMap,
                        'result' => $result,
                        'submitted' => true,
                        'showPoints' => true,
                    ])
                @else
                    <p class="sw-inline-note">
                        <i class="fa-solid fa-circle-info"></i>
                        @if ($isClosed)
                            Bài kiểm tra đã kết thúc. Bạn không thể nộp bài.
                        @else
                            Bài kiểm tra chưa mở. Vui lòng quay lại đúng giờ theo lịch học.
                        @endif
                    </p>
                @endif
            </div>
        </article>
    @endif
</div>
@endsection

@push('scripts')
    @if ($canShowQuizForm)
        <script src="{{ asset('assets/js/assessment-offline-queue.js') }}?v=2"></script>
    @endif
    @include('student.partials.quiz-scripts')
    @if ($canShowQuizForm)
        <script src="{{ asset('assets/js/assessment-leave-guard.js') }}?v=1"></script>
        <script src="{{ asset('assets/js/network-offline-banner.js') }}?v=1"></script>
    @endif
@endpush
