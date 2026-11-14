<?php
/** @var string $listeningAudio */
/** @var list<array<string, mixed>> $listeningQuestions */
/** @var bool $isListeningType */
/** @var string $listeningJson */
/** @var int|string $nameIndex */
/** @var callable $fieldName */
/** @var \App\Models\StudyProgramSessionQuestionModel $pairBundleModel */

$listeningAudio = trim((string) ($listeningAudio ?? ''));
$listeningQuestions = $listeningQuestions ?? [];
$isListeningType = $isListeningType ?? false;
$pairBundleModel = $pairBundleModel ?? model(\App\Models\StudyProgramSessionQuestionModel::class);

if ($listeningQuestions === []) {
    $listeningQuestions = [$pairBundleModel->defaultListeningQuestion()];
}

$listeningModes = [
    'choice'       => 'Trắc nghiệm',
    'fill'         => 'Tự luận',
    'true_false'   => 'Đúng / Sai',
    'multi_choice' => 'Nhiều TN',
    'word_link'    => 'Nối từ',
    'fill_blanks'  => 'Nhiều chỗ trống',
];
?>
<div class="session-listening-items-panel session-phat-am-panel--items mb-3" data-session-listening-items-panel <?= $isListeningType ? '' : 'hidden' ?>>
  <input
    type="hidden"
    name="<?= esc($fieldName('listening_json')) ?>"
    value="<?= esc($listeningJson) ?>"
    data-session-listening-json
    data-original-name="<?= esc($fieldName('listening_json')) ?>"
    <?= $isListeningType ? '' : 'disabled' ?>
  >

  <div class="session-phat-am-items-head mt-2">
    <div>
      <label class="form-label mb-1">Câu hỏi cho học sinh</label>
      <p class="course-field-hint session-phat-am-hint mb-0">
        <i class="bi bi-info-circle"></i>
        Học sinh nghe file MP3 phía trên, sau đó trả lời từng câu hỏi bên dưới — mỗi câu có kiểu trả lời riêng.
        Nhập <strong>Điểm câu này</strong> cho từng câu. Tổng điểm câu lớn = tổng các câu nhỏ. Chỉ để trống hết thì hệ thống mới chia đều.
      </p>
    </div>
  </div>

  <ol class="session-phat-am-items-list session-listening-items-list" data-session-listening-questions-list>
    <?php foreach ($listeningQuestions as $itemIndex => $listeningQuestion): ?>
      <?php
        $itemAnswerMode       = (string) ($listeningQuestion['answer_mode'] ?? 'choice');
        $itemChoiceQuestion   = trim((string) ($listeningQuestion['choice_question'] ?? ''));
        $itemOptions          = is_array($listeningQuestion['options'] ?? null) ? array_values($listeningQuestion['options']) : [];
        $itemCorrect          = strtolower(trim((string) ($listeningQuestion['correct'] ?? 'a')));
        $itemCorrectText      = trim((string) ($listeningQuestion['correct_text'] ?? ''));
        $itemTrueFalseItems   = is_array($listeningQuestion['true_false_items'] ?? null) ? $listeningQuestion['true_false_items'] : [['statement' => '', 'answer' => true]];
        $itemMultiChoiceItems = is_array($listeningQuestion['multi_choice_items'] ?? null) ? $listeningQuestion['multi_choice_items'] : [['question' => '', 'options' => ['', ''], 'correct' => 'a']];
        $itemWordLinkPairs    = is_array($listeningQuestion['word_link_pairs'] ?? null) ? $listeningQuestion['word_link_pairs'] : [];
        $itemFillBlanks       = is_array($listeningQuestion['fill_blanks'] ?? null) ? $listeningQuestion['fill_blanks'] : ['template' => '', 'answers' => []];
        $itemFillBlanksTemplate = trim((string) ($itemFillBlanks['template'] ?? ''));
        $itemFillBlanksAnswers  = is_array($itemFillBlanks['answers'] ?? null) ? array_values($itemFillBlanks['answers']) : [];
        $itemOptionSlots      = min(max(4, count($itemOptions)), 8);
        $itemModeGroupName    = 'listening-mode-' . (string) ($nameIndex ?? 'q') . '-' . (string) $itemIndex;
        $itemPointsValue      = $pairBundleModel->formatOptionalItemPoints(
            $pairBundleModel->normalizeOptionalItemPoints($listeningQuestion['points'] ?? null)
        );

        if ($itemWordLinkPairs === []) {
            $itemWordLinkPairs = [['a_type' => 'image', 'a_value' => '', 'b_type' => 'text', 'b_value' => '']];
        }

        if ($itemOptionSlots < 4) {
            $itemOptionSlots = 4;
        }

        $blankCount = $pairBundleModel->countFillBlanksInTemplate($itemFillBlanksTemplate);
        if ($blankCount < 1) {
            $blankCount = max(count($itemFillBlanksAnswers), 2);
        }
      ?>
      <li class="session-phat-am-item session-listening-item" data-session-listening-question>
        <div class="session-phat-am-item-head">
          <span class="session-phat-am-item-badge" data-session-listening-item-badge><?= esc((string) ((int) $itemIndex + 1)) ?></span>
          <strong class="session-phat-am-item-title">Câu hỏi</strong>
          <?= view('study_programs/_session_item_points_field', [
            'itemPointsValue'    => $itemPointsValue,
            'itemPointsDisabled' => ! $isListeningType,
          ]) ?>
          <button
            class="btn sunny-mini session-phat-am-item-remove"
            type="button"
            data-session-listening-remove-question
            title="Xóa câu hỏi"
            <?= count($listeningQuestions) <= 1 ? 'hidden' : '' ?>
          >
            <i class="bi bi-trash" aria-hidden="true"></i>
          </button>
        </div>

        <div class="session-listening-meta mt-0 mb-3">
          <div class="session-listening-mode-wrap">
            <label class="form-label form-label-sm">Kiểu trả lời của học sinh</label>
            <div class="session-listening-mode-toggle session-listening-mode-toggle--wrap">
              <?php foreach ($listeningModes as $modeKey => $modeLabel): ?>
                <label class="session-listening-mode-option">
                  <input
                    type="radio"
                    name="<?= esc($itemModeGroupName) ?>"
                    value="<?= esc($modeKey) ?>"
                    data-listening-item-answer-mode-radio
                    <?= $itemAnswerMode === $modeKey ? 'checked' : '' ?>
                    <?= $isListeningType ? '' : 'disabled' ?>
                  >
                  <span><?= esc($modeLabel) ?></span>
                </label>
              <?php endforeach ?>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label form-label-sm">Câu hỏi</label>
          <textarea
            class="form-control form-control-sm richtext-question richtext-compact"
            rows="2"
            data-session-listening-item-choice-question
            placeholder="Ví dụ: Theo nội dung bạn vừa nghe, câu nào là đúng?"
            <?= $isListeningType ? '' : 'disabled' ?>
          ><?= esc($itemChoiceQuestion) ?></textarea>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-listening-item-choice-panel <?= $itemAnswerMode === 'choice' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Các lựa chọn trả lời</label>
          <div class="session-phat-am-item-options" data-session-listening-item-options>
            <?php for ($optIndex = 0; $optIndex < $itemOptionSlots; $optIndex++): ?>
              <?php $optionKey = chr(97 + $optIndex); ?>
              <div class="session-phat-am-item-option-row" data-session-listening-item-option-row data-option-key="<?= esc($optionKey) ?>">
                <button
                  type="button"
                  class="session-choice-option-mark<?= $itemCorrect === $optionKey ? ' is-correct' : '' ?>"
                  data-session-listening-item-mark="<?= esc($optionKey) ?>"
                  title="Đánh dấu đáp án đúng"
                ><?= esc(strtoupper($optionKey)) ?></button>
                <input
                  class="form-control form-control-sm"
                  type="text"
                  value="<?= esc((string) ($itemOptions[$optIndex] ?? '')) ?>"
                  placeholder="Lựa chọn <?= esc(strtoupper($optionKey)) ?>"
                  data-session-listening-item-option
                  <?= $isListeningType ? '' : 'disabled' ?>
                >
              </div>
            <?php endfor ?>
          </div>
          <input type="hidden" value="<?= esc($itemCorrect !== '' ? $itemCorrect : 'a') ?>" data-session-listening-item-correct>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-listening-item-fill-panel <?= $itemAnswerMode === 'fill' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Đáp án mẫu (tự luận)</label>
          <textarea
            class="form-control form-control-sm richtext-model-answer richtext-compact"
            rows="3"
            data-session-listening-item-correct-text
            placeholder="Nhập đáp án mẫu để hệ thống so khớp..."
            <?= $isListeningType ? '' : 'disabled' ?>
          ><?= esc($itemCorrectText) ?></textarea>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-listening-item-tf-panel <?= $itemAnswerMode === 'true_false' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Danh sách câu đúng / sai</label>
          <ol class="session-listening-true-false-list session-phat-am-item-tf-list" data-session-listening-item-tf-list>
            <?php foreach ($itemTrueFalseItems as $tfIndex => $tfItem): ?>
              <?php
                $tfStatement = trim((string) ($tfItem['statement'] ?? ''));
                $tfAnswer    = ! empty($tfItem['answer']);
                $tfRowId     = 'listening-tf-' . $itemIndex . '-' . $tfIndex;
              ?>
              <li class="session-listening-true-false-item" data-session-listening-item-tf-row>
                <span class="session-listening-true-false-badge"><?= esc((string) ((int) $tfIndex + 1)) ?></span>
                <input
                  type="text"
                  class="form-control form-control-sm session-listening-true-false-statement"
                  data-session-listening-item-tf-statement
                  value="<?= esc($tfStatement) ?>"
                  placeholder="VD: Học sinh đi học lúc 8 giờ sáng."
                  <?= $isListeningType ? '' : 'disabled' ?>
                >
                <div class="session-listening-true-false-answer-toggle" role="group">
                  <label class="session-listening-true-false-answer-option">
                    <input type="radio" name="<?= esc($tfRowId) ?>" value="true" data-session-listening-item-tf-answer <?= $tfAnswer ? 'checked' : '' ?> <?= $isListeningType ? '' : 'disabled' ?>>
                    <span>Đúng</span>
                  </label>
                  <label class="session-listening-true-false-answer-option">
                    <input type="radio" name="<?= esc($tfRowId) ?>" value="false" data-session-listening-item-tf-answer <?= ! $tfAnswer ? 'checked' : '' ?> <?= $isListeningType ? '' : 'disabled' ?>>
                    <span>Sai</span>
                  </label>
                </div>
                <button class="btn sunny-mini session-listening-true-false-remove" type="button" data-session-listening-item-tf-remove title="Xóa câu" <?= count($itemTrueFalseItems) <= 1 ? 'hidden' : '' ?>>
                  <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
              </li>
            <?php endforeach ?>
          </ol>
          <button class="btn btn-outline btn-sm session-listening-true-false-add mt-2" type="button" data-session-listening-item-tf-add>
            <i class="bi bi-plus-lg"></i> Thêm câu
          </button>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-listening-item-mc-panel <?= $itemAnswerMode === 'multi_choice' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Danh sách câu trắc nghiệm</label>
          <ol class="session-listening-multi-choice-list session-phat-am-item-mc-list" data-session-listening-item-mc-list>
            <?php foreach ($itemMultiChoiceItems as $mcIndex => $mcItem): ?>
              <?php
                $mcQuestion = trim((string) ($mcItem['question'] ?? ''));
                $mcOptions  = is_array($mcItem['options'] ?? null) ? array_values($mcItem['options']) : ['', ''];
                $mcCorrect  = strtolower(trim((string) ($mcItem['correct'] ?? 'a')));
              ?>
              <li class="session-listening-multi-choice-item" data-session-listening-item-mc-row>
                <div class="session-listening-multi-choice-item-head">
                  <span class="session-listening-multi-choice-badge"><?= esc((string) ((int) $mcIndex + 1)) ?></span>
                  <input
                    type="text"
                    class="form-control form-control-sm session-listening-multi-choice-question"
                    data-session-listening-item-mc-question
                    value="<?= esc($mcQuestion) ?>"
                    placeholder="Câu hỏi trắc nghiệm"
                    <?= $isListeningType ? '' : 'disabled' ?>
                  >
                  <button class="btn sunny-mini session-listening-multi-choice-remove-item" type="button" data-session-listening-item-mc-remove title="Xóa câu" <?= count($itemMultiChoiceItems) <= 1 ? 'hidden' : '' ?>>
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                  </button>
                </div>
                <div class="session-listening-multi-choice-options" data-session-listening-item-mc-options>
                  <?php for ($mcOptIndex = 0; $mcOptIndex < max(2, count($mcOptions)); $mcOptIndex++): ?>
                    <?php $mcOptionKey = chr(97 + $mcOptIndex); ?>
                    <div class="session-listening-mc-option-row" data-session-listening-item-mc-option-row data-option-key="<?= esc($mcOptionKey) ?>">
                      <button type="button" class="session-choice-option-mark<?= $mcCorrect === $mcOptionKey ? ' is-correct' : '' ?>" data-session-listening-item-mc-mark="<?= esc($mcOptionKey) ?>"><?= esc(strtoupper($mcOptionKey)) ?></button>
                      <input type="text" class="form-control form-control-sm" value="<?= esc((string) ($mcOptions[$mcOptIndex] ?? '')) ?>" placeholder="Lựa chọn <?= esc(strtoupper($mcOptionKey)) ?>" data-session-listening-item-mc-option <?= $isListeningType ? '' : 'disabled' ?>>
                    </div>
                  <?php endfor ?>
                </div>
                <input type="hidden" value="<?= esc($mcCorrect !== '' ? $mcCorrect : 'a') ?>" data-session-listening-item-mc-correct>
              </li>
            <?php endforeach ?>
          </ol>
          <button class="btn btn-outline btn-sm session-listening-multi-choice-add mt-2" type="button" data-session-listening-item-mc-add>
            <i class="bi bi-plus-lg"></i> Thêm câu TN
          </button>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-listening-item-word-link-panel <?= $itemAnswerMode === 'word_link' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Cặp nối từ – ảnh và chữ</label>
          <p class="course-field-hint session-memory-pairs-hint mb-2">
            <i class="bi bi-info-circle"></i>
            <strong>Mỗi dòng = 1 cặp gồm 2 ô.</strong> Chọn từng ô là <strong>Ảnh</strong> hoặc <strong>Chữ</strong>.
          </p>
          <div class="session-word-link-pairs-list" data-session-listening-item-word-link-list>
            <?php foreach ($itemWordLinkPairs as $pairIndex => $pair): ?>
              <?= view('study_programs/_session_word_link_pair_row', [
                  'pair'      => is_array($pair) ? $pair : [],
                  'pairIndex' => $pairIndex,
              ]) ?>
            <?php endforeach ?>
          </div>
          <button class="btn sunny-mini btn-with-icon mt-2" type="button" data-session-listening-item-word-link-add>
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Thêm cặp nối từ</span>
          </button>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-listening-item-fill-blanks-panel <?= $itemAnswerMode === 'fill_blanks' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Câu có nhiều chỗ trống</label>
          <p class="course-field-hint session-fill-blanks-hint mb-2">
            <i class="bi bi-info-circle"></i>
            Dùng <strong>___</strong> (3 gạch dưới) cho mỗi chỗ trống.
          </p>
          <div class="session-fill-blanks-template-row">
            <textarea
              class="form-control form-control-sm session-fill-blanks-template"
              rows="3"
              data-session-listening-item-fill-blanks-template
              placeholder="VD: Tôi ___ đi học lúc ___ giờ mỗi ngày."
              <?= $isListeningType ? '' : 'disabled' ?>
            ><?= esc($itemFillBlanksTemplate) ?></textarea>
            <button class="btn btn-outline btn-sm session-fill-blanks-insert" type="button" data-session-listening-item-fill-blanks-insert <?= $isListeningType ? '' : 'disabled' ?>>
              <i class="bi bi-plus-lg"></i> Chèn ___
            </button>
          </div>
          <div class="session-fill-blanks-answers-wrap mt-2">
            <label class="form-label form-label-sm mb-1">Đáp án từng ô (theo thứ tự ___)</label>
            <ol class="session-fill-blanks-answers-list" data-session-listening-item-fill-blanks-answers-list>
              <?php for ($blankIndex = 0; $blankIndex < $blankCount; $blankIndex++): ?>
                <li class="session-fill-blanks-answer-item">
                  <span class="session-fill-blanks-answer-no">Ô <?= esc((string) ($blankIndex + 1)) ?></span>
                  <input
                    type="text"
                    class="form-control form-control-sm session-fill-blanks-answer-input"
                    data-session-listening-item-fill-blanks-answer-input
                    value="<?= esc((string) ($itemFillBlanksAnswers[$blankIndex] ?? '')) ?>"
                    placeholder="Đáp án cho ô <?= esc((string) ($blankIndex + 1)) ?>"
                    <?= $isListeningType ? '' : 'disabled' ?>
                  >
                </li>
              <?php endfor ?>
            </ol>
          </div>
        </div>
      </li>
    <?php endforeach ?>
  </ol>

  <button class="btn btn-outline btn-sm session-phat-am-add-item mt-2" type="button" data-session-listening-add-question <?= $isListeningType ? '' : 'disabled' ?>>
    <i class="bi bi-plus-lg"></i> Thêm câu hỏi
  </button>
</div>
