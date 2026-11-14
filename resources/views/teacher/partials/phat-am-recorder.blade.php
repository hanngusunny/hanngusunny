@php
    $uploadUrl = trim((string) ($uploadUrl ?? ''));
    $inputName = trim((string) ($inputName ?? ''));
    $inputValue = trim((string) ($inputValue ?? ''));
    $valueMode = trim((string) ($valueMode ?? 'path'));
    $requireOnSubmit = ! empty($requireOnSubmit);
    $playerLabel = trim((string) ($playerLabel ?? 'Nghe lại bản ghi'));
    $statusIdleTitle = trim((string) ($statusIdleTitle ?? 'Sẵn sàng ghi âm'));
    $statusIdleHint = trim((string) ($statusIdleHint ?? $statusIdle ?? 'Bấm Ghi âm để thu phản hồi.'));
    $existingAudioUrl = trim((string) ($existingAudioUrl ?? ''));
@endphp

@if ($uploadUrl !== '')
<div
    class="session-phat-am-board"
    data-session-phat-am-board
    data-phat-am-upload-url="{{ $uploadUrl }}"
    data-phat-am-value-mode="{{ $valueMode }}"
    data-phat-am-require-submit="{{ $requireOnSubmit ? '1' : '0' }}"
    data-phat-am-idle-title="{{ $statusIdleTitle }}"
    data-phat-am-idle-hint="{{ $statusIdleHint }}"
    @if ($existingAudioUrl !== '') data-phat-am-existing-url="{{ $existingAudioUrl }}" @endif
>
    <div class="session-phat-am-recorder">
        <div class="session-phat-am-recorder-actions">
            <button type="button" class="session-phat-am-btn session-phat-am-btn--record" data-phat-am-record>
                <span class="session-phat-am-btn-icon" aria-hidden="true"><i class="fa-solid fa-microphone"></i></span>
                <span class="session-phat-am-btn-text">
                    <strong>Ghi âm</strong>
                    <small>Bấm để bắt đầu</small>
                </span>
            </button>
            <button type="button" class="session-phat-am-btn session-phat-am-btn--stop" data-phat-am-stop hidden>
                <span class="session-phat-am-btn-icon" aria-hidden="true"><i class="fa-solid fa-stop"></i></span>
                <span class="session-phat-am-btn-text">
                    <strong>Dừng</strong>
                    <small>Đang ghi...</small>
                </span>
            </button>
        </div>

        <div class="session-phat-am-player-wrap" data-phat-am-player-wrap hidden>
            <p class="session-phat-am-player-label"><i class="fa-solid fa-headphones"></i> {{ $playerLabel }}</p>
            <div class="session-phat-am-audio-bar">
                <button type="button" class="session-phat-am-bar-play" data-phat-am-play aria-label="Phát / Tạm dừng">
                    <i class="fa-solid fa-play" aria-hidden="true"></i>
                </button>
                <div class="session-phat-am-bar-body">
                    <div class="session-phat-am-waveform" data-phat-am-waveform aria-hidden="true"></div>
                    <div class="session-phat-am-bar-progress">
                        <div class="session-phat-am-bar-track">
                            <span class="session-phat-am-bar-fill" data-phat-am-progress-fill style="width: 0%"></span>
                        </div>
                        <input type="range" class="session-phat-am-bar-seek" data-phat-am-seek min="0" max="100" value="0" step="0.01" aria-label="Tiến độ phát">
                    </div>
                    <div class="session-phat-am-bar-meta">
                        <span data-phat-am-current>0:00</span>
                        <span aria-hidden="true">/</span>
                        <span data-phat-am-duration>0:00</span>
                    </div>
                </div>
            </div>
            <audio class="session-phat-am-audio-element" data-phat-am-playback preload="metadata" hidden></audio>
            <button type="button" class="session-phat-am-btn session-phat-am-btn--rerecord" data-phat-am-rerecord hidden>
                <span class="session-phat-am-btn-icon" aria-hidden="true"><i class="fa-solid fa-rotate-left"></i></span>
                <span class="session-phat-am-btn-text">
                    <strong>Thu âm lại</strong>
                    <small>Ghi lại từ đầu</small>
                </span>
            </button>
        </div>

        <input
            type="hidden"
            @if ($inputName !== '') name="{{ $inputName }}" @endif
            value="{{ $inputValue }}"
            data-phat-am-complete-input
        >
        <div class="session-phat-am-status is-idle" data-phat-am-status data-state="idle" role="status" aria-live="polite">
            <span class="session-phat-am-status-icon" aria-hidden="true"></span>
            <span class="session-phat-am-status-copy">
                <strong class="session-phat-am-status-title">{{ $statusIdleTitle }}</strong>
                <small class="session-phat-am-status-hint">{{ $statusIdleHint }}</small>
            </span>
        </div>
    </div>
</div>
@endif
