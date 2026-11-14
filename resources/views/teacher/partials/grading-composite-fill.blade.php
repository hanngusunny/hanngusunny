@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $questionId = (int) ($questionId ?? 0);
    $question = is_array($question ?? null) ? $question : [];
    $answerRow = is_array($answerRow ?? null) ? $answerRow : [];
    $selectedJson = trim((string) ($selectedJson ?? ''));
    $question = $parser->normalizeQuestionRowForEdit($question);
    $fillItems = $parser->compositeFillItemsForReview($question);
    $questionType = (string) ($question['question_type'] ?? '');
    $submittedAnswers = $parser->compositeSubPayloads($question, $selectedJson);
    $itemReviews = $parser->decodeCompositeFillItemReviews((string) ($answerRow['teacher_comment'] ?? ''));
    $isGraded = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';
    $gradedAt = (string) ($answerRow['teacher_graded_at'] ?? '');
    $commentPlain = $parser->decodeCompositeFillReviewComment((string) ($answerRow['teacher_comment'] ?? ''));
    $teacherReview = $parser->decodePhatAmTeacherReview((string) ($answerRow['teacher_comment'] ?? ''));
    $teacherRecordingPath = trim((string) ($teacherReview['recording'] ?? ''));
    $teacherRecordingUrl = $teacherRecordingPath !== ''
        ? $parser->resolvePhatAmRecordingUrl($teacherRecordingPath)
        : '';
    $correctCount = 0;
    foreach ($itemReviews as $review) {
        if (! empty($review['is_correct'])) {
            ++$correctCount;
        }
    }
    $saveUrl = (string) ($saveUrl ?? route('teacher.grading.save'));
    $showInboxFields = ! empty($showInboxFields);
    $showRecorder = $questionType === 'phat_am';
@endphp

