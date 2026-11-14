<?php
/** @var list<list<array{type: string, value?: string, index?: int}>> $wordSelectLineGroups */
/** @var list<string> $wordSelectBank */
/** @var int|string $questionId */

$wordSelectLineGroups = $wordSelectLineGroups ?? [];
$wordSelectBank = $wordSelectBank ?? [];
?>
<div class="session-word-select-sentence session-word-select-sentence--lined">
  <?php foreach ($wordSelectLineGroups as $lineParts): ?>
    <div class="session-word-select-line<?= $lineParts === [] ? ' session-word-select-line--empty' : '' ?>">
      <?php if ($lineParts === []): ?>
        <span class="session-word-select-line-spacer" aria-hidden="true">&nbsp;</span>
      <?php else: ?>
        <?php foreach ($lineParts as $part): ?>
          <?php if (($part['type'] ?? '') === 'text'): ?>
            <span class="session-word-select-text"><?= esc((string) ($part['value'] ?? '')) ?></span>
          <?php else: ?>
            <?php $blankNo = (int) ($part['index'] ?? 0) + 1; ?>
            <span
              class="session-word-select-slot<?= $blankNo === 1 ? ' is-active' : '' ?>"
              data-word-select-slot
              data-blank-index="<?= esc((string) ($part['index'] ?? 0)) ?>"
            >
              <input type="hidden" value="" data-word-select-value>
              <button type="button" class="session-word-select-slot-btn" data-word-select-slot-btn aria-label="Ô trống <?= esc((string) $blankNo) ?>">Chọn…</button>
              <select class="session-word-select-dropdown" data-word-select-dropdown aria-label="Chọn từ cho ô <?= esc((string) $blankNo) ?>">
                <option value="">—</option>
                <?php foreach ($wordSelectBank as $bankWord): ?>
                  <option value="<?= esc((string) $bankWord) ?>"><?= esc((string) $bankWord) ?></option>
                <?php endforeach ?>
              </select>
            </span>
          <?php endif ?>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  <?php endforeach ?>
</div>
