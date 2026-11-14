@extends('layouts.pwa', ['body_class' => 'is-home is-session'])

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
    $title = trim((string) ($assessment['title'] ?? 'Bài kiểm tra'));
    $description = trim((string) ($assessment['description'] ?? ''));
    $isRetake = ! empty($allowRetake) && ! empty($hasSubmitted);
    $badge = match ($phase) {
        'open', 'retake' => 'live',
        'completed', 'closed' => 'ended',
        default => 'upcoming',
    };
@endphp

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/student-workspace.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pwa-session.css') }}?v=4">
@endpush

@section('content')
<main class="pwa-home pwa-page pwa-session-page">
    @include('pwa.partials.header')

    @if (session('error'))
        <div class="pwa-alert is-error">{{ session('error') }}</div>
    @endif
    @if (session('success') || session('info'))
        <div class="pwa-alert is-ok">{{ session('success') ?: session('info') }}</div>
    @endif

    <a class="pwa-back" href="{{ $hubUrl }}">
        <i class="fa-solid fa-chevron-left"></i>
        Kiểm tra
    </a>

    <section class="pwa-card pwa-session-hero">
        <span class="pwa-phase pwa-phase--{{ $badge }}">
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
        <h1>{{ $title }}</h1>
        @if (trim((string) ($course->title ?? '')) !== '')
            <p class="pwa-help">{{ $course->title }}</p>
        @endif
        <div class="pwa-assess-chips">
            <span>{{ $totalQuestions }} câu{{ $questionSummary !== [] ? ' · '.implode(' · ', $questionSummary) : '' }}</span>
            @if ($quizHasTimeLimit)
                <span>{{ $quizDurationMinutes }} phút</span>
            @endif
            @if ($hasSchedule && ! empty($display['dateLabel']))
                <span>{{ $display['weekdayShort'] ?? '' }}{{ ($display['weekdayShort'] ?? '') !== '' ? ', ' : '' }}{{ $display['dateLabel'] }}</span>
            @endif
            @if ($hasSchedule && ! empty($display['timeLabel']))
                <span>{{ $display['timeLabel'] }}</span>
            @endif
        </div>
        @if ($submitted && $result !== null && $pointsPossible > 0)
            <p class="pwa-count">{{ $submissionService->formatPoints($pointsEarned) }}/{{ $submissionService->formatPoints($pointsPossible) }} · {{ $scorePct }}%</p>
        @endif
    </section>

    <div class="pwa-session-work pwa-assess-work" id="assessment-quiz">
        @if ($showQuizStart)
            <section class="pwa-card">
                @if ($isRetake)
                    <p class="pwa-help">Giáo viên cho phép làm lại. Câu đã điền sẽ được điền sẵn. Kết quả mới sẽ thay thế bài cũ.</p>
                @else
                    <p class="pwa-help">Chỉ làm <strong>một lần</strong> — bấm bắt đầu trong khung giờ quy định.</p>
                @endif
                @if ($description !== '')
                    <details class="pwa-assess-guide">
                        <summary>Hướng dẫn</summary>
                        <div class="course-rich-content">{!! \App\Support\HtmlContent::render($description) !!}</div>
                    </details>
                @endif
                <form method="post" action="{{ $startAction }}" data-pwa-wait="Đang bắt đầu…">
                    @csrf
                    @include('pwa.partials.return-field')
                    <button type="submit" class="pwa-btn">
                        <i class="fa-solid fa-play"></i>
                        {{ $isRetake ? 'Bắt đầu làm lại' : 'Bắt đầu làm bài' }}
                    </button>
                    @if ($quizHasTimeLimit)
                        <p class="pwa-hint">Đồng hồ chạy ngay khi bấm bắt đầu · Hết giờ tự nộp bài</p>
                    @endif
                </form>
            </section>
        @else
            @if ($canTakeQuiz && ! $canShowQuizForm && $isRetake)
                <p class="pwa-alert">Giáo viên cho phép làm lại.</p>
            @elseif ($phase === 'upcoming')
                <p class="pwa-alert">Bài kiểm tra sẽ mở đúng giờ theo lịch khóa học.</p>
            @endif

            @if (($questions ?? []) === [])
                <section class="pwa-card">
                    <h2>Chưa có câu hỏi</h2>
                    <p class="pwa-help">Giáo viên chưa thêm câu hỏi cho bài kiểm tra này.</p>
                </section>
            @elseif ($quizHasTimeLimit && $quizIsExpired && empty($hasSubmitted) && $quizAttemptStarted)
                <p class="pwa-alert is-error">Đã hết {{ $quizDurationMinutes }} phút làm bài. Hệ thống sẽ tự nộp nếu chưa nộp.</p>
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
                    class="js-student-quiz session-quiz-form pwa-card"
                    novalidate
                    data-timed="{{ $enableQuizTimer ? '1' : '0' }}"
                    data-quiz-autosave
                    data-autosave-url="{{ $autosaveUrl }}"
                    data-assessment-leave-guard
                    data-assessment-answer-autosave
                    data-assessment-id="{{ (int) ($assessment['id'] ?? 0) }}"
                    data-user-id="{{ (int) auth()->id() }}"
                    data-attempt-key="{{ $assessment['offline_attempt_key'] ?? '' }}"
                    data-image-upload-url="{{ route('student.media.image') }}"
                    data-recording-upload-url="{{ route('student.media.recording') }}"
                    data-pwa-wait="Đang nộp bài…"
                >
                    @csrf
                    @include('pwa.partials.return-field')
                    <input type="hidden" name="timed_out" value="0" class="js-timed-out">
                    <div class="sw-assess-toolbar">
                        <span>Đang làm bài</span>
                        <span>{{ $totalQuestions }} câu</span>
                    </div>
                    @include('student.partials.quiz-form', [
                        'quizQuestions' => $questions,
                        'savedAnswers' => $draftAnswers ?? [],
                        'showPoints' => true,
                    ])
                    <p class="pwa-help">Sau khi nộp, giáo viên sẽ chấm rồi công bố kết quả. Bạn {{ ! empty($allowRetake) ? 'có thể được giáo viên cho làm lại' : 'không thể sửa bài' }}.</p>
                    <button type="submit" class="pwa-btn">
                        <i class="fa-solid fa-paper-plane"></i>
                        {{ $isRetake ? 'Nộp bài làm lại' : 'Nộp bài kiểm tra' }}
                    </button>
                </form>
            @elseif ($pendingReview)
                <section class="pwa-card">
                    <h2>Đã nộp bài — chờ chấm</h2>
                    <p class="pwa-help">Bài làm đã gửi tới giáo viên. Kết quả hiện sau khi giáo viên chấm và công bố.</p>
                    @if (! empty($submissionMeta?->submitted_at))
                        <p class="pwa-meta"><i class="fa-regular fa-clock"></i> Nộp lúc {{ $submissionMeta->submitted_at->format('H:i d/m/Y') }}</p>
                    @endif
                </section>
            @elseif ($submitted && $result !== null)
                <div class="pwa-card">
                    @include('student.partials.quiz-result', [
                        'title' => 'Kết quả bài kiểm tra',
                        'questions' => $questions,
                        'answerMap' => $answerMap,
                        'result' => $result,
                        'submitted' => true,
                        'showPoints' => true,
                    ])
                </div>
            @elseif (! $showQuizStart)
                <section class="pwa-card">
                    <p class="pwa-help">
                        @if ($isClosed)
                            Bài kiểm tra đã kết thúc. Bạn không thể nộp bài.
                        @else
                            Bài kiểm tra chưa mở. Vui lòng quay lại đúng giờ theo lịch học.
                        @endif
                    </p>
                </section>
            @endif
        @endif
    </div>
</main>
@endsection

@push('scripts')
    @if ($canShowQuizForm)
        <script src="{{ asset('assets/js/assessment-offline-queue.js') }}?v=2"></script>
    @endif
    @include('student.partials.quiz-scripts')
    <link rel="stylesheet" href="{{ asset('assets/css/pwa-session.css') }}?v=4">
    @if ($canShowQuizForm)
        <script src="{{ asset('assets/js/assessment-leave-guard.js') }}?v=1"></script>
        <script src="{{ asset('assets/js/network-offline-banner.js') }}?v=1"></script>
    @endif
@endpush
