@php
    $video = is_array($video ?? null) ? $video : [];
    $title = trim((string) ($video['title'] ?? 'Video trước buổi'));
    $embedSrc = trim((string) ($video['embed_src'] ?? ''));
    $thumbnailUrl = trim((string) ($video['thumbnail_url'] ?? ''));
    $detailUrl = trim((string) ($video['detail_url'] ?? ''));
    $playerType = trim((string) ($video['player_type'] ?? 'drive'));
    $sessionNo = (int) ($video['session_no'] ?? 0);
    $videoIndex = (int) ($video['video_index'] ?? 0);
    $globalIndex = (int) ($video['global_index'] ?? 0);
    $isActive = ! empty($isActive);
    $sessionLabel = $sessionNo > 0
        ? 'Buổi '.$sessionNo.($videoIndex > 0 ? ' · Video '.$videoIndex : '')
        : '';
@endphp

@if ($embedSrc !== '')
    <button
        type="button"
        class="cvl-item{{ $isActive ? ' is-active' : '' }}"
        data-cvl-play
        data-embed-src="{{ $embedSrc }}"
        data-player-type="{{ $playerType }}"
        data-title="{{ $title }}"
        data-detail-url="{{ $detailUrl }}"
        data-session-label="{{ $sessionLabel }}"
        aria-current="{{ $isActive ? 'true' : 'false' }}"
    >
        <span class="cvl-item-thumb">
            @if ($thumbnailUrl !== '')
                <img
                    src="{{ $thumbnailUrl }}"
                    alt=""
                    width="128"
                    height="72"
                    loading="lazy"
                    decoding="async"
                    referrerpolicy="no-referrer"
                    class="cvl-playlist-thumb-img"
                >
            @endif
            <span class="cvl-item-fallback" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
            @if ($globalIndex > 0)
                <span class="cvl-item-index">{{ $globalIndex }}</span>
            @endif
        </span>
        <span class="cvl-item-copy">
            @if ($sessionLabel !== '')
                <span class="cvl-item-session">{{ $sessionLabel }}</span>
            @endif
            <span class="cvl-item-title">{{ $title }}</span>
            <span class="cvl-item-live">Đang phát</span>
        </span>
    </button>
@endif
