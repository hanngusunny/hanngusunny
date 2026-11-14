<?php
$item = $item ?? [];
$itemIndex = (int) ($itemIndex ?? 0);
$disabled = (bool) ($disabled ?? false);
$pairModel = model(\App\Models\StudyProgramSessionQuestionModel::class);
$normalized = $pairModel->normalizeWordTranslateItem(is_array($item) ? $item : []);
$sourceType = (string) ($normalized['source_type'] ?? 'text');
$sourceValue = (string) ($normalized['source'] ?? '');
$answerValue = (string) ($normalized['answer'] ?? '');

$renderSourceValue = static function (string $type, string $value) use ($pairModel): string {
    if ($type === 'image') {
        $displayUrl = $value !== '' ? $pairModel->resolveWordLinkMediaUrl($value) : '';

        return '<div class="session-pair-image-input-wrap" data-session-pair-image-wrap>'
            . '<div class="session-pair-image-input-row">'
            . '<input type="text"'
            . ' class="form-control form-control-sm session-pair-image-url session-word-translate-source-image session-memory-pair-input"'
            . ' data-session-word-translate-source-image'
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
        . ' type="text"'
        . ' class="form-control form-control-sm session-word-translate-source-text"'
        . ' data-session-word-translate-source-text'
        . ' value="' . esc($value) . '"'
        . ' placeholder="Từ / cụm cần dịch">';
};
?>
<li class="session-word-translate-item" data-session-word-translate-item>
  <span class="session-word-translate-item-no"><?= esc((string) ($itemIndex + 1)) ?></span>
  <div class="session-word-translate-source-cell">
    <select class="form-select form-select-sm session-word-translate-source-type" data-session-word-translate-source-type aria-label="Loại từ gốc" <?= $disabled ? 'disabled' : '' ?>>
      <option value="text" <?= $sourceType === 'text' ? 'selected' : '' ?>>Chữ</option>
      <option value="image" <?= $sourceType === 'image' ? 'selected' : '' ?>>Ảnh</option>
    </select>
    <div class="session-word-translate-source-value-wrap" data-session-word-translate-source-value-wrap>
      <?= $renderSourceValue($sourceType, $sourceValue) ?>
    </div>
  </div>
  <input
    type="text"
    class="form-control form-control-sm session-word-translate-answer-input"
    data-session-word-translate-answer-input
    value="<?= esc($answerValue) ?>"
    placeholder="Đáp án dịch đúng"
    <?= $disabled ? 'disabled' : '' ?>
  >
  <button class="btn sunny-mini btn-delete-user" type="button" data-session-word-translate-remove-item title="Xóa dòng" <?= $disabled ? 'disabled' : '' ?>>
    <i class="bi bi-trash"></i>
  </button>
</li>
