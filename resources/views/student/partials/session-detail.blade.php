@php
    use App\Services\SessionQuizDraftService;
    use App\Services\StudentCourseNavService;
    use App\Services\StudentQuizPracticeService;
    use App\Support\HtmlContent;
    use App\Support\VideoEmbed;

    $contentTab = (string) ($contentTab ?? StudentCourseNavService::TAB_PRE);
    $showPre = $contentTab === StudentCourseNavService::TAB_PRE;
    $showPost = $contentTab === StudentCourseNavService::TAB_POST;
    $showKnowledgeReview = $contentTab === StudentCourseNavService::TAB_KNOWLEDGE_REVIEW;
    $session = $sessionDetail['session'];
    $phase = (string) ($session['phase'] ?? 'upcoming');
    $isEnded = $phase === 'ended' || $phase === 'cancelled';
    $display = $sessionDetail['display'] ?? $session['display'] ?? [];
    $publicCode = trim((string) ($session['public_code'] ?? ''));
    $sessionIdInt = (int) ($session['id'] ?? 0);
    $studentUserId = (int) auth()->id();
    $quizPractice = app(StudentQuizPracticeService::class);
    $quizDrafts = app(SessionQuizDraftService::class);
    $description = (string) ($session['description'] ?? '');
@endphp

