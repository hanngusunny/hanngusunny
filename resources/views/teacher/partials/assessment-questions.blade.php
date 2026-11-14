@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $scoring = app(\App\Services\AssessmentSubmissionService::class);
    $quizQuestions = $questions ?? [];
    $quizAnswerMap = $answerMap ?? [];
    $answerLabels = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
    $teacherReviewMode = ! empty($teacherReviewMode);
    $useAssessmentGradeForm = ! empty($useAssessmentGradeForm);
    $assessmentGradeUrl = (string) ($assessmentGradeUrl ?? '');
    $assessmentHubCourseId = (int) ($assessmentHubCourseId ?? 0);
    $assessmentHubAssessmentId = (int) ($assessmentHubAssessmentId ?? 0);
    $assessmentHubUserId = (int) ($assessmentHubUserId ?? 0);

    $typeLabel = static function (string $type): string {
        return match ($type) {
            'fill' => 'Tự luận',
            'memory_match' => 'Lật ảnh',
            'word_link' => 'Nối từ',
            'word_order' => 'Sắp xếp câu',
            'listening' => 'Nghe đáp',
            'category_sort' => 'Phân loại',
            'fill_blanks' => 'Điền chỗ trống',
            'word_select' => 'Chọn từ',
            'word_translate' => 'Dịch từ',
            'phat_am' => 'Phát âm',
            'image_hotspot' => 'Click ảnh',
            'image_label_drop' => 'Kéo nhãn ảnh',
            'multi_select' => 'Nhiều đáp án',
            default => '',
        };
    };
@endphp

