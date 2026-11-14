<?php
$itemPointsValue    = (string) ($itemPointsValue ?? '');
$itemPointsDisabled = ! empty($itemPointsDisabled);
?>
<label class="session-item-points-field">
  <span class="form-label form-label-sm mb-0">Điểm câu này</span>
  <input
    type="number"
    class="form-control form-control-sm session-item-points-input"
    min="0.01"
    max="1000"
    step="0.01"
    inputmode="decimal"
    placeholder="vd: 2"
    data-session-item-points
    value="<?= esc($itemPointsValue) ?>"
    <?= $itemPointsDisabled ? 'disabled' : '' ?>
  >
</label>
