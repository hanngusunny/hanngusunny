<?php
/** @var list<list<array{type: string, value?: string, index?: int}>> $lineGroups */
/** @var array<int, string> $studentBlanks */
/** @var array<int, string> $expectedBlanks */
/** @var 'student'|'model' $displayMode */
/** @var 'fill_blanks'|'word_select' $variant */
/** @var bool $showBlankGradeMarks */
/** @var array<int, bool> $blankCorrectMap */

$lineGroups = $lineGroups ?? [];
$studentBlanks = is_array($studentBlanks ?? null) ? $studentBlanks : [];
$expectedBlanks = is_array($expectedBlanks ?? null) ? $expectedBlanks : [];
$displayMode = ($displayMode ?? 'student') === 'model' ? 'model' : 'student';
$variant = ($variant ?? 'fill_blanks') === 'word_select' ? 'word_select' : 'fill_blanks';
$showBlankGradeMarks = ! empty($showBlankGradeMarks) && $displayMode === 'student';
$blankCorrectMap = is_array($blankCorrectMap ?? null) ? $blankCorrectMap : [];
$questionPairModel = model(\App\Models\StudyProgramSessionQuestionModel::class);

if ($variant === 'word_select') {
    $sentenceClass = 'session-word-select-result-sentence';
    if ($displayMode === 'model') {
        $sentenceClass .= ' session-word-select-result-sentence--model';
    }
    $textClass = 'session-word-select-text';
    $blankClassPrefix = 'session-word-select-result-blank';
} else {
    $sentenceClass = 'session-fill-blanks-result-sentence';
    if ($displayMode === 'model') {
        $sentenceClass .= ' session-fill-blanks-result-sentence--model';
    }
    $textClass = 'session-fill-blanks-text';
    $blankClassPrefix = 'session-fill-blanks-result-blank';
}

$sentenceClass .= ' session-blanks-result-sentence--lined';

if ($showBlankGradeMarks) {
    $sentenceClass .= ' session-blanks-result-sentence--grade';
}
?>
<div class="<?= esc($sentenceClass) ?>">
  <?php foreach ($lineGroups as $lineParts): ?>
    <?php
      $lineBlankIndexes = [];
      foreach ($lineParts as $linePart) {
          if (($linePart['type'] ?? '') === 'blank') {
              $lineBlankIndexes[] = (int) ($linePart['index'] ?? 0);
          }
      }
    ?>
    <div class="session-blanks-result-line<?= $lineParts === [] ? ' session-blanks-result-line--empty' : '' ?><?= $showBlankGradeMarks && $lineBlankIndexes !== [] ? ' session-blanks-result-line--grade' : '' ?>">
      <?php if ($lineParts === []): ?>
        <span class="session-blanks-result-line-spacer" aria-hidden="true">&nbsp;</span>
      <?php else: ?>
        <div class="session-blanks-result-line-text">
        <?php foreach ($lineParts as $part): ?>
          <?php if (($part['type'] ?? '') === 'text'): ?>
            <span class="<?= esc($textClass) ?>"><?= esc((string) ($part['value'] ?? '')) ?></span>
          <?php else: ?>
            <?php
              $blankIndex = (int) ($part['index'] ?? 0);
              $expectedValue = trim((string) ($expectedBlanks[$blankIndex] ?? ''));
            ?>
            <?php if ($displayMode === 'model'): ?>
              <span class="<?= esc($blankClassPrefix) ?> <?= esc($blankClassPrefix) ?>--model"><?= esc($expectedValue !== '' ? $expectedValue : '…') ?></span>
            <?php else: ?>
              <?php
                $studentValue = trim((string) ($studentBlanks[$blankIndex] ?? ''));
                $blankCorrect = array_key_exists($blankIndex, $blankCorrectMap)
                    ? ! empty($blankCorrectMap[$blankIndex])
                    : $questionPairModel->isFillBlankAnswerCorrect($studentValue, $expectedValue);
                $blankClass = $studentValue === ''
                    ? $blankClassPrefix . ' ' . $blankClassPrefix . '--empty'
                    : $blankClassPrefix . ' ' . $blankClassPrefix . '--' . ($blankCorrect ? 'correct' : 'wrong');
              ?>
              <span class="<?= esc($blankClass) ?>" title="<?= esc($expectedValue !== '' ? 'Đáp án: ' . $expectedValue : '') ?>">
                <?= $studentValue !== '' ? esc($studentValue) : '…' ?>
              </span>
            <?php endif ?>
          <?php endif ?>
        <?php endforeach ?>
        </div>
        <?php if ($showBlankGradeMarks && $lineBlankIndexes !== []): ?>
          <div class="session-blanks-grade-marks" role="group" aria-label="Chấm đúng từng ô">
            <?php foreach ($lineBlankIndexes as $markIndex): ?>
              <?php $markChecked = ! empty($blankCorrectMap[$markIndex]); ?>
              <label class="session-blanks-grade-mark<?= $markChecked ? ' is-checked' : '' ?>">
                <input
                  type="checkbox"
                  name="item_grades[<?= esc((string) $markIndex) ?>][is_correct]"
                  value="1"
                  <?= $markChecked ? 'checked' : '' ?>
                  data-fill-blank-correct
                  aria-label="Ô <?= esc((string) ($markIndex + 1)) ?> đúng"
                >
                <span class="session-blanks-grade-mark-box" aria-hidden="true">
                  <i class="bi bi-check-lg"></i>
                </span>
                <span class="session-blanks-grade-mark-text">Đúng</span>
              </label>
            <?php endforeach ?>
          </div>
        <?php endif ?>
      <?php endif ?>
    </div>
  <?php endforeach ?>
</div>
