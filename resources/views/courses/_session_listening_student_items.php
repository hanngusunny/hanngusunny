<?php
/** @var array<string, mixed> $question */
/** @var int|string $questionId */
/** @var \App\Models\StudyProgramSessionQuestionModel $questionPairModel */
/** @var list<array<string, mixed>> $listeningQuestions */
/** @var array<string, string> $answerLabels */

$listeningQuestions = $listeningQuestions ?? [];
$questionCount = count($listeningQuestions);
$richTextModel = $richTextModel ?? model(\App\Models\CourseMonthlyEvaluationModel::class);
$savedListeningAnswers = [];
$savedListeningDecoded = json_decode((string) ($savedRaw ?? ''), true);

if (is_array($savedListeningDecoded) && is_array($savedListeningDecoded['answers'] ?? null)) {
    $savedListeningAnswers = $savedListeningDecoded['answers'];
}

require __DIR__ . '/_session_sub_item_title_helpers.php';
require_once __DIR__ . '/_session_quiz_title_helpers.php';
?>
<div
  class="session-listening-student-wrap"
  data-session-listening-student
  data-listening-question-count="<?= esc((string) $questionCount) ?>"
  data-listening-question-id="<?= esc((string) $questionId) ?>"
>
  <input
    type="hidden"
    name="answers[<?= esc((string) $questionId) ?>]"
    value="<?= esc((string) ($savedRaw ?? ''), 'attr') ?>"
    data-session-listening-answer-output
  >

  <ol class="session-phat-am-student-items session-listening-student-items">
    <?php foreach ($listeningQuestions as $itemIndex => $listeningQuestion): ?>
      <?php
        $itemAnswerMode       = (string) ($listeningQuestion['answer_mode'] ?? 'choice');
        $itemChoiceQuestion   = trim((string) ($listeningQuestion['choice_question'] ?? ''));
        $itemOptions          = is_array($listeningQuestion['options'] ?? null) ? array_values($listeningQuestion['options']) : [];
        $itemTrueFalseItems   = is_array($listeningQuestion['true_false_items'] ?? null) ? $listeningQuestion['true_false_items'] : [];
        $itemMultiChoiceItems = is_array($listeningQuestion['multi_choice_items'] ?? null) ? $listeningQuestion['multi_choice_items'] : [];
        $itemWordLinkPairs    = $questionPairModel->decodePlayableWordLinkPairsFromRows(is_array($listeningQuestion['word_link_pairs'] ?? null) ? $listeningQuestion['word_link_pairs'] : []);
        $itemFillBlanks       = $questionPairModel->normalizeFillBlanksData(is_array($listeningQuestion['fill_blanks'] ?? null) ? $listeningQuestion['fill_blanks'] : []);
        $itemFillBlanksLineGroups = $questionPairModel->parseFillBlanksLineGroups((string) ($itemFillBlanks['template'] ?? ''));
        $itemUid              = (string) $questionId . '-' . (string) $itemIndex;
        $savedItemAnswer      = is_array($savedListeningAnswers[$itemIndex] ?? null)
            ? $savedListeningAnswers[$itemIndex]
            : [];
        $savedItemJson        = $savedItemAnswer !== []
            ? (string) json_encode($savedItemAnswer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
      ?>
      <li
        class="session-phat-am-student-item session-listening-student-item"
        data-session-listening-student-item
        data-listening-answer-mode="<?= esc($itemAnswerMode) ?>"
        data-listening-item-index="<?= esc((string) $itemIndex) ?>"
      >
        <div class="session-phat-am-student-item-head">
          <span class="session-phat-am-student-item-no"><?= esc((string) ((int) $itemIndex + 1)) ?></span>
          <div class="session-phat-am-student-item-title session-quiz-item-title"><?= sessionSubItemRenderTitleHtml((int) $itemIndex + 1, $itemAnswerMode, $itemChoiceQuestion, $richTextModel) ?></div>
        </div>

        <?php if ($itemAnswerMode === 'fill'): ?>
          <div class="session-quiz-essay session-listening-answer">
            <label class="visually-hidden" for="listening-fill-<?= esc($itemUid) ?>">Câu trả lời của bạn</label>
            <textarea
              class="form-control session-quiz-essay-input"
              id="listening-fill-<?= esc($itemUid) ?>"
              rows="4"
              data-listening-fill-input
              placeholder="Viết lại / trả lời theo nội dung bạn nghe được..."
            ><?= esc((string) ($savedItemAnswer['text'] ?? '')) ?></textarea>
          </div>
        <?php elseif ($itemAnswerMode === 'true_false'): ?>
          <div class="session-listening-true-false-board session-listening-answer" data-session-listening-true-false-board>
            <ol class="session-listening-true-false-student-list">
              <?php foreach ($itemTrueFalseItems as $tfIndex => $tfItem): ?>
                <?php $savedTf = (string) ($savedItemAnswer['answers'][$tfIndex] ?? ''); ?>
                <li class="session-listening-true-false-student-item">
                  <div class="session-listening-true-false-student-head">
                    <span class="session-listening-true-false-student-no"><?= esc((string) ((int) $tfIndex + 1)) ?></span>
                    <div class="session-listening-true-false-student-text course-rich-content"><?= sessionQuizRenderRichHtml((string) ($tfItem['statement'] ?? ''), $richTextModel) ?></div>
                  </div>
                  <div class="session-listening-true-false-student-choices" role="group" aria-label="Câu <?= esc((string) ((int) $tfIndex + 1)) ?>">
                    <label class="session-listening-true-false-student-choice">
                      <input type="radio" name="listening-tf-<?= esc($itemUid) ?>-<?= esc((string) $tfIndex) ?>" value="true" data-listening-true-false-choice<?= $savedTf === 'true' ? ' checked' : '' ?>>
                      <span>Đúng</span>
                    </label>
                    <label class="session-listening-true-false-student-choice">
                      <input type="radio" name="listening-tf-<?= esc($itemUid) ?>-<?= esc((string) $tfIndex) ?>" value="false" data-listening-true-false-choice<?= $savedTf === 'false' ? ' checked' : '' ?>>
                      <span>Sai</span>
                    </label>
                  </div>
                </li>
              <?php endforeach ?>
            </ol>
            <input type="hidden" value="" data-listening-true-false-complete-input data-listening-true-false-output>
            <p class="session-listening-true-false-status" data-listening-true-false-status></p>
          </div>
        <?php elseif ($itemAnswerMode === 'multi_choice'): ?>
          <div class="session-listening-multi-choice-board session-listening-answer" data-session-listening-multi-choice-board>
            <ol class="session-listening-multi-choice-student-list">
              <?php foreach ($itemMultiChoiceItems as $mcIndex => $mcItem): ?>
                <?php
                  $mcQuestion = trim((string) ($mcItem['question'] ?? ''));
                  $mcOptions  = is_array($mcItem['options'] ?? null) ? array_values($mcItem['options']) : [];
                ?>
                <li class="session-listening-multi-choice-student-item">
                  <div class="session-listening-multi-choice-student-head">
                    <span class="session-listening-multi-choice-student-no"><?= esc((string) ((int) $mcIndex + 1)) ?></span>
                    <div class="session-listening-multi-choice-student-text course-rich-content"><?= sessionQuizRenderRichHtml($mcQuestion, $richTextModel) ?></div>
                  </div>
                  <div class="session-listening-multi-choice-student-options" role="group" aria-label="Câu <?= esc((string) ((int) $mcIndex + 1)) ?>">
                    <?php foreach ($mcOptions as $optIndex => $optText): ?>
                      <?php $optionKey = chr(97 + (int) $optIndex); ?>
                      <label class="session-listening-multi-choice-student-option">
                        <input type="radio" name="listening-mc-<?= esc($itemUid) ?>-<?= esc((string) $mcIndex) ?>" value="<?= esc($optionKey) ?>" data-listening-multi-choice-choice>
                        <span class="session-listening-multi-choice-student-option-key"><?= esc(strtoupper($optionKey)) ?></span>
                        <span class="session-listening-multi-choice-student-option-text"><?= esc((string) $optText) ?></span>
                      </label>
                    <?php endforeach ?>
                  </div>
                </li>
              <?php endforeach ?>
            </ol>
            <input type="hidden" value="" data-listening-multi-choice-complete-input data-listening-multi-choice-output>
            <p class="session-listening-multi-choice-status" data-listening-multi-choice-status></p>
          </div>
        <?php elseif ($itemAnswerMode === 'word_link'): ?>
          <div
            class="session-word-link-board session-listening-answer"
            data-session-word-link-board
            data-app-base="<?= esc(rtrim(base_url(), '/')) ?>"
            data-word-link-pairs="<?= esc(json_encode($itemWordLinkPairs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
          >
            <div class="session-word-link-columns">
              <div class="session-word-link-column session-word-link-column--left">
                <h4 class="session-word-link-column-title"><i class="bi bi-layout-sidebar-inset"></i> Bên trái</h4>
                <div class="session-word-link-items" data-word-link-left></div>
              </div>
              <div class="session-word-link-column session-word-link-column--right">
                <h4 class="session-word-link-column-title"><i class="bi bi-layout-sidebar-inset-reverse"></i> Bên phải</h4>
                <div class="session-word-link-items" data-word-link-right></div>
              </div>
            </div>
            <input type="hidden" value="<?= esc($savedItemJson, 'attr') ?>" data-word-link-complete-input data-listening-word-link-output>
            <p class="session-word-link-status" data-word-link-status></p>
          </div>
        <?php elseif ($itemAnswerMode === 'fill_blanks'): ?>
          <div class="session-fill-blanks-board session-listening-answer" data-session-fill-blanks-board>
            <?= view('courses/_session_fill_blanks_student_sentence', [
                'fillBlanksLineGroups' => $itemFillBlanksLineGroups,
                'questionId'           => $itemUid,
                'inputIdPrefix'        => 'listening-blank',
                'completeInputAttrs'   => 'data-listening-fill-blanks-output' . ($savedItemJson !== '' ? ' value="' . esc($savedItemJson, 'attr') . '"' : ''),
            ]) ?>
            <p class="session-fill-blanks-status" data-fill-blanks-status></p>
          </div>
        <?php else: ?>
          <div class="session-quiz-options session-listening-choice-answer">
            <?php foreach ($itemOptions as $optIndex => $optText): ?>
              <?php
                $key = chr(97 + (int) $optIndex);
                $label = $answerLabels[$key] ?? strtoupper($key);
                $optText = trim((string) $optText);
                if ($optText === '') {
                    continue;
                }
              ?>
              <label class="session-quiz-option">
                <input type="radio" name="listening-choice-<?= esc($itemUid) ?>" value="<?= esc($key) ?>" data-listening-choice-input<?= trim((string) ($savedItemAnswer['choice'] ?? '')) === $key ? ' checked' : '' ?>>
                <span class="session-quiz-option-key"><?= esc($label) ?></span>
                <span class="session-quiz-option-text course-rich-content"><?= app(\App\Support\Questions\QuestionBankItemModel::class)->renderOptionContent($optText, $label) ?></span>
              </label>
            <?php endforeach ?>
          </div>
        <?php endif ?>
      </li>
    <?php endforeach ?>
  </ol>
</div>
