<?php
/** @var array<string, mixed> $question */
/** @var int|string $questionId */
/** @var array<string, mixed> $answerRow */
/** @var \App\Models\StudyProgramSessionQuestionModel $questionPairModel */
/** @var \App\Models\StudyProgramKnowledgeReviewQuestionModel|\App\Models\StudyProgramSessionQuestionModel $richTextModel */
/** @var string $phatAmPrompt */
/** @var list<array<string, mixed>> $phatAmQuestions */
/** @var bool $phatAmRequireRecording */
/** @var list<array<string, mixed>> $phatAmSubmittedAnswers */
/** @var string $phatAmRecordingPath */
/** @var bool $teacherReviewMode */
/** @var bool $teacherGraded */
/** @var bool $isCorrect */
/** @var bool $gameCompleted */
/** @var string $teacherEssayGradeUrl */
/** @var string $teacherEssayGradeMuc */
/** @var array<string, string> $answerLabels */

$selected = (string) ($answerRow['selected_answer'] ?? '');
$teacherReview = $questionPairModel->decodePhatAmTeacherReview((string) ($answerRow['teacher_comment'] ?? ''));
$teacherCommentHtml = trim((string) ($teacherReview['text'] ?? ''));
$teacherFeedbackPath = trim((string) ($teacherReview['recording'] ?? ''));
$teacherFeedbackUrl = $teacherFeedbackPath !== ''
    ? $questionPairModel->resolvePhatAmRecordingUrl($teacherFeedbackPath)
    : '';
$hasTeacherFeedback = $teacherGraded && $questionPairModel->hasPhatAmTeacherReviewContent((string) ($answerRow['teacher_comment'] ?? ''));
$phatAmPrompt = trim((string) ($phatAmPrompt ?? ''));
$phatAmRequireRecording = ($phatAmRequireRecording ?? true) !== false;
$phatAmRecordingPath = trim((string) ($phatAmRecordingPath ?? ''));
$richTextModel = $richTextModel ?? model(\App\Models\CourseMonthlyEvaluationModel::class);
$recordingUrl = $phatAmRecordingPath !== '' ? $questionPairModel->resolvePhatAmRecordingUrl($phatAmRecordingPath) : '';
$showCompositeItemGrade = ! empty($showCompositeItemGrade);
$showItemMaxPoints = ! empty($showItemMaxPoints) || $showCompositeItemGrade;
$compositeItemWeights = is_array($compositeItemWeights ?? null) ? $compositeItemWeights : [];
$compositeItemEarned = is_array($compositeItemEarned ?? null) ? $compositeItemEarned : [];
$answerModel = $answerModel ?? model(\App\Models\CourseAssessmentAnswerModel::class);
$useAssessmentCompositeGrade = ! empty($useAssessmentCompositeGrade);

if ($teacherCommentHtml !== '' && class_exists(\App\Support\HtmlContent::class)) {
    $teacherCommentHtml = \App\Support\HtmlContent::has($teacherCommentHtml)
        ? \App\Support\HtmlContent::render($teacherCommentHtml)
        : '';
}

require __DIR__ . '/_session_sub_item_title_helpers.php';
require_once __DIR__ . '/_session_quiz_title_helpers.php';
?>
<?php if ($phatAmPrompt !== ''): ?>
  <div class="session-phat-am-result-block session-phat-am-result-block--sample session-phat-am-result-block--shared session-phat-am-sample-box">
    <div class="session-phat-am-result-prompt session-phat-am-sample-text course-rich-content"><?= sessionQuizRenderRichHtml($phatAmPrompt, $richTextModel) ?></div>
  </div>
<?php endif ?>

