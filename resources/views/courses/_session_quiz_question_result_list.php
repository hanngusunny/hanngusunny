<?php
  $quizQuestions = $quizQuestions ?? $questions ?? [];
  $quizAnswerMap = $quizAnswerMap ?? $answerMap ?? [];
  $richTextModel = $richTextModel ?? model(\App\Models\CourseMonthlyEvaluationModel::class);
  $answerLabels = $answerLabels ?? ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
  $teacherReviewMode = ! empty($teacherReviewMode);
  $showPoints = ! empty($showPoints);
  $answerModel = $answerModel ?? null;
  $useAssessmentGradeForm = ! empty($useAssessmentGradeForm);
  $assessmentGradeUrl = (string) ($assessmentGradeUrl ?? '');
  $assessmentHubCourseId = (int) ($assessmentHubCourseId ?? 0);
  $assessmentHubAssessmentId = (int) ($assessmentHubAssessmentId ?? 0);
  $assessmentHubUserId = (int) ($assessmentHubUserId ?? 0);
  $teacherEssayGradeUrl = (string) ($teacherEssayGradeUrl ?? '');
  $teacherEssayGradeMuc = (string) ($teacherEssayGradeMuc ?? '');
  require_once __DIR__ . '/_session_quiz_title_helpers.php';
