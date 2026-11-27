@php
    $video = is_array($video ?? null) ? $video : [];
    $isLocked = ! empty($video['is_locked']);
    $isActive = ! $isLocked && ! empty($isActive);
    $status = (string) ($video['status'] ?? ($isLocked ? 'locked' : 'done'));
    $statusLabel = (string) ($video['status_label'] ?? match ($status) {
        'learning' => 'Đang học',
        'locked' => 'Chưa mở',
        default => 'Đã học',
    });

    $kicker = trim((string) ($video['card_kicker'] ?? ('Buổi '.(int) ($video['session_no'] ?? 1))));
    $cardTitle = trim((string) ($video['card_title'] ?? $video['title'] ?? 'Video trước buổi'));
    $displayTitle = trim((string) ($video['display_title'] ?? $video['title'] ?? $cardTitle));
    $embedSrc = $isLocked ? '' : trim((string) ($video['embed_src'] ?? ''));
    $thumbnailUrl = $isLocked ? '' : trim((string) ($video['thumbnail_url'] ?? ''));
    $playerType = $isLocked ? '' : trim((string) ($video['player_type'] ?? 'drive'));
    $sessionNo = (int) ($video['session_no'] ?? 0);
    $globalIndex = (int) ($video['global_index'] ?? 1);
    $totalItems = max(1, (int) ($totalItems ?? 1));
    $counterLabel = ($sessionNo > 0 ? 'Buổi '.$sessionNo.' · ' : '').$globalIndex.'/'.$totalItems;
@endphp

@if ($isLocked)
    <div class="sn-video-item is-locked" aria-disabled="true">
        <span class="sn-video-item-thumb sn-video-item-thumb--locked" aria-hidden="true">
            <span class="sn-video-item-fallback">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M5.83337 5.83464V6.66797C4.45266 6.66797 3.33337 7.78726 3.33337 9.16797V15.8346C3.33337 17.2153 4.45266 18.3346 5.83337 18.3346H14.1667C15.5474 18.3346 16.6667 17.2153 16.6667 15.8346V9.16797C16.6667 7.78726 15.5474 6.66797 14.1667 6.66797V5.83464C14.1667 3.53345 12.3012 1.66797 10 1.66797C7.69885 1.66797 5.83337 3.53345 5.83337 5.83464ZM10 3.33464C8.61933 3.33464 7.50004 4.45392 7.50004 5.83464V6.66797H12.5V5.83464C12.5 4.45392 11.3808 3.33464 10 3.33464ZM11.6667 11.668C11.6667 12.2849 11.3315 12.8235 10.8334 13.1117V14.168C10.8334 14.6282 10.4603 15.0013 10 15.0013C9.5398 15.0013 9.16671 14.6282 9.16671 14.168V13.1117C8.66854 12.8235 8.33337 12.2849 8.33337 11.668C8.33337 10.7475 9.07957 10.0013 10 10.0013C10.9205 10.0013 11.6667 10.7475 11.6667 11.668Z" fill-rule="evenodd" clip-rule="evenodd" fill="#8092A8"/>
                </svg>
            </span>
        </span>

        <span class="sn-video-item-info">
            <span class="sn-video-item-session">{{ $kicker }}</span>
            <strong class="sn-video-item-title">{{ $cardTitle }}</strong>
            <span class="sn-video-item-badge sn-video-item-badge--locked">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M5.83337 5.83464V6.66797C4.45266 6.66797 3.33337 7.78726 3.33337 9.16797V15.8346C3.33337 17.2153 4.45266 18.3346 5.83337 18.3346H14.1667C15.5474 18.3346 16.6667 17.2153 16.6667 15.8346V9.16797C16.6667 7.78726 15.5474 6.66797 14.1667 6.66797V5.83464C14.1667 3.53345 12.3012 1.66797 10 1.66797C7.69885 1.66797 5.83337 3.53345 5.83337 5.83464ZM10 3.33464C8.61933 3.33464 7.50004 4.45392 7.50004 5.83464V6.66797H12.5V5.83464C12.5 4.45392 11.3808 3.33464 10 3.33464ZM11.6667 11.668C11.6667 12.2849 11.3315 12.8235 10.8334 13.1117V14.168C10.8334 14.6282 10.4603 15.0013 10 15.0013C9.5398 15.0013 9.16671 14.6282 9.16671 14.168V13.1117C8.66854 12.8235 8.33337 12.2849 8.33337 11.668C8.33337 10.7475 9.07957 10.0013 10 10.0013C10.9205 10.0013 11.6667 10.7475 11.6667 11.668Z" fill-rule="evenodd" clip-rule="evenodd" fill="#8092A8"/>
                </svg>
                <span>{{ $statusLabel }}</span>
            </span>
        </span>
    </div>
