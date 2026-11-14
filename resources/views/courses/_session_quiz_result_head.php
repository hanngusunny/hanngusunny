<?php
/** @var int $qIndex */
/** @var array<string, mixed> $question */
/** @var string $questionType */
/** @var object $richTextModel */
/** @var string $resultPill */
/** @var bool $showPoints */
/** @var object|null $answerModel */
/** @var array<string, mixed>|null $answerRow */

require_once __DIR__ . '/_session_quiz_title_helpers.php';

$qNo          = (int) $qIndex + 1;
$questionType = (string) ($questionType ?? 'choice');
$showPoints   = ! empty($showPoints);
$answerRow    = is_array($answerRow ?? null) ? $answerRow : null;
?>
<div class="session-quiz-result-head">
  <div class="session-quiz-item-title session-quiz-question"><?= sessionQuizRenderTitleHtml($qNo, $questionType, (string) ($question['question_text'] ?? ''), $richTextModel) ?></div>
  <?php if ($showPoints && $answerModel !== null): ?>
    <?php
      $maxPoints    = $answerModel->questionPoints($question);
      $earnedPoints = $answerModel->resolveEarnedPoints($question, $answerRow);
      $pointsPill   = $earnedPoints !== null
          ? $answerModel->formatPoints($earnedPoints) . '/' . $answerModel->formatPoints($maxPoints) . ' đ'
          : $answerModel->formatPoints($maxPoints) . ' đ · ' . ($resultPill ?? '');
    ?>
    <span class="session-quiz-result-pill session-quiz-result-pill--points"><?= esc($pointsPill) ?></span>
  <?php else: ?>
    <span class="session-quiz-result-pill"><?= esc((string) ($resultPill ?? '')) ?></span>
  <?php endif ?>
</div>
