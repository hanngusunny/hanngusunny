<?php
/** @var string $uploadUrl */
/** @var string $inputName */
/** @var string $inputValue */
/** @var string $valueMode */
/** @var bool $requireOnSubmit */
/** @var string $playerLabel */
/** @var string $statusIdle */
/** @var string $existingAudioUrl */
/** @var string $boardClass */

$uploadUrl       = trim((string) ($uploadUrl ?? ''));
$inputName       = trim((string) ($inputName ?? ''));
$inputValue      = trim((string) ($inputValue ?? ''));
$valueMode       = trim((string) ($valueMode ?? 'json'));
$requireOnSubmit = ! isset($requireOnSubmit) || $requireOnSubmit;
$playerLabel     = trim((string) ($playerLabel ?? 'Nghe lại bản ghi'));
$statusIdleTitle = trim((string) ($statusIdleTitle ?? 'Sẵn sàng ghi âm'));
$statusIdleHint  = trim((string) ($statusIdleHint ?? $statusIdle ?? 'Bấm Ghi âm rồi đọc theo câu mẫu phía trên.'));
$existingAudioUrl = trim((string) ($existingAudioUrl ?? ''));
$boardClass      = trim((string) ($boardClass ?? ''));

if ($uploadUrl === '') {
    return;
}
?>
<div
  class="session-phat-am-board<?= $boardClass !== '' ? ' ' . esc($boardClass, 'attr') : '' ?>"
  data-session-phat-am-board
  data-phat-am-upload-url="<?= esc($uploadUrl, 'attr') ?>"
  data-phat-am-value-mode="<?= esc($valueMode, 'attr') ?>"
  data-phat-am-require-submit="<?= $requireOnSubmit ? '1' : '0' ?>"
  data-csrf-name="_token"
  data-csrf-hash="<?= esc(csrf_token(), 'attr') ?>"
  data-phat-am-idle-title="<?= esc($statusIdleTitle, 'attr') ?>"
  data-phat-am-idle-hint="<?= esc($statusIdleHint, 'attr') ?>"
  <?= $existingAudioUrl !== '' ? 'data-phat-am-existing-url="' . esc($existingAudioUrl, 'attr') . '"' : '' ?>
>
  <div class="session-phat-am-recorder">
    <div class="session-phat-am-recorder-actions">
      <button type="button" class="session-phat-am-btn session-phat-am-btn--record" data-phat-am-record>
        <span class="session-phat-am-btn-icon" aria-hidden="true"><i class="bi bi-mic-fill"></i></span>
        <span class="session-phat-am-btn-text">
          <strong>Ghi âm</strong>
          <small>Bấm để bắt đầu</small>
        </span>
      </button>
      <button type="button" class="session-phat-am-btn session-phat-am-btn--stop" data-phat-am-stop hidden>
        <span class="session-phat-am-btn-icon session-phat-am-btn-icon--live" aria-hidden="true"><i class="bi bi-stop-fill"></i></span>
        <span class="session-phat-am-btn-text">
          <strong>Dừng</strong>
          <small>Đang ghi...</small>
        </span>
      </button>
    </div>

    <div class="session-phat-am-player-wrap" data-phat-am-player-wrap hidden>
      <p class="session-phat-am-player-label"><i class="bi bi-headphones"></i> <?= esc($playerLabel) ?></p>
      <div class="session-phat-am-audio-bar">
        <button type="button" class="session-phat-am-bar-play" data-phat-am-play aria-label="Phát / Tạm dừng">
          <i class="bi bi-play-fill" aria-hidden="true"></i>
        </button>
        <div class="session-phat-am-bar-body">
          <div class="session-phat-am-waveform" data-phat-am-waveform aria-hidden="true"></div>
          <div class="session-phat-am-bar-progress">
            <div class="session-phat-am-bar-track" data-phat-am-track>
              <span class="session-phat-am-bar-fill" data-phat-am-progress-fill style="width: 0%"></span>
            </div>
            <input
              type="range"
              class="session-phat-am-bar-seek"
              data-phat-am-seek
              min="0"
              max="100"
              value="0"
              step="0.01"
              aria-label="Tiến độ phát"
            >
          </div>
          <div class="session-phat-am-bar-meta">
            <span class="session-phat-am-bar-time" data-phat-am-current>0:00</span>
            <span class="session-phat-am-bar-time-sep" aria-hidden="true">/</span>
            <span class="session-phat-am-bar-time" data-phat-am-duration>0:00</span>
          </div>
        </div>
      </div>
      <audio class="session-phat-am-audio-element" data-phat-am-playback preload="metadata" hidden></audio>
      <button type="button" class="session-phat-am-btn session-phat-am-btn--rerecord" data-phat-am-rerecord hidden>
        <span class="session-phat-am-btn-icon" aria-hidden="true"><i class="bi bi-arrow-counterclockwise"></i></span>
        <span class="session-phat-am-btn-text">
          <strong>Thu âm lại</strong>
          <small>Ghi lại từ đầu</small>
        </span>
      </button>
    </div>

    <input
      type="hidden"
      <?php if ($inputName !== ''): ?>name="<?= esc($inputName, 'attr') ?>"<?php endif ?>
      value="<?= esc($inputValue, 'attr') ?>"
      data-phat-am-complete-input
    >
    <div class="session-phat-am-status is-idle" data-phat-am-status data-state="idle" role="status" aria-live="polite">
      <span class="session-phat-am-status-icon" aria-hidden="true"></span>
      <span class="session-phat-am-status-copy">
        <strong class="session-phat-am-status-title"><?= esc($statusIdleTitle) ?></strong>
        <small class="session-phat-am-status-hint"><?= esc($statusIdleHint) ?></small>
      </span>
    </div>
  </div>
</div>