?>
            <?php foreach ($quizQuestions as $qIndex => $question): ?>
              <?php
                $questionPairModel = model(\App\Models\StudyProgramSessionQuestionModel::class);
                $question = $questionPairModel->normalizeQuestionRowForEdit(is_array($question) ? $question : []);
                $questionId = (int) ($question['id'] ?? 0);
                $questionType = (string) ($question['question_type'] ?? 'choice');
                $questionType = $questionType === 'text' ? 'fill' : $questionType;
                $questionType = $questionType === 'essay' ? 'fill' : $questionType;
                $answerRow  = $quizAnswerMap[$questionId] ?? [];
                $isCorrect  = ! empty($answerRow['is_correct']);
                $selected   = (string) ($answerRow['selected_answer'] ?? '');
                if ($selected === '' && trim((string) ($answerRow['answer_text'] ?? '')) !== '') {
                    $selected = (string) ($answerRow['answer_text'] ?? '');
                }
                $correct    = (string) ($question['correct_answer'] ?? '');
                $memoryPairs = $questionType === 'word_link'
                    ? $questionPairModel->decodePlayableWordLinkPairs($question)
                    : $questionPairModel->decodePlayableMemoryPairs($question);
                $memoryStats = json_decode($selected, true);
                $options    = $questionPairModel->decodeChoiceOptions($question);
                $listeningConfig = $questionType === 'listening'
                    ? $questionPairModel->decodeListeningConfig($question)
                    : null;
                $listeningBundle = $questionType === 'listening'
                    ? $questionPairModel->decodeListeningBundle($question)
                    : ['questions' => []];
                $listeningQuestions = $listeningBundle['questions'] ?? [];
                $listeningSubmittedAnswers = $questionType === 'listening'
                    ? $questionPairModel->decodeListeningStudentAnswersPayload($selected)
                    : [];
                $listeningAnswerMode = is_array($listeningConfig)
                    ? (string) ($listeningConfig['answer_mode'] ?? 'choice')
                    : 'choice';
                $listeningAudioUrl = is_array($listeningConfig)
                    ? $questionPairModel->resolveListeningAudioUrl((string) ($listeningConfig['audio'] ?? ''))
                    : '';
                $listeningLegacyText = is_array($listeningConfig)
                    ? trim((string) ($listeningConfig['text'] ?? ''))
                    : '';
                $listeningChoiceQuestion = is_array($listeningConfig)
                    ? trim((string) ($listeningConfig['choice_question'] ?? ''))
                    : '';
                $phatAmBundle = $questionType === 'phat_am'
                    ? $questionPairModel->decodePhatAmBundle($question)
                    : ['prompt' => '', 'questions' => []];
                $phatAmPrompt = trim((string) ($phatAmBundle['prompt'] ?? ''));
                $phatAmQuestions = $phatAmBundle['questions'] ?? [];
                $phatAmRequireRecording = $questionPairModel->normalizePhatAmRequireRecording($phatAmBundle['require_recording'] ?? true);
                $phatAmAnswerMode = (string) (($phatAmQuestions[0] ?? [])['answer_mode'] ?? 'choice');
                $phatAmSubmittedAnswers = $questionType === 'phat_am'
                    ? $questionPairModel->decodePhatAmStudentAnswersPayload($selected)
                    : [];
                $phatAmRecordingPath = $questionType === 'phat_am'
                    ? $questionPairModel->decodePhatAmStudentRecording($selected)
                    : '';
                $phatAmStudentPayload = $questionType === 'phat_am'
                    ? $questionPairModel->decodePhatAmStudentPayload($selected)
                    : ['recording' => '', 'answer' => ''];
                $hasPhatAmRecording = $questionType === 'phat_am'
                    && $questionPairModel->hasPhatAmRecordingInSubmission($selected);
                $phatAmAnswerPart = $questionType === 'phat_am'
                    ? (string) ($phatAmStudentPayload['answer'] ?? '')
                    : $selected;
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

                $explanation = trim((string) ($question['answer_explanation'] ?? ''));
                $selectedKey = strtolower($questionType === 'phat_am' ? $phatAmAnswerPart : $selected);
                $selectedMulti = $questionType === 'multi_select'
                    ? $questionPairModel->parseMultiSelectLetters($selected)
                    : [];
                $correctMulti = $questionType === 'multi_select'
                    ? $questionPairModel->parseMultiSelectLetters($correct)
                    : [];
                $gameCompleted = is_array($memoryStats) && ($memoryStats['status'] ?? '') === 'completed';

                if (in_array($questionType, ['fill_blanks', 'word_select'], true)) {
                    $blankQuestionData = $questionType === 'word_select'
                        ? $questionPairModel->decodeWordSelectData($question)
                        : $questionPairModel->decodeFillBlanksData($question);
                    $fillBlanksExpectedCount = count($blankQuestionData['answers']);
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
                    $wordTranslateResultData = $questionPairModel->decodeWordTranslateData($question);
                    $wordTranslateExpectedCount = count($wordTranslateResultData['items'] ?? []);
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
                    $gameCompleted = $questionPairModel->phatAmStudentSubmissionIsComplete($question, $selected);
                }

                if ($questionType === 'listening') {
                    $gameCompleted = $questionPairModel->listeningStudentSubmissionIsComplete($question, $selected);
                }

                if ($questionType === 'image_hotspot') {
                    $imageHotspotExpectedCount = count($questionPairModel->decodeImageHotspotData($question)['targets']);
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
                    $imageLabelDropConfig = $questionPairModel->decodeImageLabelDropData($question);
                    $imageLabelDropExpectedCount = count($imageLabelDropConfig['labels'] ?? []);
                    $submittedAssignments = is_array($memoryStats) ? ($memoryStats['assignments'] ?? []) : [];
                    $gameCompleted = ($memoryStats['status'] ?? '') === 'completed'
                        && is_array($submittedAssignments)
                        && count($submittedAssignments) === $imageLabelDropExpectedCount;
                }

                $resultPill = $isCorrect ? 'Đúng' : 'Sai';
                $isEssayType = $questionType === 'fill'
                    || ($questionType === 'listening' && $listeningHasFillItem)
                    || ($questionType === 'phat_am' && $phatAmHasFillItem);
                $isTeacherGradedType = $isEssayType
                    || $questionType === 'word_translate'
                    || ($questionType === 'phat_am' && $hasPhatAmRecording);
                $teacherGraded = trim((string) ($answerRow['teacher_graded_at'] ?? '')) !== '';

                if ($questionType === 'word_translate') {
                    if (! $gameCompleted) {
                        $resultPill = 'Chưa xong';
                    } elseif (! $teacherGraded) {
                        $resultPill = $teacherReviewMode ? 'Chưa chấm' : 'Chờ chấm';
                    } else {
                        $wtItemReviews = $questionPairModel->decodeWordTranslateItemReview((string) ($answerRow['teacher_comment'] ?? ''));
                        $wtTotalItems  = count($questionPairModel->decodeWordTranslateData($question)['items'] ?? []);
                        $wtCorrectItems = $questionPairModel->countWordTranslateCorrectItems($wtItemReviews);
                        $resultPill = $wtCorrectItems . '/' . max(1, $wtTotalItems) . ' đúng';
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
                } elseif ($questionType === 'fill'
                    || ($questionType === 'listening' && $listeningHasFillItem)
                    || ($questionType === 'phat_am' && $phatAmHasFillItem)) {
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
              ?>
              <article
                class="session-quiz-result-item session-quiz-result-item--<?= esc($resultItemState) ?>"
                <?php if ($useAssessmentGradeForm && $questionId > 0): ?>
                  id="cau-<?= esc((string) $questionId) ?>"
                <?php endif ?>
              >
                <?= view('courses/_session_quiz_result_head', [
                    'qIndex'        => $qIndex,
                    'question'        => $question,
                    'questionType'    => $questionType,
                    'richTextModel'   => $richTextModel,
                    'resultPill'      => $resultPill,
                    'showPoints'      => $showPoints,
                    'answerModel'     => $answerModel,
                    'answerRow'       => $answerRow,
                ]) ?>

                <?php if ($questionType === 'listening' && is_array($listeningConfig)): ?>
                  <?php
                    if ($useAssessmentGradeForm && $answerModel === null) {
                        $answerModel = model(\App\Models\CourseAssessmentAnswerModel::class);
                    }
                    // Chỉ chấm theo câu nhỏ khi có ≥2 mục; câu đơn sửa điểm tổng trực tiếp.
                    $useCompositeGrade = $teacherReviewMode
                        && $useAssessmentGradeForm
                        && $assessmentGradeUrl !== ''
                        && $answerRow !== []
                        && count($listeningQuestions) > 1;
                    $compositeState = ['weights' => [], 'earned' => [], 'has_saved' => false];
                    if ($useCompositeGrade || ($showPoints && $listeningQuestions !== [])) {
                        $parentPoints = $answerModel !== null
                            ? $answerModel->questionPoints($question)
                            : (float) ($question['points'] ?? 1);
                        $compositeState = $questionPairModel->resolveCompositeItemGradeState(
                            $question,
                            $selected,
                            (string) ($answerRow['teacher_comment'] ?? ''),
                            $parentPoints
                        );
                    }
                    $compositeFormId = 'assessment_composite_grade_' . $questionId;
                  ?>
                  <?php if ($listeningLegacyText !== '' && $listeningAudioUrl === ''): ?>
                    <div class="session-listening-result-audio">
                      <span class="session-listening-result-label"><i class="bi bi-volume-up"></i> Nội dung đã nghe</span>
                      <div class="session-listening-result-text course-rich-content"><?= sessionQuizRenderRichHtml($listeningLegacyText, $richTextModel) ?></div>
                    </div>
                  <?php endif ?>
                  <?= view('courses/_session_listening_result_items', [
                      'listeningQuestions'         => $listeningQuestions,
                      'listeningSubmittedAnswers'  => $listeningSubmittedAnswers,
                      'questionPairModel'          => $questionPairModel,
                      'teacherReviewMode'          => $teacherReviewMode,
                      'answerLabels'               => $answerLabels,
                      'showCompositeItemGrade'     => $useCompositeGrade,
                      'showItemMaxPoints'          => $showPoints || $useCompositeGrade,
                      'compositeItemWeights'       => $compositeState['weights'],
                      'compositeItemEarned'        => $compositeState['earned'],
                      'answerModel'                => $answerModel,
                      'compositeFormId'            => $useCompositeGrade ? $compositeFormId : '',
                  ]) ?>
                  <?php if ($useCompositeGrade): ?>
                  <form
                    method="post"
                    action="<?= esc($assessmentGradeUrl) ?>"
                    class="assessment-question-grade-form session-composite-item-grade-form"
                    id="<?= esc($compositeFormId) ?>"
                    data-assessment-autosave
                    data-composite-item-live-score
                    data-max-points="<?= esc($answerModel->formatPoints($answerModel->questionPoints($question))) ?>"
                    data-saved-earned="<?= esc($answerModel->formatPoints((float) ($answerModel->resolveEarnedPoints($question, $answerRow) ?? array_sum($compositeState['earned'])))) ?>"
                  >
                    <?= csrf_field() ?>
                    <input type="hidden" name="grade_mode" value="composite_items">
                    <input type="hidden" name="question_id" value="<?= esc((string) $questionId) ?>">
                    <?php if ($assessmentHubCourseId > 0): ?>
                      <input type="hidden" name="course_id" value="<?= esc((string) $assessmentHubCourseId) ?>">
                      <input type="hidden" name="assessment_id" value="<?= esc((string) $assessmentHubAssessmentId) ?>">
                      <input type="hidden" name="user_id" value="<?= esc((string) $assessmentHubUserId) ?>">
                    <?php endif ?>
                    <?= view('courses/_assessment_question_grade_form', [
                        'questionId'          => $questionId,
                        'question'            => $question,
                        'answerRow'           => $answerRow,
                        'gradeUrl'            => $assessmentGradeUrl,
                        'hubCourseId'         => $assessmentHubCourseId,
                        'hubAssessmentId'     => $assessmentHubAssessmentId,
                        'hubUserId'           => $assessmentHubUserId,
                        'embedFieldsOnly'     => true,
                        'gradeMode'           => 'composite_items',
                        'compositeEarnedSum'  => array_sum($compositeState['earned']),
                    ]) ?>
                  </form>
                  <?php endif ?>
                <?php elseif ($questionType === 'fill'): ?>
                  <div class="session-quiz-essay-results">
                    <div class="session-quiz-essay-block session-quiz-essay-block--yours">
                      <div class="session-quiz-essay-label"><i class="bi bi-pencil-square"></i> <?= $teacherReviewMode ? 'Câu trả lời học viên' : 'Câu trả lời của bạn' ?></div>
                      <div class="course-rich-content session-quiz-essay-text"><?= $selected !== '' ? sessionQuizRenderRichHtml($selected, $richTextModel) : '<span class="muted">Chưa trả lời</span>' ?></div>
                    </div>
                    <?php if (! $teacherReviewMode && $correct !== ''): ?>
                      <div class="session-quiz-essay-block session-quiz-essay-block--model">
                        <div class="session-quiz-essay-label"><i class="bi bi-check2-circle"></i> Đáp án mẫu</div>
                        <div class="course-rich-content session-quiz-essay-text"><?= sessionQuizRenderRichHtml($correct, $richTextModel) ?></div>
                      </div>
                    <?php endif ?>
                  </div>
                  <?php if ($teacherReviewMode && $teacherEssayGradeUrl !== '' && ! $useAssessmentGradeForm): ?>
                    <?= view('courses/_teacher_student_essay_grade_form', [
                      'questionId'    => $questionId,
                      'answerRow'     => $answerRow,
                      'essayGradeUrl' => $teacherEssayGradeUrl,
                      'returnMuc'     => $teacherEssayGradeMuc,
                    ]) ?>
                  <?php endif ?>
                <?php elseif ($questionType === 'memory_match'): ?>
                  <div class="session-memory-result">
                    <?php if (is_array($memoryStats) && ($memoryStats['status'] ?? '') === 'completed'): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Đã ghép đủ <strong><?= esc((string) ($memoryStats['pairs'] ?? count($memoryPairs))) ?></strong> cặp
                        <?php if (! empty($memoryStats['moves'])): ?>
                          sau <strong><?= esc((string) $memoryStats['moves']) ?></strong> lượt lật
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa hoàn thành trò chơi lật ảnh.</p>
                    <?php endif ?>
                    <?php if ($memoryPairs !== []): ?>
                      <div class="session-memory-result-pairs">
                        <?php foreach ($memoryPairs as $pairIndex => $pair): ?>
                          <?php
                            $isWordLinkPair = isset($pair['a_type']) || isset($pair['a_value']);
                            if ($isWordLinkPair) {
                                $leftType  = (string) ($pair['a_type'] ?? 'image');
                                $rightType = (string) ($pair['b_type'] ?? 'text');
                                $leftValue = (string) ($pair['a_value'] ?? '');
                                $rightValue = (string) ($pair['b_value'] ?? '');
                            } else {
                                $leftType  = 'image';
                                $rightType = 'text';
                                $leftValue = (string) ($pair['image'] ?? '');
                                $rightValue = (string) ($pair['label'] ?? '');
                            }
                          ?>
                          <div class="session-memory-result-pair">
                            <span class="session-memory-result-card session-memory-result-card--image<?= $leftType === 'text' ? ' session-memory-result-card--text' : '' ?>">
                              <?php if ($leftType === 'image' && $leftValue !== ''): ?>
                                <img src="<?= esc($leftValue) ?>" alt="">
                              <?php else: ?>
                                <?= esc($leftValue !== '' ? $leftValue : 'Ô trái') ?>
                              <?php endif ?>
                            </span>
                            <span class="session-memory-result-card session-memory-result-card--text<?= $rightType === 'image' ? ' session-memory-result-card--image' : '' ?>">
                              <?php if ($rightType === 'image' && $rightValue !== ''): ?>
                                <img src="<?= esc($rightValue) ?>" alt="">
                              <?php else: ?>
                                <?= esc($rightValue !== '' ? $rightValue : 'Ô phải') ?>
                              <?php endif ?>
                            </span>
                            <span class="session-memory-result-pair-caption">Cặp <?= esc((string) ($pairIndex + 1)) ?></span>
                          </div>
                        <?php endforeach ?>
                      </div>
                    <?php endif ?>
                  </div>
                <?php elseif ($questionType === 'word_link'): ?>
                  <div class="session-memory-result session-word-link-result">
                    <?php if ($gameCompleted): ?>
                      <?php
                        $studentLinks = is_array($memoryStats['links'] ?? null) ? $memoryStats['links'] : [];
                        $linkCount = count($studentLinks);
                      ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Bạn đã nối <strong><?= esc((string) $linkCount) ?></strong> cặp
                        <?php if (! empty($memoryStats['moves'])): ?>
                          sau <strong><?= esc((string) $memoryStats['moves']) ?></strong> lượt nối
                        <?php endif ?>
                        <?php if ($isCorrect): ?>
                          — <strong>tất cả đều đúng</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa hoàn thành trò nối từ.</p>
                    <?php endif ?>
                    <?php if ($memoryPairs !== []): ?>
                      <p class="session-word-link-result-answer-label"><i class="bi bi-check2-circle"></i> Đáp án đúng</p>
                      <div class="session-memory-result-pairs">
                        <?php foreach ($memoryPairs as $pairIndex => $pair): ?>
                          <div class="session-memory-result-pair">
                            <span class="session-memory-result-card session-memory-result-card--image<?= ($pair['a_type'] ?? 'image') === 'text' ? ' session-memory-result-card--text' : '' ?>">
                              <?php if (($pair['a_type'] ?? 'image') === 'image' && ($pair['a_value'] ?? '') !== ''): ?>
                                <img src="<?= esc((string) ($pair['a_value'] ?? '')) ?>" alt="">
                              <?php else: ?>
                                <?= esc((string) (($pair['a_value'] ?? '') !== '' ? $pair['a_value'] : 'Ô trái')) ?>
                              <?php endif ?>
                            </span>
                            <span class="session-memory-result-card session-memory-result-card--text<?= ($pair['b_type'] ?? 'text') === 'image' ? ' session-memory-result-card--image' : '' ?>">
                              <?php if (($pair['b_type'] ?? 'text') === 'image' && ($pair['b_value'] ?? '') !== ''): ?>
                                <img src="<?= esc((string) ($pair['b_value'] ?? '')) ?>" alt="">
                              <?php else: ?>
                                <?= esc((string) (($pair['b_value'] ?? '') !== '' ? $pair['b_value'] : 'Ô phải')) ?>
                              <?php endif ?>
                            </span>
                            <span class="session-memory-result-pair-caption">Cặp <?= esc((string) ($pairIndex + 1)) ?></span>
                          </div>
                        <?php endforeach ?>
                      </div>
                    <?php endif ?>
                  </div>
                <?php elseif ($questionType === 'word_order'): ?>
                  <?php
                    $wordOrderStats = json_decode($selected, true);
                    $wordOrderCompleted = is_array($wordOrderStats) && ($wordOrderStats['status'] ?? '') === 'completed';
                    $correctSentence = $questionPairModel->formatWordOrderSentence($question);
                    $studentSentence = '';

                    if ($wordOrderCompleted && is_array($wordOrderStats['order'] ?? null)) {
                        $playableTokens = $questionPairModel->decodePlayableWordOrderTokens($question);
                        $tokenTextMap = [];

                        foreach ($playableTokens as $token) {
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
                  ?>
                  <div class="session-word-order-result">
                    <?php if ($wordOrderCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Bạn đã ghép câu
                        <?php if ($isCorrect): ?>
                          — <strong>đúng thứ tự</strong>
                        <?php else: ?>
                          — <strong>chưa đúng thứ tự</strong>
                        <?php endif ?>
                      </p>
                      <?php if ($studentSentence !== ''): ?>
                        <div class="session-word-order-result-sentence">
                          <span class="session-word-order-result-label"><i class="bi bi-pencil-square"></i> Câu bạn xếp</span>
                          <p class="session-word-order-result-text"><?= esc($studentSentence) ?></p>
                        </div>
                      <?php endif ?>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa hoàn thành trò sắp xếp câu.</p>
                    <?php endif ?>
                    <?php if ($correctSentence !== ''): ?>
                      <div class="session-word-order-result-sentence">
                        <span class="session-word-order-result-label"><i class="bi bi-check2-circle"></i> Câu đúng</span>
                        <p class="session-word-order-result-text"><?= esc($correctSentence) ?></p>
                      </div>
                    <?php endif ?>
                  </div>
                <?php elseif ($questionType === 'category_sort'): ?>
                  <?php
                    $categorySortData = $questionPairModel->decodeCategorySortData($question);
                    $categorySortStats = json_decode($selected, true);
                    $categorySortCompleted = is_array($categorySortStats) && ($categorySortStats['status'] ?? '') === 'completed';
                    $groupLabelMap = [];

                    foreach ($categorySortData['groups'] as $groupRow) {
                        $groupLabelMap[(string) ($groupRow['id'] ?? '')] = (string) ($groupRow['label'] ?? 'Nhóm');
                    }

                    $itemTextMap = [];

                    foreach ($categorySortData['items'] as $itemRow) {
                        $itemTextMap[(string) ($itemRow['id'] ?? '')] = (string) ($itemRow['text'] ?? '');
                    }
                  ?>
                  <div class="session-category-sort-result">
                    <?php if ($categorySortCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Bạn đã phân loại <strong><?= esc((string) count($categorySortStats['assignments'] ?? [])) ?></strong> thẻ
                        <?php if ($isCorrect): ?>
                          — <strong>tất cả đều đúng nhóm</strong>
                        <?php else: ?>
                          — <strong>còn thẻ sai nhóm</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa hoàn thành trò phân loại.</p>
                    <?php endif ?>
                    <p class="session-category-sort-result-label"><i class="bi bi-check2-circle"></i> Đáp án đúng theo nhóm</p>
                    <div class="session-category-sort-result-groups">
                      <?php foreach ($categorySortData['groups'] as $groupIndex => $groupRow): ?>
                        <?php
                          $groupId = (string) ($groupRow['id'] ?? '');
                          $groupItems = array_values(array_filter(
                              $categorySortData['items'],
                              static fn (array $item): bool => (string) ($item['group_id'] ?? '') === $groupId
                          ));
                        ?>
                        <div class="session-category-sort-result-group session-category-sort-result-group--<?= esc((string) ((int) $groupIndex % 4)) ?>">
                          <h5><?= esc((string) ($groupRow['label'] ?? 'Nhóm')) ?></h5>
                          <div class="session-category-sort-result-chips">
                            <?php if ($groupItems === []): ?>
                              <span class="muted">Chưa có thẻ</span>
                            <?php else: ?>
                              <?php foreach ($groupItems as $itemRow): ?>
                                <span class="session-category-sort-result-chip"><?= esc((string) ($itemRow['text'] ?? '')) ?></span>
                              <?php endforeach ?>
                            <?php endif ?>
                          </div>
                        </div>
                      <?php endforeach ?>
                    </div>
                  </div>
                <?php elseif ($questionType === 'fill_blanks'): ?>
                  <?php
                    $fillBlanksData = $questionPairModel->decodeFillBlanksData($question);
                    $fillBlanksLineGroups = $questionPairModel->parseFillBlanksLineGroups((string) ($fillBlanksData['template'] ?? ''));
                    $studentBlanks = is_array($memoryStats) ? ($memoryStats['blanks'] ?? []) : [];
                    $expectedBlanks = $fillBlanksData['answers'] ?? [];
                    $useFillBlanksGrade = $teacherReviewMode
                        && $useAssessmentGradeForm
                        && $assessmentGradeUrl !== ''
                        && $answerRow !== [];
                    $autoBlankMap = $questionPairModel->fillBlanksCorrectMap($expectedBlanks, is_array($studentBlanks) ? $studentBlanks : []);
                    $savedBlankReviews = $useFillBlanksGrade
                        ? $questionPairModel->decodeFillBlanksBlankReview((string) ($answerRow['teacher_comment'] ?? ''))
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
                    $fillBlanksFormId = 'assessment_fill_blanks_grade_' . $questionId;
                    if ($useFillBlanksGrade && $answerModel === null) {
                        $answerModel = model(\App\Models\CourseAssessmentAnswerModel::class);
                    }
                  ?>
                  <?php if ($useFillBlanksGrade): ?>
                  <form
                    method="post"
                    action="<?= esc($assessmentGradeUrl) ?>"
                    class="assessment-question-grade-form session-fill-blanks-grade-form"
                    id="<?= esc($fillBlanksFormId) ?>"
                    data-assessment-autosave
                    data-fill-blanks-live-score
                    data-max-points="<?= esc($answerModel->formatPoints($answerModel->questionPoints($question))) ?>"
                    data-blank-count="<?= esc((string) count($expectedBlanks)) ?>"
                    data-saved-earned="<?= esc($answerModel->formatPoints((float) ($answerModel->resolveEarnedPoints($question, $answerRow) ?? 0))) ?>"
                    <?= $savedBlankReviews === [] && trim((string) ($answerRow['teacher_graded_at'] ?? '')) === '' ? 'data-sync-from-blanks="1"' : '' ?>
                  >
                    <?= csrf_field() ?>
                    <input type="hidden" name="grade_mode" value="fill_blanks">
                    <input type="hidden" name="question_id" value="<?= esc((string) $questionId) ?>">
                    <?php if ($assessmentHubCourseId > 0): ?>
                      <input type="hidden" name="course_id" value="<?= esc((string) $assessmentHubCourseId) ?>">
                      <input type="hidden" name="assessment_id" value="<?= esc((string) $assessmentHubAssessmentId) ?>">
                      <input type="hidden" name="user_id" value="<?= esc((string) $assessmentHubUserId) ?>">
                    <?php endif ?>
                  <?php endif ?>
                  <div class="session-fill-blanks-result">
                    <?php if ($gameCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Bạn đã điền đủ <strong><?= esc((string) count($studentBlanks)) ?></strong> ô
                        <?php if ($isCorrect): ?>
                          — <strong>tất cả đều đúng</strong>
                        <?php else: ?>
                          — <strong>còn ô sai</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa điền đủ các ô trống.</p>
                    <?php endif ?>
                    <div class="session-fill-blanks-result-block">
                      <span class="session-fill-blanks-result-label"><i class="bi bi-pencil-square"></i> Câu bạn điền</span>
                      <?= view('courses/_session_blanks_result_sentence', [
                          'lineGroups'          => $fillBlanksLineGroups,
                          'studentBlanks'       => $studentBlanks,
                          'expectedBlanks'      => $expectedBlanks,
                          'displayMode'         => 'student',
                          'variant'             => 'fill_blanks',
                          'showBlankGradeMarks' => $useFillBlanksGrade,
                          'blankCorrectMap'     => $blankCorrectMap,
                      ]) ?>
                    </div>
                    <?php if ($useFillBlanksGrade): ?>
                      <?= view('courses/_assessment_question_grade_form', [
                          'questionId'         => $questionId,
                          'question'           => $question,
                          'answerRow'          => $answerRow,
                          'gradeUrl'           => $assessmentGradeUrl,
                          'hubCourseId'        => $assessmentHubCourseId,
                          'hubAssessmentId'    => $assessmentHubAssessmentId,
                          'hubUserId'          => $assessmentHubUserId,
                          'embedFieldsOnly'    => true,
                          'gradeMode'          => 'fill_blanks',
                          'blankCorrectMap'    => $blankCorrectMap,
                      ]) ?>
                    <?php endif ?>
                    <?php if ($expectedBlanks !== []): ?>
                      <div class="session-fill-blanks-result-block session-fill-blanks-result-block--model">
                        <span class="session-fill-blanks-result-label"><i class="bi bi-check2-circle"></i> Đáp án đúng</span>
                        <?= view('courses/_session_blanks_result_sentence', [
                            'lineGroups'     => $fillBlanksLineGroups,
                            'studentBlanks'  => $studentBlanks,
                            'expectedBlanks' => $expectedBlanks,
                            'displayMode'    => 'model',
                            'variant'        => 'fill_blanks',
                        ]) ?>
                      </div>
                    <?php endif ?>
                  </div>
                  <?php if ($useFillBlanksGrade): ?>
                  </form>
                  <?php endif ?>
                <?php elseif ($questionType === 'word_select'): ?>
                  <?php
                    $wordSelectData = $questionPairModel->decodeWordSelectData($question);
                    $wordSelectLineGroups = $questionPairModel->parseFillBlanksLineGroups((string) ($wordSelectData['template'] ?? ''));
                    $studentBlanks = is_array($memoryStats) ? ($memoryStats['blanks'] ?? []) : [];
                    $expectedBlanks = $wordSelectData['answers'] ?? [];
                  ?>
                  <div class="session-word-select-result">
                    <?php if ($gameCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Bạn đã chọn đủ <strong><?= esc((string) count($studentBlanks)) ?></strong> từ
                        <?php if ($isCorrect): ?>
                          — <strong>tất cả đều đúng</strong>
                        <?php else: ?>
                          — <strong>còn ô sai</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa chọn đủ các từ.</p>
                    <?php endif ?>
                    <div class="session-word-select-result-block">
                      <span class="session-word-select-result-label"><i class="bi bi-pencil-square"></i> Câu bạn chọn</span>
                      <?= view('courses/_session_blanks_result_sentence', [
                          'lineGroups'     => $wordSelectLineGroups,
                          'studentBlanks'  => $studentBlanks,
                          'expectedBlanks' => $expectedBlanks,
                          'displayMode'    => 'student',
                          'variant'        => 'word_select',
                      ]) ?>
                    </div>
                    <?php if ($expectedBlanks !== []): ?>
                      <div class="session-word-select-result-block session-word-select-result-block--model">
                        <span class="session-word-select-result-label"><i class="bi bi-check2-circle"></i> Đáp án đúng</span>
                        <?= view('courses/_session_blanks_result_sentence', [
                            'lineGroups'     => $wordSelectLineGroups,
                            'studentBlanks'  => $studentBlanks,
                            'expectedBlanks' => $expectedBlanks,
                            'displayMode'    => 'model',
                            'variant'        => 'word_select',
                        ]) ?>
                      </div>
                    <?php endif ?>
                  </div>
                <?php elseif ($questionType === 'word_translate'): ?>
                  <?php
                    $wordTranslateData = $questionPairModel->decodeWordTranslateData($question);
                    $wordTranslateItems = $wordTranslateData['items'] ?? [];
                    $wordTranslateColumns = (int) ($wordTranslateData['columns'] ?? 1);
                    $wordTranslateLayout = (string) ($wordTranslateData['layout'] ?? 'vertical');
                    $studentTranslations = is_array($memoryStats) ? ($memoryStats['translations'] ?? []) : [];
                    $wordTranslateItemReviews = $questionPairModel->decodeWordTranslateItemReview((string) ($answerRow['teacher_comment'] ?? ''));
                    $wordTranslateCorrectCount = $questionPairModel->countWordTranslateCorrectItems($wordTranslateItemReviews);
                    $wordTranslateResultGridClass = 'session-word-translate-result-grid';
                    $wordTranslateResultGridClass .= $wordTranslateColumns === 2 ? ' session-word-translate-result-grid--cols-2' : '';
                    $wordTranslateResultGridClass .= $wordTranslateLayout === 'horizontal'
                        ? ' session-word-translate-result-grid--horizontal'
                        : ' session-word-translate-result-grid--vertical';
                    $useAssessmentWordTranslateGrade = $teacherReviewMode
                        && $useAssessmentGradeForm
                        && $assessmentGradeUrl !== '';
                  ?>
                  <?php if ($useAssessmentWordTranslateGrade): ?>
                    <?= view('courses/_teacher_word_translate_grade_form', [
                      'questionId'    => $questionId,
                      'question'      => $question,
                      'answerRow'     => $answerRow,
                      'selectedJson'  => $selected,
                      'gradeUrl'      => $assessmentGradeUrl,
                      'formMode'      => 'assessment_hub',
                      'courseId'      => $assessmentHubCourseId,
                      'assessmentId'  => $assessmentHubAssessmentId,
                      'userId'        => $assessmentHubUserId,
                    ]) ?>
                  <?php else: ?>
                  <div class="session-word-translate-result">
                    <?php if ($gameCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        <?= $teacherReviewMode ? 'Học viên đã dịch đủ' : 'Bạn đã dịch đủ' ?> <strong><?= esc((string) count($studentTranslations)) ?></strong> từ
                        <?php if ($teacherGraded): ?>
                          — giáo viên chấm: <strong><?= esc((string) $wordTranslateCorrectCount) ?>/<?= esc((string) count($wordTranslateItems)) ?> đúng</strong>
                        <?php else: ?>
                          — <strong>đang chờ giáo viên chấm</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa dịch đủ các từ.</p>
                    <?php endif ?>

                    <div class="session-word-translate-result-list session-word-translate-result-list--review">
                      <?php foreach ($wordTranslateItems as $itemIndex => $itemRow): ?>
                        <?php
                          $normalizedItem = $questionPairModel->normalizeWordTranslateItem(is_array($itemRow) ? $itemRow : []);
                          $sourceType = (string) ($normalizedItem['source_type'] ?? 'text');
                          $sourceText = trim((string) ($normalizedItem['source'] ?? ''));
                          $sourceImageUrl = $sourceType === 'image' && $sourceText !== ''
                              ? $questionPairModel->resolveWordLinkMediaUrl($sourceText)
                              : '';
                          $expectedAnswer = trim((string) ($normalizedItem['answer'] ?? ''));
                          $studentValue = trim((string) ($studentTranslations[(int) $itemIndex] ?? ''));
                          $itemReview = $wordTranslateItemReviews[(int) $itemIndex] ?? null;
                          $itemMarkedCorrect = is_array($itemReview) && ! empty($itemReview['is_correct']);
                          $itemTeacherComment = is_array($itemReview) ? trim((string) ($itemReview['comment'] ?? '')) : '';
                          $valueClass = $studentValue === ''
                              ? 'session-word-translate-result-value session-word-translate-result-value--empty'
                              : ($teacherGraded
                                  ? 'session-word-translate-result-value session-word-translate-result-value--' . ($itemMarkedCorrect ? 'correct' : 'wrong')
                                  : 'session-word-translate-result-value session-word-translate-result-value--submitted');
                          $itemCardClass = 'session-word-translate-review-item';
                          if ($teacherGraded && $studentValue !== '') {
                              $itemCardClass .= $itemMarkedCorrect
                                  ? ' session-word-translate-review-item--correct'
                                  : ' session-word-translate-review-item--wrong';
                          }
                        ?>
                        <article class="<?= esc($itemCardClass) ?>">
                          <div class="session-word-translate-review-item-head">
                            <span class="session-word-translate-review-item-no"><?= esc((string) ((int) $itemIndex + 1)) ?></span>
                            <div class="session-word-translate-review-item-source">
                              <?php if ($sourceType === 'image' && $sourceImageUrl !== ''): ?>
                                <img class="session-word-translate-result-source-image" src="<?= esc($sourceImageUrl) ?>" alt="Từ gốc">
                              <?php else: ?>
                                <strong><?= esc($sourceText !== '' ? $sourceText : '…') ?></strong>
                              <?php endif ?>
                            </div>
                            <?php if ($teacherGraded && $studentValue !== ''): ?>
                              <span class="session-word-translate-review-item-pill session-word-translate-review-item-pill--<?= $itemMarkedCorrect ? 'correct' : 'wrong' ?>">
                                <?= $itemMarkedCorrect ? 'Đúng' : 'Sai' ?>
                              </span>
                            <?php endif ?>
                          </div>
                          <div class="session-word-translate-review-item-body">
                            <div class="session-word-translate-review-field">
                              <span class="session-word-translate-result-label"><i class="bi bi-pencil-square"></i> <?= $teacherReviewMode ? 'Học viên dịch' : 'Bạn dịch' ?></span>
                              <span class="<?= esc($valueClass) ?>"><?= $studentValue !== '' ? esc($studentValue) : '…' ?></span>
                            </div>
                            <?php if ($teacherGraded && $expectedAnswer !== ''): ?>
                              <div class="session-word-translate-review-field session-word-translate-review-field--model">
                                <span class="session-word-translate-result-label"><i class="bi bi-check2-circle"></i> Đáp án mẫu</span>
                                <span class="session-word-translate-result-value session-word-translate-result-value--model"><?= esc($expectedAnswer) ?></span>
                              </div>
                            <?php endif ?>
                            <?php if ($teacherGraded && \App\Support\HtmlContent::has($itemTeacherComment)): ?>
                              <div class="session-word-translate-review-field session-word-translate-review-field--note">
                                <span class="session-word-translate-result-label"><i class="bi bi-chat-left-text"></i> Nhận xét giáo viên</span>
                                <div class="session-word-translate-review-comment course-rich-content"><?= sessionQuizRenderRichHtml($itemTeacherComment, $richTextModel) ?></div>
                              </div>
                            <?php endif ?>
                          </div>
                        </article>
                      <?php endforeach ?>
                    </div>
                  </div>
                  <?php if ($teacherReviewMode && $teacherEssayGradeUrl !== '' && ! $useAssessmentGradeForm): ?>
                    <?= view('courses/_teacher_word_translate_grade_form', [
                      'questionId'   => $questionId,
                      'question'     => $question,
                      'answerRow'    => $answerRow,
                      'selectedJson' => $selected,
                      'gradeUrl'     => $teacherEssayGradeUrl,
                      'returnMuc'    => $teacherEssayGradeMuc,
                      'formMode'     => 'student_panel',
                    ]) ?>
                  <?php endif ?>
                  <?php endif ?>
                <?php elseif ($questionType === 'phat_am'): ?>
                  <?php
                    if ($useAssessmentGradeForm && $answerModel === null) {
                        $answerModel = model(\App\Models\CourseAssessmentAnswerModel::class);
                    }
                    // Chỉ chấm theo câu nhỏ khi có ≥2 mục; câu đơn sửa điểm tổng trực tiếp.
                    $useCompositeGrade = $teacherReviewMode
                        && $useAssessmentGradeForm
                        && $assessmentGradeUrl !== ''
                        && $answerRow !== []
                        && count($phatAmQuestions) > 1;
                    $compositeState = ['weights' => [], 'earned' => [], 'has_saved' => false];
                    if ($useCompositeGrade || ($showPoints && $phatAmQuestions !== [])) {
                        $parentPoints = $answerModel !== null
                            ? $answerModel->questionPoints($question)
                            : (float) ($question['points'] ?? 1);
                        $compositeState = $questionPairModel->resolveCompositeItemGradeState(
                            $question,
                            $selected,
                            (string) ($answerRow['teacher_comment'] ?? ''),
                            $parentPoints
                        );
                    }
                    $compositeFormId = 'assessment_composite_grade_' . $questionId;
                  ?>
                  <div class="session-phat-am-result">
                    <?php if ($gameCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        <?= $teacherReviewMode ? 'Học viên đã hoàn thành' : 'Bạn đã hoàn thành' ?>
                        <?= count($phatAmQuestions) ?> câu hỏi
                        <?php if ($useCompositeGrade): ?>
                        <?php elseif ($hasPhatAmRecording && $teacherGraded): ?>
                          — giáo viên chấm: <strong><?= $isCorrect ? 'Đúng' : 'Sai' ?></strong>
                        <?php elseif ($hasPhatAmRecording): ?>
                          — <strong>đang chờ giáo viên chấm</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending"><?= $phatAmRequireRecording ? 'Chưa hoàn thành đủ bài phát âm và câu hỏi.' : 'Chưa hoàn thành đủ câu hỏi.' ?></p>
                    <?php endif ?>
                    <?= view('courses/_session_phat_am_result_items', [
                        'question'                     => $question,
                        'questionId'                   => $questionId,
                        'answerRow'                    => $answerRow,
                        'questionPairModel'            => $questionPairModel,
                        'richTextModel'                => $richTextModel,
                        'phatAmPrompt'                 => $phatAmPrompt,
                        'phatAmQuestions'              => $phatAmQuestions,
                        'phatAmRequireRecording'       => $phatAmRequireRecording,
                        'phatAmSubmittedAnswers'       => $phatAmSubmittedAnswers,
                        'phatAmRecordingPath'          => $phatAmRecordingPath,
                        'teacherReviewMode'            => $teacherReviewMode,
                        'teacherGraded'                => $teacherGraded,
                        'isCorrect'                    => $isCorrect,
                        'gameCompleted'                => $gameCompleted,
                        'teacherEssayGradeUrl'         => $teacherEssayGradeUrl,
                        'teacherEssayGradeMuc'         => $teacherEssayGradeMuc,
                        'answerLabels'                 => $answerLabels,
                        'useAssessmentCompositeGrade'  => $useCompositeGrade,
                        'showCompositeItemGrade'       => $useCompositeGrade,
                        'showItemMaxPoints'            => $showPoints || $useCompositeGrade,
                        'compositeItemWeights'         => $compositeState['weights'],
                        'compositeItemEarned'          => $compositeState['earned'],
                        'answerModel'                  => $answerModel,
                        'compositeFormId'              => $useCompositeGrade ? $compositeFormId : '',
                    ]) ?>
                  </div>
                  <?php if ($useCompositeGrade): ?>
                  <form
                    method="post"
                    action="<?= esc($assessmentGradeUrl) ?>"
                    class="assessment-question-grade-form session-composite-item-grade-form"
                    id="<?= esc($compositeFormId) ?>"
                    data-assessment-autosave
                    data-composite-item-live-score
                    data-max-points="<?= esc($answerModel->formatPoints($answerModel->questionPoints($question))) ?>"
                    data-saved-earned="<?= esc($answerModel->formatPoints((float) ($answerModel->resolveEarnedPoints($question, $answerRow) ?? array_sum($compositeState['earned'])))) ?>"
                  >
                    <?= csrf_field() ?>
                    <input type="hidden" name="grade_mode" value="composite_items">
                    <input type="hidden" name="question_id" value="<?= esc((string) $questionId) ?>">
                    <?php if ($assessmentHubCourseId > 0): ?>
                      <input type="hidden" name="course_id" value="<?= esc((string) $assessmentHubCourseId) ?>">
                      <input type="hidden" name="assessment_id" value="<?= esc((string) $assessmentHubAssessmentId) ?>">
                      <input type="hidden" name="user_id" value="<?= esc((string) $assessmentHubUserId) ?>">
                    <?php endif ?>
                    <?= view('courses/_assessment_question_grade_form', [
                        'questionId'          => $questionId,
                        'question'            => $question,
                        'answerRow'           => $answerRow,
                        'gradeUrl'            => $assessmentGradeUrl,
                        'hubCourseId'         => $assessmentHubCourseId,
                        'hubAssessmentId'     => $assessmentHubAssessmentId,
                        'hubUserId'           => $assessmentHubUserId,
                        'embedFieldsOnly'     => true,
                        'gradeMode'           => 'composite_items',
                        'compositeEarnedSum'  => array_sum($compositeState['earned']),
                    ]) ?>
                  </form>
                  <?php endif ?>
                <?php elseif ($questionType === 'image_hotspot'): ?>
                  <?php
                    $imageHotspotData = $questionPairModel->decodeImageHotspotData($question);
                    $imageHotspotUrl = $questionPairModel->resolveImageHotspotUrl((string) ($imageHotspotData['image'] ?? ''));
                    $imageHotspotTargets = $imageHotspotData['targets'] ?? [];
                    $imageHotspotTolerance = (float) ($imageHotspotData['tolerance'] ?? 8);
                    $studentClicks = is_array($memoryStats) ? ($memoryStats['clicks'] ?? []) : [];
                    $usedClickIndexes = [];

                    $clickMatchesTarget = static function (array $target, array $click) use ($imageHotspotTolerance): bool {
                        $dx = (float) ($target['x'] ?? 0) - (float) ($click['x'] ?? 0);
                        $dy = (float) ($target['y'] ?? 0) - (float) ($click['y'] ?? 0);

                        return sqrt(($dx * $dx) + ($dy * $dy)) <= $imageHotspotTolerance;
                    };
                  ?>
                  <div class="session-image-hotspot-result">
                    <?php if ($gameCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Bạn đã chọn <strong><?= esc((string) count($studentClicks)) ?></strong> điểm trên ảnh
                        <?php if ($isCorrect): ?>
                          — <strong>đúng vị trí</strong>
                        <?php else: ?>
                          — <strong>chưa đúng vị trí</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa chọn đủ các điểm trên ảnh.</p>
                    <?php endif ?>
                    <div class="session-image-hotspot-result-block">
                      <span class="session-image-hotspot-result-label"><i class="bi bi-pencil-square"></i> Vị trí bạn chọn</span>
                      <div class="session-image-hotspot-result-canvas">
                        <img src="<?= esc($imageHotspotUrl) ?>" alt="Ảnh kết quả">
                        <div class="session-image-hotspot-result-overlay">
                          <?php foreach ($studentClicks as $clickIndex => $clickRow): ?>
                            <?php if (! is_array($clickRow)) continue; ?>
                            <?php
                              $clickCorrect = false;
                              foreach ($imageHotspotTargets as $targetIndex => $targetRow) {
                                  if (in_array($targetIndex, $usedClickIndexes, true)) {
                                      continue;
                                  }
                                  if ($clickMatchesTarget($targetRow, $clickRow)) {
                                      $usedClickIndexes[] = $targetIndex;
                                      $clickCorrect = true;
                                      break;
                                  }
                              }
                            ?>
                            <span
                              class="session-image-hotspot-result-marker session-image-hotspot-result-marker--<?= $clickCorrect ? 'correct' : 'wrong' ?>"
                              style="left: <?= esc((string) ($clickRow['x'] ?? 0)) ?>%; top: <?= esc((string) ($clickRow['y'] ?? 0)) ?>%;"
                              title="<?= esc((string) ($clickRow['x'] ?? 0)) ?>%, <?= esc((string) ($clickRow['y'] ?? 0)) ?>%"
                            ><?= esc((string) ($clickIndex + 1)) ?></span>
                          <?php endforeach ?>
                        </div>
                      </div>
                    </div>
                    <div class="session-image-hotspot-result-block session-image-hotspot-result-block--model">
                      <span class="session-image-hotspot-result-label"><i class="bi bi-check2-circle"></i> Vị trí đúng</span>
                      <div class="session-image-hotspot-result-canvas">
                        <img src="<?= esc($imageHotspotUrl) ?>" alt="Đáp án vị trí">
                        <div class="session-image-hotspot-result-overlay">
                          <?php foreach ($imageHotspotTargets as $targetIndex => $targetRow): ?>
                            <span
                              class="session-image-hotspot-result-marker session-image-hotspot-result-marker--model"
                              style="left: <?= esc((string) ($targetRow['x'] ?? 0)) ?>%; top: <?= esc((string) ($targetRow['y'] ?? 0)) ?>%; --hotspot-radius: <?= esc((string) $imageHotspotTolerance) ?>%;"
                              title="<?= esc((string) ($targetRow['label'] ?? ('Vị trí ' . ($targetIndex + 1)))) ?>"
                            ><?= esc((string) ($targetIndex + 1)) ?></span>
                          <?php endforeach ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php elseif ($questionType === 'image_label_drop'): ?>
                  <?php
                    $imageLabelDropData = $questionPairModel->decodeImageLabelDropData($question);
                    $imageLabelDropUrl = $questionPairModel->resolveImageLabelDropUrl((string) ($imageLabelDropData['image'] ?? ''));
                    $imageLabelDropZones = $imageLabelDropData['zones'] ?? [];
                    $imageLabelDropLabels = $imageLabelDropData['labels'] ?? [];
                    $imageLabelDropRadius = (float) ($imageLabelDropData['zone_radius'] ?? 10);
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
                        if (! is_array($zoneRow)) {
                            continue;
                        }

                        $zoneLabelById[(string) ($zoneRow['id'] ?? '')] = (string) ($zoneRow['label'] ?? '');
                    }

                    $submittedByLabel = [];

                    foreach ($studentAssignments as $assignmentRow) {
                        if (! is_array($assignmentRow)) {
                            continue;
                        }

                        $submittedByLabel[(string) ($assignmentRow['label_id'] ?? '')] = (string) ($assignmentRow['zone_id'] ?? '');
                    }
                  ?>
                  <div class="session-image-label-drop-result">
                    <?php if ($gameCompleted): ?>
                      <p class="session-memory-result-summary">
                        <i class="bi bi-check-circle-fill"></i>
                        Bạn đã kéo <strong><?= esc((string) count($studentAssignments)) ?></strong> nhãn vào vùng trên ảnh
                        <?php if ($isCorrect): ?>
                          — <strong>đúng vị trí</strong>
                        <?php else: ?>
                          — <strong>chưa đúng vị trí</strong>
                        <?php endif ?>
                      </p>
                    <?php else: ?>
                      <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa kéo đủ nhãn vào vùng trên ảnh.</p>
                    <?php endif ?>
                    <ul class="session-image-label-drop-result-list">
                      <?php foreach ($expectedByLabel as $labelId => $expectedZoneId): ?>
                        <?php
                          $submittedZoneId = $submittedByLabel[$labelId] ?? '';
                          $assignmentCorrect = $submittedZoneId !== '' && $submittedZoneId === $expectedZoneId;
                        ?>
                        <li class="session-image-label-drop-result-item session-image-label-drop-result-item--<?= $assignmentCorrect ? 'correct' : 'wrong' ?>">
                          <strong><?= esc($labelTextById[$labelId] ?? $labelId) ?></strong>
                          <span>
                            <?= esc($submittedZoneId !== '' ? ($zoneLabelById[$submittedZoneId] ?? $submittedZoneId) : 'Chưa gán') ?>
                            → đúng: <?= esc($zoneLabelById[$expectedZoneId] ?? $expectedZoneId) ?>
                          </span>
                        </li>
                      <?php endforeach ?>
                    </ul>
                    <div class="session-image-label-drop-result-block session-image-label-drop-result-block--model">
                      <span class="session-image-label-drop-result-label"><i class="bi bi-check2-circle"></i> Vị trí đúng trên ảnh</span>
                      <div class="session-image-label-drop-result-canvas">
                        <img src="<?= esc($imageLabelDropUrl) ?>" alt="Đáp án kéo nhãn">
                        <div class="session-image-label-drop-result-overlay">
                          <?php foreach ($imageLabelDropZones as $zoneIndex => $zoneRow): ?>
                            <?php
                              $zoneId = (string) ($zoneRow['id'] ?? '');
                              $matchedLabel = '';

                              foreach ($imageLabelDropLabels as $labelRow) {
                                  if (is_array($labelRow) && (string) ($labelRow['zone_id'] ?? '') === $zoneId) {
                                      $matchedLabel = (string) ($labelRow['text'] ?? '');
                                      break;
                                  }
                              }
                            ?>
                            <span
                              class="session-image-label-drop-result-zone"
                              style="left: <?= esc((string) ($zoneRow['x'] ?? 0)) ?>%; top: <?= esc((string) ($zoneRow['y'] ?? 0)) ?>%; --zone-radius: <?= esc((string) $imageLabelDropRadius) ?>%;"
                              title="<?= esc((string) ($zoneRow['label'] ?? ('Vùng ' . ($zoneIndex + 1)))) ?>"
                            >
                              <span class="session-image-label-drop-result-zone-no"><?= esc((string) ($zoneIndex + 1)) ?></span>
                              <?php if ($matchedLabel !== ''): ?>
                                <span class="session-image-label-drop-result-zone-text"><?= esc($matchedLabel) ?></span>
                              <?php endif ?>
                            </span>
                          <?php endforeach ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php elseif ($questionType === 'multi_select'): ?>
                <div class="session-quiz-options session-quiz-options--result session-quiz-options--multi">
                  <?php for ($i = 0, $multiCount = max(2, count($options)); $i < $multiCount; $i++): ?>
                    <?php
                      $key = chr(97 + $i);
                      $label = $answerLabels[$key] ?? strtoupper($key);
                      $optText = trim((string) ($options[$i] ?? ''));
                      if ($optText === '') {
                          continue;
                      }
                      $isSelected = in_array($key, $selectedMulti, true);
                      $isCorrectOption = in_array($key, $correctMulti, true);
                      $optionClass = 'session-quiz-option session-quiz-option--result';
                      if ($isSelected && ! $isCorrectOption) {
                          $optionClass .= ' session-quiz-option--picked-wrong';
                      } elseif ($isCorrectOption) {
                          $optionClass .= ' session-quiz-option--picked-correct';
                      }
                    ?>
                    <div class="<?= esc($optionClass) ?>">
                      <span class="session-quiz-option-key"><?= esc($label) ?></span>
                      <span class="session-quiz-option-main">
                        <span class="session-quiz-option-text"><?= esc($optText) ?></span>
                        <?php if ($isSelected): ?>
                          <span class="session-quiz-option-tag session-quiz-option-tag--yours">Bạn chọn</span>
                        <?php endif ?>
                        <?php if ($isCorrectOption && ! $isSelected): ?>
                          <span class="session-quiz-option-tag session-quiz-option-tag--answer">Đáp án đúng</span>
                        <?php endif ?>
                      </span>
                    </div>
                  <?php endfor ?>
                </div>
                <?php else: ?>
                <div class="session-quiz-options session-quiz-options--result">
                  <?php for ($i = 0; $i < 4; $i++): ?>
                    <?php
                      $key = chr(97 + $i);
                      $label = $answerLabels[$key] ?? strtoupper($key);
                      $optText = trim((string) ($options[$i] ?? ''));
                      if ($optText === '') {
                          continue;
                      }
                      $isSelected = $selectedKey === $key;
                      $isCorrectOption = strtolower($correct) === $key;
                      $optionClass = 'session-quiz-option session-quiz-option--result';
                      if ($isSelected && ! $isCorrect) {
                          $optionClass .= ' session-quiz-option--picked-wrong';
                      } elseif ($isCorrectOption) {
                          $optionClass .= ' session-quiz-option--picked-correct';
                      }
                    ?>
                    <div class="<?= esc($optionClass) ?>">
                      <span class="session-quiz-option-key"><?= esc($label) ?></span>
                      <span class="session-quiz-option-main">
                        <span class="session-quiz-option-text course-rich-content"><?= $richTextModel->renderComment($optText) ?></span>
                        <?php if ($isSelected): ?>
                          <span class="session-quiz-option-tag session-quiz-option-tag--yours">Đáp án bạn chọn</span>
                        <?php elseif ($isCorrectOption && ! $isCorrect): ?>
                          <span class="session-quiz-option-tag session-quiz-option-tag--answer">Đáp án đúng</span>
                        <?php endif ?>
                      </span>
                    </div>
                  <?php endfor ?>
                </div>
                <?php endif ?>

                <?php
                  $hideExplanation = $questionType === 'phat_am'
                      || ($teacherReviewMode && (
                          $questionType === 'fill'
                          || ($questionType === 'listening' && $listeningHasFillItem)
                      ));
                ?>
                <?php if ($explanation !== '' && ! $hideExplanation): ?>
                  <div class="session-quiz-explanation">
                    <div class="session-quiz-explanation-label"><i class="bi bi-lightbulb"></i> Giải thích</div>
                    <div class="course-rich-content"><?= $richTextModel->renderComment($explanation) ?></div>
                  </div>
                <?php endif ?>

                <?php if ($useAssessmentGradeForm && $assessmentGradeUrl !== '' && $answerRow !== [] && $questionType !== 'word_translate' && $questionType !== 'fill_blanks' && ! ($questionType === 'listening' && count($listeningQuestions) > 1) && ! ($questionType === 'phat_am' && count($phatAmQuestions) > 1)): ?>
                  <?= view('courses/_assessment_question_grade_form', [
                      'questionId'      => $questionId,
                      'question'        => $question,
                      'answerRow'       => $answerRow,
                      'gradeUrl'        => $assessmentGradeUrl,
                      'hubCourseId'     => $assessmentHubCourseId,
                      'hubAssessmentId' => $assessmentHubAssessmentId,
                      'hubUserId'       => $assessmentHubUserId,
                  ]) ?>
                <?php endif ?>
              </article>
            <?php endforeach ?>
