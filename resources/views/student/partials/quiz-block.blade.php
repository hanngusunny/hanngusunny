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
    $isFigmaCourse = ! empty($figmaCourse);
    $questionTotal = count($quizQuestions ?? []);
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

@if (! $isFigmaCourse)
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
@else
    @php
        $lobbyHeading = trim((string) ($lobbyTitle ?? $title));
        $metaLine = $questionTotal.' câu | Nhiều dạng câu';
        if ($quizHasTimeLimit && (int) ($quizDurationMinutes ?? 0) > 0) {
            $metaLine = $questionTotal.' câu | '.(int) $quizDurationMinutes.' phút | Nhiều dạng câu';
        }
        $justSubmittedFlash = (bool) (session('success') || session('info'));
        if ($canShowForm && ($practiceMode || ($requireStart && $quizAttemptStarted))) {
            $initialView = 'taking';
        } elseif ($submitted && ($isPracticeResult || $justSubmittedFlash)) {
            $initialView = 'result';
        } else {
            $initialView = 'lobby';
        }
    @endphp

    <div
        class="sn-quiz-flow"
        id="{{ $sectionId }}"
        data-sn-quiz-flow
        data-initial-view="{{ $initialView }}"
        data-submitted="{{ $submitted ? '1' : '0' }}"
        data-practice-mode="{{ $practiceMode ? '1' : '0' }}"
    >
        {{-- VIEW 1: Lobby Practice Card (#317:12428 Chưa làm / #427:63474 Đã làm) --}}
        <div class="sn-quiz-view" data-quiz-view="lobby" @if ($initialView !== 'lobby') hidden @endif>
            <div class="sn-practice-shell">
                <div class="sn-practice-card">
                    <div class="sn-practice-mascot">
                        <img src="{{ asset('assets/images/figma-mascot-fighting.png') }}" alt="Sẵn sàng luyện tập" width="146" height="146" loading="eager">
                    </div>
                    <h3 class="sn-practice-title">{{ $lobbyHeading }}</h3>

                    @if (! empty($introHtml) && \App\Support\HtmlContent::has($introHtml))
                        <div class="sn-practice-intro sw-rich">{!! \App\Support\HtmlContent::render($introHtml) !!}</div>
                    @endif

                    <div class="sn-practice-meta-box">
                        <span class="sn-practice-meta-ico" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M14 2H7C5.3 2 4 3.3 4 5V19C4 20.7 5.3 22 7 22H17C18.7 22 20 20.7 20 19V8L14 2Z" fill="currentColor"/>
                                <path d="M8 13H16M8 17H13" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="sn-practice-meta-copy">
                            <strong>Nội dung</strong>
                            <span>{{ $metaLine }}</span>
                        </div>
                    </div>

                    <div class="sn-practice-actions">
                        @if ($submitted)
                            <button type="button" class="sn-course-btn sn-course-btn--white" data-quiz-open="result">
                                <span>Xem lại kết quả trước</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M9 14L4 9L9 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M20 20V13C20 10.7909 18.2091 9 16 9H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            @if ($practiceAction)
                                <form method="post" action="{{ $practiceAction }}" class="sn-inline-form">
                                    @csrf
                                    @include('pwa.partials.return-field')
                                    <button type="submit" class="sn-course-btn sn-course-btn--primary">
                                        <span>Làm lại</span>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10" fill="#FFFFFF"/>
                                            <path d="M10 8.5L13.8 12L10 15.5" stroke="#FF7614" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        @elseif ($showIntro)
                            <form method="post" action="{{ $startAction }}" class="sn-inline-form">
                                @csrf
                                @include('pwa.partials.return-field')
                                <button type="submit" class="sn-course-btn sn-course-btn--primary">
                                    <span>Vào làm</span>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" fill="#FFFFFF"/>
                                        <path d="M10 8.5L13.8 12L10 15.5" stroke="#FF7614" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </form>
                        @elseif ($canShowForm)
                            <button type="button" class="sn-course-btn sn-course-btn--primary" data-quiz-open="taking">
                                <span>Vào làm</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" fill="#FFFFFF"/>
                                    <path d="M10 8.5L13.8 12L10 15.5" stroke="#FF7614" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        @elseif ($quizIsExpired)
                            <p class="sw-inline-note is-warn">Đã hết thời gian làm bài.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- VIEW 2: Focus Quiz Taking Mode (#317:12494) --}}
        @if ($canShowForm)
            <div class="sn-quiz-view sn-quiz-focus-overlay" data-quiz-view="taking" @if ($initialView !== 'taking') hidden @endif>
                <div class="sn-quiz-focus-container">
                    <header class="sn-quiz-focus-header">
                        <div class="sn-quiz-focus-head-copy">
                            <div class="sn-quiz-focus-badges">
                                <span class="sn-course-level-pill" data-quiz-mirror-level>Khóa học</span>
                                @if ($practiceMode)
                                    <span class="sn-quiz-practice-pill">Đang luyện tập · Không ghi đè bài đã nộp</span>
                                @endif
                            </div>
                            <h2 class="sn-quiz-focus-title" data-quiz-mirror-session>{{ $lobbyHeading }}</h2>
                            <p class="sn-quiz-focus-sub" data-quiz-mirror-sub></p>
                        </div>
                        <button type="button" class="sn-quiz-close-btn" data-quiz-close aria-label="Dừng làm bài" title="Dừng làm bài">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M9.5 9.5L14.5 14.5M14.5 9.5L9.5 14.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </header>

                    <form
                        method="post"
                        action="{{ $formAction }}"
                        class="js-student-quiz session-quiz-form sn-quiz-focus-form"
                        novalidate
                        data-sn-figma-quiz-form
                        data-timed="{{ $quizHasTimeLimit ? '1' : '0' }}"
                        @if (! empty($autosaveUrl))
                            data-quiz-autosave
                            data-autosave-url="{{ $autosaveUrl }}"
                        @endif
                    >
                        @csrf
                        @include('pwa.partials.return-field')
                        <input type="hidden" name="timed_out" value="0" class="js-timed-out" @if ($sectionId === 'session-knowledge-review') id="sessionKnowledgeReviewTimedOut" @endif>

                        <div class="sn-quiz-focus-grid">
                            <div class="sn-quiz-focus-questions">
                                <div class="sunny-app page-session-show page-student-home sw-quiz-sunny sn-quiz-sunny-clean">
                                    @include('student.partials.quiz-form', [
                                        'quizQuestions' => $quizQuestions,
                                        'savedAnswers' => $quizDisplay['savedAnswers'] ?? $quizDisplay['quizAnswerMap'] ?? [],
                                        'showPoints' => ! empty($showPoints),
                                    ])
                                </div>
                            </div>

                            <aside class="sn-quiz-focus-aside" aria-label="Bảng câu trả lời">
                                <div class="sn-quiz-nav-card">
                                    <h3 class="sn-quiz-nav-title">Câu trả lời</h3>
                                    <p class="sn-quiz-nav-sub">Chọn số câu để chuyển nhanh</p>

                                    <div class="sn-quiz-nav-grid" data-quiz-nav-grid>
                                        @for ($qIdx = 1; $qIdx <= $questionTotal; $qIdx++)
                                            <button
                                                type="button"
                                                class="sn-quiz-nav-num{{ $qIdx === 1 ? ' is-current' : '' }}"
                                                data-quiz-jump="{{ $qIdx }}"
                                                aria-label="Đi tới câu {{ $qIdx }}"
                                            >
                                                {{ $qIdx }}
                                            </button>
                                        @endfor
                                    </div>

                                    <div class="sn-quiz-progress-block">
                                        <p class="sn-quiz-progress-label">
                                            Tiến độ · <strong data-quiz-progress-text>0/{{ $questionTotal }} câu</strong>
                                        </p>
                                        <div class="sn-quiz-progress-track">
                                            <span class="sn-quiz-progress-fill" data-quiz-progress-fill style="width: 0%"></span>
                                        </div>
                                    </div>

                                    @if ($quizHasTimeLimit)
                                        <div
                                            class="sn-quiz-timer-card"
                                            data-quiz-timer
                                            data-expires-ts="{{ (int) ($quizExpiresTs ?? 0) }}"
                                            data-remaining="{{ (int) ($quizRemainingSeconds ?? 0) }}"
                                        >
                                            <span class="sn-quiz-timer-ico" aria-hidden="true">
                                                <i class="bi bi-alarm-fill"></i>
                                            </span>
                                            <div class="sn-quiz-timer-copy">
                                                <strong>Thời gian còn lại</strong>
                                                <span class="sn-quiz-timer-val" data-timer-label>{{ sprintf('%02d:00', (int) ($quizDurationMinutes ?? 0)) }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </aside>
                        </div>

                        <div class="sn-quiz-focus-footer">
                            <button type="submit" class="sn-course-btn sn-course-btn--primary session-post-submit">
                                <span>Nộp bài</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" fill="#FFFFFF"/>
                                    <path d="M10 8.5L13.8 12L10 15.5" stroke="#FF7614" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- VIEW 3: Result View (#317:13207) --}}
        @if ($submitted)
            <div class="sn-quiz-view sn-quiz-result-view" data-quiz-view="result" @if ($initialView !== 'result') hidden @endif>
                @if ($isPracticeResult)
                    <div class="sn-quiz-result-banner">
                        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                        <span><strong>Kết quả luyện tập:</strong> Điểm lần này không thay thế bài đã nộp chính thức.</span>
                    </div>
                @endif

                @include('student.partials.quiz-result', [
                    'title' => $isPracticeResult ? 'Kết quả luyện tập' : 'Kết quả bài làm',
                    'questions' => $quizQuestions,
                    'answerMap' => $quizDisplay['quizAnswerMap'] ?? [],
                    'result' => $quizDisplay['quizResult'] ?? null,
                    'submitted' => true,
                    'readonlyNote' => $isPracticeResult ? 'Điểm luyện tập không thay thế bài đã nộp.' : null,
                    'isPracticeResult' => $isPracticeResult,
                    'hasSavedSubmission' => $hasSaved,
                    'figmaCourseResult' => true,
                ])

                <div class="sn-quiz-result-foot">
                    <button type="button" class="sn-course-btn sn-course-btn--white" data-quiz-open="lobby">
                        <span>Quay lại</span>
                    </button>
                    @if ($exitAction)
                        <form method="post" action="{{ $exitAction }}" class="sn-inline-form">
                            @csrf
                            @include('pwa.partials.return-field')
                            <button type="submit" class="sn-course-btn sn-course-btn--white">
                                <span>Xem kết quả đã nộp</span>
                            </button>
                        </form>
                    @endif
                    @if ($practiceAction)
                        <form method="post" action="{{ $practiceAction }}" class="sn-inline-form">
                            @csrf
                            @include('pwa.partials.return-field')
                            <button type="submit" class="sn-course-btn sn-course-btn--primary">
                                <span>Làm lại</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" fill="#FFFFFF"/>
                                    <path d="M10 8.5L13.8 12L10 15.5" stroke="#FF7614" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif
