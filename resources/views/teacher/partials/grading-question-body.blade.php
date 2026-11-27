@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $scoring = app(\App\Services\AssessmentSubmissionService::class);
    $gradingService = app(\App\Services\TeacherGradingService::class);

    $entry = is_array($entry ?? null) ? $entry : [];
    $detail = is_array($detail ?? null) ? $detail : [];
    $interactive = ! empty($interactive);
    $activeTab = (string) ($activeTab ?? \App\Services\TeacherGradingService::TAB_ALL);

    $question = is_array($entry['question'] ?? null) ? $entry['question'] : [];
    $qType = (string) ($entry['question_type'] ?? 'choice');
    $qId = (int) ($entry['question_id'] ?? 0);
    $answerRow = is_array($entry['answer_row'] ?? null) ? $entry['answer_row'] : [];
    $selected = (string) ($entry['selected_answer'] ?? '');
    $source = (string) ($entry['source'] ?? 'session');
    $isAssessment = $source === 'assessment';
    $maxPoints = (float) ($entry['max_points'] ?? 0);
    $earnedPoints = $entry['earned_points'] ?? null;
    $isManual = ! empty($entry['is_manual']);
    $isGraded = ! empty($entry['is_graded']);
    $gradeMode = (string) ($entry['grade_mode'] ?? 'essay');
    $answerKey = (string) ($entry['answer_key'] ?? '');
    $submissionKey = (string) ($detail['submission_key'] ?? '');
    $saveUrl = (string) ($detail['save_url'] ?? route('teacher.grading.save'));
    $uploadUrl = (string) ($detail['upload_url'] ?? route('teacher.grading.upload'));
    $courseId = (int) ($detail['course_id'] ?? 0);
    $assessmentId = (int) ($detail['assessment_id'] ?? 0);
    $studentId = (int) ($detail['student']['id'] ?? 0);
    $answerLabels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F'];

    $questionHtml = (string) ($question['question_text'] ?? '');
    $plainPrompt = \App\Support\HtmlContent::plain($questionHtml);
    $hasMediaPrompt = preg_match('/<(img|audio|video|iframe|table|ul|ol|figure)\b/i', $questionHtml) === 1;
    $hasPrompt = $plainPrompt !== '' || $hasMediaPrompt;

    $rawComment = (string) ($answerRow['teacher_comment'] ?? '');
    $plainComment = '';
    if ($rawComment !== '' && ! str_starts_with(ltrim($rawComment), '{')) {
        $plainComment = \App\Support\HtmlContent::plain($rawComment);
    } elseif ($qType === 'phat_am') {
        $paReview = $parser->decodePhatAmTeacherReview($rawComment);
        $plainComment = \App\Support\HtmlContent::plain((string) ($paReview['text'] ?? ''));
    } elseif ($parser->hasCompositeFillItems($question)) {
        $plainComment = $parser->decodeCompositeFillReviewComment($rawComment);
    }
    $decodedJson = json_decode($selected, true);
@endphp

@if ($qType === 'choice')
    @php
        $options = $parser->decodeChoiceOptions($question);
        $selectedLetter = strtolower(trim($selected));
        $correctLetter = strtolower(trim((string) ($question['correct_answer'] ?? '')));
    @endphp
    <div class="sn-tg-answer-box">
        @if ($hasPrompt)
            <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
        @endif
        <div class="sn-tg-choice-row">
            @foreach ($options as $letter => $optText)
                @php
                    $lKey = strtolower((string) $letter);
                    $isChosen = $selectedLetter === $lKey;
                    $isRight = $correctLetter === $lKey;
                    $optClass = '';
                    if ($isChosen && $isRight) {
                        $optClass = 'is-correct';
                    } elseif ($isChosen && ! $isRight) {
                        $optClass = 'is-wrong';
                    } elseif (! $isChosen && $isRight) {
                        $optClass = 'is-answer-hint';
                    }
                @endphp
                <span class="sn-tg-choice-pill {{ $optClass }}">
                    <strong>{{ strtoupper($lKey) }}</strong>
                    <span>{!! \App\Support\HtmlContent::render((string) $optText) !!}</span>
                </span>
            @endforeach
        </div>
    </div>

@elseif ($qType === 'multi_select')
    @php
        $options = $parser->decodeChoiceOptions($question);
        $selectedMulti = $parser->parseMultiSelectLetters($selected);
        $correctMulti = $parser->parseMultiSelectLetters((string) ($question['correct_answer'] ?? ''));
    @endphp
    <div class="sn-tg-answer-box">
        @if ($hasPrompt)
            <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
        @endif
        <div class="sn-tg-choice-row">
            @foreach ($options as $letter => $optText)
                @php
                    $lKey = strtolower((string) $letter);
                    $isChosen = in_array($lKey, $selectedMulti, true);
                    $isRight = in_array($lKey, $correctMulti, true);
                    $optClass = '';
                    if ($isChosen && $isRight) {
                        $optClass = 'is-correct';
                    } elseif ($isChosen && ! $isRight) {
                        $optClass = 'is-wrong';
                    } elseif (! $isChosen && $isRight) {
                        $optClass = 'is-answer-hint';
                    }
                @endphp
                <span class="sn-tg-choice-pill sn-tg-choice-pill--check {{ $optClass }}">
                    <span class="sn-tg-check-box{{ $isChosen ? ' is-checked' : '' }}" aria-hidden="true">
                        @if ($isChosen)
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2.5 6.2L4.8 8.5L9.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <span>{!! \App\Support\HtmlContent::render((string) $optText) !!}</span>
                </span>
            @endforeach
        </div>
    </div>

@elseif (in_array($qType, ['fill_blanks', 'word_select'], true))
    @php
        $blankData = $qType === 'word_select'
            ? $parser->decodeWordSelectData($question)
            : $parser->decodeFillBlanksData($question);
        $lineGroups = $parser->parseFillBlanksLineGroups((string) ($blankData['template'] ?? ''));
        $expectedBlanks = is_array($blankData['answers'] ?? null) ? $blankData['answers'] : [];
        $studentBlanks = is_array($decodedJson) && is_array($decodedJson['blanks'] ?? null) ? $decodedJson['blanks'] : [];
        $autoBlankMap = $parser->fillBlanksCorrectMap($expectedBlanks, $studentBlanks);
        $savedBlankReviews = $parser->decodeFillBlanksBlankReview($rawComment);
    @endphp
    <div class="sn-tg-answer-box">
        @if ($hasPrompt && mb_strtolower($plainPrompt) !== mb_strtolower($entry['question_type_label'] ?? ''))
            <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
        @endif
        <div class="sn-tg-blanks-lines">
            @foreach ($lineGroups as $lineIdx => $segments)
                <div class="sn-tg-blank-line">
                    @foreach ($segments as $seg)
                        @if (($seg['type'] ?? '') === 'blank')
                            @php
                                $bIdx = (int) ($seg['index'] ?? 0);
                                $expVal = trim((string) ($expectedBlanks[$bIdx] ?? ''));
                                $stuVal = trim((string) ($studentBlanks[$bIdx] ?? ''));
                                $bOk = isset($savedBlankReviews[$bIdx]['is_correct'])
                                    ? ! empty($savedBlankReviews[$bIdx]['is_correct'])
                                    : ! empty($autoBlankMap[$bIdx]);
                            @endphp
                            <span class="sn-tg-blank-pair">
                                @if ($expVal !== '')
                                    <span class="sn-tg-blank-model" title="Đáp án mẫu">{{ $expVal }}</span>
                                @endif
                                <span class="sn-tg-blank-student {{ $bOk ? 'is-correct' : 'is-wrong' }}">{{ $stuVal !== '' ? $stuVal : '—' }}</span>
                            </span>
                        @else
                            <span class="sn-tg-blank-text">{{ (string) ($seg['text'] ?? '') }}</span>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

@elseif ($qType === 'word_translate')
    @php
        $wtItems = $parser->decodeWordTranslateData($question)['items'] ?? [];
        $wtTotal = count($wtItems);
        $wtTranslations = is_array($decodedJson) && is_array($decodedJson['translations'] ?? null)
            ? $decodedJson['translations']
            : [];
        $wtReviews = $parser->decodeWordTranslateItemReview($rawComment);
        $itemWeight = $wtTotal > 0 ? round($maxPoints / $wtTotal, 2) : 0.0;
        $canGradeWt = $interactive && ($isAssessment || ! empty($entry['has_answer']));
    @endphp
    @if ($hasPrompt)
        <div class="sn-tg-section-label">Đề bài</div>
        <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
    @endif

    @if ($canGradeWt)
        <form
            method="post"
            action="{{ $saveUrl }}"
            class="sn-tg-grade-form"
            data-unified-grade-form
            data-question-id="{{ $qId }}"
            data-max-points="{{ $scoring->formatPoints($maxPoints) }}"
            data-item-count="{{ $wtTotal }}"
            data-grade-kind="word_translate"
        >
            @csrf
            <input type="hidden" name="grade_mode" value="word_translate">
            <input type="hidden" name="course_id" value="{{ $courseId }}">
            @if ($isAssessment)
                <input type="hidden" name="assessment_id" value="{{ $assessmentId }}">
                <input type="hidden" name="user_id" value="{{ $studentId }}">
                <input type="hidden" name="question_id" value="{{ $qId }}">
            @else
                <input type="hidden" name="muc" value="{{ $activeTab }}">
                <input type="hidden" name="submission_key" value="{{ $submissionKey }}">
                <input type="hidden" name="answer_key" value="{{ $answerKey }}">
                <input type="hidden" name="bai" value="{{ $answerKey }}">
            @endif
    @endif

    <div class="sn-tg-wt-table-wrap">
        <table class="sn-tg-wt-table">
            <thead>
                <tr>
                    <th>Từ cho sẵn</th>
                    <th>Học sinh điền</th>
                    <th>Đúng / Sai</th>
                    <th class="is-right">Điểm</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($wtItems as $idx => $itemRow)
                    @php
                        $norm = $parser->normalizeWordTranslateItem(is_array($itemRow) ? $itemRow : []);
                        $srcType = (string) ($norm['source_type'] ?? 'text');
                        $srcVal = trim((string) ($norm['source'] ?? ''));
                        $srcImg = ($srcType === 'image' && $srcVal !== '') ? $parser->resolveWordLinkMediaUrl($srcVal) : '';
                        $modelVal = trim((string) ($norm['answer'] ?? ''));
                        $stuVal = trim((string) ($wtTranslations[(int) $idx] ?? ''));
                        $savedRev = $wtReviews[(int) $idx] ?? null;
                        $hasVerdict = is_array($savedRev) && array_key_exists('is_correct', $savedRev);
                        $isOk = $hasVerdict && ! empty($savedRev['is_correct']);
                        $rowPts = $hasVerdict ? ($isOk ? $itemWeight : 0.0) : null;
                    @endphp
                    <tr data-wt-row>
                        <td>
                            <div class="sn-tg-wt-src">
                                @if ($srcImg !== '')
                                    <img src="{{ $srcImg }}" alt="" class="sn-tg-wt-img">
                                @else
                                    <strong>{{ $srcVal !== '' ? $srcVal : '—' }}</strong>
                                @endif
                                @if ($modelVal !== '')
                                    <small>Mẫu: {{ $modelVal }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="sn-tg-wt-student{{ $hasVerdict ? ($isOk ? ' is-correct' : ' is-wrong') : '' }}">
                                {{ $stuVal !== '' ? $stuVal : '—' }}
                            </span>
                        </td>
                        <td>
                            @if ($canGradeWt)
                                <div class="sn-tg-verdict-group" role="radiogroup">
                                    <label class="sn-tg-verdict-opt is-ok">
                                        <input type="radio" name="item_grades[{{ $idx }}][is_correct]" value="1" @checked($hasVerdict && $isOk) required>
                                        <span>Đúng</span>
                                    </label>
                                    <label class="sn-tg-verdict-opt is-no">
                                        <input type="radio" name="item_grades[{{ $idx }}][is_correct]" value="0" @checked($hasVerdict && ! $isOk) required>
                                        <span>Sai</span>
                                    </label>
                                </div>
                            @else
                                <div class="sn-tg-verdict-static">
                                    <span class="sn-tg-verdict-chip{{ $hasVerdict && $isOk ? ' is-ok' : '' }}">Đúng</span>
                                    <span class="sn-tg-verdict-chip{{ $hasVerdict && ! $isOk ? ' is-no' : '' }}">Sai</span>
                                </div>
                            @endif
                        </td>
                        <td class="is-right">
                            <span class="sn-tg-wt-pts{{ $hasVerdict && ! $isOk ? ' is-zero' : '' }}" data-wt-item-pts>
                                @if ($rowPts !== null)
                                    {{ $gradingService->formatScoreVi($rowPts) }} / {{ $gradingService->formatScoreVi($itemWeight) }}
                                @else
                                    — / {{ $gradingService->formatScoreVi($itemWeight) }}
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($canGradeWt)
            <div class="sn-tg-inline-form-foot">
                <span class="sn-tg-autosave-hint" data-form-status></span>
                <button type="submit" class="sn-tg-mini-save-btn">Lưu câu này</button>
            </div>
        </form>
    @endif

@elseif ($qType === 'word_link' || $qType === 'memory_match')
    @php
        $pairs = $qType === 'word_link'
            ? $parser->decodePlayableWordLinkPairs($question)
            : $parser->decodePlayableMemoryPairs($question);
        $gameDone = is_array($decodedJson) && ($decodedJson['status'] ?? '') === 'completed';
        $isRight = ! empty($answerRow['is_correct']);
    @endphp
    <div class="sn-tg-answer-box">
        @if ($hasPrompt && mb_strtolower($plainPrompt) !== mb_strtolower($entry['question_type_label'] ?? ''))
            <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
        @endif
        @if ($qType === 'memory_match')
            <div class="sn-tg-flip-chips">
                @foreach ($pairs as $pair)
                    @php
                        $leftVal = (string) ($pair['image'] ?? $pair['a_value'] ?? '');
                        $rightVal = (string) ($pair['label'] ?? $pair['b_value'] ?? '');
                    @endphp
                    @if ($leftVal !== '' && ! str_starts_with($leftVal, 'http') && ! str_starts_with($leftVal, '/'))
                        <span class="sn-tg-flip-chip is-correct">{{ $leftVal }}</span>
                    @endif
                    @if ($rightVal !== '')
                        <span class="sn-tg-flip-chip is-correct">{{ $rightVal }}</span>
                    @endif
                @endforeach
            </div>
            <p class="sn-tg-hint-text">{{ $gameDone ? 'Đã ghép '.count($pairs).'/'.count($pairs).' cặp' : 'Chưa hoàn thành lật thẻ' }}</p>
        @else
            <div class="sn-tg-pair-list">
                @foreach ($pairs as $pair)
                    @php
                        $lType = (string) ($pair['a_type'] ?? 'text');
                        $rType = (string) ($pair['b_type'] ?? 'text');
                        $lVal = (string) ($pair['a_value'] ?? $pair['image'] ?? '');
                        $rVal = (string) ($pair['b_value'] ?? $pair['label'] ?? '');
                    @endphp
                    <div class="sn-tg-pair-row">
                        <span class="sn-tg-pair-left">
                            @if ($lType === 'image' && $lVal !== '')
                                <img src="{{ $lVal }}" alt="">
                            @else
                                {{ $lVal !== '' ? $lVal : '—' }}
                            @endif
                        </span>
                        <span class="sn-tg-pair-arrow">→</span>
                        <span class="sn-tg-pair-right {{ $isRight ? 'is-correct' : 'is-wrong' }}">
                            @if ($rType === 'image' && $rVal !== '')
                                <img src="{{ $rVal }}" alt="">
                            @else
                                {{ $rVal !== '' ? $rVal : '—' }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

@elseif ($qType === 'phat_am' || $qType === 'listening')
    @php
        $isPhatAm = $qType === 'phat_am';
        $bundle = $isPhatAm
            ? $parser->decodePhatAmBundle($question)
            : $parser->decodeListeningBundle($question);
        $subQuestions = is_array($bundle['questions'] ?? null) ? $bundle['questions'] : [];
        $submittedSub = $isPhatAm
            ? $parser->decodePhatAmStudentAnswersPayload($selected)
            : $parser->decodeListeningStudentAnswersPayload($selected);
        $paPrompt = $isPhatAm ? trim((string) ($bundle['prompt'] ?? '')) : '';
        $paRecording = $isPhatAm ? $parser->decodePhatAmStudentRecording($selected) : '';
        $paRecUrl = $paRecording !== '' ? $parser->resolvePhatAmRecordingUrl($paRecording) : '';
        $listenCfg = ! $isPhatAm ? $parser->decodeListeningConfig($question) : null;
        $listenAudioUrl = is_array($listenCfg) ? $parser->resolveListeningAudioUrl((string) ($listenCfg['audio'] ?? '')) : '';
        $hasCompositeFill = $parser->hasCompositeFillItems($question);
        $fillIndexes = $hasCompositeFill ? $parser->compositeFillItemIndexes($question) : [];
        $compReviews = $hasCompositeFill ? $parser->decodeCompositeFillItemReviews($rawComment) : [];
        $compState = $subQuestions !== []
            ? $parser->resolveCompositeItemGradeState($question, $selected, $rawComment, $maxPoints)
            : ['weights' => [], 'earned' => []];
        $canGradeComp = $interactive && ($isAssessment || ! empty($entry['has_answer']));
    @endphp

    @if ($hasPrompt)
        <div class="sn-tg-section-label">Đề bài</div>
        <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
    @endif
    @if ($paPrompt !== '')
        <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($paPrompt) !!}</div>
    @endif

    @if ($canGradeComp)
        <form
            method="post"
            action="{{ $saveUrl }}"
            class="sn-tg-grade-form"
            data-unified-grade-form
            data-question-id="{{ $qId }}"
            data-max-points="{{ $scoring->formatPoints($maxPoints) }}"
            data-grade-kind="{{ $isAssessment ? ($subQuestions !== [] ? 'composite_items' : 'points') : ($hasCompositeFill ? 'composite_fill' : ($isPhatAm && $paRecUrl !== '' ? 'phat_am' : 'essay')) }}"
        >
            @csrf
            <input type="hidden" name="course_id" value="{{ $courseId }}">
            @if ($isAssessment)
                <input type="hidden" name="grade_mode" value="{{ $subQuestions !== [] ? 'composite_items' : '' }}">
                <input type="hidden" name="assessment_id" value="{{ $assessmentId }}">
                <input type="hidden" name="user_id" value="{{ $studentId }}">
                <input type="hidden" name="question_id" value="{{ $qId }}">
            @else
                <input type="hidden" name="grade_mode" value="{{ $hasCompositeFill ? 'composite_fill' : ($isPhatAm && $paRecUrl !== '' ? 'phat_am' : 'essay') }}">
                <input type="hidden" name="muc" value="{{ $activeTab }}">
                <input type="hidden" name="submission_key" value="{{ $submissionKey }}">
                <input type="hidden" name="answer_key" value="{{ $answerKey }}">
                <input type="hidden" name="bai" value="{{ $answerKey }}">
            @endif
    @endif

    <div class="sn-tg-sub-stack">
        @if ($listenAudioUrl !== '')
            <div class="sn-tg-sub-card">
                <div class="sn-tg-sub-head">
                    <span class="sn-tg-sub-badge is-blue">File nghe</span>
                    <strong>Audio bài nghe</strong>
                </div>
                <div class="sn-tg-audio-bar">
                    <audio controls preload="metadata" src="{{ $listenAudioUrl }}"></audio>
                </div>
            </div>
        @endif

        @if ($isPhatAm && ($paRecUrl !== '' || $parser->hasPhatAmRecordingInSubmission($selected)))
            <div class="sn-tg-sub-card">
                <div class="sn-tg-sub-head">
                    <span class="sn-tg-sub-badge is-orange">Ghi âm</span>
                    <strong>Bản ghi âm của học sinh</strong>
                </div>
                @if ($paRecUrl !== '')
                    <div class="sn-tg-audio-bar">
                        <audio controls preload="metadata" src="{{ $paRecUrl }}"></audio>
                    </div>
                @else
                    <p class="sn-tg-muted">Học sinh chưa nộp bản ghi âm.</p>
                @endif
            </div>
        @endif

        @foreach ($subQuestions as $subIdx => $subQ)
            @php
                $subMode = (string) ($subQ['answer_mode'] ?? 'choice');
                $subPrompt = trim((string) ($subQ['choice_question'] ?? ''));
                $subAns = is_array($submittedSub[$subIdx] ?? null) ? $submittedSub[$subIdx] : [];
                $subVal = trim((string) ($subAns['text'] ?? $subAns['answer'] ?? $subAns['selected'] ?? ''));
                $subWeight = (float) ($compState['weights'][$subIdx] ?? 0);
                $subEarned = $compState['earned'][$subIdx] ?? null;
                $isSubFill = $subMode === 'fill';
                $savedSubRev = $compReviews[(int) $subIdx] ?? null;
                $hasSubVerdict = is_array($savedSubRev) && array_key_exists('is_correct', $savedSubRev);
                $subVerdictOk = $hasSubVerdict && ! empty($savedSubRev['is_correct']);
                $subCode = ($entry['label'] ?? '1').chr(97 + ((int) $subIdx % 26));
            @endphp
            <div class="sn-tg-sub-card">
                <div class="sn-tg-sub-head">
                    <span class="sn-tg-sub-badge {{ $isSubFill ? 'is-orange' : 'is-blue' }}">{{ $subCode }}</span>
                    <strong>{{ $isSubFill ? 'Tự luận' : 'Trắc nghiệm' }}</strong>
                    @if ($subWeight > 0)
                        <div class="sn-tg-sub-score">
                            @if ($canGradeComp && $isAssessment)
                                <input
                                    type="text"
                                    class="sn-tg-score-input sn-tg-score-input--sm"
                                    name="item_grades[{{ $subIdx }}][points]"
                                    value="{{ $subEarned !== null ? $scoring->formatPoints((float) $subEarned) : '' }}"
                                    max="{{ $scoring->formatPoints($subWeight) }}"
                                    placeholder="—"
                                    inputmode="decimal"
                                    data-sub-points
                                >
                                <span>/ {{ $gradingService->formatScoreVi($subWeight) }}</span>
                            @else
                                <span class="sn-tg-score-chip {{ ($subEarned !== null && $subEarned >= $subWeight - 0.01) ? 'is-full' : ($subEarned !== null && $subEarned > 0 ? 'is-partial' : 'is-zero') }}">
                                    {{ $subEarned !== null ? $gradingService->formatScoreVi((float) $subEarned) : '—' }}
                                </span>
                                <span>/ {{ $gradingService->formatScoreVi($subWeight) }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($subPrompt !== '')
                    <div class="sn-tg-sub-prompt course-rich-content">{!! \App\Support\HtmlContent::render($subPrompt) !!}</div>
                @endif

                <div class="sn-tg-sub-answer">
                    @if ($subVal !== '')
                        {!! \App\Support\HtmlContent::render($subVal) !!}
                    @else
                        <span class="sn-tg-muted">Học sinh chưa trả lời câu này</span>
                    @endif
                </div>

                @if ($canGradeComp && ! $isAssessment && $isSubFill)
                    <div class="sn-tg-sub-grade-row">
                        <div class="sn-tg-verdict-group" role="radiogroup">
                            <label class="sn-tg-verdict-opt is-ok">
                                <input type="radio" name="item_grades[{{ $subIdx }}][is_correct]" value="1" @checked($hasSubVerdict && $subVerdictOk) required>
                                <span>Đúng</span>
                            </label>
                            <label class="sn-tg-verdict-opt is-no">
                                <input type="radio" name="item_grades[{{ $subIdx }}][is_correct]" value="0" @checked($hasSubVerdict && ! $subVerdictOk) required>
                                <span>Sai</span>
                            </label>
                        </div>
                        <input
                            type="text"
                            class="sn-tg-comment-input sn-tg-comment-input--sm"
                            name="item_grades[{{ $subIdx }}][comment]"
                            value="{{ is_array($savedSubRev) ? (string) ($savedSubRev['comment'] ?? '') : '' }}"
                            placeholder="Ghi chú câu {{ $subCode }}..."
                        >
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($canGradeComp)
        @if ($isAssessment && $subQuestions === [])
            <div class="sn-tg-manual-controls">
                <label class="sn-tg-pts-field">
                    <span>Điểm câu này</span>
                    <input
                        type="text"
                        class="sn-tg-score-input"
                        name="points_earned"
                        value="{{ $earnedPoints !== null ? $scoring->formatPoints((float) $earnedPoints) : '' }}"
                        max="{{ $scoring->formatPoints($maxPoints) }}"
                        placeholder="0"
                        inputmode="decimal"
                        data-question-points-input
                    >
                    <em>/ {{ $gradingService->formatScoreVi($maxPoints) }} điểm</em>
                </label>
            </div>
        @elseif ($isAssessment && $subQuestions !== [])
            <input type="hidden" name="points_earned" value="{{ $earnedPoints !== null ? $scoring->formatPoints((float) $earnedPoints) : '0' }}" data-question-points-input>
        @elseif (! $hasCompositeFill)
            <div class="sn-tg-manual-controls">
                <span class="sn-tg-control-label">Kết quả chấm:</span>
                <div class="sn-tg-verdict-group" role="radiogroup">
                    <label class="sn-tg-verdict-opt is-ok">
                        <input type="radio" name="is_correct" value="1" @checked($isGraded && ! empty($answerRow['is_correct'])) required>
                        <span>Đúng ({{ $gradingService->formatScoreVi($maxPoints) }} đ)</span>
                    </label>
                    <label class="sn-tg-verdict-opt is-no">
                        <input type="radio" name="is_correct" value="0" @checked($isGraded && empty($answerRow['is_correct'])) required>
                        <span>Sai (0 đ)</span>
                    </label>
                </div>
            </div>
        @endif

        <div class="sn-tg-comment-box">
            <textarea
                class="sn-tg-comment-input"
                name="teacher_comment"
                rows="2"
                placeholder="Nhận xét cho học sinh..."
            >{{ $plainComment }}</textarea>
        </div>

        <div class="sn-tg-inline-form-foot">
            <span class="sn-tg-autosave-hint" data-form-status></span>
            <button type="submit" class="sn-tg-mini-save-btn">Lưu câu này</button>
        </div>
        </form>
    @elseif ($plainComment !== '')
        <div class="sn-tg-comment-readonly">{{ $plainComment }}</div>
    @endif

@elseif ($qType === 'fill')
    @php
        $canGradeFill = $interactive && ($isAssessment || ! empty($entry['has_answer']));
    @endphp
    @if ($hasPrompt)
        <div class="sn-tg-section-label">Đề bài</div>
        <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
    @endif

    <div class="sn-tg-answer-box">
        <div class="course-rich-content js-essay-answer-images">
            @if (trim($selected) !== '')
                {!! \App\Support\HtmlContent::render($selected) !!}
            @else
                <span class="sn-tg-muted">Học sinh chưa trả lời câu này</span>
            @endif
        </div>
    </div>

    @if ($canGradeFill)
        <form
            method="post"
            action="{{ $saveUrl }}"
            class="sn-tg-grade-form"
            data-unified-grade-form
            data-question-id="{{ $qId }}"
            data-max-points="{{ $scoring->formatPoints($maxPoints) }}"
            data-grade-kind="{{ $isAssessment ? 'points' : 'essay' }}"
        >
            @csrf
            <input type="hidden" name="course_id" value="{{ $courseId }}">
            @if ($isAssessment)
                <input type="hidden" name="assessment_id" value="{{ $assessmentId }}">
                <input type="hidden" name="user_id" value="{{ $studentId }}">
                <input type="hidden" name="question_id" value="{{ $qId }}">

                <div class="sn-tg-manual-controls">
                    <label class="sn-tg-pts-field">
                        <span>Điểm câu này</span>
                        <input
                            type="text"
                            class="sn-tg-score-input"
                            name="points_earned"
                            value="{{ $earnedPoints !== null ? $scoring->formatPoints((float) $earnedPoints) : '' }}"
                            max="{{ $scoring->formatPoints($maxPoints) }}"
                            placeholder="Nhập điểm"
                            inputmode="decimal"
                            data-question-points-input
                        >
                        <em>/ {{ $gradingService->formatScoreVi($maxPoints) }} điểm</em>
                    </label>
                </div>
            @else
                <input type="hidden" name="muc" value="{{ $activeTab }}">
                <input type="hidden" name="submission_key" value="{{ $submissionKey }}">
                <input type="hidden" name="answer_key" value="{{ $answerKey }}">
                <input type="hidden" name="bai" value="{{ $answerKey }}">

                <div class="sn-tg-manual-controls">
                    <span class="sn-tg-control-label">Kết quả chấm:</span>
                    <div class="sn-tg-verdict-group" role="radiogroup">
                        <label class="sn-tg-verdict-opt is-ok">
                            <input type="radio" name="is_correct" value="1" @checked($isGraded && ! empty($answerRow['is_correct'])) required>
                            <span>Đúng ({{ $gradingService->formatScoreVi($maxPoints) }} đ)</span>
                        </label>
                        <label class="sn-tg-verdict-opt is-no">
                            <input type="radio" name="is_correct" value="0" @checked($isGraded && empty($answerRow['is_correct'])) required>
                            <span>Sai (0 đ)</span>
                        </label>
                    </div>
                </div>
            @endif

            <div class="sn-tg-comment-box">
                <textarea
                    class="sn-tg-comment-input"
                    name="teacher_comment"
                    rows="2"
                    placeholder="Nhận xét cho học sinh..."
                >{{ $plainComment }}</textarea>
            </div>

            <div class="sn-tg-inline-form-foot">
                <span class="sn-tg-autosave-hint" data-form-status></span>
                <button type="submit" class="sn-tg-mini-save-btn">Lưu câu này</button>
            </div>
        </form>
    @elseif ($plainComment !== '')
        <div class="sn-tg-comment-readonly">{{ $plainComment }}</div>
    @endif

@else
    {{-- Các dạng tương tác tự động khác: word_order, category_sort, image_hotspot, image_label_drop --}}
    @php
        $gameDone = is_array($decodedJson) && (($decodedJson['status'] ?? '') === 'completed' || ! empty($decodedJson));
        $isRight = ! empty($answerRow['is_correct']);
    @endphp
    <div class="sn-tg-answer-box">
        @if ($hasPrompt)
            <div class="sn-tg-prompt course-rich-content">{!! \App\Support\HtmlContent::render($questionHtml) !!}</div>
        @endif
        <p class="sn-tg-hint-text {{ $isRight ? 'is-ok' : 'is-no' }}">
            @if ($gameDone)
                Học sinh đã hoàn thành — {{ $isRight ? 'Đúng' : 'Chưa đúng' }}
            @else
                Học sinh chưa hoàn thành câu này
            @endif
        </p>
    </div>
@endif
