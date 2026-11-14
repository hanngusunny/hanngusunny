<?php
/** @var list<array<string, mixed>> $listeningQuestions */
/** @var list<array<string, mixed>> $listeningSubmittedAnswers */
/** @var \App\Models\StudyProgramSessionQuestionModel $questionPairModel */
/** @var bool $teacherReviewMode */
/** @var array<string, string> $answerLabels */

$listeningQuestions = $listeningQuestions ?? [];
$listeningSubmittedAnswers = $listeningSubmittedAnswers ?? [];
$richTextModel = $richTextModel ?? model(\App\Models\CourseMonthlyEvaluationModel::class);
$showCompositeItemGrade = ! empty($showCompositeItemGrade);
$showItemMaxPoints = ! empty($showItemMaxPoints) || $showCompositeItemGrade;
$compositeItemWeights = is_array($compositeItemWeights ?? null) ? $compositeItemWeights : [];
$compositeItemEarned = is_array($compositeItemEarned ?? null) ? $compositeItemEarned : [];
$answerModel = $answerModel ?? model(\App\Models\CourseAssessmentAnswerModel::class);

require __DIR__ . '/_session_sub_item_title_helpers.php';
require_once __DIR__ . '/_session_quiz_title_helpers.php';
?>
<ol class="session-phat-am-result-items session-listening-result-items">
  <?php foreach ($listeningQuestions as $itemIndex => $expectedItem): ?>
    <?php
      $subItem = is_array($listeningSubmittedAnswers[$itemIndex] ?? null) ? $listeningSubmittedAnswers[$itemIndex] : [];
      $itemMode = (string) ($expectedItem['answer_mode'] ?? 'choice');
      $itemChoiceQuestion = trim((string) ($expectedItem['choice_question'] ?? ''));
    ?>
    <li class="session-phat-am-result-item session-listening-result-item">
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
      <?php elseif ($itemMode === 'word_link'): ?>
        <?php
          $expectedPairs = $questionPairModel->normalizeWordLinkPairRows($expectedItem['word_link_pairs'] ?? []);
          $submittedLinks = is_array($subItem['links'] ?? null) ? $subItem['links'] : [];
        ?>
        <div class="session-memory-result session-word-link-result">
          <?php if (($subItem['status'] ?? '') === 'completed'): ?>
            <p class="session-memory-result-summary"><i class="bi bi-check-circle-fill"></i> Đã nối đủ <strong><?= esc((string) count($expectedPairs)) ?></strong> cặp</p>
          <?php else: ?>
            <p class="session-memory-result-summary session-memory-result-summary--pending">Chưa hoàn thành nối từ.</p>
          <?php endif ?>
          <?php if ($expectedPairs !== []): ?>
            <div class="session-memory-result-pairs">
              <?php foreach ($expectedPairs as $pairIndex => $pair): ?>
                <div class="session-memory-result-pair">
                  <span class="session-memory-result-card session-memory-result-card--<?= ($pair['a_type'] ?? '') === 'text' ? 'text' : 'image' ?>">
                    <?php if (($pair['a_type'] ?? '') === 'image' && ($pair['a_value'] ?? '') !== ''): ?>
                      <img src="<?= esc($questionPairModel->resolveWordLinkMediaUrl((string) $pair['a_value'])) ?>" alt="">
                    <?php else: ?>
                      <?= esc((string) ($pair['a_value'] ?? '')) ?>
                    <?php endif ?>
                  </span>
                  <span class="session-memory-result-pair-arrow" aria-hidden="true">↔</span>
                  <span class="session-memory-result-card session-memory-result-card--<?= ($pair['b_type'] ?? '') === 'text' ? 'text' : 'image' ?>">
                    <?php if (($pair['b_type'] ?? '') === 'image' && ($pair['b_value'] ?? '') !== ''): ?>
                      <img src="<?= esc($questionPairModel->resolveWordLinkMediaUrl((string) $pair['b_value'])) ?>" alt="">
                    <?php else: ?>
                      <?= esc((string) ($pair['b_value'] ?? '')) ?>
                    <?php endif ?>
                  </span>
                </div>
              <?php endforeach ?>
            </div>
          <?php endif ?>
        </div>
      <?php elseif ($itemMode === 'fill_blanks'): ?>
        <?php
          $expectedData = $questionPairModel->normalizeFillBlanksData($expectedItem['fill_blanks'] ?? []);
          $fillBlanksLineGroups = $questionPairModel->parseFillBlanksLineGroups((string) ($expectedData['template'] ?? ''));
          $submittedBlanks = is_array($subItem['blanks'] ?? null) ? $subItem['blanks'] : [];
          $expectedBlanks = $expectedData['answers'] ?? [];
          $expectedBlankCount = count($expectedBlanks);
          $filledBlankCount = 0;

          if (is_array($submittedBlanks)) {
              foreach ($submittedBlanks as $blankValue) {
                  if (trim((string) $blankValue) !== '') {
                      $filledBlankCount++;
                  }
              }
          }

          $fillBlanksCompleted = $expectedBlankCount > 0
              && is_array($submittedBlanks)
              && count($submittedBlanks) === $expectedBlankCount
              && $filledBlankCount === $expectedBlankCount;
          $fillBlanksCorrect = $fillBlanksCompleted
              && $questionPairModel->gradeFillBlanksDataSubmission($expectedData, ['blanks' => $submittedBlanks]);
        ?>
        <div class="session-fill-blanks-result">
          <?php if ($fillBlanksCompleted): ?>
            <p class="session-memory-result-summary">
              <i class="bi bi-check-circle-fill"></i>
              Bạn đã điền đủ <strong><?= esc((string) count($submittedBlanks)) ?></strong> ô
              <?php if ($fillBlanksCorrect): ?>
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
                'lineGroups'     => $fillBlanksLineGroups,
                'studentBlanks'  => $submittedBlanks,
                'expectedBlanks' => $expectedBlanks,
                'displayMode'    => 'student',
                'variant'        => 'fill_blanks',
            ]) ?>
          </div>
          <?php if (! $teacherReviewMode && $expectedBlanks !== []): ?>
            <div class="session-fill-blanks-result-block session-fill-blanks-result-block--model">
              <span class="session-fill-blanks-result-label"><i class="bi bi-check2-circle"></i> Đáp án đúng</span>
              <?= view('courses/_session_blanks_result_sentence', [
                  'lineGroups'     => $fillBlanksLineGroups,
                  'studentBlanks'  => $submittedBlanks,
                  'expectedBlanks' => $expectedBlanks,
                  'displayMode'    => 'model',
                  'variant'        => 'fill_blanks',
              ]) ?>
            </div>
          <?php endif ?>
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
              if ($optText === '') {
                  continue;
              }
              $isSelected = $selectedKey === $key;
              $isCorrectOption = $expectedKey === $key;
              $optionClass = 'session-quiz-option session-quiz-option--result';
              if ($isSelected && ! $itemChoiceCorrect) {
                  $optionClass .= ' session-quiz-option--picked-wrong';
              } elseif ($isCorrectOption && ! $itemChoiceCorrect) {
                  $optionClass .= ' session-quiz-option--picked-correct';
              }
            ?>
            <div class="<?= esc($optionClass) ?>">
              <span class="session-quiz-option-key"><?= esc($label) ?></span>
              <span class="session-quiz-option-main">
                <span class="session-quiz-option-text"><?= esc($optText) ?></span>
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
