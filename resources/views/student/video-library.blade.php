@extends('layouts.app', ['active' => 'videos'])

@php
    $course = $course ?? null;
    $courseHeading = trim((string) ($course_heading ?? $course?->title ?? 'Thư viện video'));
    $courseSubtitle = trim((string) ($course_subtitle ?? ''));
    $levels = is_array($levels ?? null) ? $levels : [];
    $activeLevelId = (int) ($active_level_id ?? 0);
    $activeLevelTitle = trim((string) ($active_level_title ?? ''));
    $videoList = is_array($videos ?? null) ? $videos : [];
    $playableCount = (int) ($total_videos ?? 0);
    $totalPlaylistItems = (int) ($total_playlist_items ?? count($videoList));
    $initialIndex = (int) ($initial_index ?? 0);
    $initialVideo = $videoList[$initialIndex] ?? null;

    if ($initialVideo === null || ! empty($initialVideo['is_locked'])) {
        $initialVideo = null;
        foreach ($videoList as $idx => $candidate) {
            if (empty($candidate['is_locked']) && trim((string) ($candidate['embed_src'] ?? '')) !== '') {
                $initialIndex = $idx;
                $initialVideo = $candidate;
                break;
            }
        }
    }

    $courseUrl = isset($navService)
        ? $navService->dashboardUrlForStudent((int) auth()->id())
        : route('dashboard');

    $playerDisplayTitle = $initialVideo !== null
        ? trim((string) ($initialVideo['display_title'] ?? $initialVideo['title'] ?? ''))
        : '';
    $playerEmbed = $initialVideo !== null ? trim((string) ($initialVideo['embed_src'] ?? '')) : '';
    $playerType = $initialVideo !== null ? trim((string) ($initialVideo['player_type'] ?? 'drive')) : 'drive';
    $playerSessionNo = $initialVideo !== null ? (int) ($initialVideo['session_no'] ?? 0) : 0;
    $playerGlobalIndex = $initialVideo !== null ? (int) ($initialVideo['global_index'] ?? 1) : 1;
    $playerStatus = $initialVideo !== null ? (string) ($initialVideo['status'] ?? 'done') : 'done';
    $playerStatusLabel = $initialVideo !== null ? (string) ($initialVideo['status_label'] ?? 'Đã học') : 'Đã học';
    $playerCounterLabel = ($playerSessionNo > 0 ? 'Buổi '.$playerSessionNo.' · ' : '')
        .$playerGlobalIndex.'/'.max(1, $totalPlaylistItems);
    $isFilePlayer = $playerType === 'file';

    // Vị trí tương đối của video mở mặc định trong danh sách video có thể phát (để bật/tắt nút ◀)
    $initialPlayablePos = 0;
    $seenPlayable = 0;
    foreach ($videoList as $idx => $item) {
        if (! empty($item['is_locked']) || trim((string) ($item['embed_src'] ?? '')) === '') {
            continue;
        }
        if ($idx === $initialIndex) {
            $initialPlayablePos = $seenPlayable;
            break;
        }
        $seenPlayable++;
    }
@endphp

@section('title', 'Thư viện video')

@push('styles-late')
    <link rel="stylesheet" href="{{ asset('assets/css/student-video-library.css') }}?v=3">
@endpush

