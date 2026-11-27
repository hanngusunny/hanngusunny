@extends('layouts.app', ['active' => 'assessments'])

@php
    $phase = (string) ($assessment['phase'] ?? 'upcoming');
    $hasSchedule = ! empty($display['hasSchedule']);
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
    $courseTitle = trim((string) ($course->title ?? ''));
    $isRetake = ! empty($allowRetake) && ! empty($hasSubmitted);
    $courseUrl = isset($navService) && auth()->check()
        ? $navService->dashboardUrlForStudent((int) auth()->id())
        : route('dashboard');

    $attemptLabel = $isRetake ? 'Làm lại' : 'Lần làm 1/1';
    $scheduleShort = $hasSchedule
        ? trim(implode(' · ', array_filter([$display['dateLabel'] ?? '', $display['timeLabel'] ?? ''])))
        : '';
    $focusSubtitle = implode(' · ', array_filter([$courseTitle, $scheduleShort, $attemptLabel]));
@endphp

@section('title', $title.' · Bài thi')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/session-quiz.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/student-quiz-office.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/student-assessment.css') }}?v=2">
@endpush

@section('content')
@if ($showQuizStart)
    {{-- Màn 1: Phòng chờ bài thi chuẩn Figma (#708:58741) --}}
    <div class="sn-exam-page sn-exam-waiting-page" id="assessment-quiz">
        <section class="sn-exam-surface sn-exam-waiting-surface">
            <div class="sn-exam-waiting-top">
                <a class="sn-exam-back-btn" href="{{ $hubUrl }}" aria-label="Quay lại danh sách bài thi" title="Quay lại danh sách bài thi">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14.5 6.5L9 12L14.5 17.5" stroke="#313B47" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            @if (session('error'))
                <div class="sn-exam-flash sn-exam-flash--error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if (session('success') || session('info'))
                <div class="sn-exam-flash sn-exam-flash--info" role="status">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>{{ session('success') ?: session('info') }}</span>
                </div>
            @endif

            <div class="sn-exam-waiting-body">
                <div class="sn-exam-waiting-header">
                    <h1 class="sn-exam-heading">{{ $title }}</h1>
                    <div class="sn-exam-pills">
                        <span class="sn-exam-pill sn-exam-pill--blue">
                            {{ $quizHasTimeLimit ? $quizDurationMinutes.' phút' : 'Không giới hạn' }}
                        </span>
                        <span class="sn-exam-pill sn-exam-pill--green">
                            {{ $totalQuestions }} câu hỏi
                        </span>
                        <span class="sn-exam-pill sn-exam-pill--orange">
                            {{ $isRetake ? 'Được phép làm lại' : 'Chỉ 1 lần làm bài' }}
                        </span>
                    </div>
                </div>

                <div class="sn-exam-waiting-grid">
                    <div class="sn-exam-rule-card sn-exam-rule-card--prep">
                        <div class="sn-exam-float-pill sn-exam-float-pill--blue">Chuẩn bị trước khi bắt đầu</div>
                        <ul class="sn-exam-rule-list">
                            <li>Chuẩn bị thiết bị có kết nối Internet ổn định và tai nghe. Kiểm tra âm thanh trước khi vào bài.</li>
                            <li>Không sử dụng từ điển hoặc công cụ dịch trong suốt thời gian làm bài.</li>
                            <li>Câu trả lời được tự động lưu. Theo dõi trạng thái lưu và kiểm tra lại khi mất kết nối.</li>
                            @if ($isRetake)
                                <li>Giáo viên cho phép làm lại. Câu đã điền ở bản nháp hoặc bài nộp trước sẽ được điền sẵn và kết quả mới sẽ thay thế bài cũ.</li>
                            @elseif ($quizHasTimeLimit)
                                <li>Em có {{ $quizDurationMinutes }} phút cho bài thi này và chỉ được làm 1 lần. Không thể làm lại sau khi nộp.</li>
                            @else
                                <li>Bài thi này chỉ được làm 1 lần. Không thể chỉnh sửa hoặc làm lại sau khi nộp.</li>
                            @endif
                            <li>
                                Kiểm tra đủ {{ $totalQuestions }} câu hỏi trước khi nộp bài.
                                @if ($quizHasTimeLimit)
                                    Khi hết giờ, hệ thống sẽ tự động nộp kể cả khi còn câu trống.
                                @endif
                            </li>
                            <li>Đáp án và điểm chỉ hiển thị sau khi giáo viên chấm xong và công bố kết quả.</li>
                        </ul>

                        @if ($description !== '')
                            <details class="sn-exam-guide-box">
                                <summary>
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    <span>Hướng dẫn từ giáo viên</span>
                                </summary>
                                <div class="course-rich-content sn-exam-guide-content">{!! \App\Support\HtmlContent::render($description) !!}</div>
                            </details>
                        @endif
                    </div>

                    <div class="sn-exam-rule-card sn-exam-rule-card--notice">
                        <div class="sn-exam-float-pill sn-exam-float-pill--red">Lưu ý</div>
                        @if ($quizHasTimeLimit)
                            <p class="sn-exam-notice-text">
                                Đồng hồ bắt đầu đếm ngay khi bạn bấm bắt đầu. Không rời khỏi hoặc tải lại trang trong khi làm bài; khi hết {{ $quizDurationMinutes }} phút, hệ thống sẽ tự động nộp bài của bạn!
                            </p>
                        @else
                            <p class="sn-exam-notice-text">
                                Không rời khỏi hoặc tải lại trang trong khi đang làm bài để đảm bảo mọi câu trả lời của bạn được lưu đầy đủ và an toàn!
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="sn-exam-waiting-footer">
                <button type="button" class="sn-exam-btn sn-exam-btn--primary" data-exam-open-start-modal>
                    {{ $isRetake ? 'Bắt đầu làm lại' : 'Bắt đầu làm bài' }}
                </button>
            </div>
        </section>

        {{-- Modal Xác nhận bắt đầu bài thi chuẩn Figma (#710:72346) --}}
        <div
            class="sn-exam-modal-backdrop"
            id="snExamStartModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="snExamStartModalTitle"
            hidden
        >
            <div class="sn-exam-modal-dialog sn-exam-modal-dialog--start">
                <div class="sn-exam-modal-head-start">
                    <h2 class="sn-exam-modal-title" id="snExamStartModalTitle">Xác nhận bắt đầu bài thi</h2>
                    <ul class="sn-exam-modal-bullets">
                        <li>Chuẩn bị thiết bị, tai nghe sẵn sàng.</li>
                        <li>Thời gian làm bài: {{ $quizHasTimeLimit ? $quizDurationMinutes.' phút' : 'Không giới hạn' }}.</li>
                        <li>Tuyệt đối không sử dụng từ điển, công cụ dịch.</li>
                    </ul>
                </div>

                <div class="sn-exam-modal-mascot">
                    <img src="{{ asset('assets/images/figma-mascot-fighting.png') }}" alt="Sẵn sàng bắt đầu" width="160" height="160" loading="eager">
                </div>

                <form method="post" action="{{ $startAction }}" class="sn-exam-modal-actions" id="snExamStartForm">
                    @csrf
                    <button type="submit" class="sn-exam-btn sn-exam-btn--primary sn-exam-btn--full" id="snExamStartSubmitBtn">
                        Đồng ý, bắt đầu
                    </button>
                    <button type="button" class="sn-exam-btn sn-exam-btn--white sn-exam-btn--full" data-exam-close-start-modal>
                        Chưa bắt đầu
                    </button>
                </form>
            </div>
        </div>
    </div>

@elseif ($canShowQuizForm)
    {{-- Màn 2: Làm bài tập trung chuẩn Figma (#708:58850, #708:58863, #468:39410) --}}
    <div class="sn-exam-focus-mode" id="assessment-quiz">
        <div class="sn-exam-focus-surface">
            <header class="sn-exam-focus-header">
                <span class="sn-exam-pill sn-exam-pill--blue">
                    {{ $isRetake ? 'Làm lại bài thi' : 'Bài kiểm tra' }}
                </span>
                <div class="sn-exam-focus-titles">
                    <h1 class="sn-exam-focus-title">{{ $title }}</h1>
                    @if ($focusSubtitle !== '')
                        <p class="sn-exam-focus-sub">{{ $focusSubtitle }}</p>
                    @endif
                </div>
            </header>

            @if (session('error'))
                <div class="sn-exam-flash sn-exam-flash--error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if (session('success') || session('info'))
                <div class="sn-exam-flash sn-exam-flash--info" role="status">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>{{ session('success') ?: session('info') }}</span>
                </div>
            @endif

            <div class="sn-exam-take-layout sw-quiz">
                <div class="sn-exam-take-main sunny-app page-session-show page-assessment-show sw-quiz-sunny sn-quiz-sunny-clean">
                    <form
                        method="post"
                        action="{{ $submitAction }}"
                        class="js-student-quiz session-quiz-form sn-exam-quiz-form"
                        id="snExamQuizForm"
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
                        data-sn-figma-quiz-form
                        data-exam-total="{{ $totalQuestions }}"
                        data-exam-per-page="5"
                    >
                        @csrf
                        <input type="hidden" name="timed_out" value="0" class="js-timed-out">

                        @include('student.partials.quiz-form', [
                            'quizQuestions' => $questions,
                            'savedAnswers' => $draftAnswers ?? [],
                            'showPoints' => true,
                        ])

                        <div class="sn-exam-take-footer">
                            <nav class="sn-exam-pagination" id="snExamPagination" aria-label="Chuyển trang câu hỏi" hidden></nav>
                            <div class="sn-exam-footer-actions">
                                <button type="submit" class="sn-exam-btn sn-exam-btn--primary sn-exam-submit-cta">
                                    <span>{{ $isRetake ? 'Nộp bài làm lại' : 'Nộp bài' }}</span>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" fill="#FFFFFF"/>
                                        <path d="M10.2 8.4L14 12L10.2 15.6" stroke="#FF7614" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="sn-exam-take-aside" aria-label="Điều hướng câu hỏi và thời gian">
                    <div class="sn-exam-palette-card">
                        <div class="sn-exam-palette-head">
                            <h2 class="sn-exam-palette-title">Câu trả lời</h2>
                            <p class="sn-exam-palette-sub">Chọn số câu để chuyển nhanh</p>
                        </div>

                        <div class="sn-exam-palette-grid" id="snExamPaletteGrid">
                            @for ($qIndex = 1; $qIndex <= $totalQuestions; $qIndex++)
                                <button
                                    type="button"
                                    class="sn-exam-q-num{{ $qIndex === 1 ? ' is-current' : '' }}"
                                    data-exam-jump="{{ $qIndex }}"
                                    aria-label="Đi đến câu {{ $qIndex }}"
                                >{{ $qIndex }}</button>
                            @endfor
                        </div>

                        <div class="sn-exam-progress-box">
                            <div class="sn-exam-progress-label">
                                Tiến độ · <strong id="snExamProgressText">0/{{ $totalQuestions }}</strong> câu
                            </div>
                            <div class="sn-exam-progress-track" aria-hidden="true">
                                <div class="sn-exam-progress-fill" id="snExamProgressFill" style="width: 0%"></div>
                            </div>
                        </div>

                        @if ($enableQuizTimer)
                            <div
                                class="sn-exam-timer-card"
                                data-quiz-timer
                                data-expires-ts="{{ (int) ($assessment['expires_ts'] ?? 0) }}"
                                data-remaining="{{ (int) ($assessment['remaining_seconds'] ?? 0) }}"
                            >
                                <span class="sn-exam-timer-ico" aria-hidden="true">
                                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                                        <path d="M4.5 11.5H9.5M3 16H8M5 20.5H9.5" stroke="#FF7614" stroke-width="2" stroke-linecap="round"/>
                                        <circle cx="19" cy="16" r="9" fill="#FFF1E6" stroke="#E05A2B" stroke-width="2.2"/>
                                        <path d="M19 11.5V16.2L22.2 18" stroke="#E05A2B" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div class="sn-exam-timer-copy">
                                    <span class="sn-exam-timer-title">Thời gian còn lại</span>
                                    <strong class="sn-exam-timer-value" data-timer-label>—</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </div>

        {{-- Modal Làm Bài Chuẩn Figma: Xác nhận nộp bài (#468:39488), Còn câu trống (#468:39470), Hết thời gian (#468:39427) --}}
        <div
            class="sn-exam-modal-backdrop"
            id="snExamActionModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="snExamActionModalTitle"
            hidden
            data-img-submit="{{ asset('assets/images/figma-mascot-submit.png') }}"
            data-img-incomplete="{{ asset('assets/images/figma-mascot-incomplete.png') }}"
            data-img-timeout="{{ asset('assets/images/figma-mascot-timeout.png') }}"
        >
            <div class="sn-exam-modal-dialog sn-exam-modal-dialog--action" id="snExamActionDialog">
                <div class="sn-exam-modal-copy">
                    <h2 class="sn-exam-modal-title" id="snExamActionModalTitle">Xác nhận nộp bài?</h2>
                    <p class="sn-exam-modal-desc" id="snExamActionModalDesc"></p>
                </div>
                <div class="sn-exam-modal-mascot" id="snExamActionMascotWrap">
                    <img id="snExamActionModalImg" src="{{ asset('assets/images/figma-mascot-submit.png') }}" alt="" width="160" height="160">
                </div>
                <div class="sn-exam-modal-actions" id="snExamActionModalButtons">
                    <button type="button" class="sn-exam-btn sn-exam-btn--primary sn-exam-btn--full" id="snExamActionPrimaryBtn">
                        <span id="snExamActionPrimaryLabel">Nộp bài</span>
                        <span class="sn-exam-btn-ico" id="snExamActionPrimaryIco" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="sn-exam-btn sn-exam-btn--white sn-exam-btn--full" id="snExamActionSecondaryBtn">
                        <span id="snExamActionSecondaryLabel">Tiếp tục làm bài</span>
                        <span class="sn-exam-btn-ico" id="snExamActionSecondaryIco" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Overlay Đang Nộp Bài Chuẩn Figma (#709:63580) --}}
        <div class="sn-exam-submitting-overlay" id="snExamSubmittingOverlay" role="status" aria-live="assertive" hidden>
            <div class="sn-exam-submitting-card">
                <div class="sn-exam-submitting-head">
                    <h2 class="sn-exam-heading">{{ $title }}</h2>
                    @if ($focusSubtitle !== '')
                        <p class="sn-exam-submitting-sub">{{ $focusSubtitle }}</p>
                    @endif
                </div>

                <div class="sn-exam-submitting-meta">
                    <span class="sn-exam-submitting-pill">
                        {{ $quizHasTimeLimit ? $quizDurationMinutes.' phút · ' : '' }}{{ $totalQuestions }} câu hỏi
                    </span>
                    @if ($enableQuizTimer)
                        <strong class="sn-exam-submitting-clock" id="snExamSubmittingClock">—</strong>
                    @endif
                </div>

                <div class="sn-exam-submitting-center">
                    <div class="sn-exam-loader-badge" aria-hidden="true">
                        <svg class="sn-exam-spinner" width="46" height="46" viewBox="0 0 46 46" fill="none">
                            <rect x="21" y="4" width="4" height="10" rx="2" fill="#FF7614"/>
                            <rect x="21" y="32" width="4" height="10" rx="2" fill="#FF7614" opacity="0.35"/>
                            <rect x="4" y="21" width="10" height="4" rx="2" fill="#FF7614" opacity="0.7"/>
                            <rect x="32" y="21" width="10" height="4" rx="2" fill="#FF7614" opacity="0.25"/>
                            <rect x="8.8" y="11.6" width="4" height="10" rx="2" transform="rotate(-45 8.8 11.6)" fill="#FF7614" opacity="0.85"/>
                            <rect x="28.6" y="31.4" width="4" height="10" rx="2" transform="rotate(-45 28.6 31.4)" fill="#FF7614" opacity="0.45"/>
                            <rect x="34.4" y="8.8" width="4" height="10" rx="2" transform="rotate(45 34.4 8.8)" fill="#FF7614" opacity="0.18"/>
                            <rect x="14.6" y="28.6" width="4" height="10" rx="2" transform="rotate(45 14.6 28.6)" fill="#FF7614" opacity="0.58"/>
                        </svg>
                    </div>
                    <h3 class="sn-exam-submitting-title">Đang nộp bài</h3>
                    <p class="sn-exam-submitting-desc" id="snExamSubmittingDesc">
                        Đang gửi {{ $totalQuestions }}/{{ $totalQuestions }} câu trả lời. Vui lòng chờ, không đóng trang hoặc nộp lại để tránh gửi trùng.
                    </p>
                </div>
            </div>
        </div>
    </div>

@elseif ($pendingReview)
    {{-- Màn 3: Hoàn thành bài thi — chờ giáo viên chấm chuẩn Figma (#710:72370) --}}
    <div class="sn-exam-page sn-exam-done-page" id="assessment-quiz">
        <section class="sn-exam-surface sn-exam-done-surface">
            <div class="sn-exam-done-mascot">
                <img
                    src="{{ asset('assets/images/figma-mascot-exam-done.png') }}"
                    alt="Bạn đã hoàn thành bài thi"
                    width="288"
                    height="234"
                    loading="eager"
                >
            </div>

            <div class="sn-exam-done-copy">
                <h1 class="sn-exam-done-title">Bạn đã hoàn thành bài thi</h1>
                <p class="sn-exam-done-desc">
                    Hệ thống đã nhận bài thi của bạn. Kết quả sẽ có sau khi giáo viên hoàn tất việc chấm điểm
                </p>

                <div class="sn-exam-done-pill">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3.2C8.7 3.2 6.2 5.7 6.2 9V11.8C6.2 12.6 5.9 13.5 5.4 14.2L4.3 15.8C3.7 16.7 4.2 18 5.3 18.2C9.7 19.1 14.3 19.1 18.7 18.2C19.8 18 20.3 16.7 19.7 15.8L18.6 14.2C18.1 13.5 17.8 12.6 17.8 11.8V9C17.8 5.7 15.3 3.2 12 3.2Z" fill="#7C5CFF"/>
                        <path d="M12 7.2V10.4" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M9.6 19.8C10.1 20.9 11 21.5 12 21.5C13 21.5 13.9 20.9 14.4 19.8" stroke="#7C5CFF" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <span>Bạn sẽ nhận thông báo khi có kết quả</span>
                </div>

                @if (! empty($submissionMeta?->submitted_at))
                    <p class="sn-exam-done-meta">
                        {{ $title }} · Nộp lúc <strong>{{ $submissionMeta->submitted_at->format('H:i d/m/Y') }}</strong>
                    </p>
                @endif
            </div>

            <div class="sn-exam-done-actions">
                <a class="sn-exam-btn sn-exam-btn--primary" href="{{ $courseUrl }}">Quay về khoá học</a>
                <a class="sn-exam-done-secondary-link" href="{{ $hubUrl }}">Về danh sách bài thi</a>
            </div>
        </section>
    </div>

@elseif ($submitted && $result !== null)
    {{-- Màn 4: Kết quả bài thi sau khi giáo viên đã chấm --}}
    <div class="sn-exam-page sn-exam-result-page" id="assessment-quiz">
        <section class="sn-exam-surface sn-exam-result-surface">
            <div class="sn-exam-result-top">
                <div class="sn-exam-result-head-left">
                    <a class="sn-exam-back-btn" href="{{ $hubUrl }}" aria-label="Quay lại danh sách bài thi" title="Quay lại danh sách bài thi">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14.5 6.5L9 12L14.5 17.5" stroke="#313B47" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <div class="sn-exam-result-titles">
                        <h1 class="sn-exam-heading">{{ $title }}</h1>
                        <div class="sn-exam-pills">
                            <span class="sn-exam-pill sn-exam-pill--blue">Đã có kết quả</span>
                            @if ($quizHasTimeLimit)
                                <span class="sn-exam-pill sn-exam-pill--muted">{{ $quizDurationMinutes }} phút</span>
                            @endif
                            <span class="sn-exam-pill sn-exam-pill--green">{{ $totalQuestions }} câu hỏi</span>
                            @if ($scheduleShort !== '')
                                <span class="sn-exam-pill sn-exam-pill--muted">{{ $scheduleShort }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($pointsPossible > 0)
                    <div class="sn-exam-score-chip">
                        <strong>{{ $submissionService->formatPoints($pointsEarned) }}/{{ $submissionService->formatPoints($pointsPossible) }}</strong>
                        <span>{{ $scorePct }}%</span>
                    </div>
                @endif
            </div>

            @if (session('error'))
                <div class="sn-exam-flash sn-exam-flash--error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if (session('success') || session('info'))
                <div class="sn-exam-flash sn-exam-flash--info" role="status">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>{{ session('success') ?: session('info') }}</span>
                </div>
            @endif

            <div class="sn-exam-result-body">
                @include('student.partials.quiz-result', [
                    'title' => 'Kết quả bài kiểm tra',
                    'questions' => $questions,
                    'answerMap' => $answerMap,
                    'result' => $result,
                    'submitted' => true,
                    'showPoints' => true,
                ])
            </div>
        </section>
    </div>

@else
    {{-- Màn 5: Trạng thái phụ (Đã đóng / Hết giờ / Chưa mở / Chưa có câu hỏi) --}}
    <div class="sn-exam-page sn-exam-state-page" id="assessment-quiz">
        <section class="sn-exam-surface sn-exam-state-surface">
            <div class="sn-exam-waiting-top">
                <a class="sn-exam-back-btn" href="{{ $hubUrl }}" aria-label="Quay lại danh sách bài thi" title="Quay lại danh sách bài thi">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14.5 6.5L9 12L14.5 17.5" stroke="#313B47" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            <div class="sn-exam-state-center">
                @if (($questions ?? []) === [])
                    <img src="{{ asset('assets/images/figma-mascot-fighting.png') }}" alt="" width="160" height="160">
                    <h1 class="sn-exam-done-title">{{ $title }}</h1>
                    <p class="sn-exam-done-desc">Giáo viên chưa thêm câu hỏi cho bài kiểm tra này.</p>
                @elseif ($quizHasTimeLimit && $quizIsExpired && empty($hasSubmitted) && $quizAttemptStarted)
                    <img src="{{ asset('assets/images/figma-mascot-timeout.png') }}" alt="" width="240" height="160">
                    <h1 class="sn-exam-done-title sn-exam-done-title--danger">Đã hết thời gian làm bài</h1>
                    <p class="sn-exam-done-desc">
                        Bạn đã hết {{ $quizDurationMinutes }} phút làm bài cho <strong>{{ $title }}</strong>. Hệ thống sẽ tự nộp bài nếu chưa nộp.
                    </p>
                @elseif ($isClosed)
                    <img src="{{ asset('assets/images/figma-mascot-pause.png') }}" alt="" width="160" height="160">
                    <h1 class="sn-exam-done-title">{{ $title }}</h1>
                    <p class="sn-exam-done-desc">Bài kiểm tra đã kết thúc. Bạn không thể nộp bài.</p>
                @else
                    <img src="{{ asset('assets/images/figma-mascot-fighting.png') }}" alt="" width="160" height="160">
                    <h1 class="sn-exam-done-title">{{ $title }}</h1>
                    <p class="sn-exam-done-desc">Bài kiểm tra chưa mở. Vui lòng quay lại đúng giờ theo lịch khóa học.</p>
                @endif

                <div class="sn-exam-pills sn-exam-pills--center">
                    @if ($quizHasTimeLimit)
                        <span class="sn-exam-pill sn-exam-pill--blue">{{ $quizDurationMinutes }} phút</span>
                    @endif
                    <span class="sn-exam-pill sn-exam-pill--green">{{ $totalQuestions }} câu hỏi</span>
                    @if ($scheduleShort !== '')
                        <span class="sn-exam-pill sn-exam-pill--muted">{{ $scheduleShort }}</span>
                    @endif
                </div>

                <div class="sn-exam-done-actions">
                    <a class="sn-exam-btn sn-exam-btn--primary" href="{{ $hubUrl }}">Quay lại danh sách bài thi</a>
                </div>
            </div>
        </section>
    </div>
@endif
@endsection

@push('scripts')
    @if ($canShowQuizForm)
        <script src="{{ asset('assets/js/assessment-offline-queue.js') }}?v=2"></script>
    @endif
    @include('student.partials.quiz-scripts')
    <link rel="stylesheet" href="{{ asset('assets/css/student-assessment.css') }}?v=2">
    @if ($canShowQuizForm)
        <script src="{{ asset('assets/js/assessment-leave-guard.js') }}?v=1"></script>
        <script src="{{ asset('assets/js/network-offline-banner.js') }}?v=1"></script>
    @endif
    @if ($showQuizStart || $canShowQuizForm)
        <script src="{{ asset('assets/js/student-assessment.js') }}?v=1"></script>
    @endif
@endpush
