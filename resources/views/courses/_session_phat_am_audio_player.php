<?php
/** @var string $audioUrl */
/** @var bool $standalone */

$audioUrl = trim((string) ($audioUrl ?? ''));
$standalone = ! empty($standalone);

if ($audioUrl === '') {
    return;
}
?>
<div
  class="session-phat-am-player-wrap<?= $standalone ? ' session-phat-am-player-wrap--standalone' : '' ?>"
  <?= $standalone ? 'data-session-phat-am-player' : '' ?>
>
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
  <audio class="session-phat-am-audio-element" data-phat-am-playback preload="metadata" src="<?= esc($audioUrl, 'attr') ?>" hidden></audio>
</div>
