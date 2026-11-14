<?php
require_once __DIR__ . '/_session_quiz_title_helpers.php';

$scorePct    = (int) ($scorePct ?? 0);
$praise      = quizScorePraise($scorePct);
$headingTag  = ($headingTag ?? 'h4') === 'h3' ? 'h3' : 'h4';
$titlePrefix = trim((string) ($titlePrefix ?? ''));
?>
<div class="session-quiz-score-praise session-quiz-score-praise--<?= esc($praise['band']) ?>">
  <span class="session-quiz-score-stickers" aria-hidden="true"><?= esc($praise['stickers']) ?></span>
  <div class="session-quiz-score-praise-copy">
    <<?= $headingTag ?>>
      <?= $titlePrefix !== '' ? esc($titlePrefix) . ' · ' : '' ?><?= esc($praise['title']) ?>
    </<?= $headingTag ?>>
    <p class="session-quiz-score-cheer"><?= esc($praise['message']) ?></p>
  </div>
</div>
