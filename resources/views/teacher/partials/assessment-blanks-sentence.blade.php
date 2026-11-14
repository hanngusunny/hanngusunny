@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $lineGroups = $lineGroups ?? [];
    $studentBlanks = is_array($studentBlanks ?? null) ? $studentBlanks : [];
    $expectedBlanks = is_array($expectedBlanks ?? null) ? $expectedBlanks : [];
    $displayMode = ($displayMode ?? 'student') === 'model' ? 'model' : 'student';
    $variant = ($variant ?? 'fill_blanks') === 'word_select' ? 'word_select' : 'fill_blanks';
    $showBlankGradeMarks = ! empty($showBlankGradeMarks) && $displayMode === 'student';
    $blankCorrectMap = is_array($blankCorrectMap ?? null) ? $blankCorrectMap : [];
    $prefix = $variant === 'word_select' ? 'word-select' : 'fill-blanks';
@endphp

<div class="session-{{ $prefix }}-result-sentence{{ $displayMode === 'model' ? ' is-model' : '' }}{{ $showBlankGradeMarks ? ' is-grade' : '' }}">
    @foreach ($lineGroups as $lineParts)
        @php
            $lineBlankIndexes = [];
            foreach ($lineParts as $linePart) {
                if (($linePart['type'] ?? '') === 'blank') {
                    $lineBlankIndexes[] = (int) ($linePart['index'] ?? 0);
                }
            }
        @endphp
        <div class="session-blanks-result-line{{ $lineParts === [] ? ' is-empty' : '' }}">
            @if ($lineParts === [])
                <span aria-hidden="true">&nbsp;</span>
            @else
                <div class="session-blanks-result-line-text">
                    @foreach ($lineParts as $part)
                        @if (($part['type'] ?? '') === 'text')
                            <span>{{ $part['value'] ?? '' }}</span>
                        @else
                            @php
                                $blankIndex = (int) ($part['index'] ?? 0);
                                $expectedValue = trim((string) ($expectedBlanks[$blankIndex] ?? ''));
                            @endphp
                            @if ($displayMode === 'model')
                                <span class="session-blank is-model">{{ $expectedValue !== '' ? $expectedValue : '…' }}</span>
                            @else
                                @php
                                    $studentValue = trim((string) ($studentBlanks[$blankIndex] ?? ''));
                                    $blankCorrect = array_key_exists($blankIndex, $blankCorrectMap)
                                        ? ! empty($blankCorrectMap[$blankIndex])
                                        : $parser->isFillBlankAnswerCorrect($studentValue, $expectedValue);
                                @endphp
                                <span class="session-blank is-{{ $studentValue === '' ? 'empty' : ($blankCorrect ? 'ok' : 'bad') }}" title="{{ $expectedValue !== '' ? 'Đáp án: '.$expectedValue : '' }}">
                                    {{ $studentValue !== '' ? $studentValue : '…' }}
                                </span>
                            @endif
                        @endif
                    @endforeach
                </div>
                @if ($showBlankGradeMarks && $lineBlankIndexes !== [])
                    <div class="session-blanks-grade-marks" role="group" aria-label="Chấm đúng từng ô">
                        @foreach ($lineBlankIndexes as $markIndex)
                            @php $markChecked = ! empty($blankCorrectMap[$markIndex]); @endphp
                            <label class="session-blanks-grade-mark{{ $markChecked ? ' is-checked' : '' }}">
                                <input
                                    type="checkbox"
                                    name="item_grades[{{ $markIndex }}][is_correct]"
                                    value="1"
                                    @checked($markChecked)
                                    data-fill-blank-correct
                                    aria-label="Ô {{ $markIndex + 1 }} đúng"
                                >
                                <span>Đúng</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    @endforeach
</div>