<section class="op-panel{{ ! empty($pwaEmbed) ? ' is-pwa-embed' : '' }}">
    @unless (! empty($pwaEmbed))
        <div class="op-panel-head is-static">
            <span class="op-panel-ico"><i class="fa-solid fa-book"></i></span>
            <span class="op-panel-title">{{ $session['title'] ?? 'Buổi học' }}</span>
            <span class="sw-phase sw-phase--{{ $phase }}">{{ $sessionDetail['phaseLabels'][$phase] ?? $phase }}</span>
        </div>
    @endunless
    <div class="op-panel-body sw-detail" @unless (! empty($pwaEmbed)) style="padding:20px" @endunless>
        @unless (! empty($pwaEmbed))
            <p class="sw-session-sub">
                {{ $sessionDetail['course']['title'] ?? '' }}
                @if (! empty($display['hasSchedule']))
                    · {{ $display['weekdayShort'] ?? '' }}, {{ $display['dateLabel'] ?? '' }} · {{ $display['timeLabel'] ?? '' }}
                @endif
            </p>

            @include('student.partials.join-room', ['session' => $session, 'publicCode' => $publicCode])
        @endunless

        @if ($showPre)
            <div class="sw-intro">
                <span class="sw-intro-ico"><i class="fa-solid fa-book-open-reader"></i></span>
                <div>
                    <h3>Chuẩn bị trước buổi học</h3>
                    <p>Đọc tài liệu gợi ý và làm bài tập trước khi vào lớp.</p>
                </div>
            </div>

            @if (HtmlContent::has($description))
                <article class="sw-card" id="session-description">
                    <h3>Mô tả buổi học</h3>
                    <div class="sw-rich">{!! HtmlContent::render($description) !!}</div>
                </article>
            @endif

            @include('student.partials.pre-materials', ['session' => $session])

            @if (($sessionDetail['preQuestions'] ?? []) !== [])
                @php
                    $preDisplay = $quizPractice->resolveStudentDisplay(
                        StudentQuizPracticeService::TYPE_PRE,
                        $sessionIdInt,
                        $studentUserId,
                        (bool) ($sessionDetail['preSubmitted'] ?? false),
                        $sessionDetail['preAnswerMap'] ?? [],
                        $sessionDetail['preResult'] ?? null
                    );
                    if (empty($preDisplay['quizSubmitted'])) {
                        $preDisplay['savedAnswers'] = $quizDrafts->answersForSessionForm(
                            $sessionIdInt,
                            $studentUserId,
                            $sessionDetail['preQuestions'],
                            $sessionDetail['preAnswerMap'] ?? [],
                            ! empty($preDisplay['quizPracticeMode'])
                        );
                    }
                @endphp
                @include('student.partials.quiz-block', [
                    'quizQuestions' => $sessionDetail['preQuestions'],
                    'quizDisplay' => $preDisplay,
                    'title' => 'Bài tập trước buổi học',
                    'desc' => count($sessionDetail['preQuestions']).' câu hỏi',
                    'sectionId' => 'session-pre-quiz',
                    'formAction' => route('student.session.pre.submit', $publicCode),
                    'autosaveUrl' => $phase !== 'cancelled'
                        ? route('student.session.pre.autosave', $publicCode) : null,
                    'practiceAction' => $preDisplay['hasSavedSubmission'] && $phase !== 'cancelled'
                        ? route('student.session.pre.practice', $publicCode) : null,
                    'exitAction' => $preDisplay['quizIsPracticeResult']
                        ? route('student.session.pre.exit', $publicCode) : null,
                    'requireStart' => false,
                ])
            @endif

            @if (($sessionDetail['reviewQuestions'] ?? []) !== [])
                @include('student.partials.quiz-result', [
                    'title' => 'Kiểm tra bài cũ (trên lớp)',
                    'questions' => $sessionDetail['reviewQuestions'],
                    'answerMap' => $sessionDetail['reviewAnswerMap'] ?? [],
                    'result' => $sessionDetail['reviewResult'] ?? null,
                    'submitted' => (bool) ($sessionDetail['reviewSubmitted'] ?? false),
                    'readonlyNote' => 'Kết quả kiểm tra bài cũ trong giờ học. Học viên chỉ xem lại.',
                ])
            @endif
        @endif

        @if ($showPost)
            @if (! $isEnded)
                <div class="sw-empty">
                    <span class="sw-empty-ico"><i class="fa-solid fa-hourglass-half"></i></span>
                    <p>Nội dung sau buổi học sẽ mở khi buổi đã kết thúc.</p>
                </div>
            @else
                @php
                    $postDisplay = $quizPractice->resolveStudentDisplay(
                        StudentQuizPracticeService::TYPE_POST,
                        $sessionIdInt,
                        $studentUserId,
                        (bool) ($sessionDetail['quizSubmitted'] ?? false),
                        $sessionDetail['quizAnswerMap'] ?? [],
                        $sessionDetail['quizResult'] ?? null
                    );
                    if (empty($postDisplay['quizSubmitted'])) {
                        $postDisplay['savedAnswers'] = $quizDrafts->answersForSessionForm(
                            $sessionIdInt,
                            $studentUserId,
                            $sessionDetail['quizQuestions'] ?? [],
                            $sessionDetail['quizAnswerMap'] ?? [],
                            ! empty($postDisplay['quizPracticeMode'])
                        );
                    }
                    $record = VideoEmbed::parse($session['record_video_url'] ?? '');
                    $hasRecord = $record && $record['type'] === 'youtube';
                    $hasMaterials = HtmlContent::has($session['materials'] ?? '') || HtmlContent::has($session['session_summary'] ?? '');
                    $hasQuiz = ($sessionDetail['quizQuestions'] ?? []) !== [];
                    $hasReflection = (int) ($sessionDetail['reflection']['session_rating'] ?? 0) > 0
                        || trim((string) ($sessionDetail['reflection']['student_comment'] ?? '')) !== '';
                    $steps = [];
                    if ($hasRecord) { $steps[] = ['id' => 'session-record-video', 'label' => 'Xem lại', 'icon' => 'bi-play-btn', 'done' => true]; }
                    if ($hasMaterials) { $steps[] = ['id' => 'session-materials', 'label' => 'Tài liệu', 'icon' => 'bi-file-earmark-richtext', 'done' => true]; }
                    if ($hasQuiz) { $steps[] = ['id' => 'session-quiz', 'label' => 'Ôn tập', 'icon' => 'bi-ui-checks', 'done' => $postDisplay['quizSubmitted']]; }
                    $steps[] = ['id' => 'session-reflection', 'label' => 'Đánh giá', 'icon' => 'bi-star', 'done' => $hasReflection];
                    $doneCount = count(array_filter($steps, fn ($s) => $s['done']));
                    $progressPct = $steps !== [] ? (int) round(($doneCount / count($steps)) * 100) : 0;
                @endphp

                <div class="sunny-app page-session-show page-student-home sw-quiz-sunny">
                <div class="session-post-embedded">
                    <div class="session-post-embedded-progress" aria-label="Tiến độ hoàn thành sau buổi học">
                        <div class="session-post-embedded-progress-bar">
                            <span style="width: {{ $progressPct }}%"></span>
                        </div>
                        <p><strong>{{ $progressPct }}%</strong> hoàn thành · {{ $doneCount }}/{{ count($steps) }} bước</p>
                    </div>
                    <nav class="session-post-steps session-post-steps--compact" aria-label="Các bước ôn tập">
                        @foreach ($steps as $i => $step)
                            <a
                                class="session-post-step{{ $step['done'] ? ' is-done' : '' }}{{ ! $step['done'] && ($i === 0 || ($steps[$i - 1]['done'] ?? false)) ? ' is-next' : '' }}"
                                href="#{{ $step['id'] }}"
                            >
                                <span class="session-post-step-icon"><i class="bi {{ $step['icon'] }}"></i></span>
                                <span class="session-post-step-label">{{ $step['label'] }}</span>
                                @if ($step['done'])
                                    <span class="session-post-step-check"><i class="bi bi-check-lg"></i></span>
                                @endif
                            </a>
                        @endforeach
                    </nav>

                    <div class="session-post-stack">
                @if ($hasRecord)
                    <article class="session-post-card" id="session-record-video">
                        <header class="session-post-card-head">
                            <span class="session-post-card-icon session-post-card-icon--blue"><i class="bi bi-play-btn"></i></span>
                            <div class="session-post-card-title">
                                <h3>Video ghi hình</h3>
                                <p>Xem lại buổi học</p>
                            </div>
                        </header>
                        <div class="session-record-video-player" style="margin:0 16px 16px">
                            <iframe src="{{ $record['src'] }}" title="Video buổi học" allowfullscreen></iframe>
                        </div>
                    </article>
                @elseif (trim((string) ($session['record_video_url'] ?? '')) !== '')
                    <article class="session-post-card" id="session-record-video">
                        <header class="session-post-card-head">
                            <span class="session-post-card-icon session-post-card-icon--blue"><i class="bi bi-play-btn"></i></span>
                            <div class="session-post-card-title">
                                <h3>Video ghi hình</h3>
                                <p>Mở liên kết ghi hình</p>
                            </div>
                        </header>
                        <div class="session-post-card-foot">
                            <a class="btn btn-primary btn-with-icon" href="{{ $session['record_video_url'] }}" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right"></i>
                                <span>Mở video</span>
                            </a>
                        </div>
                    </article>
                @endif

                @if ($hasMaterials)
                    <article class="session-post-card" id="session-materials">
                        <header class="session-post-card-head">
                            <span class="session-post-card-icon session-post-card-icon--purple"><i class="bi bi-file-earmark-richtext"></i></span>
                            <div class="session-post-card-title">
                                <h3>Tài liệu & tóm tắt</h3>
                                <p>Ôn lại nội dung buổi học</p>
                            </div>
                        </header>
                        <div style="padding:0 22px 18px">
                            @if (HtmlContent::has($session['materials'] ?? ''))
                                <div class="sw-rich">{!! HtmlContent::render($session['materials']) !!}</div>
                            @endif
                            @if (HtmlContent::has($session['session_summary'] ?? ''))
                                <h4>Tóm tắt buổi</h4>
                                <div class="sw-rich">{!! HtmlContent::render($session['session_summary']) !!}</div>
                            @endif
                        </div>
                    </article>
                @endif

                @if ($hasQuiz)
                    @include('student.partials.quiz-block', [
                        'quizQuestions' => $sessionDetail['quizQuestions'],
                        'quizDisplay' => $postDisplay,
                        'title' => 'Ôn tập sau buổi học',
                        'desc' => count($sessionDetail['quizQuestions']).' câu hỏi ôn tập',
                        'sectionId' => 'session-quiz',
                        'formAction' => route('student.session.post.submit', $publicCode),
                        'autosaveUrl' => $phase === 'ended'
                            ? route('student.session.post.autosave', $publicCode) : null,
                        'practiceAction' => $postDisplay['hasSavedSubmission']
                            ? route('student.session.post.practice', $publicCode) : null,
                        'exitAction' => $postDisplay['quizIsPracticeResult']
                            ? route('student.session.post.exit', $publicCode) : null,
                        'requireStart' => false,
                    ])
                @endif

                @include('student.partials.reflection', [
                    'reflection' => $sessionDetail['reflection'] ?? null,
                    'publicCode' => $publicCode,
                ])

                @if (($sessionDetail['reviewQuestions'] ?? []) !== [])
                    <article class="session-post-card" id="session-review-quiz">
                        <header class="session-post-card-head">
                            <span class="session-post-card-icon session-post-card-icon--red"><i class="bi bi-clipboard-check"></i></span>
                            <div class="session-post-card-title">
                                <h3>Kiểm tra bài cũ (trên lớp)</h3>
                                <p>Kết quả kiểm tra trong giờ học. Học viên chỉ xem lại.</p>
                            </div>
                        </header>
                        @include('student.partials.quiz-result', [
                            'title' => 'Kiểm tra bài cũ',
                            'questions' => $sessionDetail['reviewQuestions'],
                            'answerMap' => $sessionDetail['reviewAnswerMap'] ?? [],
                            'result' => $sessionDetail['reviewResult'] ?? null,
                            'submitted' => (bool) ($sessionDetail['reviewSubmitted'] ?? false),
                            'readonlyNote' => 'Kết quả kiểm tra bài cũ trong giờ học.',
                            'embeddedInSunny' => true,
                        ])
                    </article>
                @endif
                    </div>
                </div>
                </div>
            @endif
        @endif

        @if ($showKnowledgeReview)
            @if (! $isEnded)
                <div class="sw-empty">
                    <span class="sw-empty-ico"><i class="fa-solid fa-hourglass-half"></i></span>
                    <p>Ôn tập kiến thức bài sẽ mở khi buổi học đã kết thúc.</p>
                </div>
            @elseif (($sessionDetail['knowledgeReviewQuestions'] ?? []) === [])
                <div class="sw-empty">
                    <span class="sw-empty-ico"><i class="fa-solid fa-lightbulb"></i></span>
                    <p>Bài ôn tập kiến thức chưa có câu hỏi hoặc chưa được đồng bộ sang khóa học.</p>
                </div>
            @else
                @php
                    $knowledgeReview = $sessionDetail['knowledgeReview'] ?? [];
                    $courseKrId = (int) ($knowledgeReview['id'] ?? 0);
                    $krDisplay = $quizPractice->resolveStudentDisplay(
                        StudentQuizPracticeService::TYPE_KNOWLEDGE,
                        $courseKrId,
                        $studentUserId,
                        (bool) ($sessionDetail['knowledgeReviewSubmitted'] ?? false),
                        $sessionDetail['knowledgeReviewAnswerMap'] ?? [],
                        $sessionDetail['knowledgeReviewResult'] ?? null
                    );
                    if (empty($krDisplay['quizSubmitted'])) {
                        $krDisplay['savedAnswers'] = $quizDrafts->answersForKnowledgeReviewForm(
                            $courseKrId,
                            $studentUserId,
                            $sessionDetail['knowledgeReviewQuestions'],
                            $sessionDetail['knowledgeReviewAnswerMap'] ?? [],
                            ! empty($krDisplay['quizPracticeMode'])
                        );
                    }
                @endphp
                @include('student.partials.quiz-block', [
                    'quizQuestions' => $sessionDetail['knowledgeReviewQuestions'],
                    'quizDisplay' => $krDisplay,
                    'title' => trim((string) ($knowledgeReview['title'] ?? '')) ?: 'Ôn tập kiến thức bài',
                    'desc' => HtmlContent::has($knowledgeReview['description'] ?? '') ? '' : (count($sessionDetail['knowledgeReviewQuestions']).' câu hỏi'),
                    'introHtml' => $knowledgeReview['description'] ?? '',
                    'sectionId' => 'session-knowledge-review',
                    'formAction' => route('student.session.kr.submit', $publicCode),
                    'autosaveUrl' => route('student.session.kr.autosave', $publicCode),
                    'startAction' => route('student.session.kr.start', $publicCode),
                    'practiceAction' => $krDisplay['hasSavedSubmission']
                        ? route('student.session.kr.practice', $publicCode) : null,
                    'exitAction' => $krDisplay['quizIsPracticeResult']
                        ? route('student.session.kr.exit', $publicCode) : null,
                    'requireStart' => true,
                    'quizAttemptStarted' => ! empty($knowledgeReview['quiz_started']),
                    'practiceIsTakeable' => ! empty($knowledgeReview['is_takeable']),
                    'quizDurationMinutes' => (int) ($knowledgeReview['duration_minutes'] ?? 0),
                    'quizExpiresTs' => (int) ($knowledgeReview['expires_ts'] ?? 0),
                    'quizRemainingSeconds' => (int) ($knowledgeReview['remaining_seconds'] ?? 0),
                    'quizIsExpired' => ! empty($knowledgeReview['is_expired']),
                    'quizHasTimeLimit' => ! empty($knowledgeReview['has_time_limit']),
                ])
            @endif
        @endif
    </div>
</section>
