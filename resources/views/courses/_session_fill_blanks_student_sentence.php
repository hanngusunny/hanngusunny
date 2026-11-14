<?php
/** @var list<list<array{type: string, value?: string, index?: int}>> $fillBlanksLineGroups */
/** @var int|string $questionId */
/** @var string $inputIdPrefix */
/** @var string $completeInputAttrs */

$fillBlanksLineGroups = $fillBlanksLineGroups ?? [];
$inputIdPrefix = (string) ($inputIdPrefix ?? 'fill-blank');
$completeInputAttrs = (string) ($completeInputAttrs ?? '');
?>
<div class="session-fill-blanks-sentence session-fill-blanks-sentence--lined">
  <?php foreach ($fillBlanksLineGroups as $lineParts): ?>
    <div class="session-fill-blanks-line<?= $lineParts === [] ? ' session-fill-blanks-line--empty' : '' ?>">
      <?php if ($lineParts === []): ?>
        <span class="session-fill-blanks-line-spacer" aria-hidden="true">&nbsp;</span>
      <?php else: ?>
        <?php foreach ($lineParts as $part): ?>
          <?php if (($part['type'] ?? '') === 'text'): ?>
            <span class="session-fill-blanks-text"><?= esc((string) ($part['value'] ?? '')) ?></span>
          <?php else: ?>
            <?php $blankNo = (int) ($part['index'] ?? 0) + 1; ?>
            <span class="session-fill-blanks-slot">
              <label class="visually-hidden" for="<?= esc($inputIdPrefix) ?>-<?= esc((string) $questionId) ?>-<?= esc((string) ($part['index'] ?? 0)) ?>">Ô trống <?= esc((string) $blankNo) ?></label>
              <input
                type="text"
                class="session-fill-blanks-input"
                id="<?= esc($inputIdPrefix) ?>-<?= esc((string) $questionId) ?>-<?= esc((string) ($part['index'] ?? 0)) ?>"
                data-fill-blank-input
                data-blank-index="<?= esc((string) ($part['index'] ?? 0)) ?>"
                autocomplete="off"
                spellcheck="false"
                placeholder="…"
                aria-label="Ô trống <?= esc((string) $blankNo) ?>"
              >
            </span>
          <?php endif ?>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  <?php endforeach ?>
</div>
<input
  type="hidden"
  data-fill-blanks-complete-input
  <?= $completeInputAttrs ?>
>
