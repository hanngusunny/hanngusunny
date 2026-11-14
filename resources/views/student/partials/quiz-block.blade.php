@php
    $quizDisplay = $quizDisplay ?? [];
    $submitted = ! empty($quizDisplay['quizSubmitted']);
    $practiceMode = ! empty($quizDisplay['quizPracticeMode']);
    $isPracticeResult = ! empty($quizDisplay['quizIsPracticeResult']);
    $hasSaved = ! empty($quizDisplay['hasSavedSubmission']);
    $requireStart = ! empty($requireStart);
    $quizAttemptStarted = $requireStart ? ! empty($quizAttemptStarted) : true;
    $practiceIsTakeable = $practiceIsTakeable ?? true;
    $quizHasTimeLimit = ! empty($quizHasTimeLimit);
    $quizIsExpired = ! empty($quizIsExpired);
    $showIntro = $requireStart && ! $quizAttemptStarted && ! $submitted && ! $isPracticeResult && $practiceIsTakeable;
    $canShowForm = $quizAttemptStarted && ! $submitted && $practiceIsTakeable && ! $quizIsExpired;
    $sectionId = (string) ($sectionId ?? 'session-quiz');
    $cardIcon = match ($sectionId) {
        'session-pre-quiz' => ['bi-journal-check', 'blue'],
        'session-knowledge-review' => ['bi-lightbulb', 'purple'],
        default => ['bi-ui-checks', 'amber'],
    };
    $submitLabel = match ($sectionId) {
        'session-pre-quiz' => 'Nộp bài tập',
        'session-knowledge-review' => 'Nộp bài ôn tập',
        default => 'Nộp bài ôn tập',
    };
@endphp

<div class="sunny-app page-session-show page-student-home sw-quiz-sunny">
<article class="session-post-card session-post-card--quiz sw-quiz" id="{{ $sectionId }}">
    <header class="session-post-card-head">
        <span class="session-post-card-icon session-post-card-icon--{{ $cardIcon[1] }}" aria-hidden="true">
            <i class="bi {{ $cardIcon[0] }}"></i>
        </span>
        <div class="session-post-card-title">
            <h3>{{ $title }}</h3>
            @if (($desc ?? '') !== '')
                <p>{{ $desc }}</p>
            @endif
        </div>
        @if ($practiceMode)
            <span class="sw-phase sw-phase--live">Luyện tập</span>
        @elseif ($isPracticeResult)
            <span class="sw-phase">Kết quả luyện tập</span>
        @endif
    </header>

    @if (! empty($introHtml) && \App\Support\HtmlContent::has($introHtml))
        <div class="sw-rich" style="padding:0 22px 8px">{!! \App\Support\HtmlContent::render($introHtml) !!}</div>
    @endif

    @if ($practiceMode)
        <div class="session-quiz-practice-banner session-quiz-practice-banner--active">
            <i class="bi bi-pencil-square" aria-hidden="true"></i>
            <div>
                <strong>Đang luyện tập</strong>
                Câu trả lời được lưu tự động. Kết quả lần này không thay thế bài đã nộp.
            </div>
        </div>
    @elseif ($isPracticeResult)
        <div class="session-quiz-practice-banner">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <div>
                <strong>Kết quả luyện tập</strong>
                Điểm lần này không thay thế bài đã nộp.
            </div>
        </div>
    @endif

    @if ($showIntro)
        <div class="sw-quiz-start">
            @if ($quizHasTimeLimit)
                <p>Bài có thời gian <strong>{{ (int) $quizDurationMinutes }} phút</strong>. Đồng hồ bắt đầu khi bạn bấm bắt đầu.</p>
            @else
                <p>Bấm bắt đầu để làm bài ôn tập kiến thức.</p>
            @endif
            <form method="post" action="{{ $startAction }}">
                @csrf
                @include('pwa.partials.return-field')
                <button type="submit" class="btn btn-primary btn-with-icon">
                    <i class="bi bi-play-fill"></i>
                    <span>Bắt đầu làm bài</span>
                </button>
            </form>
        </div>
    @elseif ($submitted)
        @include('student.partials.quiz-result', [
            'title' => $isPracticeResult ? 'Kết quả luyện tập' : 'Kết quả bài làm',
            'questions' => $quizQuestions,
            'answerMap' => $quizDisplay['quizAnswerMap'] ?? [],
            'result' => $quizDisplay['quizResult'] ?? null,
            'submitted' => true,
            'readonlyNote' => $isPracticeResult ? 'Điểm luyện tập không thay thế bài đã nộp.' : null,
            'isPracticeResult' => $isPracticeResult,
            'hasSavedSubmission' => $hasSaved,
            'embeddedInSunny' => true,
        ])
        <div class="session-post-card-foot session-post-card-foot--reset">
            @if ($practiceAction)
                <form method="post" action="{{ $practiceAction }}">
                    @csrf
                    @include('pwa.partials.return-field')
                    <button type="submit" class="btn btn-primary btn-with-icon">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Làm lại (luyện tập)</span>
                    </button>
                </form>
            @endif
            @if ($exitAction)
                <form method="post" action="{{ $exitAction }}">
                    @csrf
                    @include('pwa.partials.return-field')
                    <button type="submit" class="op-btn-ghost">Xem kết quả đã nộp</button>
                </form>
            @endif
        </div>
    @elseif ($canShowForm)
        @if ($quizHasTimeLimit)
            <div
                class="session-knowledge-review-timer-bar mock-exam-timer-bar sw-timer"
                data-quiz-timer
                data-expires-ts="{{ (int) ($quizExpiresTs ?? 0) }}"
                data-remaining="{{ (int) ($quizRemainingSeconds ?? 0) }}"
            >
                <div class="mock-exam-timer-inner">
                    <span class="mock-exam-timer-icon" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                    <div class="mock-exam-timer-copy">
                        <strong>Thời gian còn lại</strong>
                        <span class="mock-exam-timer-value" data-timer-label>—</span>
                    </div>
                    <span class="mock-exam-timer-meta">{{ (int) ($quizDurationMinutes ?? 0) }} phút</span>
                </div>
            </div>
        @endif
        <form
            method="post"
            action="{{ $formAction }}"
            class="js-student-quiz session-quiz-form"
            novalidate
            data-timed="{{ $quizHasTimeLimit ? '1' : '0' }}"
            @if (! empty($autosaveUrl))
                data-quiz-autosave
                data-autosave-url="{{ $autosaveUrl }}"
            @endif
        >
            @csrf
            @include('pwa.partials.return-field')
            <input type="hidden" name="timed_out" value="0" class="js-timed-out">
            @include('student.partials.quiz-form', [
                'quizQuestions' => $quizQuestions,
                'savedAnswers' => $quizDisplay['savedAnswers'] ?? $quizDisplay['quizAnswerMap'] ?? [],
                'showPoints' => ! empty($showPoints),
            ])
            <div class="session-post-card-foot">
                <button type="submit" class="btn btn-primary btn-with-icon session-post-submit">
                    <i class="bi bi-send"></i>
                    <span>{{ $submitLabel }}</span>
                </button>
            </div>
        </form>
    @elseif ($quizIsExpired)
        <p class="sw-inline-note is-warn" style="margin:16px">Đã hết thời gian làm bài.</p>
    @endif
</article>
</div>
