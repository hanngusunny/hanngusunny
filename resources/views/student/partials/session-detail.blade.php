@php
    use App\Services\SessionQuizDraftService;
    use App\Services\StudentCourseNavService;
    use App\Services\StudentQuizPracticeService;
    use App\Support\HtmlContent;
    use App\Support\VideoEmbed;

    $isPwa = ! empty($pwaEmbed);
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

@if ($isPwa)
    {{-- Giữ nguyên 100% nhánh PWA embed để không phá vỡ chức năng cũ --}}
    <section class="op-panel is-pwa-embed">
        <div class="op-panel-body sw-detail">
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
                                                <h3>Tài liệu &amp; tóm tắt</h3>
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
@else
    {{-- Giao diện Web Chuẩn Figma Màn Khóa Học (#317:12150, #378:7827, #317:12428, #427:63474) --}}
    <section class="sn-course-surface-card sn-course-content-card sw-detail">
        @include('student.partials.join-room', ['session' => $session, 'publicCode' => $publicCode])

        @if ($showPre)
            @php
                $preQuestions = $sessionDetail['preQuestions'] ?? [];
                $hasPreQuiz = $preQuestions !== [];
                $preDisplay = null;
                if ($hasPreQuiz) {
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
                            $preQuestions,
                            $sessionDetail['preAnswerMap'] ?? [],
                            ! empty($preDisplay['quizPracticeMode'])
                        );
                    }
                }
                $defaultPreSubtab = ($hasPreQuiz && (! empty($preDisplay['quizPracticeMode']) || ! empty($preDisplay['quizIsPracticeResult'])))
                    ? 'quiz'
                    : 'materials';
            @endphp

            <div class="sn-course-subtabs-wrap" data-course-subtabs data-default-subtab="{{ $defaultPreSubtab }}" data-quiz-hash="#session-pre-quiz">
                <div class="sn-course-pill-tabs" role="tablist" aria-label="Nội dung trước buổi học">
                    <button
                        type="button"
                        class="sn-course-pill-tab{{ $defaultPreSubtab === 'materials' ? ' is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $defaultPreSubtab === 'materials' ? 'true' : 'false' }}"
                        data-subtab-btn="materials"
                    >
                        Tài liệu chuẩn bị
                    </button>
                    <button
                        type="button"
                        class="sn-course-pill-tab{{ $defaultPreSubtab === 'quiz' ? ' is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $defaultPreSubtab === 'quiz' ? 'true' : 'false' }}"
                        data-subtab-btn="quiz"
                    >
                        Bài tập trước buổi
                    </button>
                </div>

                {{-- Pane 1: Tài liệu chuẩn bị (#317:12150) --}}
                <div class="sn-course-subpane" role="tabpanel" data-subtab-pane="materials" @if ($defaultPreSubtab !== 'materials') hidden @endif>
                    @if (HtmlContent::has($description))
                        <div class="sn-session-desc-banner" id="session-description">
                            <span class="sn-session-desc-label">Nội dung trọng tâm:</span>
                            <div class="sn-session-desc-text sw-rich">{!! HtmlContent::render($description) !!}</div>
                        </div>
                    @endif

                    @include('student.partials.pre-materials', ['session' => $session, 'figmaCourse' => true])

                    @if (($sessionDetail['reviewQuestions'] ?? []) !== [])
                        <div class="sn-material-group" id="session-review-quiz">
                            <div class="sn-material-group-head">
                                <span class="sn-material-num"><i class="fa-solid fa-clipboard-check"></i></span>
                                <h3 class="sn-material-group-title">Kiểm tra bài cũ (trên lớp)</h3>
                            </div>
                            <div class="sn-material-group-body">
                                @include('student.partials.quiz-result', [
                                    'title' => 'Kiểm tra bài cũ (trên lớp)',
                                    'questions' => $sessionDetail['reviewQuestions'],
                                    'answerMap' => $sessionDetail['reviewAnswerMap'] ?? [],
                                    'result' => $sessionDetail['reviewResult'] ?? null,
                                    'submitted' => (bool) ($sessionDetail['reviewSubmitted'] ?? false),
                                    'readonlyNote' => 'Kết quả kiểm tra bài cũ trong giờ học. Học viên chỉ xem lại.',
                                    'figmaCourseResult' => true,
                                ])
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Pane 2: Bài tập trước buổi (#317:12428 / #427:63474 / #317:12494 / #317:13207) --}}
                <div class="sn-course-subpane" role="tabpanel" data-subtab-pane="quiz" @if ($defaultPreSubtab !== 'quiz') hidden @endif>
                    @if ($hasPreQuiz)
                        @include('student.partials.quiz-block', [
                            'quizQuestions' => $preQuestions,
                            'quizDisplay' => $preDisplay,
                            'title' => 'Bài tập trước buổi học',
                            'lobbyTitle' => 'Ôn luyện trước buổi học',
                            'desc' => count($preQuestions).' câu hỏi',
                            'sectionId' => 'session-pre-quiz',
                            'formAction' => route('student.session.pre.submit', $publicCode),
                            'autosaveUrl' => $phase !== 'cancelled'
                                ? route('student.session.pre.autosave', $publicCode) : null,
                            'practiceAction' => $preDisplay['hasSavedSubmission'] && $phase !== 'cancelled'
                                ? route('student.session.pre.practice', $publicCode) : null,
                            'exitAction' => $preDisplay['quizIsPracticeResult']
                                ? route('student.session.pre.exit', $publicCode) : null,
                            'requireStart' => false,
                            'figmaCourse' => true,
                        ])
                    @else
                        <div class="sn-practice-shell">
                            <div class="sn-practice-card">
                                <div class="sn-practice-mascot">
                                    <img src="{{ asset('assets/images/figma-mascot-fighting.png') }}" alt="" width="146" height="146" loading="lazy">
                                </div>
                                <h3 class="sn-practice-title">Ôn luyện trước buổi học</h3>
                                <p class="sn-practice-empty-desc">Buổi học này chưa có bài tập trước buổi.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($showPost)
            @if (! $isEnded)
                <div class="sn-course-empty">
                    <span class="sn-course-empty-ico"><i class="fa-solid fa-hourglass-half"></i></span>
                    <p class="sn-course-empty-title">Nội dung sau buổi học sẽ mở khi buổi đã kết thúc.</p>
                    <p class="sn-course-empty-sub">Sau khi hoàn thành giờ học trực tuyến, bạn có thể xem lại video record và làm bài tập ôn luyện tại đây.</p>
                </div>
            @else
                @php
                    $postQuestions = $sessionDetail['quizQuestions'] ?? [];
                    $hasQuiz = $postQuestions !== [];
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
                            $postQuestions,
                            $sessionDetail['quizAnswerMap'] ?? [],
                            ! empty($postDisplay['quizPracticeMode'])
                        );
                    }
                    $record = VideoEmbed::parse($session['record_video_url'] ?? '');
                    $hasRecord = $record && $record['type'] === 'youtube';
                    $rawRecordUrl = trim((string) ($session['record_video_url'] ?? ''));
                    $hasMaterials = HtmlContent::has($session['materials'] ?? '') || HtmlContent::has($session['session_summary'] ?? '');
                    $defaultPostSubtab = ($hasQuiz && (! empty($postDisplay['quizPracticeMode']) || ! empty($postDisplay['quizIsPracticeResult'])))
                        ? 'quiz'
                        : 'video';
                    $postSectionIdx = 1;
                @endphp

                <div class="sn-course-subtabs-wrap" data-course-subtabs data-default-subtab="{{ $defaultPostSubtab }}" data-quiz-hash="#session-quiz">
                    <div class="sn-course-pill-tabs" role="tablist" aria-label="Nội dung sau buổi học">
                        <button
                            type="button"
                            class="sn-course-pill-tab{{ $defaultPostSubtab === 'video' ? ' is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ $defaultPostSubtab === 'video' ? 'true' : 'false' }}"
                            data-subtab-btn="video"
                        >
                            Video record
                        </button>
                        <button
                            type="button"
                            class="sn-course-pill-tab{{ $defaultPostSubtab === 'quiz' ? ' is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ $defaultPostSubtab === 'quiz' ? 'true' : 'false' }}"
                            data-subtab-btn="quiz"
                        >
                            Bài tập sau buổi
                        </button>
                    </div>

                    {{-- Pane 1: Video record (#378:7827) + Tài liệu & Đánh giá buổi học --}}
                    <div class="sn-course-subpane" role="tabpanel" data-subtab-pane="video" @if ($defaultPostSubtab !== 'video') hidden @endif>
                        <div class="sn-material-group" id="session-record-video">
                            <div class="sn-material-group-head">
                                <span class="sn-material-num">{{ $postSectionIdx++ }}</span>
                                <h3 class="sn-material-group-title">Video record</h3>
                            </div>
                            @if ($hasRecord)
                                <div class="sn-video-frame">
                                    <iframe src="{{ $record['src'] }}" title="Video buổi học" allowfullscreen loading="lazy"></iframe>
                                </div>
                            @elseif ($rawRecordUrl !== '')
                                <div class="sn-video-external-box">
                                    <p>Xem lại video ghi hình buổi học tại liên kết bên dưới.</p>
                                    <a class="sn-course-btn sn-course-btn--primary" href="{{ $rawRecordUrl }}" target="_blank" rel="noopener">
                                        <span>Mở video</span>
                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            @else
                                <div class="sn-video-empty-box">
                                    <i class="fa-solid fa-video-slash" aria-hidden="true"></i>
                                    <p>Video ghi hình buổi học đang được cập nhật.</p>
                                </div>
                            @endif
                        </div>

                        @if ($hasMaterials)
                            <div class="sn-material-group" id="session-materials">
                                <div class="sn-material-group-head">
                                    <span class="sn-material-num">{{ $postSectionIdx++ }}</span>
                                    <h3 class="sn-material-group-title">Tài liệu &amp; tóm tắt</h3>
                                </div>
                                <div class="sn-material-rich-card sw-rich">
                                    @if (HtmlContent::has($session['materials'] ?? ''))
                                        <div>{!! HtmlContent::render($session['materials']) !!}</div>
                                    @endif
                                    @if (HtmlContent::has($session['session_summary'] ?? ''))
                                        <h4 class="sn-material-subhead">Tóm tắt buổi học</h4>
                                        <div>{!! HtmlContent::render($session['session_summary']) !!}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="sn-material-group sn-reflection-group">
                            <div class="sn-material-group-head">
                                <span class="sn-material-num">{{ $postSectionIdx++ }}</span>
                                <h3 class="sn-material-group-title">Đánh giá &amp; cảm nhận buổi học</h3>
                            </div>
                            <div class="sunny-app page-session-show page-student-home sw-quiz-sunny sn-reflection-embedded">
                                @include('student.partials.reflection', [
                                    'reflection' => $sessionDetail['reflection'] ?? null,
                                    'publicCode' => $publicCode,
                                ])
                            </div>
                        </div>

                        @if (($sessionDetail['reviewQuestions'] ?? []) !== [])
                            <div class="sn-material-group" id="session-review-quiz">
                                <div class="sn-material-group-head">
                                    <span class="sn-material-num">{{ $postSectionIdx++ }}</span>
                                    <h3 class="sn-material-group-title">Kiểm tra bài cũ (trên lớp)</h3>
                                </div>
                                <div class="sn-material-group-body">
                                    @include('student.partials.quiz-result', [
                                        'title' => 'Kiểm tra bài cũ',
                                        'questions' => $sessionDetail['reviewQuestions'],
                                        'answerMap' => $sessionDetail['reviewAnswerMap'] ?? [],
                                        'result' => $sessionDetail['reviewResult'] ?? null,
                                        'submitted' => (bool) ($sessionDetail['reviewSubmitted'] ?? false),
                                        'readonlyNote' => 'Kết quả kiểm tra bài cũ trong giờ học.',
                                        'figmaCourseResult' => true,
                                    ])
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Pane 2: Bài tập sau buổi (#432:64339 / #432:64356 / #317:12494 / #317:13207) --}}
                    <div class="sn-course-subpane" role="tabpanel" data-subtab-pane="quiz" @if ($defaultPostSubtab !== 'quiz') hidden @endif>
                        @if ($hasQuiz)
                            @include('student.partials.quiz-block', [
                                'quizQuestions' => $postQuestions,
                                'quizDisplay' => $postDisplay,
                                'title' => 'Ôn tập sau buổi học',
                                'lobbyTitle' => 'Ôn luyện sau buổi học',
                                'desc' => count($postQuestions).' câu hỏi ôn tập',
                                'sectionId' => 'session-quiz',
                                'formAction' => route('student.session.post.submit', $publicCode),
                                'autosaveUrl' => $phase === 'ended'
                                    ? route('student.session.post.autosave', $publicCode) : null,
                                'practiceAction' => $postDisplay['hasSavedSubmission']
                                    ? route('student.session.post.practice', $publicCode) : null,
                                'exitAction' => $postDisplay['quizIsPracticeResult']
                                    ? route('student.session.post.exit', $publicCode) : null,
                                'requireStart' => false,
                                'figmaCourse' => true,
                            ])
                        @else
                            <div class="sn-practice-shell">
                                <div class="sn-practice-card">
                                    <div class="sn-practice-mascot">
                                        <img src="{{ asset('assets/images/figma-mascot-fighting.png') }}" alt="" width="146" height="146" loading="lazy">
                                    </div>
                                    <h3 class="sn-practice-title">Ôn luyện sau buổi học</h3>
                                    <p class="sn-practice-empty-desc">Buổi học này chưa có bài tập sau buổi.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        @if ($showKnowledgeReview)
            @if (! $isEnded)
                <div class="sn-course-empty">
                    <span class="sn-course-empty-ico"><i class="fa-solid fa-hourglass-half"></i></span>
                    <p class="sn-course-empty-title">Ôn tập kiến thức bài sẽ mở khi buổi học đã kết thúc.</p>
                </div>
            @elseif (($sessionDetail['knowledgeReviewQuestions'] ?? []) === [])
                <div class="sn-course-empty">
                    <span class="sn-course-empty-ico"><i class="fa-solid fa-lightbulb"></i></span>
                    <p class="sn-course-empty-title">Bài ôn tập kiến thức chưa có câu hỏi hoặc chưa được đồng bộ sang khóa học.</p>
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
                    $krTitle = trim((string) ($knowledgeReview['title'] ?? '')) ?: 'Ôn tập kiến thức bài';
                @endphp
                @include('student.partials.quiz-block', [
                    'quizQuestions' => $sessionDetail['knowledgeReviewQuestions'],
                    'quizDisplay' => $krDisplay,
                    'title' => $krTitle,
                    'lobbyTitle' => $krTitle,
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
                    'figmaCourse' => true,
                ])
            @endif
        @endif
    </section>
@endif