@if ($questionId > 0 && $fillItems !== [])
<section class="teacher-word-translate-grade-edit teacher-composite-fill-grade-edit{{ $isGraded ? ' teacher-word-translate-grade-edit--graded is-graded' : '' }}" aria-label="Chấm từng câu tự luận">
    <div class="teacher-essay-grade-edit-head">
        <div>
            <h4><i class="fa-solid fa-pen-to-square"></i> {{ $isGraded ? 'Chỉnh sửa chấm từng câu' : 'Chấm từng câu tự luận' }}</h4>
            <p class="teacher-essay-grade-edit-hint">Chọn Đúng/Sai cho từng câu tự luận. Câu trắc nghiệm vẫn được hệ thống chấm sẵn.</p>
        </div>
        @if ($isGraded)
            <span class="teacher-essay-grade-status teacher-essay-grade-status--done is-done">
                <i class="fa-solid fa-circle-check"></i> Đã chấm {{ $correctCount }}/{{ count($fillItems) }} đúng
            </span>
        @else
            <span class="teacher-essay-grade-status teacher-essay-grade-status--pending is-pending">Chưa chấm</span>
        @endif
    </div>

    <form method="post" action="{{ $saveUrl }}" class="teacher-word-translate-grade-form">
        @csrf
        <input type="hidden" name="grade_mode" value="composite_fill">
        @if ($showInboxFields)
            <input type="hidden" name="course_id" value="{{ (int) $courseId }}">
            <input type="hidden" name="muc" value="{{ $activeTab }}">
            <input type="hidden" name="bai" value="{{ $selectedKey }}">
        @else
            <input type="hidden" name="question_id" value="{{ $questionId }}">
            <input type="hidden" name="muc" value="{{ $activeTab }}">
        @endif

        <div class="teacher-word-translate-grade-table-wrap">
            <table class="teacher-word-translate-grade-table">
                <thead>
                    <tr>
                        <th scope="col">Câu hỏi nhỏ</th>
                        <th scope="col">Câu trả lời học viên</th>
                        <th scope="col">Đúng/Sai</th>
                        <th scope="col">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fillItems as $itemIndex => $itemRow)
                        @php
                            $itemPrompt = trim((string) ($itemRow['choice_question'] ?? ''));
                            $expectedAnswer = trim((string) ($itemRow['correct_text'] ?? ''));
                            $subItem = is_array($submittedAnswers[$itemIndex] ?? null) ? $submittedAnswers[$itemIndex] : [];
                            $studentValue = trim((string) ($subItem['text'] ?? $subItem['answer'] ?? ''));
                            $savedReview = $itemReviews[(int) $itemIndex] ?? null;
                            $hasVerdict = is_array($savedReview) && array_key_exists('is_correct', $savedReview);
                            $savedCorrect = $hasVerdict && ! empty($savedReview['is_correct']);
                            $savedComment = is_array($savedReview) ? trim((string) ($savedReview['comment'] ?? '')) : '';
                        @endphp
                        <tr class="teacher-word-translate-grade-row">
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--source">
                                <span class="teacher-word-translate-grade-source-text">Câu {{ (int) $itemIndex + 1 }}</span>
                                @if ($itemPrompt !== '')
                                    <div class="course-rich-content">{{ \Illuminate\Support\Str::limit(\App\Support\HtmlContent::plain($itemPrompt), 160) }}</div>
                                @endif
                                @if ($expectedAnswer !== '')
                                    <small class="teacher-word-translate-grade-model-hint">Mẫu: {{ $expectedAnswer }}</small>
                                @endif
                            </td>
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--student">
                                <span class="teacher-word-translate-grade-student-value">
                                    {{ $studentValue !== '' ? \App\Support\HtmlContent::plain($studentValue) : '—' }}
                                </span>
                            </td>
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--verdict">
                                <div class="teacher-word-translate-grade-verdict" role="radiogroup" aria-label="Kết quả mục {{ (int) $itemIndex + 1 }}">
                                    <label class="teacher-word-translate-grade-verdict-option teacher-word-translate-grade-verdict-option--correct is-correct">
                                        <input type="radio" name="item_grades[{{ $itemIndex }}][is_correct]" value="1"{{ $hasVerdict && $savedCorrect ? ' checked' : '' }} required>
                                        <span class="teacher-word-translate-grade-verdict-chip"><i class="fa-solid fa-check" aria-hidden="true"></i> Đúng</span>
                                    </label>
                                    <label class="teacher-word-translate-grade-verdict-option teacher-word-translate-grade-verdict-option--wrong is-wrong">
                                        <input type="radio" name="item_grades[{{ $itemIndex }}][is_correct]" value="0"{{ $hasVerdict && ! $savedCorrect ? ' checked' : '' }} required>
                                        <span class="teacher-word-translate-grade-verdict-chip"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Sai</span>
                                    </label>
                                </div>
                            </td>
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--note">
                                <textarea
                                    class="form-control teacher-word-translate-grade-note-input"
                                    name="item_grades[{{ $itemIndex }}][comment]"
                                    rows="2"
                                    placeholder="Ghi chú..."
                                >{{ $savedComment }}</textarea>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="teacher-essay-grade-comment">
            <label class="form-label" for="composite_fill_comment_{{ $questionId }}">Đánh giá / nhận xét chung</label>
            <textarea
                class="form-control js-assessment-comment-editor"
                id="composite_fill_comment_{{ $questionId }}"
                name="teacher_comment"
                rows="4"
                placeholder="Nhận xét cho học viên về các câu tự luận này..."
            >{{ $commentPlain }}</textarea>
        </div>

        @if ($showRecorder)
            <div class="teacher-phat-am-grade-feedback session-phat-am-record-section">
                <p class="session-phat-am-record-intro"><i class="fa-solid fa-microphone"></i> Phản hồi bằng giọng nói (tuỳ chọn)</p>
                @include('teacher.partials.phat-am-recorder', [
                    'uploadUrl' => route('teacher.grading.upload'),
                    'inputName' => 'teacher_recording',
                    'inputValue' => $teacherRecordingPath,
                    'valueMode' => 'path',
                    'requireOnSubmit' => false,
                    'playerLabel' => 'Nghe lại phản hồi của bạn',
                    'statusIdleTitle' => 'Sẵn sàng ghi âm',
                    'statusIdleHint' => 'Bấm Ghi âm để thu phản hồi cho học viên.',
                    'existingAudioUrl' => $teacherRecordingUrl,
                ])
            </div>
        @endif

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