<?php if ($phatAmRequireRecording || $recordingUrl !== ''): ?>
<div class="session-phat-am-result-block session-phat-am-result-block--recording session-phat-am-result-block--shared">
  <span class="session-phat-am-result-label"><i class="bi bi-mic-fill"></i> <?= $teacherReviewMode ? 'Bản ghi học viên' : 'Bản ghi của bạn' ?></span>
  <?php if ($recordingUrl !== ''): ?>
    <?= view('courses/_session_phat_am_audio_player', ['audioUrl' => $recordingUrl, 'standalone' => true]) ?>
  <?php else: ?>
    <p class="muted">Chưa có bản ghi.</p>
  <?php endif ?>
</div>
<?php endif ?>

<ol class="session-phat-am-result-items">
  <?php foreach ($phatAmQuestions as $itemIndex => $expectedItem): ?>
    <?php
      $subItem = is_array($phatAmSubmittedAnswers[$itemIndex] ?? null) ? $phatAmSubmittedAnswers[$itemIndex] : [];
      $itemMode = (string) ($expectedItem['answer_mode'] ?? 'choice');
      $itemChoiceQuestion = trim((string) ($expectedItem['choice_question'] ?? ''));
      $itemComplete = $questionPairModel->phatAmStudentQuestionIsComplete($subItem, $expectedItem);
    ?>
    <li class="session-phat-am-result-item">
      <div class="session-phat-am-student-item-head">
        <span class="session-phat-am-student-item-no"><?= esc((string) ((int) $itemIndex + 1)) ?></span>
        <div class="session-phat-am-student-item-title session-quiz-item-title"><?= sessionSubItemRenderTitleHtml((int) $itemIndex + 1, $itemMode, $itemChoiceQuestion, $richTextModel) ?></div>
        <?= view('courses/_session_composite_item_grade_field', [
            'itemIndex'               => (int) $itemIndex,
            'itemMax'                 => (float) ($compositeItemWeights[$itemIndex] ?? 0),
            'itemEarned'              => (float) ($compositeItemEarned[$itemIndex] ?? 0),
            'showCompositeItemGrade'  => $showCompositeItemGrade,
            'showItemMaxPoints'       => $showItemMaxPoints,
            'answerModel'             => $answerModel,
            'compositeFormId'         => (string) ($compositeFormId ?? ''),
        ]) ?>
      </div>

      <?php if ($itemMode === 'fill'): ?>
        <?php $studentText = trim((string) ($subItem['text'] ?? '')); ?>
        <div class="session-quiz-essay-results">
          <div class="session-quiz-essay-block session-quiz-essay-block--yours">
            <div class="session-quiz-essay-label"><i class="bi bi-pencil-square"></i> <?= $teacherReviewMode ? 'Câu trả lời học viên' : 'Câu trả lời của bạn' ?></div>
            <div class="course-rich-content session-quiz-essay-text"><?= $studentText !== '' ? sessionQuizRenderRichHtml($studentText, $richTextModel) : '<span class="muted">Chưa trả lời</span>' ?></div>
          </div>
          <?php if (! $teacherReviewMode && trim((string) ($expectedItem['correct_text'] ?? '')) !== ''): ?>
            <div class="session-quiz-essay-block session-quiz-essay-block--model">
              <div class="session-quiz-essay-label"><i class="bi bi-check2-circle"></i> Đáp án mẫu</div>
              <div class="course-rich-content session-quiz-essay-text"><?= sessionQuizRenderRichHtml((string) ($expectedItem['correct_text'] ?? ''), $richTextModel) ?></div>
            </div>
          <?php endif ?>
        </div>
      <?php elseif ($itemMode === 'true_false'): ?>
        <?php
          $expectedTfItems = $expectedItem['true_false_items'] ?? [];
          $submittedTfAnswers = is_array($subItem['answers'] ?? null) ? $subItem['answers'] : [];
        ?>
        <div class="session-listening-true-false-results">
          <ol class="session-listening-true-false-result-list">
            <?php foreach ($expectedTfItems as $tfIndex => $tfItem): ?>
              <?php
                $expectedBool = ! empty($tfItem['answer']);
                $expectedLabel = $expectedBool ? 'Đúng' : 'Sai';
                $studentVal = strtolower(trim((string) ($submittedTfAnswers[$tfIndex] ?? '')));
                $studentLabel = $studentVal === 'true' ? 'Đúng' : ($studentVal === 'false' ? 'Sai' : '—');
                $tfCorrect = $studentVal === ($expectedBool ? 'true' : 'false');
              ?>
              <li class="session-listening-true-false-result-item session-listening-true-false-result-item--<?= $tfCorrect ? 'correct' : 'wrong' ?>">
                <div class="session-listening-true-false-result-head">
                  <span class="session-listening-true-false-result-no"><?= esc((string) ((int) $tfIndex + 1)) ?></span>
                  <div class="session-listening-true-false-result-text course-rich-content"><?= sessionQuizRenderRichHtml((string) ($tfItem['statement'] ?? ''), $richTextModel) ?></div>
                </div>
                <div class="session-listening-true-false-result-meta">
                  <span class="session-listening-true-false-result-tag session-listening-true-false-result-tag--yours">Bạn chọn: <strong><?= esc($studentLabel) ?></strong></span>
                  <?php if (! $tfCorrect): ?>
                    <span class="session-listening-true-false-result-tag session-listening-true-false-result-tag--answer">Đáp án: <strong><?= esc($expectedLabel) ?></strong></span>
                  <?php endif ?>
                </div>
              </li>
            <?php endforeach ?>
          </ol>
        </div>
      <?php elseif ($itemMode === 'multi_choice'): ?>
        <?php
          $expectedMcItems = $expectedItem['multi_choice_items'] ?? [];
          $submittedMcAnswers = is_array($subItem['answers'] ?? null) ? $subItem['answers'] : [];
        ?>
        <div class="session-listening-multi-choice-results">
          <ol class="session-listening-multi-choice-result-list">
            <?php foreach ($expectedMcItems as $mcIndex => $mcItem): ?>
              <?php
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
              ?>
              <li class="session-listening-multi-choice-result-item session-listening-multi-choice-result-item--<?= $mcCorrect ? 'correct' : 'wrong' ?>">
                <div class="session-listening-multi-choice-result-head">
                  <span class="session-listening-multi-choice-result-no"><?= esc((string) ((int) $mcIndex + 1)) ?></span>
                  <div class="session-listening-multi-choice-result-text course-rich-content"><?= sessionQuizRenderRichHtml((string) ($mcItem['question'] ?? ''), $richTextModel) ?></div>
                </div>
                <div class="session-listening-multi-choice-result-meta">
                  <span class="session-listening-multi-choice-result-tag session-listening-multi-choice-result-tag--yours">Bạn chọn: <strong><?= esc($studentLabel) ?></strong></span>
                  <?php if (! $mcCorrect): ?>
                    <span class="session-listening-multi-choice-result-tag session-listening-multi-choice-result-tag--answer">Đáp án: <strong><?= esc($expectedLabel) ?></strong></span>
                  <?php endif ?>
                </div>
              </li>
            <?php endforeach ?>
          </ol>
        </div>
      <?php elseif ($itemMode === 'multi_select'): ?>
        <?php
          $itemOptions = is_array($expectedItem['options'] ?? null) ? array_values($expectedItem['options']) : [];
          $selectedMulti = $questionPairModel->parseMultiSelectLetters((string) ($subItem['choice'] ?? ''));
          $correctMulti = $questionPairModel->parseMultiSelectLetters((string) ($expectedItem['correct'] ?? ''));
        ?>
        <div class="session-quiz-options session-quiz-options--result session-quiz-options--multi">
          <?php foreach ($itemOptions as $optIndex => $optText): ?>
            <?php
              $key = chr(97 + (int) $optIndex);
              $label = $answerLabels[$key] ?? strtoupper($key);
              $optText = trim((string) $optText);
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
                <span class="session-quiz-option-text course-rich-content"><?= app(\App\Support\Questions\QuestionBankItemModel::class)->renderOptionContent($optText, $label) ?></span>
                <?php if ($isSelected): ?>
                  <span class="session-quiz-option-tag session-quiz-option-tag--yours">Bạn chọn</span>
                <?php endif ?>
                <?php if ($isCorrectOption && ! $isSelected): ?>
                  <span class="session-quiz-option-tag session-quiz-option-tag--answer">Đáp án đúng</span>
                <?php endif ?>
              </span>
            </div>
          <?php endforeach ?>
        </div>
      <?php else: ?>
        <?php
          $itemOptions = is_array($expectedItem['options'] ?? null) ? array_values($expectedItem['options']) : [];
          $selectedKey = strtolower(trim((string) ($subItem['choice'] ?? '')));
          $expectedKey = strtolower(trim((string) ($expectedItem['correct'] ?? 'a')));
          $itemChoiceCorrect = $selectedKey !== '' && $selectedKey === $expectedKey;
        ?>
        <div class="session-quiz-options session-quiz-options--result">
          <?php foreach ($itemOptions as $optIndex => $optText): ?>
            <?php
              $key = chr(97 + (int) $optIndex);
              $label = $answerLabels[$key] ?? strtoupper($key);
              $optText = trim((string) $optText);
              if ($optText === '') continue;
              $isSelected = $selectedKey === $key;
              $isCorrectOption = $expectedKey === $key;
              $optionClass = 'session-quiz-option session-quiz-option--result';
              if ($isSelected && ! $itemChoiceCorrect) $optionClass .= ' session-quiz-option--picked-wrong';
              elseif ($isCorrectOption && ! $itemChoiceCorrect) $optionClass .= ' session-quiz-option--picked-correct';
            ?>
            <div class="<?= esc($optionClass) ?>">
              <span class="session-quiz-option-key"><?= esc($label) ?></span>
              <span class="session-quiz-option-main">
                <span class="session-quiz-option-text course-rich-content"><?= app(\App\Support\Questions\QuestionBankItemModel::class)->renderOptionContent($optText, $label) ?></span>
                <?php if ($isSelected): ?>
                  <span class="session-quiz-option-tag session-quiz-option-tag--yours">Đáp án bạn chọn</span>
                <?php elseif ($isCorrectOption && ! $itemChoiceCorrect): ?>
                  <span class="session-quiz-option-tag session-quiz-option-tag--answer">Đáp án đúng</span>
                <?php endif ?>
              </span>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </li>
  <?php endforeach ?>
