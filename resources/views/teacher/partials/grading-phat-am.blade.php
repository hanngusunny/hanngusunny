@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $questionId = (int) ($questionId ?? 0);
    $question = is_array($question ?? null) ? $question : [];
    $answerRow = is_array($answerRow ?? null) ? $answerRow : [];
    $selectedJson = trim((string) ($selectedJson ?? ''));
    $phatAmData = $parser->decodePhatAmData($question);
    $samplePrompt = trim((string) ($phatAmData['prompt'] ?? ''));
    $payload = json_decode($selectedJson, true);
    $recordingPath = is_array($payload) ? trim((string) ($payload['recording'] ?? '')) : '';
    $recordingUrl = $recordingPath !== '' ? $parser->resolvePhatAmRecordingUrl($recordingPath) : '';
    $isGraded = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';
    $isCorrect = ! empty($answerRow['is_correct']);
    $comment = (string) ($answerRow['teacher_comment'] ?? '');
    $gradedAt = (string) ($answerRow['teacher_graded_at'] ?? '');
    $teacherReview = $parser->decodePhatAmTeacherReview($comment);
    $commentHtml = trim((string) ($teacherReview['text'] ?? ''));
    if ($commentHtml !== '' && ! \App\Support\HtmlContent::has($commentHtml)) {
        $commentHtml = '';
    } elseif ($commentHtml !== '') {
        $commentHtml = \App\Support\HtmlContent::sanitize($commentHtml);
    }
    $teacherRecordingPath = trim((string) ($teacherReview['recording'] ?? ''));
    $teacherRecordingUrl = $teacherRecordingPath !== ''
        ? $parser->resolvePhatAmRecordingUrl($teacherRecordingPath)
        : '';
@endphp

@if ($questionId > 0)
<section class="teacher-phat-am-grade-edit session-phat-am-result{{ $isGraded ? ' teacher-essay-grade-edit--graded is-graded' : '' }}" aria-label="Chấm bài phát âm">
    <div class="teacher-essay-grade-edit-head session-phat-am-head">
        <div>
            <h4 class="session-phat-am-title"><i class="fa-solid fa-microphone"></i> {{ $isGraded ? 'Chỉnh sửa chấm phát âm' : 'Chấm bài phát âm' }}</h4>
            <p class="teacher-essay-grade-edit-hint">Nghe bản ghi học viên, chọn Đúng/Sai, viết nhận xét hoặc ghi âm phản hồi.</p>
        </div>
        @if ($isGraded)
            <span class="teacher-essay-grade-status teacher-essay-grade-status--done is-done"><i class="fa-solid fa-circle-check"></i> Đã chấm</span>
        @else
            <span class="teacher-essay-grade-status teacher-essay-grade-status--pending is-pending">Chưa chấm</span>
        @endif
    </div>

    <div class="teacher-phat-am-grade-body">
        @if ($samplePrompt !== '')
            <div class="session-phat-am-sample session-phat-am-sample--shared">
                <span class="session-phat-am-sample-label"><i class="fa-solid fa-quote-left"></i> Câu mẫu</span>
                <div class="session-phat-am-sample-box">
                    <p class="session-phat-am-sample-text">{{ $samplePrompt }}</p>
                </div>
            </div>
        @endif

        <div class="session-phat-am-result-block session-phat-am-result-block--shared">
            <span class="session-phat-am-result-label"><i class="fa-solid fa-headphones"></i> Bản ghi học viên</span>
            @if ($recordingUrl !== '')
                @include('teacher.partials.phat-am-player', ['audioUrl' => $recordingUrl])
            @else
                <p class="session-phat-am-status is-error" role="status">
                    <span class="session-phat-am-status-icon" aria-hidden="true"></span>
                    <span class="session-phat-am-status-copy">
                        <strong class="session-phat-am-status-title">Chưa có bản ghi</strong>
                        <small class="session-phat-am-status-hint">Học viên chưa nộp file ghi âm cho câu này.</small>
                    </span>
                </p>
            @endif
        </div>
    </div>

    <form method="post" action="{{ route('teacher.grading.save') }}" class="teacher-essay-grade-form">
        @csrf
        <input type="hidden" name="grade_mode" value="phat_am">
        <input type="hidden" name="course_id" value="{{ (int) $courseId }}">
        <input type="hidden" name="muc" value="{{ $activeTab }}">
        <input type="hidden" name="bai" value="{{ $selectedKey }}">

        <div class="teacher-essay-grade-options" role="radiogroup" aria-label="Kết quả chấm bài phát âm">
            <span class="teacher-essay-grade-options-label">Kết quả chấm</span>
            <div class="teacher-essay-grade-options-grid">
                <label class="teacher-essay-grade-option teacher-essay-grade-option--correct is-correct">
                    <input type="radio" name="is_correct" value="1"{{ $isGraded && $isCorrect ? ' checked' : '' }} required>
                    <span class="teacher-essay-grade-option-card">
                        <span class="teacher-essay-grade-option-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                        <span class="teacher-essay-grade-option-text">
                            <strong>Đúng</strong>
                            <small>Phát âm đạt yêu cầu</small>
                        </span>
                    </span>
                </label>
                <label class="teacher-essay-grade-option teacher-essay-grade-option--wrong is-wrong">
                    <input type="radio" name="is_correct" value="0"{{ $isGraded && ! $isCorrect ? ' checked' : '' }} required>
                    <span class="teacher-essay-grade-option-card">
                        <span class="teacher-essay-grade-option-icon" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                        <span class="teacher-essay-grade-option-text">
                            <strong>Sai</strong>
                            <small>Cần luyện thêm</small>
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <div class="teacher-essay-grade-comment">
            <label class="form-label" for="phat_am_comment_{{ $questionId }}">Đánh giá / nhận xét (tuỳ chọn)</label>
            <textarea
                class="form-control js-assessment-comment-editor"
                id="phat_am_comment_{{ $questionId }}"
                name="teacher_comment"
                rows="4"
                placeholder="Nhận xét về phát âm, ngữ điệu, độ chính xác..."
            >{{ $commentHtml }}</textarea>
        </div>

        <div class="teacher-phat-am-grade-feedback session-phat-am-record-section">
            <p class="session-phat-am-record-intro"><i class="fa-solid fa-microphone"></i> Phản hồi bằng giọng nói (tuỳ chọn)</p>
            <p class="teacher-essay-grade-edit-hint">Ghi âm mẫu phát âm hoặc nhận xét bằng giọng để học viên nghe lại.</p>
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
