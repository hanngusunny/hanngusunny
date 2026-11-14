<?php
/** @var int $qIndex */
/** @var array<string, mixed> $question */
/** @var string $questionType */
/** @var object $richTextModel */
/** @var bool $showPoints */
/** @var object|null $answerModel */

require_once __DIR__ . '/_session_quiz_title_helpers.php';

$qNo          = (int) $qIndex + 1;
$questionType = (string) ($questionType ?? 'choice');
$showPoints   = ! empty($showPoints);
?>
<div class="session-quiz-legend">
  <div class="session-quiz-item-title"><?= sessionQuizRenderTitleHtml($qNo, $questionType, (string) ($question['question_text'] ?? ''), $richTextModel) ?></div>
  <?php if ($showPoints && $answerModel !== null): ?>
    <span class="session-quiz-points-badge"><?= esc($answerModel->formatPoints($answerModel->questionPoints($question))) ?> đ</span>
  <?php endif ?>
  <span class="session-quiz-autosave-badge" data-question-autosave-badge<?= ! empty($hasSavedDraft) ? '' : ' hidden' ?>>
    <i class="bi bi-cloud-check" aria-hidden="true"></i> Đã lưu
  </span>
</div>
