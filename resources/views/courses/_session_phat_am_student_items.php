<?php
/** @var array<string, mixed> $question */
/** @var int|string $questionId */
/** @var \App\Models\StudyProgramSessionQuestionModel $questionPairModel */
/** @var string $phatAmPrompt */
/** @var list<array<string, mixed>> $phatAmQuestions */
/** @var bool $phatAmRequireRecording */
/** @var array<string, string> $answerLabels */

$phatAmPrompt = trim((string) ($phatAmPrompt ?? ''));
$phatAmQuestions = $phatAmQuestions ?? [];
$phatAmRequireRecording = ($phatAmRequireRecording ?? true) !== false;
$questionCount = count($phatAmQuestions);
$richTextModel = $richTextModel ?? model(\App\Models\CourseMonthlyEvaluationModel::class);
$savedRecording = $questionPairModel->decodePhatAmStudentRecording((string) ($savedRaw ?? ''));
$savedRecordingUrl = $savedRecording !== '' ? $questionPairModel->resolvePhatAmRecordingUrl($savedRecording) : '';
$savedRecordingValue = $savedRecording !== ''
    ? json_encode(['recording' => $savedRecording], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : '';
$savedPhatAmAnswers = [];
$savedPhatAmDecoded = json_decode((string) ($savedRaw ?? ''), true);

if (is_array($savedPhatAmDecoded) && is_array($savedPhatAmDecoded['answers'] ?? null)) {
    $savedPhatAmAnswers = $savedPhatAmDecoded['answers'];
}

require __DIR__ . '/_session_sub_item_title_helpers.php';
require_once __DIR__ . '/_session_quiz_title_helpers.php';
?>
<div
  class="session-phat-am-student-wrap"
  data-session-phat-am-student
  data-phat-am-question-count="<?= esc((string) $questionCount) ?>"
  data-phat-am-question-id="<?= esc((string) $questionId) ?>"
  data-phat-am-require-recording="<?= $phatAmRequireRecording ? '1' : '0' ?>"
>
  <input
    type="hidden"
    name="answers[<?= esc((string) $questionId) ?>]"
    value="<?= esc((string) ($savedRaw ?? ''), 'attr') ?>"
    data-session-phat-am-answer-output
  >

  <?php if ($phatAmPrompt !== ''): ?>
    <div class="session-phat-am-sample session-phat-am-sample--shared session-phat-am-sample-box">
      <div class="session-phat-am-sample-text course-rich-content"><?= sessionQuizRenderRichHtml($phatAmPrompt, $richTextModel) ?></div>
    </div>
  <?php endif ?>

  <?php if ($phatAmRequireRecording): ?>
  <div class="session-phat-am-record-section session-phat-am-record-section--shared">
    <p class="session-phat-am-record-intro">
      <i class="bi bi-mic"></i>
      Ghi âm phát âm theo câu mẫu
    </p>
    <?= view('courses/_session_phat_am_recorder', [
        'uploadUrl'        => base_url('khoa-hoc-cua-toi/upload-ghi-am'),
        'inputName'        => '',
        'inputValue'       => $savedRecordingValue,
        'valueMode'        => 'json',
        'requireOnSubmit'  => true,
        'playerLabel'      => 'Nghe lại bản ghi',
        'statusIdleTitle'  => 'Sẵn sàng ghi âm',
        'statusIdleHint'   => 'Bấm Ghi âm rồi đọc theo câu mẫu phía trên.',
        'existingAudioUrl' => $savedRecordingUrl,
        'boardClass'       => 'session-phat-am-board--student',
    ]) ?>
  </div>
  <?php endif ?>

  <ol class="session-phat-am-student-items">
    <?php foreach ($phatAmQuestions as $itemIndex => $phatAmQuestion): ?>
      <?php
        $itemAnswerMode     = (string) ($phatAmQuestion['answer_mode'] ?? 'choice');
        $itemChoiceQuestion = trim((string) ($phatAmQuestion['choice_question'] ?? ''));
        $itemOptions        = is_array($phatAmQuestion['options'] ?? null) ? array_values($phatAmQuestion['options']) : [];
        $itemTrueFalseItems = is_array($phatAmQuestion['true_false_items'] ?? null) ? $phatAmQuestion['true_false_items'] : [];
        $itemMultiChoiceItems = is_array($phatAmQuestion['multi_choice_items'] ?? null) ? $phatAmQuestion['multi_choice_items'] : [];
        $itemUid            = (string) $questionId . '-' . (string) $itemIndex;
        $savedItemAnswer    = is_array($savedPhatAmAnswers[$itemIndex] ?? null)
            ? $savedPhatAmAnswers[$itemIndex]
            : [];
        $savedFillText      = trim((string) ($savedItemAnswer['text'] ?? ''));
      ?>
      <li
        class="session-phat-am-student-item"
        data-session-phat-am-student-item
        data-phat-am-answer-mode="<?= esc($itemAnswerMode) ?>"
        data-phat-am-item-index="<?= esc((string) $itemIndex) ?>"
      >
        <div class="session-phat-am-student-item-head">
          <span class="session-phat-am-student-item-no"><?= esc((string) ((int) $itemIndex + 1)) ?></span>
          <div class="session-phat-am-student-item-title session-quiz-item-title"><?= sessionSubItemRenderTitleHtml((int) $itemIndex + 1, $itemAnswerMode, $itemChoiceQuestion, $richTextModel) ?></div>
        </div>

        <?php if ($itemAnswerMode === 'fill'): ?>
          <div class="session-quiz-essay session-phat-am-answer">
            <label class="visually-hidden" for="phat-am-fill-<?= esc($itemUid) ?>">Câu trả lời của bạn</label>
            <textarea
              class="form-control session-quiz-essay-input"
              id="phat-am-fill-<?= esc($itemUid) ?>"
              rows="4"
              data-phat-am-fill-input
              placeholder="Viết câu trả lời của bạn..."
            ><?= esc($savedFillText) ?></textarea>
          </div>
        <?php elseif ($itemAnswerMode === 'true_false'): ?>
          <div class="session-listening-true-false-board session-phat-am-answer" data-session-listening-true-false-board>
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
                      <input type="radio" name="phat-am-tf-<?= esc($itemUid) ?>-<?= esc((string) $tfIndex) ?>" value="true" data-listening-true-false-choice<?= $savedTf === 'true' ? ' checked' : '' ?>>
                      <span>Đúng</span>
                    </label>
                    <label class="session-listening-true-false-student-choice">
                      <input type="radio" name="phat-am-tf-<?= esc($itemUid) ?>-<?= esc((string) $tfIndex) ?>" value="false" data-listening-true-false-choice<?= $savedTf === 'false' ? ' checked' : '' ?>>
                      <span>Sai</span>
                    </label>
                  </div>
                </li>
              <?php endforeach ?>
            </ol>
            <input type="hidden" value="" data-listening-true-false-complete-input data-phat-am-true-false-output>
            <p class="session-listening-true-false-status" data-listening-true-false-status></p>
          </div>
        <?php elseif ($itemAnswerMode === 'multi_choice'): ?>
          <div class="session-listening-multi-choice-board session-phat-am-answer" data-session-listening-multi-choice-board>
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
                        <input type="radio" name="phat-am-mc-<?= esc($itemUid) ?>-<?= esc((string) $mcIndex) ?>" value="<?= esc($optionKey) ?>" data-listening-multi-choice-choice>
                        <span class="session-listening-multi-choice-student-option-key"><?= esc(strtoupper($optionKey)) ?></span>
                        <span class="session-listening-multi-choice-student-option-text"><?= esc((string) $optText) ?></span>
                      </label>
                    <?php endforeach ?>
                  </div>
                </li>
              <?php endforeach ?>
            </ol>
            <input type="hidden" value="" data-listening-multi-choice-complete-input data-phat-am-multi-choice-output>
            <p class="session-listening-multi-choice-status" data-listening-multi-choice-status></p>
          </div>
        <?php elseif ($itemAnswerMode === 'multi_select'): ?>
          <div class="session-quiz-options session-quiz-options--multi session-phat-am-answer" data-session-phat-am-multi-select-group>
            <?php foreach ($itemOptions as $optIndex => $optText): ?>
              <?php
                $key = chr(97 + (int) $optIndex);
                $label = $answerLabels[$key] ?? strtoupper($key);
                $optText = trim((string) $optText);
                if ($optText === '') {
                    continue;
                }
              ?>
              <label class="session-quiz-option session-quiz-option--multi">
                <input type="checkbox" value="<?= esc($key) ?>" data-phat-am-multi-select-choice>
                <span class="session-quiz-option-key"><?= esc($label) ?></span>
                <span class="session-quiz-option-text"><?= esc($optText) ?></span>
              </label>
            <?php endforeach ?>
            <input type="hidden" value="" data-phat-am-multi-select-output>
          </div>
        <?php else: ?>
          <div class="session-quiz-options session-phat-am-choice-answer">
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
                <input type="radio" name="phat-am-choice-<?= esc($itemUid) ?>" value="<?= esc($key) ?>" data-phat-am-choice-input<?= trim((string) ($savedItemAnswer['choice'] ?? '')) === $key ? ' checked' : '' ?>>
                <span class="session-quiz-option-key"><?= esc($label) ?></span>
                <span class="session-quiz-option-text"><?= esc($optText) ?></span>
              </label>
            <?php endforeach ?>
          </div>
        <?php endif ?>
      </li>
    <?php endforeach ?>
  </ol>
</div>
