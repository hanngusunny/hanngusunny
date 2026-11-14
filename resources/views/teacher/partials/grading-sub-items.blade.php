@php
    $parser = $parser ?? app(\App\Support\Questions\QuestionParser::class);
    $items = is_array($items ?? null) ? $items : [];
    $submittedAnswers = is_array($submittedAnswers ?? null) ? $submittedAnswers : [];
    $answerLabels = is_array($answerLabels ?? null) ? $answerLabels : ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
    $compositeItemParts = is_array($compositeItemParts ?? null) ? $compositeItemParts : [];
    $compositeItemPartWeights = is_array($compositeItemPartWeights ?? null) ? $compositeItemPartWeights : [];
@endphp

@if ($items !== [])
<ol class="teacher-grading-sub-items">
    @foreach ($items as $itemIndex => $expectedItem)
        @php
            $subItem = is_array($submittedAnswers[$itemIndex] ?? null) ? $submittedAnswers[$itemIndex] : [];
            $itemMode = (string) ($expectedItem['answer_mode'] ?? 'choice');
            $itemChoiceQuestion = trim((string) ($expectedItem['choice_question'] ?? ''));
            $itemPartWeights = is_array(($compositeItemPartWeights ?? [])[$itemIndex] ?? null)
                ? ($compositeItemPartWeights[$itemIndex] ?? [])
                : [];
            $itemPartEarned = is_array(($compositeItemParts ?? [])[$itemIndex] ?? null)
                ? ($compositeItemParts[$itemIndex] ?? [])
                : [];
            $showItemParts = ! empty($showCompositeItemGrade) && count($itemPartWeights) > 1;
        @endphp
        <li class="teacher-grading-sub-item">
            <div class="teacher-grading-sub-item-head">
                <span class="teacher-grading-sub-item-no">{{ (int) $itemIndex + 1 }}</span>
                <div class="teacher-grading-sub-item-title course-rich-content">
                    {!! $itemChoiceQuestion !== '' ? \App\Support\HtmlContent::render($itemChoiceQuestion) : e(match ($itemMode) {
                        'fill' => 'Tự luận',
                        'true_false' => 'Đúng / Sai',
                        'multi_choice' => 'Trắc nghiệm',
                        'multi_select' => 'Chọn nhiều',
                        'word_link' => 'Nối từ',
                        'fill_blanks' => 'Điền vào chỗ trống',
                        default => 'Trắc nghiệm',
                    }) !!}
                </div>
                @include('teacher.partials.assessment-composite-points', [
                    'itemIndex' => (int) $itemIndex,
                    'itemMax' => (float) (($compositeItemWeights ?? [])[$itemIndex] ?? 0),
                    'itemEarned' => (float) (($compositeItemEarned ?? [])[$itemIndex] ?? 0),
                    'showCompositeItemGrade' => ! empty($showCompositeItemGrade),
                    'showItemMaxPoints' => ! empty($showItemMaxPoints) || ! empty($showCompositeItemGrade),
                    'compositeFormId' => (string) ($compositeFormId ?? ''),
                    'readonly' => $showItemParts,
                ])
            </div>

            @if ($itemMode === 'fill')
                @php $studentText = trim((string) ($subItem['text'] ?? '')); @endphp
                <div class="teacher-grading-essay-block">
                    <div class="teacher-grading-result-label"><i class="fa-solid fa-pen-to-square"></i> Câu trả lời học viên</div>
                    <div class="course-rich-content js-essay-answer-images">
                        {!! $studentText !== '' ? \App\Support\HtmlContent::render($studentText) : '<span class="muted">Chưa trả lời</span>' !!}
                    </div>
                </div>
            @elseif ($itemMode === 'true_false')
                @php
                    $expectedTfItems = $expectedItem['true_false_items'] ?? [];
                    $submittedTfAnswers = is_array($subItem['answers'] ?? null) ? $subItem['answers'] : [];
                @endphp
                <ol class="teacher-grading-tf-list">
                    @foreach ($expectedTfItems as $tfIndex => $tfItem)
                        @php
                            $expectedBool = ! empty($tfItem['answer']);
                            $expectedLabel = $expectedBool ? 'Đúng' : 'Sai';
                            $studentVal = strtolower(trim((string) ($submittedTfAnswers[$tfIndex] ?? '')));
                            $studentLabel = $studentVal === 'true' ? 'Đúng' : ($studentVal === 'false' ? 'Sai' : '—');
                            $tfCorrect = $studentVal === ($expectedBool ? 'true' : 'false');
                        @endphp
                        <li class="teacher-grading-tf-item is-{{ $tfCorrect ? 'ok' : 'bad' }}">
                            <div class="teacher-grading-tf-main">
                                <div class="course-rich-content">{!! \App\Support\HtmlContent::render((string) ($tfItem['statement'] ?? '')) !!}</div>
                                <div class="teacher-grading-tf-meta">
                                    <span>Học viên chọn: <strong>{{ $studentLabel }}</strong></span>
                                    @if (! $tfCorrect)
                                        <span>Đáp án: <strong>{{ $expectedLabel }}</strong></span>
                                    @endif
                                </div>
                            </div>
                            @if ($showItemParts)
                                @include('teacher.partials.assessment-composite-points', [
                                    'itemIndex' => (int) $itemIndex,
                                    'partIndex' => (int) $tfIndex,
                                    'itemMax' => (float) ($itemPartWeights[$tfIndex] ?? 0),
                                    'itemEarned' => (float) ($itemPartEarned[$tfIndex] ?? 0),
                                    'showCompositeItemGrade' => true,
                                    'showItemMaxPoints' => true,
                                    'compositeFormId' => (string) ($compositeFormId ?? ''),
                                ])
                            @endif
                        </li>
                    @endforeach
                </ol>
            @elseif ($itemMode === 'multi_choice')
                @php
                    $expectedMcItems = $expectedItem['multi_choice_items'] ?? [];
                    $submittedMcAnswers = is_array($subItem['answers'] ?? null) ? $subItem['answers'] : [];
                @endphp
                <ol class="teacher-grading-tf-list">
                    @foreach ($expectedMcItems as $mcIndex => $mcItem)
                        @php
                            $expectedKey = strtolower(trim((string) ($mcItem['correct'] ?? 'a')));
                            $mcOptions = is_array($mcItem['options'] ?? null) ? array_values($mcItem['options']) : [];
                            $studentKey = strtolower(trim((string) ($submittedMcAnswers[$mcIndex] ?? '')));
                            $optionLabels = [];
                            foreach ($mcOptions as $optIndex => $optText) {
                                $key = chr(97 + (int) $optIndex);
                                $optionLabels[$key] = (string) $optText;
                            }
                            $expectedLabel = $optionLabels[$expectedKey] ?? strtoupper($expectedKey);
                            $studentLabel = $studentKey !== '' ? ($optionLabels[$studentKey] ?? strtoupper($studentKey)) : '—';
                            $mcCorrect = $studentKey === $expectedKey;
                        @endphp
                        <li class="teacher-grading-tf-item is-{{ $mcCorrect ? 'ok' : 'bad' }}">
                            <div class="teacher-grading-tf-main">
                                <div class="course-rich-content">{!! \App\Support\HtmlContent::render((string) ($mcItem['question'] ?? '')) !!}</div>
                                <div class="teacher-grading-tf-meta">
                                    <span>Học viên chọn: <strong>{{ $studentLabel }}</strong></span>
                                    @if (! $mcCorrect)
                                        <span>Đáp án: <strong>{{ $expectedLabel }}</strong></span>
                                    @endif
                                </div>
                            </div>
                            @if ($showItemParts)
                                @include('teacher.partials.assessment-composite-points', [
                                    'itemIndex' => (int) $itemIndex,
                                    'partIndex' => (int) $mcIndex,
                                    'itemMax' => (float) ($itemPartWeights[$mcIndex] ?? 0),
                                    'itemEarned' => (float) ($itemPartEarned[$mcIndex] ?? 0),
                                    'showCompositeItemGrade' => true,
                                    'showItemMaxPoints' => true,
                                    'compositeFormId' => (string) ($compositeFormId ?? ''),
                                ])
                            @endif
                        </li>
                    @endforeach
                </ol>
            @elseif ($itemMode === 'word_link')
                @php
                    $expectedPairs = $parser->normalizeWordLinkPairRows($expectedItem['word_link_pairs'] ?? []);
                @endphp
                <div class="teacher-grading-word-link">
                    @if (($subItem['status'] ?? '') === 'completed')
                        <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Đã nối đủ {{ count($expectedPairs) }} cặp</p>
                    @else
                        <p class="teacher-grading-result-summary is-pending">Chưa hoàn thành nối từ.</p>
                    @endif
                    @foreach ($expectedPairs as $pair)
                        <div class="teacher-grading-word-link-pair">
                            <span>
                                @if (($pair['a_type'] ?? '') === 'image' && ($pair['a_value'] ?? '') !== '')
                                    <img src="{{ $parser->resolveWordLinkMediaUrl((string) $pair['a_value']) }}" alt="">
                                @else
                                    {{ $pair['a_value'] ?? '' }}
                                @endif
                            </span>
                            <em>↔</em>
                            <span>
                                @if (($pair['b_type'] ?? '') === 'image' && ($pair['b_value'] ?? '') !== '')
                                    <img src="{{ $parser->resolveWordLinkMediaUrl((string) $pair['b_value']) }}" alt="">
                                @else
                                    {{ $pair['b_value'] ?? '' }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @elseif ($itemMode === 'fill_blanks')
                @php
                    $expectedData = $parser->normalizeFillBlanksData($expectedItem['fill_blanks'] ?? []);
                    $submittedBlanks = is_array($subItem['blanks'] ?? null) ? $subItem['blanks'] : [];
                    $expectedBlanks = $expectedData['answers'] ?? [];
                @endphp
                <ol class="teacher-grading-blank-list">
                    @foreach ($expectedBlanks as $blankIndex => $expectedBlank)
                        @php $studentBlank = trim((string) ($submittedBlanks[$blankIndex] ?? '')); @endphp
                        <li>
                            <span>Ô {{ (int) $blankIndex + 1 }}:</span>
                            <strong>{{ $studentBlank !== '' ? $studentBlank : '—' }}</strong>
                            @if ($showItemParts)
                                @include('teacher.partials.assessment-composite-points', [
                                    'itemIndex' => (int) $itemIndex,
                                    'partIndex' => (int) $blankIndex,
                                    'itemMax' => (float) ($itemPartWeights[$blankIndex] ?? 0),
                                    'itemEarned' => (float) ($itemPartEarned[$blankIndex] ?? 0),
                                    'showCompositeItemGrade' => true,
                                    'showItemMaxPoints' => true,
                                    'compositeFormId' => (string) ($compositeFormId ?? ''),
                                ])
                            @endif
                        </li>
                    @endforeach
                </ol>
            @elseif ($itemMode === 'multi_select')
                @php
                    $itemOptions = is_array($expectedItem['options'] ?? null) ? array_values($expectedItem['options']) : [];
                    $selectedMulti = $parser->parseMultiSelectLetters((string) ($subItem['choice'] ?? ''));
                    $correctMulti = $parser->parseMultiSelectLetters((string) ($expectedItem['correct'] ?? ''));
                @endphp
                <div class="teacher-grading-options">
                    @foreach ($itemOptions as $optIndex => $optText)
                        @php
                            $key = chr(97 + (int) $optIndex);
                            $optText = trim((string) $optText);
                            if ($optText === '') {
                                continue;
                            }
                            $isSelected = in_array($key, $selectedMulti, true);
                            $isCorrectOption = in_array($key, $correctMulti, true);
                        @endphp
                        <div class="teacher-grading-option{{ $isSelected && ! $isCorrectOption ? ' is-bad' : '' }}{{ $isCorrectOption ? ' is-ok' : '' }}">
                            <b>{{ $answerLabels[$key] ?? strtoupper($key) }}</b>
                            <span>{{ $optText }}</span>
                            @if ($isSelected)<em>Học viên chọn</em>@endif
                            @if ($isCorrectOption && ! $isSelected)<em>Đáp án đúng</em>@endif
                        </div>
                    @endforeach
                </div>
            @else
                @php
                    $itemOptions = is_array($expectedItem['options'] ?? null) ? array_values($expectedItem['options']) : [];
                    $selectedKeyChoice = strtolower(trim((string) ($subItem['choice'] ?? '')));
                    $expectedKey = strtolower(trim((string) ($expectedItem['correct'] ?? 'a')));
                    $itemChoiceCorrect = $selectedKeyChoice !== '' && $selectedKeyChoice === $expectedKey;
                @endphp
                <div class="teacher-grading-options">
                    @foreach ($itemOptions as $optIndex => $optText)
                        @php
                            $key = chr(97 + (int) $optIndex);
                            $optText = trim((string) $optText);
                            if ($optText === '') {
                                continue;
                            }
                            $isSelected = $selectedKeyChoice === $key;
                            $isCorrectOption = $expectedKey === $key;
                        @endphp
                        <div class="teacher-grading-option{{ $isSelected && ! $itemChoiceCorrect ? ' is-bad' : '' }}{{ $isCorrectOption && ! $itemChoiceCorrect ? ' is-ok' : '' }}">
                            <b>{{ $answerLabels[$key] ?? strtoupper($key) }}</b>
                            <span>{{ $optText }}</span>
                            @if ($isSelected)<em>Học viên chọn</em>@endif
                            @if ($isCorrectOption && ! $itemChoiceCorrect)<em>Đáp án đúng</em>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </li>
    @endforeach
</ol>
@endif