@section('content')
<div class="sn-video-page">
    <section class="sn-video-surface">
        @if ($course === null)
            <div class="sn-video-empty">
                <div class="sn-video-empty-ico" aria-hidden="true">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                        <circle cx="40" cy="40" r="40" fill="#EFF5FD"/>
                        <rect x="22" y="27" width="26" height="26" rx="6" fill="#0D99FF"/>
                        <path d="M51 35L59 30.5V49.5L51 45V35Z" fill="#0D99FF"/>
                        <path d="M32 35.5L40 40L32 44.5V35.5Z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <h1 class="sn-video-empty-title">Bạn chưa tham gia khóa học nào</h1>
                <p class="sn-video-empty-desc">Khi được ghi danh vào khóa học, toàn bộ video ôn tập theo từng buổi sẽ hiển thị tại đây.</p>
                <a class="sn-video-btn-primary" href="{{ route('overview') }}">Về tổng quan</a>
            </div>
        @elseif ($initialVideo === null || $playableCount === 0)
            <header class="sn-video-head">
                <div class="sn-video-head-copy">
                    <h1 class="sn-video-course-title">{{ $courseHeading }}</h1>
                    @if ($courseSubtitle !== '')
                        <p class="sn-video-course-sub">{{ $courseSubtitle }}</p>
                    @endif
                </div>
                @if ($levels !== [])
                    <nav class="sn-video-levels" aria-label="Cấp độ khóa học">
                        @foreach ($levels as $lvl)
                            <a
                                href="{{ route('student.video-library', ['trinh-do' => $lvl['id']]) }}"
                                class="sn-video-level-pill{{ (int) $lvl['id'] === $activeLevelId ? ' is-active' : '' }}"
                            >{{ $lvl['title'] }}</a>
                        @endforeach
                    </nav>
                @endif
            </header>

            <div class="sn-video-empty">
                <div class="sn-video-empty-ico" aria-hidden="true">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                        <circle cx="40" cy="40" r="40" fill="#EFF5FD"/>
                        <rect x="22" y="27" width="26" height="26" rx="6" fill="#0D99FF"/>
                        <path d="M51 35L59 30.5V49.5L51 45V35Z" fill="#0D99FF"/>
                        <path d="M32 35.5L40 40L32 44.5V35.5Z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <h2 class="sn-video-empty-title">Chưa có video ôn tập</h2>
                <p class="sn-video-empty-desc">Chưa có video trước buổi cho các buổi học đang mở của cấp độ này. Em có thể xem tài liệu trực tiếp trong khóa học.</p>
                <a class="sn-video-btn-primary" href="{{ $courseUrl }}">Vào khóa học</a>
            </div>
        @else
            {{-- Đầu trang khóa học & Badge cấp độ chuẩn Figma (#445:22576 & #445:22580) --}}
            <header class="sn-video-head">
                <div class="sn-video-head-copy">
                    <h1 class="sn-video-course-title">{{ $courseHeading }}</h1>
                    @if ($courseSubtitle !== '')
                        <p class="sn-video-course-sub">{{ $courseSubtitle }}</p>
                    @endif
                </div>

                @if ($levels !== [])
                    <nav class="sn-video-levels" aria-label="Cấp độ khóa học">
                        @foreach ($levels as $lvl)
                            @if (count($levels) === 1)
                                <span class="sn-video-level-pill is-active">{{ $lvl['title'] }}</span>
                            @else
                                <a
                                    href="{{ route('student.video-library', ['trinh-do' => $lvl['id']]) }}"
                                    class="sn-video-level-pill{{ (int) $lvl['id'] === $activeLevelId ? ' is-active' : '' }}"
                                    @if ((int) $lvl['id'] === $activeLevelId) aria-current="page" @endif
                                >{{ $lvl['title'] }}</a>
                            @endif
                        @endforeach
                    </nav>
                @elseif ($activeLevelTitle !== '')
                    <div class="sn-video-levels">
                        <span class="sn-video-level-pill is-active">{{ $activeLevelTitle }}</span>
                    </div>
                @endif
            </header>

            {{-- Khối 2 cột Player & Playlist chuẩn Figma (#445:22581) --}}
            <div class="sn-video-stage" data-cvl-root>
                <div class="sn-video-theater" aria-label="Trình phát video">
                    <div class="cvl-player-frame sn-video-player-frame">
                        <iframe
                            data-cvl-iframe
                            src="{{ $isFilePlayer ? '' : $playerEmbed }}"
                            title="{{ $playerDisplayTitle }}"
                            allow="autoplay; encrypted-media; fullscreen"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            @if ($isFilePlayer) hidden @endif
                        ></iframe>
                        <video
                            data-cvl-video
                            src="{{ $isFilePlayer ? $playerEmbed : '' }}"
                            title="{{ $playerDisplayTitle }}"
                            controls
                            playsinline
                            preload="metadata"
                            @if (! $isFilePlayer) hidden @endif
                        ></video>
                    </div>

                    <div class="sn-video-controls">
                        <div class="sn-video-now-copy">
                            <div class="sn-video-meta-row">
                                <span class="sn-video-count-pill" data-cvl-counter>{{ $playerCounterLabel }}</span>
                                <span class="sn-video-status-text is-{{ $playerStatus }}" data-cvl-status>{{ $playerStatusLabel }}</span>
                            </div>
                            <h2 class="sn-video-now-title" data-cvl-title>{{ $playerDisplayTitle }}</h2>
                        </div>

                        <div class="sn-video-actions">
                            <button
                                type="button"
                                class="sn-video-btn-prev"
                                data-cvl-prev
                                aria-label="Video trước"
                                title="Video trước"
                                @if ($initialPlayablePos <= 0) disabled @endif
                            >
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15.5 12H9.5" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M11.5 9L8.5 12L11.5 15" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>

                            <button
                                type="button"
                                class="sn-video-btn-primary cvl-fullscreen-btn"
                                aria-label="Xem toàn màn hình"
                                title="Xem toàn màn hình"
                            >
                                <span data-cvl-full-label>Toàn màn hình</span>
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="sn-video-playlist-card" aria-label="Danh sách video">
                    <div class="sn-video-playlist-head">
                        <h3>Danh sách video</h3>
                        <span class="sn-video-total-pill">{{ $totalPlaylistItems }} video</span>
                    </div>

                    <div class="sn-video-playlist-body" data-cvl-playlist>
                        @foreach ($videoList as $index => $video)
                            @include('student.partials.video-playlist-item', [
                                'video' => $video,
                                'isActive' => $index === $initialIndex,
                                'totalItems' => $totalPlaylistItems,
                            ])
                        @endforeach
                    </div>

                    <div class="sn-video-playlist-foot">
                        {{ $totalPlaylistItems }} video · Cuộn để xem thêm ↓
                    </div>
                </aside>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/course-video-library.js') }}?v=3"></script>
@endpush
