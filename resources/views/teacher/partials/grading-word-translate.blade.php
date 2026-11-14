@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $questionId = (int) ($questionId ?? 0);
    $question = is_array($question ?? null) ? $question : [];
    $answerRow = is_array($answerRow ?? null) ? $answerRow : [];
    $selectedJson = trim((string) ($selectedJson ?? ''));
    $wordTranslateItems = $parser->decodeWordTranslateData($question)['items'] ?? [];
    $payload = json_decode($selectedJson, true);
    $studentTranslations = is_array($payload) ? ($payload['translations'] ?? []) : [];
    $studentTranslations = is_array($studentTranslations) ? $studentTranslations : [];
    $itemReviews = $parser->decodeWordTranslateItemReview((string) ($answerRow['teacher_comment'] ?? ''));
    $isGraded = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';
    $gradedAt = (string) ($answerRow['teacher_graded_at'] ?? '');
    $correctCount = $parser->countWordTranslateCorrectItems($itemReviews);
@endphp

@if ($questionId > 0)
<section class="teacher-word-translate-grade-edit{{ $isGraded ? ' teacher-word-translate-grade-edit--graded is-graded' : '' }}" aria-label="Chấm bài dịch từ">
    <div class="teacher-essay-grade-edit-head">
        <div>
            <h4><i class="fa-solid fa-language"></i> {{ $isGraded ? 'Chỉnh sửa chấm từng từ' : 'Chấm từng từ dịch' }}</h4>
            <p class="teacher-essay-grade-edit-hint">Chọn Đúng/Sai cho từng câu dịch. Học viên chỉ thấy đáp án mẫu sau khi bạn lưu chấm.</p>
        </div>
        @if ($isGraded)
            <span class="teacher-essay-grade-status teacher-essay-grade-status--done is-done">
                <i class="fa-solid fa-circle-check"></i> Đã chấm {{ $correctCount }}/{{ count($wordTranslateItems) }} đúng
            </span>
        @else
            <span class="teacher-essay-grade-status teacher-essay-grade-status--pending is-pending">Chưa chấm</span>
        @endif
    </div>

    <form method="post" action="{{ route('teacher.grading.save') }}" class="teacher-word-translate-grade-form">
        @csrf
        <input type="hidden" name="grade_mode" value="word_translate">
        <input type="hidden" name="course_id" value="{{ (int) $courseId }}">
        <input type="hidden" name="muc" value="{{ $activeTab }}">
        <input type="hidden" name="bai" value="{{ $selectedKey }}">

        <div class="teacher-word-translate-grade-table-wrap">
            <table class="teacher-word-translate-grade-table">
                <thead>
                    <tr>
                        <th scope="col">Từ gốc</th>
                        <th scope="col">Câu trả lời học viên</th>
                        <th scope="col">Đúng/Sai</th>
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
                            $savedComment = is_array($savedReview) ? trim((string) ($savedReview['comment'] ?? '')) : '';
                        @endphp
                        <tr class="teacher-word-translate-grade-row">
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
