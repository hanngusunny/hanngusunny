@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $scoring = app(\App\Services\AssessmentSubmissionService::class);
    $questionId = (int) ($questionId ?? 0);
    $question = is_array($question ?? null) ? $question : [];
    $answerRow = is_array($answerRow ?? null) ? $answerRow : [];
    $selectedJson = trim((string) ($selectedJson ?? ''));
    $gradeUrl = (string) ($gradeUrl ?? '');

    if ($questionId <= 0 || $gradeUrl === '') {
        return;
    }

    $wordTranslateItems = $parser->decodeWordTranslateData($question)['items'] ?? [];
    $payload = json_decode($selectedJson, true);
    $studentTranslations = is_array($payload) ? ($payload['translations'] ?? []) : [];
    $studentTranslations = is_array($studentTranslations) ? $studentTranslations : [];
    $itemReviews = $parser->decodeWordTranslateItemReview((string) ($answerRow['teacher_comment'] ?? ''));
    $isGraded = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';
    $gradedAt = (string) ($answerRow['teacher_graded_at'] ?? '');
    $gradedTs = $gradedAt !== '' ? strtotime($gradedAt) : false;
    $itemCount = count($wordTranslateItems);
    $correctCount = $parser->countWordTranslateCorrectItems($itemReviews);
    $maxPoints = $scoring->questionPoints($question);
    $itemPoints = $scoring->wordTranslateItemPoints($maxPoints, $itemCount);
    $savedEarned = $isGraded ? (float) ($scoring->resolveEarnedPoints($question, $answerRow) ?? 0) : 0.0;
    $questionEarned = $scoring->wordTranslateEarnedPoints($maxPoints, $itemCount, $isGraded ? $correctCount : 0);
@endphp