@elseif ($embedSrc !== '')
    <button
        type="button"
        class="sn-video-item{{ $isActive ? ' is-active' : '' }}"
        data-cvl-play
        data-embed-src="{{ $embedSrc }}"
        data-player-type="{{ $playerType }}"
        data-title="{{ $displayTitle }}"
        data-counter="{{ $counterLabel }}"
        data-status="{{ $status }}"
        data-status-label="{{ $statusLabel }}"
        aria-current="{{ $isActive ? 'true' : 'false' }}"
    >
        <span class="sn-video-item-thumb">
            @if ($thumbnailUrl !== '')
                <img
                    src="{{ $thumbnailUrl }}"
                    alt=""
                    width="68"
                    height="60"
                    loading="lazy"
                    decoding="async"
                    referrerpolicy="no-referrer"
                    class="cvl-playlist-thumb-img"
                >
            @endif
            <span class="sn-video-item-fallback" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M9.99996 1.66797C5.39996 1.66797 1.66663 5.4013 1.66663 10.0013C1.66663 14.6013 5.39996 18.3346 9.99996 18.3346C14.6 18.3346 18.3333 14.6013 18.3333 10.0013C18.3333 5.4013 14.6 1.66797 9.99996 1.66797ZM12.2166 11.443L11.15 12.0596L10.0833 12.6763C8.70829 13.468 7.58329 12.818 7.58329 11.2346V10.0013V8.76797C7.58329 7.1763 8.70829 6.53464 10.0833 7.3263L11.15 7.94297L12.2166 8.55964C13.5916 9.3513 13.5916 10.6513 12.2166 11.443Z" fill="#FFFFFF"/>
                </svg>
            </span>
        </span>

        <span class="sn-video-item-info">
            <span class="sn-video-item-session">{{ $kicker }}</span>
            <strong class="sn-video-item-title">{{ $cardTitle }}</strong>
            <span class="sn-video-item-badge sn-video-item-badge--{{ $status }}">
                @if ($status === 'learning')
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M9.99996 1.66797C5.39996 1.66797 1.66663 5.4013 1.66663 10.0013C1.66663 14.6013 5.39996 18.3346 9.99996 18.3346C14.6 18.3346 18.3333 14.6013 18.3333 10.0013C18.3333 5.4013 14.6 1.66797 9.99996 1.66797ZM12.2166 11.443L11.15 12.0596L10.0833 12.6763C8.70829 13.468 7.58329 12.818 7.58329 11.2346V10.0013V8.76797C7.58329 7.1763 8.70829 6.53464 10.0833 7.3263L11.15 7.94297L12.2166 8.55964C13.5916 9.3513 13.5916 10.6513 12.2166 11.443Z" fill="#0D99FF"/>
                    </svg>
                @else
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M9.99996 1.66797C5.40829 1.66797 1.66663 5.40964 1.66663 10.0013C1.66663 14.593 5.40829 18.3346 9.99996 18.3346C14.5916 18.3346 18.3333 14.593 18.3333 10.0013C18.3333 5.40964 14.5916 1.66797 9.99996 1.66797ZM13.9833 8.08464L9.25829 12.8096C9.14163 12.9263 8.98329 12.993 8.81663 12.993C8.64996 12.993 8.49163 12.9263 8.37496 12.8096L6.01663 10.4513C5.77496 10.2096 5.77496 9.80964 6.01663 9.56797C6.25829 9.3263 6.65829 9.3263 6.89996 9.56797L8.81663 11.4846L13.1 7.2013C13.3416 6.95964 13.7416 6.95964 13.9833 7.2013C14.225 7.44297 14.225 7.83464 13.9833 8.08464Z" fill="#1BC04D"/>
                    </svg>
                @endif
                <span>{{ $statusLabel }}</span>
            </span>
        </span>
    </button>
@endif
