<?php
$pair = $pair ?? [];
$pairIndex = (int) ($pairIndex ?? 0);
$pairModel = model(\App\Models\StudyProgramSessionQuestionModel::class);
$normalized = $pairModel->normalizeWordLinkPairRow(is_array($pair) ? $pair : []);
$aType = (string) ($normalized['a_type'] ?? 'image');
$bType = (string) ($normalized['b_type'] ?? 'text');
$aValue = (string) ($normalized['a_value'] ?? '');
$bValue = (string) ($normalized['b_value'] ?? '');

$renderSlotValue = static function (string $slot, string $type, string $value) use ($pairModel): string {
    if ($type === 'image') {
        $displayUrl = $value !== '' ? $pairModel->resolveWordLinkMediaUrl($value) : '';

        return '<div class="session-pair-image-input-wrap" data-session-pair-image-wrap>'
            . '<div class="session-pair-image-input-row">'
            . '<input type="text"'
            . ' class="form-control form-control-sm session-pair-image-url session-pair-slot-image-value session-memory-pair-input"'
            . ' data-session-pair-slot-image-value="' . esc($slot) . '"'
            . ' value="' . esc($displayUrl) . '"'
            . ' placeholder="Dán ảnh (Ctrl+V) — tự lưu file và điền link">'
            . '<label class="btn btn-outline btn-sm session-pair-image-file-btn" title="Chọn ảnh từ máy">'
            . '<i class="bi bi-upload" aria-hidden="true"></i>'
            . '<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="session-pair-image-file" hidden>'
            . '</label>'
            . '</div>'
            . '<p class="session-pair-image-hint" data-session-pair-image-status aria-live="polite"></p>'
            . '</div>';
    }

    return '<input'
        . ' class="form-control form-control-sm session-pair-slot-text-value"'
        . ' type="text"'
        . ' value="' . esc($value) . '"'
        . ' data-session-pair-slot-text-value="' . esc($slot) . '"'
        . ' placeholder="Nhập chữ / từ / cụm từ"'
        . '>';
};
?>
<div class="session-word-link-pair-item" data-session-word-link-pair-item>
  <div class="session-memory-pair-row session-word-link-pair-row">
    <span class="session-memory-pair-label" data-session-word-link-pair-label>Cặp <?= esc((string) ($pairIndex + 1)) ?></span>
    <div class="session-word-link-pair-slots">
      <div class="session-pair-slot" data-session-pair-slot="a">
        <div class="session-pair-slot-head">
          <span class="session-pair-slot-title">Ô trái</span>
          <select class="form-select form-select-sm session-pair-slot-type" data-session-pair-slot-type="a">
            <option value="image" <?= $aType === 'image' ? 'selected' : '' ?>>Ảnh</option>
            <option value="text" <?= $aType === 'text' ? 'selected' : '' ?>>Chữ</option>
          </select>
        </div>
        <div class="session-pair-slot-value-wrap" data-session-pair-slot-value-wrap="a">
          <?= $renderSlotValue('a', $aType, $aValue) ?>
        </div>
      </div>
      <div class="session-pair-slot" data-session-pair-slot="b">
        <div class="session-pair-slot-head">
          <span class="session-pair-slot-title">Ô phải</span>
          <select class="form-select form-select-sm session-pair-slot-type" data-session-pair-slot-type="b">
            <option value="image" <?= $bType === 'image' ? 'selected' : '' ?>>Ảnh</option>
            <option value="text" <?= $bType === 'text' ? 'selected' : '' ?>>Chữ</option>
          </select>
        </div>
        <div class="session-pair-slot-value-wrap" data-session-pair-slot-value-wrap="b">
          <?= $renderSlotValue('b', $bType, $bValue) ?>
        </div>
      </div>
    </div>
    <div class="session-memory-pair-preview session-word-link-pair-preview" data-session-word-link-pair-preview<?= ($aValue === '' && $bValue === '') ? ' hidden' : '' ?>>
      <span class="session-memory-pair-preview-card session-word-link-preview-card session-word-link-preview-card--a<?= $aValue === '' ? ' is-empty' : '' ?>" data-session-word-link-preview-a>
        <?php if ($aType === 'image' && $aValue !== ''): ?>
          <img src="<?= esc($pairModel->resolveWordLinkMediaUrl($aValue)) ?>" alt="">
        <?php elseif ($aType === 'image'): ?>
          <i class="bi bi-image" aria-hidden="true"></i>
        <?php else: ?>
          <?= $aValue !== '' ? esc($aValue) : 'Chữ' ?>
        <?php endif ?>
      </span>
      <span class="session-memory-pair-preview-card session-word-link-preview-card session-word-link-preview-card--b<?= $bValue === '' ? ' is-empty' : '' ?>" data-session-word-link-preview-b>
        <?php if ($bType === 'image' && $bValue !== ''): ?>
          <img src="<?= esc($pairModel->resolveWordLinkMediaUrl($bValue)) ?>" alt="">
        <?php elseif ($bType === 'image'): ?>
          <i class="bi bi-image" aria-hidden="true"></i>
        <?php else: ?>
          <?= $bValue !== '' ? esc($bValue) : 'Chữ' ?>
        <?php endif ?>
      </span>
    </div>
    <button class="btn sunny-mini btn-delete-user btn-remove-word-link-pair" type="button" title="Xóa cặp">
      <i class="bi bi-trash" aria-hidden="true"></i>
    </button>
  </div>
</div>
