<?php
/** @var int $itemIndex */
/** @var float $itemMax */
/** @var float $itemEarned */
/** @var bool $showCompositeItemGrade */
/** @var bool $showItemMaxPoints */

$itemIndex = (int) ($itemIndex ?? 0);
$itemMax = max(0.0, (float) ($itemMax ?? 0));
$itemEarned = max(0.0, (float) ($itemEarned ?? 0));
$showCompositeItemGrade = ! empty($showCompositeItemGrade);
$showItemMaxPoints = ! empty($showItemMaxPoints) || $showCompositeItemGrade;
$answerModel = $answerModel ?? model(\App\Models\CourseAssessmentAnswerModel::class);

if (! $showItemMaxPoints) {
    return;
}
?>
<div class="session-composite-item-grade">
  <?php if ($showCompositeItemGrade): ?>
    <label class="session-composite-item-grade-label">
      <span class="session-composite-item-grade-caption">Điểm</span>
      <span class="program-question-points-input-wrap session-composite-item-grade-wrap">
        <input
          type="text"
          class="form-control program-question-points-input"
          name="item_grades[<?= esc((string) $itemIndex) ?>][points]"
          value="<?= esc($answerModel->formatPoints($itemEarned)) ?>"
          min="0"
          max="<?= esc($answerModel->formatPoints($itemMax)) ?>"
          step="0.01"
          inputmode="decimal"
          lang="en"
          autocomplete="off"
          data-composite-item-points
          aria-label="Điểm câu hỏi nhỏ <?= esc((string) ($itemIndex + 1)) ?>"
          <?php if (trim((string) ($compositeFormId ?? '')) !== ''): ?>
            form="<?= esc((string) $compositeFormId) ?>"
          <?php endif ?>
        >
        <span class="program-question-points-suffix">/ <?= esc($answerModel->formatPoints($itemMax)) ?></span>
      </span>
    </label>
  <?php else: ?>
    <span class="session-composite-item-max"><?= esc($answerModel->formatPoints($itemMax)) ?> điểm</span>
  <?php endif ?>
</div>
