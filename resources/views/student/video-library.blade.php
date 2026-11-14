@extends('layouts.app', ['active' => 'videos'])

@php
    $course = $course ?? null;
    $videoList = is_array($videos ?? null) ? $videos : [];
    $totalVideos = (int) ($totalVideos ?? count($videoList));
    $firstVideo = $videoList[0] ?? null;
    $courseTitle = trim((string) ($course?->title ?? ''));
    $courseUrl = isset($navService)
        ? $navService->dashboardUrlForStudent((int) auth()->id())
        : route('dashboard');

    if ($courseTitle !== '' && $totalVideos > 0) {
        $pageSubtitle = $totalVideos.' video ôn tập trước buổi — khóa «'.$courseTitle.'».';
    } elseif ($courseTitle !== '') {
        $pageSubtitle = 'Khóa «'.$courseTitle.'» chưa có video trước buổi học cho các buổi bạn đang xem.';
    } else {
        $pageSubtitle = 'Bạn chưa tham gia khóa học nào.';
    }

    $playerTitle = $firstVideo !== null ? trim((string) ($firstVideo['title'] ?? '')) : '';
    $playerEmbed = $firstVideo !== null ? trim((string) ($firstVideo['embed_src'] ?? '')) : '';
    $playerType = $firstVideo !== null ? trim((string) ($firstVideo['player_type'] ?? 'drive')) : 'drive';
    $playerDetail = $firstVideo !== null ? trim((string) ($firstVideo['detail_url'] ?? '')) : '';
    $playerSessionNo = $firstVideo !== null ? (int) ($firstVideo['session_no'] ?? 0) : 0;
    $playerVideoIndex = $firstVideo !== null ? (int) ($firstVideo['video_index'] ?? 0) : 0;
    $playerSessionLabel = $playerSessionNo > 0
        ? 'Buổi '.$playerSessionNo.($playerVideoIndex > 0 ? ' · Video '.$playerVideoIndex : '')
        : '';
    $isFilePlayer = $playerType === 'file';
@endphp

@section('title', 'Thư viện video')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/student-video-library.css') }}?v=2">
@endpush

@section('content')
<div class="op-stack cvl-page">
    <x-page-title kicker="Video trước buổi học" icon="fa-circle-play" title="Thư viện video" :lead="$pageSubtitle">
        @if ($course !== null)
            <x-slot:aside>
                <a class="op-btn-ghost" href="{{ $courseUrl }}">
                    <i class="fa-solid fa-chalkboard"></i> Vào khóa học
                </a>
            </x-slot:aside>
        @endif
    </x-page-title>

    @if ($course === null)
        <div class="cvl-empty">
            <span class="cvl-empty-ico"><i class="fa-solid fa-chalkboard"></i></span>
            <strong>Chưa có khóa học</strong>
            <p>Khi được ghi danh, video ôn tập trước buổi sẽ hiển thị tại đây.</p>
            <a class="op-btn-ghost" href="{{ route('overview') }}">
                <i class="fa-solid fa-house"></i>
                <span>Về tổng quan</span>
            </a>
        </div>
    @elseif ($videoList === [])
        <div class="cvl-empty">
            <span class="cvl-empty-ico"><i class="fa-solid fa-clapperboard"></i></span>
            <strong>Chưa có video</strong>
            <p>Chưa có video trong tài liệu trước buổi của các buổi học đang mở.</p>
            <a class="op-btn-ghost" href="{{ $courseUrl }}">
                <i class="fa-solid fa-chalkboard"></i>
                <span>Xem buổi học</span>
            </a>
        </div>
    @else
        <div class="cvl-stage" data-cvl-root>
            <section class="cvl-theater" aria-label="Trình phát video">
                <div class="cvl-player-frame">
                    <iframe
                        data-cvl-iframe
                        src="{{ $isFilePlayer ? '' : $playerEmbed }}"
                        title="{{ $playerTitle }}"
                        allow="autoplay; encrypted-media; fullscreen"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        @if ($isFilePlayer) hidden @endif
                    ></iframe>
                    <video
                        data-cvl-video
                        src="{{ $isFilePlayer ? $playerEmbed : '' }}"
                        title="{{ $playerTitle }}"
                        controls
                        playsinline
                        preload="metadata"
                        @if (! $isFilePlayer) hidden @endif
                    ></video>
                </div>

                <div class="cvl-now">
                    <div class="cvl-now-copy">
                        <p class="cvl-now-kicker">
                            <i class="fa-solid fa-play"></i>
                            Đang xem
                        </p>
                        <span class="cvl-now-session" data-cvl-session @if ($playerSessionLabel === '') hidden @endif>{{ $playerSessionLabel }}</span>
                        <h2 class="cvl-now-title" data-cvl-title>{{ $playerTitle }}</h2>
                    </div>
                    <div class="cvl-now-actions">
                        <button
                            type="button"
                            class="op-btn-ghost cvl-fullscreen-btn"
                            aria-label="Xem full màn hình"
                            title="Xem full màn hình"
                        >
                            <i class="fa-solid fa-expand"></i> Toàn màn hình
                        </button>
                        <a
                            class="op-btn"
                            data-cvl-detail
                            href="{{ $playerDetail !== '' ? $playerDetail : '#' }}"
                            @if ($playerDetail === '') hidden @endif
                        >
                            <i class="fa-solid fa-book-open"></i> Trước buổi
                        </a>
                    </div>
                </div>
            </section>

            <aside class="cvl-rail" aria-label="Danh sách video">
                <div class="cvl-rail-head">
                    <span class="cvl-rail-ico"><i class="fa-solid fa-list-ul"></i></span>
                    <div>
                        <strong>Danh sách video</strong>
                        <small>{{ $totalVideos }} video ôn tập</small>
                    </div>
                    <span class="cvl-rail-count">{{ $totalVideos }}</span>
                </div>
                <div class="cvl-playlist" data-cvl-playlist>
                    @foreach ($videoList as $index => $video)
                        @include('student.partials.video-playlist-item', [
                            'video' => $video,
                            'isActive' => $index === 0,
                        ])
                    @endforeach
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/course-video-library.js') }}?v=2"></script>
@endpush