</ol>

<?php if ($hasTeacherFeedback && ! $teacherReviewMode): ?>
  <div class="session-phat-am-result-block session-phat-am-result-block--comment">
    <span class="session-phat-am-result-label"><i class="bi bi-chat-left-text"></i> Nhận xét giáo viên</span>
    <?php if ($teacherCommentHtml !== ''): ?>
      <div class="session-phat-am-result-comment course-rich-content"><?= $teacherCommentHtml ?></div>
    <?php endif ?>
    <?php if ($teacherFeedbackUrl !== ''): ?>
      <div class="session-phat-am-result-feedback-audio">
        <span class="session-phat-am-result-label"><i class="bi bi-megaphone"></i> Phản hồi giọng nói</span>
        <?= view('courses/_session_phat_am_audio_player', ['audioUrl' => $teacherFeedbackUrl, 'standalone' => true]) ?>
      </div>
    <?php endif ?>
  </div>
<?php endif ?>

<?php if ($teacherReviewMode && $teacherEssayGradeUrl !== '' && ! $useAssessmentCompositeGrade): ?>
  <?= view('courses/_teacher_phat_am_grade_form', [
      'questionId'   => $questionId,
      'question'     => $question,
      'answerRow'    => $answerRow,
      'selectedJson' => $selected,
      'gradeUrl'     => $teacherEssayGradeUrl,
      'returnMuc'    => $teacherEssayGradeMuc,
      'formMode'     => 'student_panel',
  ]) ?>
<?php endif ?>
