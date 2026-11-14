<?php
/** @var string $phatAmPrompt */
/** @var list<array<string, mixed>> $phatAmQuestions */
/** @var bool $isPhatAmType */
/** @var bool $phatAmRequireRecording */
/** @var string $phatAmJson */
/** @var int|string $nameIndex */
/** @var callable $fieldName */

$phatAmPrompt = trim((string) ($phatAmPrompt ?? ''));
$phatAmQuestions = $phatAmQuestions ?? [];
$isPhatAmType = $isPhatAmType ?? false;
$phatAmRequireRecording = ($phatAmRequireRecording ?? true) !== false;
$requireRecordingGroupName = 'phat_am_require_recording_' . (string) ($nameIndex ?? 'q');

if ($phatAmQuestions === []) {
    $phatAmQuestions = [model(\App\Models\StudyProgramSessionQuestionModel::class)->defaultPhatAmQuestion()];
}
?>
<div class="session-phat-am-panel session-phat-am-panel--items mb-3" data-session-phat-am-panel <?= $isPhatAmType ? '' : 'hidden' ?>>
  <input
    type="hidden"
    name="<?= esc($fieldName('phat_am_json')) ?>"
    value="<?= esc($phatAmJson) ?>"
    data-session-phat-am-json
    data-original-name="<?= esc($fieldName('phat_am_json')) ?>"
    <?= $isPhatAmType ? '' : 'disabled' ?>
  >

  <div class="session-phat-am-items-head">
    <div>
      <label class="form-label mb-1">Câu mẫu phát âm</label>
      <p class="course-field-hint session-phat-am-hint mb-2" data-session-phat-am-recording-hint-required <?= $phatAmRequireRecording ? '' : 'hidden' ?>>
        <i class="bi bi-info-circle"></i>
        Học sinh ghi âm theo câu mẫu này một lần, sau đó trả lời các câu hỏi bên dưới.
      </p>
      <p class="course-field-hint session-phat-am-hint mb-2" data-session-phat-am-recording-hint-optional <?= $phatAmRequireRecording ? 'hidden' : '' ?>>
        <i class="bi bi-info-circle"></i>
        Học sinh chỉ trả lời các câu hỏi bên dưới — không cần ghi âm.
      </p>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label form-label-sm">Nội dung câu mẫu</label>
    <textarea
      class="form-control form-control-sm richtext-question richtext-compact"
      rows="3"
      data-session-phat-am-prompt
      placeholder="Ví dụ: 안녕하세요, 만나서 반갑습니다."
      <?= $isPhatAmType ? '' : 'disabled' ?>
    ><?= esc($phatAmPrompt) ?></textarea>
  </div>

  <div class="session-listening-meta mb-3">
    <div class="session-listening-mode-wrap">
      <label class="form-label form-label-sm">Yêu cầu ghi âm của học sinh</label>
      <div class="session-listening-mode-toggle">
        <label class="session-listening-mode-option">
          <input
            type="radio"
            name="<?= esc($requireRecordingGroupName) ?>"
            value="1"
            data-session-phat-am-require-recording-radio
            <?= $phatAmRequireRecording ? 'checked' : '' ?>
            <?= $isPhatAmType ? '' : 'disabled' ?>
          >
          <span>Có — phải ghi âm theo câu mẫu</span>
        </label>
        <label class="session-listening-mode-option">
          <input
            type="radio"
            name="<?= esc($requireRecordingGroupName) ?>"
            value="0"
            data-session-phat-am-require-recording-radio
            <?= ! $phatAmRequireRecording ? 'checked' : '' ?>
            <?= $isPhatAmType ? '' : 'disabled' ?>
          >
          <span>Không — chỉ trả lời câu hỏi</span>
        </label>
      </div>
    </div>
  </div>

  <div class="session-phat-am-items-head mt-2">
    <div>
      <label class="form-label mb-1">Câu hỏi cho học sinh</label>
      <p class="course-field-hint session-phat-am-hint mb-0">
        <i class="bi bi-info-circle"></i>
        Mỗi câu hỏi có kiểu trả lời riêng (trắc nghiệm, nhiều đáp án, tự luận, đúng/sai, nhiều TN).
        Nhập <strong>Điểm câu này</strong> cho từng câu. Tổng điểm câu lớn = tổng các câu nhỏ. Chỉ để trống hết thì hệ thống mới chia đều.
      </p>
    </div>
  </div>

  <ol class="session-phat-am-items-list" data-session-phat-am-questions-list>
    <?php foreach ($phatAmQuestions as $itemIndex => $phatAmQuestion): ?>
      <?php
        $itemAnswerMode      = (string) ($phatAmQuestion['answer_mode'] ?? 'choice');
        $itemChoiceQuestion  = trim((string) ($phatAmQuestion['choice_question'] ?? ''));
        $itemOptions         = is_array($phatAmQuestion['options'] ?? null) ? array_values($phatAmQuestion['options']) : [];
        $itemCorrect         = strtolower(trim((string) ($phatAmQuestion['correct'] ?? 'a')));
        $itemCorrectText     = trim((string) ($phatAmQuestion['correct_text'] ?? ''));
        $itemTrueFalseItems  = is_array($phatAmQuestion['true_false_items'] ?? null) ? $phatAmQuestion['true_false_items'] : [['statement' => '', 'answer' => true]];
        $itemMultiChoiceItems = is_array($phatAmQuestion['multi_choice_items'] ?? null) ? $phatAmQuestion['multi_choice_items'] : [['question' => '', 'options' => ['', ''], 'correct' => 'a']];
        $itemOptionSlots     = min(max(4, count($itemOptions)), 8);
        $itemModeGroupName   = 'phat-am-mode-' . (string) ($nameIndex ?? 'q') . '-' . (string) $itemIndex;
        $questionPairModel   = model(\App\Models\StudyProgramSessionQuestionModel::class);
        $itemPointsValue     = $questionPairModel->formatOptionalItemPoints(
            $questionPairModel->normalizeOptionalItemPoints($phatAmQuestion['points'] ?? null)
        );
        $itemCorrectLetters  = $itemAnswerMode === 'multi_select'
            ? $questionPairModel->parseMultiSelectLetters($itemCorrect)
            : [$itemCorrect];

        if ($itemOptionSlots < 4) {
            $itemOptionSlots = 4;
        }
      ?>
      <li class="session-phat-am-item" data-session-phat-am-question>
        <div class="session-phat-am-item-head">
          <span class="session-phat-am-item-badge" data-session-phat-am-item-badge><?= esc((string) ((int) $itemIndex + 1)) ?></span>
          <strong class="session-phat-am-item-title">Câu hỏi</strong>
          <?= view('study_programs/_session_item_points_field', [
            'itemPointsValue'    => $itemPointsValue,
            'itemPointsDisabled' => ! $isPhatAmType,
          ]) ?>
          <button
            class="btn sunny-mini session-phat-am-item-remove"
            type="button"
            data-session-phat-am-remove-question
            title="Xóa câu hỏi"
            <?= count($phatAmQuestions) <= 1 ? 'hidden' : '' ?>
          >
            <i class="bi bi-trash" aria-hidden="true"></i>
          </button>
        </div>

        <div class="session-listening-meta mt-0 mb-3">
          <div class="session-listening-mode-wrap">
            <label class="form-label form-label-sm">Kiểu trả lời của học sinh</label>
            <div class="session-listening-mode-toggle">
              <?php foreach (['choice' => 'Trắc nghiệm', 'multi_select' => 'Nhiều đáp án', 'fill' => 'Tự luận', 'true_false' => 'Đúng / Sai', 'multi_choice' => 'Nhiều TN'] as $modeKey => $modeLabel): ?>
                <label class="session-listening-mode-option">
                  <input
                    type="radio"
                    name="<?= esc($itemModeGroupName) ?>"
                    value="<?= esc($modeKey) ?>"
                    data-phat-am-item-answer-mode-radio
                    <?= $itemAnswerMode === $modeKey ? 'checked' : '' ?>
                    <?= $isPhatAmType ? '' : 'disabled' ?>
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
            data-session-phat-am-item-choice-question
            placeholder="Ví dụ: Cách phát âm nào sau đây là đúng?"
            <?= $isPhatAmType ? '' : 'disabled' ?>
          ><?= esc($itemChoiceQuestion) ?></textarea>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-phat-am-item-choice-panel <?= in_array($itemAnswerMode, ['choice', 'multi_select'], true) ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm" data-session-phat-am-item-choice-label><?= $itemAnswerMode === 'multi_select' ? 'Các lựa chọn (có thể chọn nhiều đáp án đúng)' : 'Các lựa chọn trả lời' ?></label>
          <div class="session-phat-am-item-options" data-session-phat-am-item-options data-choice-multi="<?= $itemAnswerMode === 'multi_select' ? '1' : '0' ?>">
            <?php for ($optIndex = 0; $optIndex < $itemOptionSlots; $optIndex++): ?>
              <?php $optionKey = chr(97 + $optIndex); ?>
              <div class="session-phat-am-item-option-row" data-session-phat-am-item-option-row data-option-key="<?= esc($optionKey) ?>">
                <button
                  type="button"
                  class="session-choice-option-mark<?= in_array($optionKey, $itemCorrectLetters, true) ? ' is-correct' : '' ?>"
                  data-session-phat-am-item-mark="<?= esc($optionKey) ?>"
                  title="Đánh dấu đáp án đúng"
                ><?= esc(strtoupper($optionKey)) ?></button>
                <input
                  class="form-control form-control-sm"
                  type="text"
                  value="<?= esc((string) ($itemOptions[$optIndex] ?? '')) ?>"
                  placeholder="Lựa chọn <?= esc(strtoupper($optionKey)) ?>"
                  data-session-phat-am-item-option
                  <?= $isPhatAmType ? '' : 'disabled' ?>
                >
              </div>
            <?php endfor ?>
          </div>
          <input type="hidden" value="<?= esc($itemAnswerMode === 'multi_select' ? implode(',', $itemCorrectLetters) : ($itemCorrect !== '' ? $itemCorrect : 'a')) ?>" data-session-phat-am-item-correct>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-phat-am-item-fill-panel <?= $itemAnswerMode === 'fill' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Đáp án mẫu (tự luận)</label>
          <textarea
            class="form-control form-control-sm richtext-model-answer richtext-compact"
            rows="3"
            data-session-phat-am-item-correct-text
            placeholder="Nhập đáp án mẫu để hệ thống so khớp..."
            <?= $isPhatAmType ? '' : 'disabled' ?>
          ><?= esc($itemCorrectText) ?></textarea>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-phat-am-item-tf-panel <?= $itemAnswerMode === 'true_false' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Danh sách câu đúng / sai</label>
          <ol class="session-listening-true-false-list session-phat-am-item-tf-list" data-session-phat-am-item-tf-list>
            <?php foreach ($itemTrueFalseItems as $tfIndex => $tfItem): ?>
              <?php
                $tfStatement = trim((string) ($tfItem['statement'] ?? ''));
                $tfAnswer    = ! empty($tfItem['answer']);
                $tfRowId     = 'phat-am-tf-' . $itemIndex . '-' . $tfIndex;
              ?>
              <li class="session-listening-true-false-item" data-session-phat-am-item-tf-row>
                <span class="session-listening-true-false-badge"><?= esc((string) ((int) $tfIndex + 1)) ?></span>
                <input
                  type="text"
                  class="form-control form-control-sm session-listening-true-false-statement"
                  data-session-phat-am-item-tf-statement
                  value="<?= esc($tfStatement) ?>"
                  placeholder="VD: Cách phát âm này là đúng."
                  <?= $isPhatAmType ? '' : 'disabled' ?>
                >
                <div class="session-listening-true-false-answer-toggle" role="group">
                  <label class="session-listening-true-false-answer-option">
                    <input type="radio" name="<?= esc($tfRowId) ?>" value="true" data-session-phat-am-item-tf-answer <?= $tfAnswer ? 'checked' : '' ?> <?= $isPhatAmType ? '' : 'disabled' ?>>
                    <span>Đúng</span>
                  </label>
                  <label class="session-listening-true-false-answer-option">
                    <input type="radio" name="<?= esc($tfRowId) ?>" value="false" data-session-phat-am-item-tf-answer <?= ! $tfAnswer ? 'checked' : '' ?> <?= $isPhatAmType ? '' : 'disabled' ?>>
                    <span>Sai</span>
                  </label>
                </div>
                <button class="btn sunny-mini session-listening-true-false-remove" type="button" data-session-phat-am-item-tf-remove title="Xóa câu" <?= count($itemTrueFalseItems) <= 1 ? 'hidden' : '' ?>>
                  <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
              </li>
            <?php endforeach ?>
          </ol>
          <button class="btn btn-outline btn-sm session-listening-true-false-add mt-2" type="button" data-session-phat-am-item-tf-add>
            <i class="bi bi-plus-lg"></i> Thêm câu
          </button>
        </div>

        <div class="session-phat-am-item-mode-panel" data-session-phat-am-item-mc-panel <?= $itemAnswerMode === 'multi_choice' ? '' : 'hidden' ?>>
          <label class="form-label form-label-sm">Danh sách câu trắc nghiệm</label>
          <ol class="session-listening-multi-choice-list session-phat-am-item-mc-list" data-session-phat-am-item-mc-list>
            <?php foreach ($itemMultiChoiceItems as $mcIndex => $mcItem): ?>
              <?php
                $mcQuestion = trim((string) ($mcItem['question'] ?? ''));
                $mcOptions  = is_array($mcItem['options'] ?? null) ? array_values($mcItem['options']) : ['', ''];
                $mcCorrect  = strtolower(trim((string) ($mcItem['correct'] ?? 'a')));
              ?>
              <li class="session-listening-multi-choice-item" data-session-phat-am-item-mc-row>
                <div class="session-listening-multi-choice-item-head">
                  <span class="session-listening-multi-choice-badge"><?= esc((string) ((int) $mcIndex + 1)) ?></span>
                  <input
                    type="text"
                    class="form-control form-control-sm session-listening-multi-choice-question"
                    data-session-phat-am-item-mc-question
                    value="<?= esc($mcQuestion) ?>"
                    placeholder="Câu hỏi trắc nghiệm"
                    <?= $isPhatAmType ? '' : 'disabled' ?>
                  >
                  <button class="btn sunny-mini session-listening-multi-choice-remove-item" type="button" data-session-phat-am-item-mc-remove title="Xóa câu" <?= count($itemMultiChoiceItems) <= 1 ? 'hidden' : '' ?>>
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                  </button>
                </div>
                <div class="session-listening-multi-choice-options" data-session-phat-am-item-mc-options>
                  <?php for ($mcOptIndex = 0; $mcOptIndex < max(2, count($mcOptions)); $mcOptIndex++): ?>
                    <?php $mcOptionKey = chr(97 + $mcOptIndex); ?>
                    <div class="session-listening-mc-option-row" data-session-phat-am-item-mc-option-row data-option-key="<?= esc($mcOptionKey) ?>">
                      <button type="button" class="session-choice-option-mark<?= $mcCorrect === $mcOptionKey ? ' is-correct' : '' ?>" data-session-phat-am-item-mc-mark="<?= esc($mcOptionKey) ?>"><?= esc(strtoupper($mcOptionKey)) ?></button>
                      <input type="text" class="form-control form-control-sm" value="<?= esc((string) ($mcOptions[$mcOptIndex] ?? '')) ?>" placeholder="Lựa chọn <?= esc(strtoupper($mcOptionKey)) ?>" data-session-phat-am-item-mc-option <?= $isPhatAmType ? '' : 'disabled' ?>>
                    </div>
                  <?php endfor ?>
                </div>
                <input type="hidden" value="<?= esc($mcCorrect !== '' ? $mcCorrect : 'a') ?>" data-session-phat-am-item-mc-correct>
              </li>
            <?php endforeach ?>
          </ol>
          <button class="btn btn-outline btn-sm session-listening-multi-choice-add mt-2" type="button" data-session-phat-am-item-mc-add>
            <i class="bi bi-plus-lg"></i> Thêm câu TN
          </button>
        </div>
      </li>
    <?php endforeach ?>
  </ol>

  <button class="btn btn-outline btn-sm session-phat-am-add-item mt-2" type="button" data-session-phat-am-add-question <?= $isPhatAmType ? '' : 'disabled' ?>>
    <i class="bi bi-plus-lg"></i> Thêm câu hỏi
  </button>
</div>