@foreach ($quizQuestions as $qIndex => $question)
    @php
        $question = $parser->normalizeQuestionRowForEdit(is_array($question) ? $question : []);
        $questionId = (int) ($question['id'] ?? 0);
        $questionType = (string) ($question['question_type'] ?? 'choice');
        $questionType = $questionType === 'text' ? 'fill' : $questionType;
        $questionType = $questionType === 'essay' ? 'fill' : $questionType;
        $answerRow = $quizAnswerMap[$questionId] ?? [];
        $isCorrect = ! empty($answerRow['is_correct']);
        $selected = (string) ($answerRow['selected_answer'] ?? '');
        if ($selected === '' && trim((string) ($answerRow['answer_text'] ?? '')) !== '') {
            $selected = (string) ($answerRow['answer_text'] ?? '');
        }
        $correct = (string) ($question['correct_answer'] ?? '');
        $memoryPairs = $questionType === 'word_link'
            ? $parser->decodePlayableWordLinkPairs($question)
            : $parser->decodePlayableMemoryPairs($question);
        $memoryStats = json_decode($selected, true);
        $options = $parser->decodeChoiceOptions($question);
        $listeningConfig = $questionType === 'listening' ? $parser->decodeListeningConfig($question) : null;
        $listeningBundle = $questionType === 'listening' ? $parser->decodeListeningBundle($question) : ['questions' => []];
        $listeningQuestions = $listeningBundle['questions'] ?? [];
        $listeningSubmittedAnswers = $questionType === 'listening' ? $parser->decodeListeningStudentAnswersPayload($selected) : [];
        $listeningAudioUrl = is_array($listeningConfig) ? $parser->resolveListeningAudioUrl((string) ($listeningConfig['audio'] ?? '')) : '';
        $listeningLegacyText = is_array($listeningConfig) ? trim((string) ($listeningConfig['text'] ?? '')) : '';
        $phatAmBundle = $questionType === 'phat_am' ? $parser->decodePhatAmBundle($question) : ['prompt' => '', 'questions' => []];
        $phatAmPrompt = trim((string) ($phatAmBundle['prompt'] ?? ''));
        $phatAmQuestions = $phatAmBundle['questions'] ?? [];
        $phatAmRequireRecording = $parser->normalizePhatAmRequireRecording($phatAmBundle['require_recording'] ?? true);
        $phatAmSubmittedAnswers = $questionType === 'phat_am' ? $parser->decodePhatAmStudentAnswersPayload($selected) : [];
        $phatAmRecordingPath = $questionType === 'phat_am' ? $parser->decodePhatAmStudentRecording($selected) : '';
        $hasPhatAmRecording = $questionType === 'phat_am' && $parser->hasPhatAmRecordingInSubmission($selected);
        $explanation = trim((string) ($question['answer_explanation'] ?? ''));
        $selectedKey = strtolower($selected);
        $selectedMulti = $questionType === 'multi_select' ? $parser->parseMultiSelectLetters($selected) : [];
        $correctMulti = $questionType === 'multi_select' ? $parser->parseMultiSelectLetters($correct) : [];
        $gameCompleted = is_array($memoryStats) && ($memoryStats['status'] ?? '') === 'completed';

        if (in_array($questionType, ['fill_blanks', 'word_select'], true)) {
            $blankQuestionData = $questionType === 'word_select'
                ? $parser->decodeWordSelectData($question)
                : $parser->decodeFillBlanksData($question);
            $fillBlanksExpectedCount = count($blankQuestionData['answers'] ?? []);
            $submittedBlanks = is_array($memoryStats) ? ($memoryStats['blanks'] ?? []) : [];
            $filledBlankCount = 0;
            if (is_array($submittedBlanks)) {
                foreach ($submittedBlanks as $blankValue) {
                    if (trim((string) $blankValue) !== '') {
                        $filledBlankCount++;
                    }
                }
            }
            $gameCompleted = $fillBlanksExpectedCount > 0
                && is_array($submittedBlanks)
                && count($submittedBlanks) === $fillBlanksExpectedCount
                && $filledBlankCount === $fillBlanksExpectedCount;
        }

        if ($questionType === 'word_translate') {
            $wordTranslateExpectedCount = count($parser->decodeWordTranslateData($question)['items'] ?? []);
            $submittedTranslations = is_array($memoryStats) ? ($memoryStats['translations'] ?? []) : [];
            $filledTranslationCount = 0;
            if (is_array($submittedTranslations)) {
                foreach ($submittedTranslations as $translationValue) {
                    if (trim((string) $translationValue) !== '') {
                        $filledTranslationCount++;
                    }
                }
            }
            $gameCompleted = $wordTranslateExpectedCount > 0
                && is_array($submittedTranslations)
                && count($submittedTranslations) === $wordTranslateExpectedCount
                && $filledTranslationCount === $wordTranslateExpectedCount;
        }

        if ($questionType === 'phat_am') {
            $gameCompleted = $parser->phatAmStudentSubmissionIsComplete($question, $selected);
        }

        if ($questionType === 'listening') {
            $gameCompleted = $parser->listeningStudentSubmissionIsComplete($question, $selected);
        }

        if ($questionType === 'image_hotspot') {
            $imageHotspotExpectedCount = count($parser->decodeImageHotspotData($question)['targets'] ?? []);
            $submittedClicks = is_array($memoryStats) ? ($memoryStats['clicks'] ?? []) : [];
            $filledClickCount = 0;
            if (is_array($submittedClicks)) {
                foreach ($submittedClicks as $clickRow) {
                    if (is_array($clickRow) && isset($clickRow['x'], $clickRow['y'])) {
                        $filledClickCount++;
                    }
                }
            }
            $gameCompleted = $imageHotspotExpectedCount > 0
                && is_array($submittedClicks)
                && count($submittedClicks) === $imageHotspotExpectedCount
                && $filledClickCount === $imageHotspotExpectedCount;
        }

        if ($questionType === 'image_label_drop') {
            $imageLabelDropConfig = $parser->decodeImageLabelDropData($question);
            $imageLabelDropExpectedCount = count($imageLabelDropConfig['labels'] ?? []);
            $submittedAssignments = is_array($memoryStats) ? ($memoryStats['assignments'] ?? []) : [];
            $gameCompleted = ($memoryStats['status'] ?? '') === 'completed'
                && is_array($submittedAssignments)
                && count($submittedAssignments) === $imageLabelDropExpectedCount;
        }

        $phatAmHasFillItem = false;
        foreach ($phatAmQuestions as $phatAmQuestionRow) {
            if ((string) ($phatAmQuestionRow['answer_mode'] ?? 'choice') === 'fill') {
                $phatAmHasFillItem = true;
                break;
            }
        }
        $listeningHasFillItem = false;
        foreach ($listeningQuestions as $listeningQuestionRow) {
            if ((string) ($listeningQuestionRow['answer_mode'] ?? 'choice') === 'fill') {
                $listeningHasFillItem = true;
                break;
            }
        }

        $resultPill = $isCorrect ? 'Đúng' : 'Sai';
        $isEssayType = $questionType === 'fill'
            || ($questionType === 'listening' && $listeningHasFillItem)
            || ($questionType === 'phat_am' && $phatAmHasFillItem);
        $isTeacherGradedType = $isEssayType || $questionType === 'word_translate' || ($questionType === 'phat_am' && $hasPhatAmRecording);
        $teacherGraded = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';

        if ($questionType === 'word_translate') {
            if (! $gameCompleted) {
                $resultPill = 'Chưa xong';
            } elseif (! $teacherGraded) {
                $resultPill = $teacherReviewMode ? 'Chưa chấm' : 'Chờ chấm';
            } else {
                $wtItemReviews = $parser->decodeWordTranslateItemReview((string) ($answerRow['teacher_comment'] ?? ''));
                $wtTotalItems = count($parser->decodeWordTranslateData($question)['items'] ?? []);
                $wtCorrectItems = $parser->countWordTranslateCorrectItems($wtItemReviews);
                $resultPill = $wtCorrectItems.'/'.max(1, $wtTotalItems).' đúng';
            }
        } elseif ($questionType === 'phat_am' && $hasPhatAmRecording) {
            if (! $gameCompleted) {
                $resultPill = 'Chưa ghi âm';
            } elseif (! $teacherGraded) {
                $resultPill = $teacherReviewMode ? 'Chưa chấm' : 'Chờ chấm';
            } else {
                $resultPill = $isCorrect ? 'Đúng' : 'Sai';
            }
        } elseif ($teacherReviewMode && $isEssayType) {
            $resultPill = $teacherGraded ? ($isCorrect ? 'Đúng' : 'Sai') : 'Chưa chấm';
        } elseif ($questionType === 'fill' || ($questionType === 'listening' && $listeningHasFillItem) || ($questionType === 'phat_am' && $phatAmHasFillItem)) {
            $resultPill = 'Đối chiếu';
        } elseif (in_array($questionType, ['memory_match', 'word_link', 'word_order', 'category_sort', 'fill_blanks', 'word_select', 'image_hotspot', 'image_label_drop'], true) && ! $gameCompleted) {
            $resultPill = 'Chưa xong';
        }

        $resultItemState = 'wrong';
        if ($isTeacherGradedType && ! $teacherGraded && ($questionType !== 'word_translate' || $gameCompleted)) {
            $resultItemState = 'pending';
        } elseif ($isCorrect) {
            $resultItemState = 'correct';
        }

        $maxPoints = $scoring->questionPoints($question);
        $earnedPoints = $scoring->resolveEarnedPoints($question, $answerRow);
        $pointsPill = $earnedPoints !== null
            ? $scoring->formatPoints($earnedPoints).'/'.$scoring->formatPoints($maxPoints).' đ'
            : $scoring->formatPoints($maxPoints).' đ · '.$resultPill;
        $plainTitle = \App\Support\HtmlContent::plain($question['question_text'] ?? '');
        $defaultType = $typeLabel($questionType);
        $hasCustomTitle = $plainTitle !== '' && mb_strtolower($plainTitle) !== mb_strtolower($defaultType);
    @endphp

    <article
        class="session-quiz-result-item is-{{ $resultItemState }} session-quiz-result-item--{{ $resultItemState }}"
        @if ($useAssessmentGradeForm && $questionId > 0) id="cau-{{ $questionId }}" @endif
    >
        <div class="session-quiz-result-head">
            <div class="session-quiz-item-title">
                <span class="session-quiz-legend-prefix">Câu hỏi {{ $qIndex + 1 }}:</span>
                @if ($hasCustomTitle)
                    <div class="course-rich-content">{!! \App\Support\HtmlContent::render($question['question_text'] ?? '') !!}</div>
                @elseif ($defaultType !== '')
                    {{ $defaultType }}
                @endif
            </div>
            <span class="session-quiz-result-pill">{{ $pointsPill }}</span>
        </div>

        @if ($questionType === 'listening' && is_array($listeningConfig))
            @php
                $useCompositeGrade = $teacherReviewMode && $useAssessmentGradeForm && $assessmentGradeUrl !== '' && $answerRow !== [] && $listeningQuestions !== [];
                $compositeState = ['weights' => [], 'earned' => []];
                if ($useCompositeGrade || $listeningQuestions !== []) {
                    $compositeState = $parser->resolveCompositeItemGradeState(
                        $question,
                        $selected,
                        (string) ($answerRow['teacher_comment'] ?? ''),
                        $maxPoints
                    );
                }
                $compositeFormId = 'assessment_composite_grade_'.$questionId;
            @endphp
            @if ($listeningLegacyText !== '' && $listeningAudioUrl === '')
                <div class="teacher-grading-essay-block">
                    <div class="teacher-grading-result-label"><i class="fa-solid fa-volume-high"></i> Nội dung đã nghe</div>
                    <div class="course-rich-content">{!! \App\Support\HtmlContent::render($listeningLegacyText) !!}</div>
                </div>
            @endif
            @if ($listeningAudioUrl !== '')
                @include('teacher.partials.phat-am-player', ['audioUrl' => $listeningAudioUrl])
            @endif
            @include('teacher.partials.grading-sub-items', [
                'items' => $listeningQuestions,
                'submittedAnswers' => $listeningSubmittedAnswers,
                'answerLabels' => $answerLabels,
                'showCompositeItemGrade' => $useCompositeGrade,
                'showItemMaxPoints' => true,
                'compositeItemWeights' => $compositeState['weights'],
                'compositeItemEarned' => $compositeState['earned'],
                'compositeItemParts' => $compositeState['parts'] ?? [],
                'compositeItemPartWeights' => $compositeState['part_weights'] ?? [],
                'compositeFormId' => $useCompositeGrade ? $compositeFormId : '',
            ])
            @if ($useCompositeGrade)
                <form
                    method="post"
                    action="{{ $assessmentGradeUrl }}"
                    class="assessment-question-grade-form session-composite-item-grade-form"
                    id="{{ $compositeFormId }}"
                    data-assessment-autosave
                    data-composite-item-live-score
                    data-max-points="{{ $scoring->formatPoints($maxPoints) }}"
                    data-saved-earned="{{ $scoring->formatPoints((float) ($scoring->resolveEarnedPoints($question, $answerRow) ?? array_sum($compositeState['earned']))) }}"
                >
                    @csrf
                    <input type="hidden" name="grade_mode" value="composite_items">
                    <input type="hidden" name="question_id" value="{{ $questionId }}">
                    <input type="hidden" name="course_id" value="{{ $assessmentHubCourseId }}">
                    <input type="hidden" name="assessment_id" value="{{ $assessmentHubAssessmentId }}">
                    <input type="hidden" name="user_id" value="{{ $assessmentHubUserId }}">
                    @include('teacher.partials.assessment-grade-form', [
                        'questionId' => $questionId,
                        'question' => $question,
                        'answerRow' => $answerRow,
                        'gradeUrl' => $assessmentGradeUrl,
                        'hubCourseId' => $assessmentHubCourseId,
                        'hubAssessmentId' => $assessmentHubAssessmentId,
                        'hubUserId' => $assessmentHubUserId,
                        'embedFieldsOnly' => true,
                        'gradeMode' => 'composite_items',
                        'compositeEarnedSum' => array_sum($compositeState['earned']),
                    ])
                </form>
            @endif
        @elseif ($questionType === 'fill')
            <div class="teacher-grading-essay-block">
                <div class="teacher-grading-result-label"><i class="fa-solid fa-pen-to-square"></i> Câu trả lời học viên</div>
                <div class="course-rich-content js-essay-answer-images">{!! $selected !== '' ? \App\Support\HtmlContent::render($selected) : '<span class="muted">Chưa trả lời</span>' !!}</div>
            </div>
        @elseif ($questionType === 'memory_match' || $questionType === 'word_link')
            <div class="teacher-grading-word-link">
                @if ($gameCompleted)
                    <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Học viên đã hoàn thành{{ $isCorrect ? ' — tất cả đều đúng' : '' }}</p>
                @else
                    <p class="teacher-grading-result-summary is-pending">Chưa hoàn thành.</p>
                @endif
                @foreach ($memoryPairs as $pairIndex => $pair)
                    @php
                        $isWordLinkPair = isset($pair['a_type']) || isset($pair['a_value']);
                        $leftType = $isWordLinkPair ? (string) ($pair['a_type'] ?? 'image') : 'image';
                        $rightType = $isWordLinkPair ? (string) ($pair['b_type'] ?? 'text') : 'text';
                        $leftValue = $isWordLinkPair ? (string) ($pair['a_value'] ?? '') : (string) ($pair['image'] ?? '');
                        $rightValue = $isWordLinkPair ? (string) ($pair['b_value'] ?? '') : (string) ($pair['label'] ?? '');
                    @endphp
                    <div class="teacher-grading-word-link-pair">
                        <span>
                            @if ($leftType === 'image' && $leftValue !== '')
                                <img src="{{ $leftValue }}" alt="">
                            @else
                                {{ $leftValue !== '' ? $leftValue : 'Ô trái' }}
                            @endif
                        </span>
                        <em>↔</em>
                        <span>
                            @if ($rightType === 'image' && $rightValue !== '')
                                <img src="{{ $rightValue }}" alt="">
                            @else
                                {{ $rightValue !== '' ? $rightValue : 'Ô phải' }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @elseif ($questionType === 'word_order')
            @php
                $wordOrderStats = json_decode($selected, true);
                $wordOrderCompleted = is_array($wordOrderStats) && ($wordOrderStats['status'] ?? '') === 'completed';
                $correctSentence = $parser->formatWordOrderSentence($question);
                $studentSentence = '';
                if ($wordOrderCompleted && is_array($wordOrderStats['order'] ?? null)) {
                    $tokenTextMap = [];
                    foreach ($parser->decodePlayableWordOrderTokens($question) as $token) {
                        $tokenTextMap[(string) ($token['id'] ?? '')] = (string) ($token['text'] ?? '');
                    }
                    $parts = [];
                    foreach ($wordOrderStats['order'] as $tokenId) {
                        $text = $tokenTextMap[(string) $tokenId] ?? '';
                        if ($text !== '') {
                            $parts[] = $text;
                        }
                    }
                    $studentSentence = implode(' ', $parts);
                }
            @endphp
            <div class="teacher-grading-essay-block">
                @if ($wordOrderCompleted)
                    <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Đã ghép câu{{ $isCorrect ? ' — đúng thứ tự' : ' — chưa đúng thứ tự' }}</p>
                    @if ($studentSentence !== '')
                        <p><strong>Câu học viên xếp:</strong> {{ $studentSentence }}</p>
                    @endif
                @else
                    <p class="teacher-grading-result-summary is-pending">Chưa hoàn thành trò sắp xếp câu.</p>
                @endif
                @if ($correctSentence !== '')
                    <p><strong>Câu đúng:</strong> {{ $correctSentence }}</p>
                @endif
            </div>
        @elseif ($questionType === 'category_sort')
            @php
                $categorySortData = $parser->decodeCategorySortData($question);
                $categorySortStats = json_decode($selected, true);
                $categorySortCompleted = is_array($categorySortStats) && ($categorySortStats['status'] ?? '') === 'completed';
            @endphp
            <div>
                @if ($categorySortCompleted)
                    <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Đã phân loại{{ $isCorrect ? ' — tất cả đúng nhóm' : ' — còn thẻ sai nhóm' }}</p>
                @else
                    <p class="teacher-grading-result-summary is-pending">Chưa hoàn thành trò phân loại.</p>
                @endif
                <div class="teacher-assessment-sort-groups">
                    @foreach ($categorySortData['groups'] ?? [] as $groupRow)
                        @php
                            $groupId = (string) ($groupRow['id'] ?? '');
                            $groupItems = array_values(array_filter(
                                $categorySortData['items'] ?? [],
                                static fn (array $item): bool => (string) ($item['group_id'] ?? '') === $groupId
                            ));
                        @endphp
                        <div class="teacher-assessment-sort-group">
                            <h5>{{ $groupRow['label'] ?? 'Nhóm' }}</h5>
                            <div>
                                @forelse ($groupItems as $itemRow)
                                    <span class="teacher-assessment-chip">{{ $itemRow['text'] ?? '' }}</span>
                                @empty
                                    <span class="muted">Chưa có thẻ</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif ($questionType === 'fill_blanks')
            @php
                $fillBlanksData = $parser->decodeFillBlanksData($question);
                $fillBlanksLineGroups = $parser->parseFillBlanksLineGroups((string) ($fillBlanksData['template'] ?? ''));
                $studentBlanks = is_array($memoryStats) ? ($memoryStats['blanks'] ?? []) : [];
                $expectedBlanks = $fillBlanksData['answers'] ?? [];
                $useFillBlanksGrade = $teacherReviewMode && $useAssessmentGradeForm && $assessmentGradeUrl !== '' && $answerRow !== [];
                $autoBlankMap = $parser->fillBlanksCorrectMap($expectedBlanks, is_array($studentBlanks) ? $studentBlanks : []);
                $savedBlankReviews = $useFillBlanksGrade
                    ? $parser->decodeFillBlanksBlankReview((string) ($answerRow['teacher_comment'] ?? ''))
                    : [];
                $blankCorrectMap = [];
                foreach ($expectedBlanks as $blankIndex => $_answer) {
                    $blankIndex = (int) $blankIndex;
                    if (isset($savedBlankReviews[$blankIndex]) && array_key_exists('is_correct', $savedBlankReviews[$blankIndex])) {
                        $blankCorrectMap[$blankIndex] = ! empty($savedBlankReviews[$blankIndex]['is_correct']);
                    } else {
                        $blankCorrectMap[$blankIndex] = ! empty($autoBlankMap[$blankIndex]);
                    }
                }
                $fillBlanksFormId = 'assessment_fill_blanks_grade_'.$questionId;
            @endphp
            @if ($useFillBlanksGrade)
                <form
                    method="post"
                    action="{{ $assessmentGradeUrl }}"
                    class="assessment-question-grade-form session-fill-blanks-grade-form"
                    id="{{ $fillBlanksFormId }}"
                    data-assessment-autosave
                    data-fill-blanks-live-score
                    data-max-points="{{ $scoring->formatPoints($maxPoints) }}"
                    data-blank-count="{{ count($expectedBlanks) }}"
                    data-saved-earned="{{ $scoring->formatPoints((float) ($scoring->resolveEarnedPoints($question, $answerRow) ?? 0)) }}"
                    @if ($savedBlankReviews === [] && trim((string) ($answerRow['teacher_graded_at'] ?? '')) === '') data-sync-from-blanks="1" @endif
                >
                    @csrf
                    <input type="hidden" name="grade_mode" value="fill_blanks">
                    <input type="hidden" name="question_id" value="{{ $questionId }}">
                    <input type="hidden" name="course_id" value="{{ $assessmentHubCourseId }}">
                    <input type="hidden" name="assessment_id" value="{{ $assessmentHubAssessmentId }}">
                    <input type="hidden" name="user_id" value="{{ $assessmentHubUserId }}">
            @endif
            <div>
                @if ($gameCompleted)
                    <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Đã điền đủ ô{{ $isCorrect ? ' — tất cả đều đúng' : ' — còn ô sai' }}</p>
                @else
                    <p class="teacher-grading-result-summary is-pending">Chưa điền đủ các ô trống.</p>
                @endif
                <div class="teacher-grading-essay-block">
                    <div class="teacher-grading-result-label"><i class="fa-solid fa-pen-to-square"></i> Câu học viên điền</div>
                    @include('teacher.partials.assessment-blanks-sentence', [
                        'lineGroups' => $fillBlanksLineGroups,
                        'studentBlanks' => $studentBlanks,
                        'expectedBlanks' => $expectedBlanks,
                        'displayMode' => 'student',
                        'variant' => 'fill_blanks',
                        'showBlankGradeMarks' => $useFillBlanksGrade,
                        'blankCorrectMap' => $blankCorrectMap,
                    ])
                </div>
                @if ($useFillBlanksGrade)
                    @include('teacher.partials.assessment-grade-form', [
                        'questionId' => $questionId,
                        'question' => $question,
                        'answerRow' => $answerRow,
                        'gradeUrl' => $assessmentGradeUrl,
                        'hubCourseId' => $assessmentHubCourseId,
                        'hubAssessmentId' => $assessmentHubAssessmentId,
                        'hubUserId' => $assessmentHubUserId,
                        'embedFieldsOnly' => true,
                        'gradeMode' => 'fill_blanks',
                        'blankCorrectMap' => $blankCorrectMap,
                    ])
                @endif
                @if ($expectedBlanks !== [])
                    <div class="teacher-grading-essay-block">
                        <div class="teacher-grading-result-label"><i class="fa-solid fa-circle-check"></i> Đáp án đúng</div>
                        @include('teacher.partials.assessment-blanks-sentence', [
                            'lineGroups' => $fillBlanksLineGroups,
                            'studentBlanks' => $studentBlanks,
                            'expectedBlanks' => $expectedBlanks,
                            'displayMode' => 'model',
                            'variant' => 'fill_blanks',
                        ])
                    </div>
                @endif
            </div>
            @if ($useFillBlanksGrade)
                </form>
            @endif
        @elseif ($questionType === 'word_select')
            @php
                $wordSelectData = $parser->decodeWordSelectData($question);
                $wordSelectLineGroups = $parser->parseFillBlanksLineGroups((string) ($wordSelectData['template'] ?? ''));
                $studentBlanks = is_array($memoryStats) ? ($memoryStats['blanks'] ?? []) : [];
                $expectedBlanks = $wordSelectData['answers'] ?? [];
            @endphp
            <div>
                @if ($gameCompleted)
                    <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Đã chọn đủ từ{{ $isCorrect ? ' — tất cả đều đúng' : ' — còn ô sai' }}</p>
                @else
                    <p class="teacher-grading-result-summary is-pending">Chưa chọn đủ các từ.</p>
                @endif
                <div class="teacher-grading-essay-block">
                    <div class="teacher-grading-result-label"><i class="fa-solid fa-pen-to-square"></i> Câu học viên chọn</div>
                    @include('teacher.partials.assessment-blanks-sentence', [
                        'lineGroups' => $wordSelectLineGroups,
                        'studentBlanks' => $studentBlanks,
                        'expectedBlanks' => $expectedBlanks,
                        'displayMode' => 'student',
                        'variant' => 'word_select',
                    ])
                </div>
                @if ($expectedBlanks !== [])
                    <div class="teacher-grading-essay-block">
                        <div class="teacher-grading-result-label"><i class="fa-solid fa-circle-check"></i> Đáp án đúng</div>
                        @include('teacher.partials.assessment-blanks-sentence', [
                            'lineGroups' => $wordSelectLineGroups,
                            'studentBlanks' => $studentBlanks,
                            'expectedBlanks' => $expectedBlanks,
                            'displayMode' => 'model',
                            'variant' => 'word_select',
                        ])
                    </div>
                @endif
            </div>
        @elseif ($questionType === 'word_translate')
            @if ($teacherReviewMode && $useAssessmentGradeForm && $assessmentGradeUrl !== '')
                @include('teacher.partials.assessment-word-translate-form', [
                    'questionId' => $questionId,
                    'question' => $question,
                    'answerRow' => $answerRow,
                    'selectedJson' => $selected,
                    'gradeUrl' => $assessmentGradeUrl,
                    'courseId' => $assessmentHubCourseId,
                    'assessmentId' => $assessmentHubAssessmentId,
                    'userId' => $assessmentHubUserId,
                ])
            @endif
        @elseif ($questionType === 'phat_am')
            @php
                $useCompositeGrade = $teacherReviewMode && $useAssessmentGradeForm && $assessmentGradeUrl !== '' && $answerRow !== [] && $phatAmQuestions !== [];
                $compositeState = ['weights' => [], 'earned' => []];
                if ($useCompositeGrade || $phatAmQuestions !== []) {
                    $compositeState = $parser->resolveCompositeItemGradeState(
                        $question,
                        $selected,
                        (string) ($answerRow['teacher_comment'] ?? ''),
                        $maxPoints
                    );
                }
                $compositeFormId = 'assessment_composite_grade_'.$questionId;
                $recordingUrl = $phatAmRecordingPath !== '' ? $parser->resolvePhatAmRecordingUrl($phatAmRecordingPath) : '';
                $teacherReview = $parser->decodePhatAmTeacherReview((string) ($answerRow['teacher_comment'] ?? ''));
                $teacherFeedbackPath = trim((string) ($teacherReview['recording'] ?? ''));
                $teacherFeedbackUrl = $teacherFeedbackPath !== '' ? $parser->resolvePhatAmRecordingUrl($teacherFeedbackPath) : '';
            @endphp
            @if ($phatAmPrompt !== '')
                <div class="session-phat-am-sample session-phat-am-sample--shared">
                    <span class="session-phat-am-sample-label"><i class="fa-solid fa-quote-left"></i> Câu mẫu</span>
                    <div class="session-phat-am-sample-box">
                        <div class="session-phat-am-sample-text course-rich-content">{!! \App\Support\HtmlContent::render($phatAmPrompt) !!}</div>
                    </div>
                </div>
            @endif
            @if ($phatAmRequireRecording || $recordingUrl !== '')
                <div class="session-phat-am-result-block session-phat-am-result-block--shared">
                    <span class="session-phat-am-result-label"><i class="fa-solid fa-microphone"></i> Bản ghi học viên</span>
                    @if ($recordingUrl !== '')
                        @include('teacher.partials.phat-am-player', ['audioUrl' => $recordingUrl])
                    @else
                        <p class="muted">Chưa có bản ghi.</p>
                    @endif
                </div>
            @endif
            @if ($teacherFeedbackUrl !== '')
                <div class="session-phat-am-result-block session-phat-am-result-feedback-audio">
                    <span class="session-phat-am-result-label"><i class="fa-solid fa-comment"></i> Phản hồi giáo viên</span>
                    @include('teacher.partials.phat-am-player', ['audioUrl' => $teacherFeedbackUrl])
                </div>
            @endif
            @include('teacher.partials.grading-sub-items', [
                'items' => $phatAmQuestions,
                'submittedAnswers' => $phatAmSubmittedAnswers,
                'answerLabels' => $answerLabels,
                'showCompositeItemGrade' => $useCompositeGrade,
                'showItemMaxPoints' => true,
                'compositeItemWeights' => $compositeState['weights'],
                'compositeItemEarned' => $compositeState['earned'],
                'compositeItemParts' => $compositeState['parts'] ?? [],
                'compositeItemPartWeights' => $compositeState['part_weights'] ?? [],
                'compositeFormId' => $useCompositeGrade ? $compositeFormId : '',
            ])
            @if ($useCompositeGrade)
                <form
                    method="post"
                    action="{{ $assessmentGradeUrl }}"
                    class="assessment-question-grade-form session-composite-item-grade-form"
                    id="{{ $compositeFormId }}"
                    data-assessment-autosave
                    data-composite-item-live-score
                    data-max-points="{{ $scoring->formatPoints($maxPoints) }}"
                    data-saved-earned="{{ $scoring->formatPoints((float) ($scoring->resolveEarnedPoints($question, $answerRow) ?? array_sum($compositeState['earned']))) }}"
                >
                    @csrf
                    <input type="hidden" name="grade_mode" value="composite_items">
                    <input type="hidden" name="question_id" value="{{ $questionId }}">
                    <input type="hidden" name="course_id" value="{{ $assessmentHubCourseId }}">
                    <input type="hidden" name="assessment_id" value="{{ $assessmentHubAssessmentId }}">
                    <input type="hidden" name="user_id" value="{{ $assessmentHubUserId }}">
                    @include('teacher.partials.assessment-grade-form', [
                        'questionId' => $questionId,
                        'question' => $question,
                        'answerRow' => $answerRow,
                        'gradeUrl' => $assessmentGradeUrl,
                        'hubCourseId' => $assessmentHubCourseId,
                        'hubAssessmentId' => $assessmentHubAssessmentId,
                        'hubUserId' => $assessmentHubUserId,
                        'embedFieldsOnly' => true,
                        'gradeMode' => 'composite_items',
                        'compositeEarnedSum' => array_sum($compositeState['earned']),
                    ])
                </form>
            @endif
        @elseif ($questionType === 'image_hotspot')
            @php
                $imageHotspotData = $parser->decodeImageHotspotData($question);
                $imageHotspotUrl = $parser->resolveImageHotspotUrl((string) ($imageHotspotData['image'] ?? ''));
                $imageHotspotTargets = $imageHotspotData['targets'] ?? [];
                $imageHotspotTolerance = (float) ($imageHotspotData['tolerance'] ?? 8);
                $studentClicks = is_array($memoryStats) ? ($memoryStats['clicks'] ?? []) : [];
            @endphp
            <div>
                @if ($gameCompleted)
                    <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Đã chọn {{ count($studentClicks) }} điểm{{ $isCorrect ? ' — đúng vị trí' : ' — chưa đúng vị trí' }}</p>
                @else
                    <p class="teacher-grading-result-summary is-pending">Chưa chọn đủ các điểm trên ảnh.</p>
                @endif
                @if ($imageHotspotUrl !== '')
                    <div class="teacher-assessment-hotspot">
                        <img src="{{ $imageHotspotUrl }}" alt="Ảnh kết quả">
                    </div>
                @endif
            </div>
        @elseif ($questionType === 'image_label_drop')
            @php
                $imageLabelDropData = $parser->decodeImageLabelDropData($question);
                $imageLabelDropUrl = $parser->resolveImageLabelDropUrl((string) ($imageLabelDropData['image'] ?? ''));
                $imageLabelDropLabels = $imageLabelDropData['labels'] ?? [];
                $imageLabelDropZones = $imageLabelDropData['zones'] ?? [];
                $studentAssignments = is_array($memoryStats) ? ($memoryStats['assignments'] ?? []) : [];
                $expectedByLabel = [];
                $labelTextById = [];
                $zoneLabelById = [];
                foreach ($imageLabelDropLabels as $labelRow) {
                    if (! is_array($labelRow)) {
                        continue;
                    }
                    $labelId = (string) ($labelRow['id'] ?? '');
                    $expectedByLabel[$labelId] = (string) ($labelRow['zone_id'] ?? '');
                    $labelTextById[$labelId] = (string) ($labelRow['text'] ?? '');
                }
                foreach ($imageLabelDropZones as $zoneRow) {
                    if (is_array($zoneRow)) {
                        $zoneLabelById[(string) ($zoneRow['id'] ?? '')] = (string) ($zoneRow['label'] ?? '');
                    }
                }
                $submittedByLabel = [];
                foreach ($studentAssignments as $assignmentRow) {
                    if (is_array($assignmentRow)) {
                        $submittedByLabel[(string) ($assignmentRow['label_id'] ?? '')] = (string) ($assignmentRow['zone_id'] ?? '');
                    }
                }
            @endphp
            <div>
                @if ($gameCompleted)
                    <p class="teacher-grading-result-summary is-done"><i class="fa-solid fa-circle-check"></i> Đã kéo nhãn{{ $isCorrect ? ' — đúng vị trí' : ' — chưa đúng vị trí' }}</p>
                @else
                    <p class="teacher-grading-result-summary is-pending">Chưa kéo đủ nhãn vào vùng trên ảnh.</p>
                @endif
                <ul class="teacher-assessment-label-list">
                    @foreach ($expectedByLabel as $labelId => $expectedZoneId)
                        @php
                            $submittedZoneId = $submittedByLabel[$labelId] ?? '';
                            $assignmentCorrect = $submittedZoneId !== '' && $submittedZoneId === $expectedZoneId;
                        @endphp
                        <li class="is-{{ $assignmentCorrect ? 'ok' : 'bad' }}">
                            <strong>{{ $labelTextById[$labelId] ?? $labelId }}</strong>
                            <span>
                                {{ $submittedZoneId !== '' ? ($zoneLabelById[$submittedZoneId] ?? $submittedZoneId) : 'Chưa gán' }}
                                → đúng: {{ $zoneLabelById[$expectedZoneId] ?? $expectedZoneId }}
                            </span>
                        </li>
                    @endforeach
                </ul>
                @if ($imageLabelDropUrl !== '')
                    <div class="teacher-assessment-hotspot">
                        <img src="{{ $imageLabelDropUrl }}" alt="Đáp án kéo nhãn">
                    </div>
                @endif
            </div>
        @elseif ($questionType === 'multi_select')
            <div class="teacher-grading-options">
                @for ($i = 0, $multiCount = max(2, count($options)); $i < $multiCount; $i++)
                    @php
                        $key = chr(97 + $i);
                        $optText = trim((string) ($options[$i] ?? ''));
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
                @endfor
            </div>
        @else
            <div class="teacher-grading-options">
                @for ($i = 0; $i < 4; $i++)
                    @php
                        $key = chr(97 + $i);
                        $optText = trim((string) ($options[$i] ?? ''));
                        if ($optText === '') {
                            continue;
                        }
                        $isSelected = $selectedKey === $key;
                        $isCorrectOption = strtolower($correct) === $key;
                    @endphp
                    <div class="teacher-grading-option{{ $isSelected && ! $isCorrect ? ' is-bad' : '' }}{{ $isCorrectOption ? ' is-ok' : '' }}">
                        <b>{{ $answerLabels[$key] ?? strtoupper($key) }}</b>
                        <span class="course-rich-content">{!! \App\Support\HtmlContent::render($optText) !!}</span>
                        @if ($isSelected)<em>Học viên chọn</em>@endif
                        @if ($isCorrectOption && ! $isCorrect)<em>Đáp án đúng</em>@endif
                    </div>
                @endfor
            </div>
        @endif

        @php
            $hideExplanation = $questionType === 'phat_am'
                || ($teacherReviewMode && ($questionType === 'fill' || ($questionType === 'listening' && $listeningHasFillItem)));
        @endphp
        @if ($explanation !== '' && ! $hideExplanation)
            <div class="teacher-grading-essay-block">
                <div class="teacher-grading-result-label"><i class="fa-solid fa-lightbulb"></i> Giải thích</div>
                <div class="course-rich-content">{!! \App\Support\HtmlContent::render($explanation) !!}</div>
            </div>
        @endif

        @if (
            $useAssessmentGradeForm
            && $assessmentGradeUrl !== ''
            && $answerRow !== []
            && $questionType !== 'word_translate'
            && $questionType !== 'fill_blanks'
            && ! ($questionType === 'listening' && $listeningQuestions !== [])
            && ! ($questionType === 'phat_am' && $phatAmQuestions !== [])
        )
            @include('teacher.partials.assessment-grade-form', [
                'questionId' => $questionId,
                'question' => $question,
                'answerRow' => $answerRow,
                'gradeUrl' => $assessmentGradeUrl,
                'hubCourseId' => $assessmentHubCourseId,
                'hubAssessmentId' => $assessmentHubAssessmentId,
                'hubUserId' => $assessmentHubUserId,
            ])
        @endif
    </article>
@endforeach