<section class="teacher-word-translate-grade-edit{{ $isGraded ? ' is-graded' : '' }}" aria-label="Chấm bài dịch từ câu {{ $questionId }}">
    <div class="teacher-essay-grade-edit-head">
        <div>
            <h4><i class="fa-solid fa-language"></i> {{ $isGraded ? 'Chỉnh sửa chấm từng từ' : 'Chấm từng từ dịch' }}</h4>
            <p class="teacher-essay-grade-edit-hint">
                Chọn Đúng/Sai cho từng câu dịch — hệ thống lưu tự động.
                @if ($itemCount > 0)
                    Tổng <strong>{{ $scoring->formatPoints($maxPoints) }}</strong> điểm chia đều
                    <strong>{{ $itemCount }}</strong> câu
                    (mỗi câu đúng <strong>{{ $scoring->formatPoints($itemPoints) }}</strong> điểm).
                @endif
            </p>
        </div>
        @if ($isGraded)
            <span class="teacher-essay-grade-status is-done">
                <i class="fa-solid fa-circle-check"></i> Đã chấm {{ $correctCount }}/{{ count($wordTranslateItems) }} đúng
            </span>
        @else
            <span class="teacher-essay-grade-status is-pending">Chưa chấm</span>
        @endif
    </div>

    <form
        method="post"
        action="{{ $gradeUrl }}"
        class="teacher-word-translate-grade-form"
        id="assessment_word_translate_grade_{{ $questionId }}"
        data-assessment-autosave
        data-word-translate-live-score
        data-max-points="{{ $scoring->formatPoints($maxPoints) }}"
        data-item-count="{{ $itemCount }}"
        data-saved-earned="{{ $scoring->formatPoints($savedEarned) }}"
    >
        @csrf
        <input type="hidden" name="grade_mode" value="word_translate">
        <input type="hidden" name="question_id" value="{{ $questionId }}">
        <input type="hidden" name="course_id" value="{{ (int) ($courseId ?? 0) }}">
        <input type="hidden" name="assessment_id" value="{{ (int) ($assessmentId ?? 0) }}">
        <input type="hidden" name="user_id" value="{{ (int) ($userId ?? 0) }}">

        <div class="teacher-word-translate-grade-table-wrap">
            <table class="teacher-word-translate-grade-table">
                <thead>
                    <tr>
                        <th scope="col">Từ gốc</th>
                        <th scope="col">Câu trả lời học viên</th>
                        <th scope="col">Đúng/Sai</th>
                        <th scope="col">Điểm</th>
                        <th scope="col">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($wordTranslateItems as $itemIndex => $itemRow)
                        @php
                            $normalizedItem = $parser->normalizeWordTranslateItem(is_array($itemRow) ? $itemRow : []);
                            $sourceType = (string) ($normalizedItem['source_type'] ?? 'text');
                            $sourceText = trim((string) ($normalizedItem['source'] ?? ''));
                            $sourceImageUrl = $sourceType === 'image' && $sourceText !== ''
                                ? $parser->resolveWordLinkMediaUrl($sourceText)
                                : '';
                            $expectedAnswer = trim((string) ($normalizedItem['answer'] ?? ''));
                            $studentValue = trim((string) ($studentTranslations[(int) $itemIndex] ?? ''));
                            $savedReview = $itemReviews[(int) $itemIndex] ?? null;
                            $hasVerdict = is_array($savedReview) && array_key_exists('is_correct', $savedReview);
                            $savedCorrect = $hasVerdict && ! empty($savedReview['is_correct']);
                            $savedComment = is_array($savedReview)
                                ? \App\Support\HtmlContent::plain((string) ($savedReview['comment'] ?? ''))
                                : '';
                        @endphp
                        <tr class="teacher-word-translate-grade-row" data-word-translate-grade-row>
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--source">
                                @if ($sourceType === 'image' && $sourceImageUrl !== '')
                                    <img src="{{ $sourceImageUrl }}" alt="Từ gốc" class="teacher-word-translate-grade-source-image">
                                @else
                                    <span class="teacher-word-translate-grade-source-text">{{ $sourceText !== '' ? $sourceText : '—' }}</span>
                                @endif
                                @if ($expectedAnswer !== '')
                                    <small class="teacher-word-translate-grade-model-hint">Mẫu: {{ $expectedAnswer }}</small>
                                @endif
                            </td>
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--student">
                                <span class="teacher-word-translate-grade-student-value">{{ $studentValue !== '' ? $studentValue : '—' }}</span>
                            </td>
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--verdict">
                                <div class="teacher-word-translate-grade-verdict" role="radiogroup">
                                    <label class="teacher-word-translate-grade-verdict-option teacher-word-translate-grade-verdict-option--correct is-ok">
                                        <input type="radio" name="item_grades[{{ $itemIndex }}][is_correct]" value="1" @checked($hasVerdict && $savedCorrect) required>
                                        <span class="teacher-word-translate-grade-verdict-chip"><i class="fa-solid fa-check" aria-hidden="true"></i> Đúng</span>
                                    </label>
                                    <label class="teacher-word-translate-grade-verdict-option teacher-word-translate-grade-verdict-option--wrong is-bad">
                                        <input type="radio" name="item_grades[{{ $itemIndex }}][is_correct]" value="0" @checked($hasVerdict && ! $savedCorrect) required>
                                        <span class="teacher-word-translate-grade-verdict-chip"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Sai</span>
                                    </label>
                                </div>
                            </td>
                            <td class="teacher-word-translate-grade-cell teacher-word-translate-grade-cell--points">
                                <span class="teacher-word-translate-grade-item-points{{ $isGraded && $savedCorrect ? ' is-earned' : '' }}" data-item-points-display>
                                    {{ $isGraded && $savedCorrect ? $scoring->formatPoints($itemPoints) : '0' }}
                                </span>
                                <small>/ {{ $scoring->formatPoints($itemPoints) }}</small>
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

        <div class="teacher-essay-grade-actions">
            <span class="teacher-word-translate-grade-question-total">
                Điểm câu này:
                <strong data-question-live-earned>{{ $scoring->formatPoints($questionEarned) }}</strong>
                / {{ $scoring->formatPoints($maxPoints) }} đ
            </span>
            @if ($isGraded && $gradedTs)
                <span class="teacher-essay-grade-updated" data-autosave-status>
                    <i class="fa-regular fa-clock"></i>
                    Cập nhật lần cuối: {{ date('H:i d/m/Y', $gradedTs) }}
                </span>
            @else
                <span class="teacher-essay-grade-updated" data-autosave-status hidden></span>
            @endif
            <button type="submit" class="op-btn op-btn-sm" data-autosave-submit>
                <i class="fa-solid fa-floppy-disk"></i>
                <span>{{ $isGraded ? 'Cập nhật chấm bài' : 'Lưu chấm bài' }}</span>
            </button>
        </div>
    </form>
</section>
