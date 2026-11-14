@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $questions = $questions ?? [];
    $answerMap = $answerMap ?? [];
    $submitted = ! empty($submitted);
@endphp

@if ($questions === [])
    <article class="op-panel">
        <div class="op-panel-body" style="padding:20px">
            <p class="text-sm text-slate-600">Buổi này chưa có câu hỏi {{ $title }}.</p>
        </div>
    </article>
@elseif (! $submitted)
    <article class="op-panel">
        <div class="op-panel-body" style="padding:20px">
            <p class="text-sm text-slate-600">Học viên chưa nộp {{ mb_strtolower($title) }}.</p>
        </div>
    </article>
@else
    @include('student.partials.quiz-result', [
        'title' => $title,
        'questions' => $questions,
        'answerMap' => $answerMap,
        'result' => $result,
        'submitted' => true,
    ])

    @foreach ($questions as $question)
        @php
            $question = $parser->normalizeQuestionRowForEdit($question);
            $qid = (int) ($question['id'] ?? 0);
            $type = (string) ($question['question_type'] ?? '');
            $needsGrade = $parser->requiresTeacherGrading($question) || in_array($type, ['word_translate', 'phat_am'], true);
            $row = $answerMap[$qid] ?? null;
        @endphp
        @if ($needsGrade && $row)
            <article class="op-panel tc-grade">
                <div class="op-panel-head is-static">
                    <span class="op-panel-title">Chấm câu #{{ $qid }}</span>
                    @if (! empty($row['teacher_graded_at']))
                        <span class="te-side-meta">Đã chấm</span>
                    @endif
                </div>
                <div class="op-panel-body" style="padding:16px 20px">
                    <form method="post" action="{{ $essayGradeUrl }}" class="tc-grade-form">
                        @csrf
                        <input type="hidden" name="muc" value="{{ $tab }}">
                        <input type="hidden" name="question_id" value="{{ $qid }}">
                        @if ($type === 'word_translate')
                            @php $items = $parser->decodeWordTranslateData($question)['items'] ?? []; @endphp
                            <input type="hidden" name="grade_mode" value="word_translate">
                            @foreach ($items as $index => $item)
                                <label>Từ {{ $index + 1 }}
                                    <select name="item_grades[{{ $index }}][is_correct]" required>
                                        <option value="">—</option>
                                        <option value="1">Đúng</option>
                                        <option value="0">Sai</option>
                                    </select>
                                </label>
                            @endforeach
                        @elseif ($parser->hasCompositeFillItems($question))
                            @php
                                $fillItems = $parser->compositeFillItemsForReview($question);
                                $fillReviews = $parser->decodeCompositeFillItemReviews((string) ($row['teacher_comment'] ?? ''));
                            @endphp
                            <input type="hidden" name="grade_mode" value="composite_fill">
                            @foreach ($fillItems as $index => $fillItem)
                                @php
                                    $savedFill = $fillReviews[(int) $index] ?? null;
                                    $savedFillCorrect = is_array($savedFill) && array_key_exists('is_correct', $savedFill)
                                        ? (! empty($savedFill['is_correct']) ? '1' : '0')
                                        : '';
                                @endphp
                                <label>Câu tự luận {{ (int) $index + 1 }}
                                    <select name="item_grades[{{ $index }}][is_correct]" required>
                                        <option value="">—</option>
                                        <option value="1" @selected($savedFillCorrect === '1')>Đúng</option>
                                        <option value="0" @selected($savedFillCorrect === '0')>Sai</option>
                                    </select>
                                </label>
                            @endforeach
                            <label>Nhận xét
                                <textarea name="teacher_comment" rows="3">{{ $parser->decodeCompositeFillReviewComment((string) ($row['teacher_comment'] ?? '')) }}</textarea>
                            </label>
                        @else
                            @if ($type === 'phat_am')
                                <input type="hidden" name="grade_mode" value="phat_am">
                            @endif
                            <label>Kết quả
                                <select name="is_correct" required>
                                    <option value="">— Chọn —</option>
                                    <option value="1" @selected((string) ($row['is_correct'] ?? '') === '1')>Đúng</option>
                                    <option value="0" @selected((string) ($row['is_correct'] ?? '') === '0')>Sai</option>
                                </select>
                            </label>
                            <label>Nhận xét
                                <textarea name="teacher_comment" rows="3">{{ $row['teacher_comment'] ?? '' }}</textarea>
                            </label>
                        @endif
                        <button type="submit" class="op-btn">Lưu chấm</button>
                    </form>
                </div>
            </article>
        @endif
    @endforeach
@endif
