@php
    $item = is_array($selectedItem ?? null) ? $selectedItem : null;
@endphp

@if ($item === null)
    <article class="op-panel teacher-grading-detail-card teacher-grading-detail-card--empty">
        <div class="teacher-grading-detail-empty">
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            <h2>Chọn bài để chấm</h2>
            <p>Danh sách bên trái hiển thị các câu cần chấm theo từng loại bài.</p>
        </div>
    </article>
@else
    @php
        $parser = app(\App\Support\Questions\QuestionParser::class);
        $answerRow = is_array($item['answer_row'] ?? null) ? $item['answer_row'] : [];
        $questionId = (int) ($item['question_id'] ?? 0);
        $selected = trim((string) ($item['selected_answer'] ?? ''));
        $isGraded = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';
        $isCorrect = ! empty($answerRow['is_correct']);
        $comment = (string) ($answerRow['teacher_comment'] ?? '');
        $gradedAt = (string) ($answerRow['teacher_graded_at'] ?? '');
        $commentHtml = \App\Support\HtmlContent::has($comment)
            ? \App\Support\HtmlContent::sanitize($comment)
            : '';
        $submitted = trim((string) ($item['submitted_at'] ?? ''));
        $questionType = (string) ($item['question_type'] ?? '');
        $isWordTranslate = $questionType === 'word_translate';
        $isLegacyPhatAmRecording = $questionType === 'phat_am' && $parser->hasPhatAmRecordingInSubmission($selected);
        $isPhatAm = $questionType === 'phat_am' && $isLegacyPhatAmRecording;

        $questionRow = $parser->normalizeQuestionRowForEdit([
            'question_type' => $questionType,
            'options_json' => (string) ($item['options_json'] ?? ''),
            'question_text' => (string) ($item['question_html'] ?? ''),
            'correct_answer' => (string) ($item['correct_answer'] ?? ''),
        ]);
        $questionType = (string) ($questionRow['question_type'] ?? $questionType);
        $questionType = $questionType === 'text' || $questionType === 'essay' ? 'fill' : $questionType;
        $isListening = $questionType === 'listening';
        $hasCompositeFill = $parser->hasCompositeFillItems($questionRow);
        $isPhatAm = $isPhatAm && ! $hasCompositeFill;
        $answerLabels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F'];

        $listeningConfig = null;
        $listeningQuestions = [];
        $listeningSubmittedAnswers = [];
        $listeningAudioUrl = '';
        $listeningLegacyText = '';

        if ($isListening) {
            $listeningConfig = $parser->decodeListeningConfig($questionRow);
            $listeningBundle = $parser->decodeListeningBundle($questionRow);
            $listeningQuestions = $listeningBundle['questions'] ?? [];
            $listeningSubmittedAnswers = $parser->decodeListeningStudentAnswersPayload($selected);
            $listeningAudioUrl = $parser->resolveListeningAudioUrl((string) ($listeningConfig['audio'] ?? ''));
            $listeningLegacyText = trim((string) ($listeningConfig['text'] ?? ''));
        }

        $phatAmPrompt = '';
        $phatAmQuestions = [];
        $phatAmSubmittedAnswers = [];
        $phatAmRecordingPath = '';
        $phatAmRequireRecording = true;
        $phatAmGameCompleted = false;
        $showPhatAmStructured = $questionType === 'phat_am' && ! $isPhatAm;

        if ($showPhatAmStructured) {
            $phatAmBundle = $parser->decodePhatAmBundle($questionRow);
            $phatAmPrompt = trim((string) ($phatAmBundle['prompt'] ?? ''));
            $phatAmQuestions = $phatAmBundle['questions'] ?? [];
            $phatAmRequireRecording = $parser->normalizePhatAmRequireRecording($phatAmBundle['require_recording'] ?? true);
            $phatAmSubmittedAnswers = $parser->decodePhatAmStudentAnswersPayload($selected);
            $phatAmRecordingPath = $parser->decodePhatAmStudentRecording($selected);
            $phatAmGameCompleted = $parser->phatAmStudentSubmissionIsComplete($questionRow, $selected);
        }

        $gradeFormTitle = match (true) {
            $isListening => 'Chấm bài nghe đáp',
            $showPhatAmStructured => 'Chấm bài phát âm',
            default => 'Chấm bài tự luận',
        };
        $tabTone = match ((string) ($item['tab'] ?? $activeTab ?? '')) {
            \App\Services\TeacherGradingService::TAB_POST => 'post',
            \App\Services\TeacherGradingService::TAB_KNOWLEDGE_REVIEW => 'kr',
            default => 'pre',
        };
        $sessionId = (int) ($item['session_id'] ?? 0);
        $itemCourseId = (int) ($item['course_id'] ?? $courseId);
        $itemUserId = (int) ($item['user_id'] ?? 0);
        $studentUrl = ($sessionId > 0 && $itemCourseId > 0 && $itemUserId > 0)
            ? route('teacher.course.student', [
                'id' => $itemCourseId,
                'sessionId' => $sessionId,
                'userId' => $itemUserId,
                'muc' => $activeTab,
            ])
            : '';
    @endphp

    <article class="op-panel teacher-grading-detail-card">
        <header class="teacher-grading-detail-head">
            <div class="teacher-grading-detail-student">
                <x-user-avatar
                    class="teacher-grading-detail-avatar"
                    :url="$item['student_avatar'] ?? null"
                    :name="$item['student_name'] ?? ''"
                />
                <div>
                    <h2>{{ $item['student_name'] ?? 'Học viên' }}</h2>
                    <p>
                        <span class="teacher-grading-detail-tag teacher-grading-detail-tag--{{ $tabTone }}">{{ $item['tab_label'] ?? '' }}</span>
                        {{ $item['course_title'] ?? '' }}
                        · {{ $item['assignment_label'] ?? '' }}
                    </p>
                </div>
            </div>
            @if ($studentUrl !== '')
                <a class="op-btn-ghost teacher-grading-session-link" href="{{ $studentUrl }}">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Xem buổi học</span>
                </a>
            @endif
        </header>

        <div class="teacher-grading-detail-body">
            <section class="teacher-grading-question-block">
                <h3><i class="fa-regular fa-circle-question"></i> Câu hỏi</h3>
                <div class="course-rich-content session-quiz-question">
                    {!! \App\Support\HtmlContent::render((string) ($item['question_html'] ?? '')) ?: '<span class="muted">Không có nội dung câu hỏi.</span>' !!}
                </div>
                @if ($isListening && is_array($listeningConfig))
                    @if ($listeningAudioUrl !== '')
                        <div class="teacher-grading-listening-audio">
                            <span class="teacher-grading-result-label"><i class="fa-solid fa-volume-high"></i> File nghe</span>
                            <audio controls preload="metadata" src="{{ $listeningAudioUrl }}"></audio>
                        </div>
                    @elseif ($listeningLegacyText !== '')
                        <div class="teacher-grading-listening-audio">
                            <span class="teacher-grading-result-label"><i class="fa-solid fa-volume-high"></i> Nội dung đã nghe</span>
                            <div class="course-rich-content">{!! \App\Support\HtmlContent::render($listeningLegacyText) !!}</div>
                        </div>
                    @endif
                @endif
            </section>

            @if ($isListening)
                @include('teacher.partials.grading-sub-items', [
                    'items' => $listeningQuestions,
                    'submittedAnswers' => $listeningSubmittedAnswers,
                    'parser' => $parser,
                    'answerLabels' => $answerLabels,
                    'teacherReviewMode' => true,
                ])
            @elseif ($showPhatAmStructured)
                <div class="session-phat-am-result">
                    @if ($phatAmGameCompleted)
                        <p class="teacher-grading-result-summary is-done">
                            <i class="fa-solid fa-circle-check"></i>
                            Học viên đã hoàn thành {{ count($phatAmQuestions) }} câu hỏi
                        </p>
                    @else
                        <p class="teacher-grading-result-summary is-pending">Học viên chưa hoàn thành đủ câu hỏi.</p>
                    @endif
                    @if ($phatAmPrompt !== '')
                        <div class="session-phat-am-sample session-phat-am-sample--shared">
                            <span class="session-phat-am-sample-label"><i class="fa-solid fa-quote-left"></i> Câu mẫu</span>
                            <div class="session-phat-am-sample-box">
                                <div class="session-phat-am-sample-text course-rich-content">{!! \App\Support\HtmlContent::render($phatAmPrompt) !!}</div>
                            </div>
                        </div>
                    @endif
                    @if ($phatAmRequireRecording || $phatAmRecordingPath !== '')
                        <div class="session-phat-am-result-block session-phat-am-result-block--shared">
                            <span class="session-phat-am-result-label"><i class="fa-solid fa-microphone"></i> Bản ghi học viên</span>
                            @if ($phatAmRecordingPath !== '')
                                @include('teacher.partials.phat-am-player', ['audioUrl' => $parser->resolvePhatAmRecordingUrl($phatAmRecordingPath)])
                            @else
                                <p class="muted">Chưa có bản ghi.</p>
                            @endif
                        </div>
                    @endif
                    @include('teacher.partials.grading-sub-items', [
                        'items' => $phatAmQuestions,
                        'submittedAnswers' => $phatAmSubmittedAnswers,
                        'parser' => $parser,
                        'answerLabels' => $answerLabels,
                        'teacherReviewMode' => true,
                    ])
                </div>
            @elseif (! $isWordTranslate && ! $isPhatAm)
                <div class="session-quiz-essay-results">
                    <div class="session-quiz-essay-block session-quiz-essay-block--yours teacher-grading-essay-block">
                        <div class="session-quiz-essay-label teacher-grading-result-label"><i class="fa-solid fa-pen-to-square"></i> Câu trả lời học viên</div>
                        <div class="course-rich-content session-quiz-essay-text js-essay-answer-images">
                            {!! $selected !== '' ? \App\Support\HtmlContent::render($selected) : '<span class="muted">Chưa trả lời</span>' !!}
                        </div>
                    </div>
                </div>
            @endif

            @if ($submitted !== '')
                <p class="teacher-grading-detail-submitted">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Học viên nộp lúc {{ date('H:i d/m/Y', strtotime($submitted)) }}
                </p>
            @endif

            @if ($isWordTranslate)
                @include('teacher.partials.grading-word-translate', [
                    'questionId' => $questionId,
                    'question' => [
                        'question_type' => $questionType,
                        'options_json' => (string) ($item['options_json'] ?? ''),
                    ],
                    'answerRow' => $answerRow,
                    'selectedJson' => $selected,
                    'courseId' => $courseId,
                    'activeTab' => $activeTab,
                    'selectedKey' => $selectedKey,
                ])
            @elseif ($hasCompositeFill)
                @include('teacher.partials.grading-composite-fill', [
                    'questionId' => $questionId,
                    'question' => $questionRow,
                    'answerRow' => $answerRow,
                    'selectedJson' => $selected,
                    'courseId' => $courseId,
                    'activeTab' => $activeTab,
                    'selectedKey' => $selectedKey,
                    'showInboxFields' => true,
                ])
            @elseif ($isPhatAm)
                @include('teacher.partials.grading-phat-am', [
                    'questionId' => $questionId,
                    'question' => [
                        'question_type' => $questionType,
                        'options_json' => (string) ($item['options_json'] ?? ''),
                    ],
                    'answerRow' => $answerRow,
                    'selectedJson' => $selected,
                    'courseId' => $courseId,
                    'activeTab' => $activeTab,
                    'selectedKey' => $selectedKey,
                ])
            @else
                <section class="teacher-essay-grade-edit{{ $isGraded ? ' teacher-essay-grade-edit--graded is-graded' : '' }}" aria-label="{{ $isGraded ? 'Chỉnh sửa chấm bài' : 'Chấm bài' }}">
                    <div class="teacher-essay-grade-edit-head">
                        <div>
                            <h4><i class="fa-solid fa-pen-to-square"></i> {{ $isGraded ? 'Chỉnh sửa chấm bài' : $gradeFormTitle }}</h4>
                            @if ($isGraded)
                                <p class="teacher-essay-grade-edit-hint">Bạn có thể đổi Đúng/Sai và cập nhật nhận xét bất cứ lúc nào.</p>
                            @endif
                        </div>
                        @if ($isGraded)
                            <span class="teacher-essay-grade-status teacher-essay-grade-status--done is-done"><i class="fa-solid fa-circle-check"></i> Đã chấm</span>
                        @else
                            <span class="teacher-essay-grade-status teacher-essay-grade-status--pending is-pending">Chưa chấm</span>
                        @endif
                    </div>

                    <form method="post" action="{{ route('teacher.grading.save') }}" class="teacher-essay-grade-form">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $courseId }}">
                        <input type="hidden" name="muc" value="{{ $activeTab }}">
                        <input type="hidden" name="bai" value="{{ $selectedKey }}">

                        <div class="teacher-essay-grade-options" role="radiogroup" aria-label="Kết quả chấm bài">
                            <span class="teacher-essay-grade-options-label">Kết quả chấm</span>
                            <div class="teacher-essay-grade-options-grid">
                                <label class="teacher-essay-grade-option teacher-essay-grade-option--correct is-correct">
                                    <input type="radio" name="is_correct" value="1"{{ $isGraded && $isCorrect ? ' checked' : '' }} required>
                                    <span class="teacher-essay-grade-option-card">
                                        <span class="teacher-essay-grade-option-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                                        <span class="teacher-essay-grade-option-text">
                                            <strong>Đúng</strong>
                                            <small>Câu trả lời đạt yêu cầu</small>
                                        </span>
                                    </span>
                                </label>
                                <label class="teacher-essay-grade-option teacher-essay-grade-option--wrong is-wrong">
                                    <input type="radio" name="is_correct" value="0"{{ $isGraded && ! $isCorrect ? ' checked' : '' }} required>
                                    <span class="teacher-essay-grade-option-card">
                                        <span class="teacher-essay-grade-option-icon" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                                        <span class="teacher-essay-grade-option-text">
                                            <strong>Sai</strong>
                                            <small>Cần làm lại hoặc chưa đúng</small>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="teacher-essay-grade-comment">
                            <label class="form-label" for="teacher_grading_comment">Đánh giá / nhận xét</label>
                            <textarea
                                class="form-control js-assessment-comment-editor"
                                id="teacher_grading_comment"
                                name="teacher_comment"
                                rows="4"
                                placeholder="Nhận xét cho học viên về câu trả lời này..."
                            >{{ $commentHtml }}</textarea>
                        </div>

                        <div class="teacher-essay-grade-actions">
                            @if ($isGraded && $gradedAt !== '')
                                <span class="teacher-essay-grade-updated">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    Cập nhật lần cuối: {{ date('H:i d/m/Y', strtotime($gradedAt)) }}
                                </span>
                            @endif
                            <button type="submit" class="op-btn">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>{{ $isGraded ? 'Cập nhật chấm bài' : 'Lưu chấm bài' }}</span>
                            </button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </article>
@endif
