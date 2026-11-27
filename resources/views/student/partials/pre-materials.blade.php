@php
    use App\Support\HtmlContent;
    use App\Support\VideoEmbed;
    $hasMaterials = HtmlContent::has($session['pre_session_materials'] ?? '');
    $hasVideoContent = HtmlContent::has($session['pre_session_video_content'] ?? '');
    $rawPreVideos = trim((string) ($session['pre_session_videos'] ?? ''));
    $hasPreVideosHtml = $rawPreVideos !== '' && $rawPreVideos !== '[]' && HtmlContent::has($rawPreVideos);
    $preVideo = VideoEmbed::parse($session['pre_session_video_url'] ?? '');
    $isFigmaCourse = ! empty($figmaCourse);
@endphp

@if (! $isFigmaCourse)
    @if ($hasMaterials || $hasVideoContent || $hasPreVideosHtml || $preVideo)
        <article class="sw-card" id="session-pre-materials">
            <h3>Tài liệu chuẩn bị</h3>
            @if ($hasMaterials)
                <div class="sw-rich">{!! HtmlContent::render($session['pre_session_materials']) !!}</div>
            @endif
            @if ($hasVideoContent)
                <div class="sw-rich">{!! HtmlContent::render($session['pre_session_video_content']) !!}</div>
            @endif
            @if ($hasPreVideosHtml)
                <div class="sw-rich">{!! HtmlContent::render($session['pre_session_videos']) !!}</div>
            @endif
            @if ($preVideo && $preVideo['type'] === 'youtube')
                <div class="sw-video">
                    <iframe src="{{ $preVideo['src'] }}" title="Video trước buổi" allowfullscreen></iframe>
                </div>
            @elseif ($preVideo)
                <p><a class="op-btn" href="{{ $preVideo['src'] }}" target="_blank" rel="noopener">Mở video chuẩn bị</a></p>
            @endif
        </article>
    @else
        <article class="sw-card">
            <h3>Tài liệu chuẩn bị</h3>
            <p class="text-sm text-slate-500">Chưa có tài liệu trước buổi.</p>
        </article>
    @endif
@else
    <div class="sn-pre-materials-stage" id="session-pre-materials" data-sn-pre-materials>
        @if ($hasMaterials || $hasVideoContent || $hasPreVideosHtml || $preVideo)
            <div class="sn-materials-source" data-sn-materials-source>
                @if ($hasMaterials)
                    <div class="sn-materials-raw sw-rich">{!! HtmlContent::render($session['pre_session_materials']) !!}</div>
                @endif
                @if ($hasVideoContent)
                    <div class="sn-materials-raw sw-rich">{!! HtmlContent::render($session['pre_session_video_content']) !!}</div>
                @endif
                @if ($hasPreVideosHtml)
                    <div class="sn-materials-raw sw-rich">{!! HtmlContent::render($session['pre_session_videos']) !!}</div>
                @endif
            </div>

            @if ($preVideo && $preVideo['type'] === 'youtube')
                <div class="sn-material-group" id="session-pre-video-embed">
                    <div class="sn-material-group-head">
                        <span class="sn-material-num"><i class="fa-solid fa-play"></i></span>
                        <h3 class="sn-material-group-title">Video chuẩn bị</h3>
                    </div>
                    <div class="sn-video-frame">
                        <iframe src="{{ $preVideo['src'] }}" title="Video trước buổi" allowfullscreen loading="lazy"></iframe>
                    </div>
                </div>
            @elseif ($preVideo)
                <div class="sn-material-group" id="session-pre-video-link">
                    <div class="sn-material-group-head">
                        <span class="sn-material-num"><i class="fa-solid fa-play"></i></span>
                        <h3 class="sn-material-group-title">Video chuẩn bị</h3>
                    </div>
                    <div class="sn-video-external-box">
                        <a class="sn-course-btn sn-course-btn--primary" href="{{ $preVideo['src'] }}" target="_blank" rel="noopener">
                            <span>Mở video chuẩn bị</span>
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            @endif
        @else
            <div class="sn-course-empty">
                <span class="sn-course-empty-ico"><i class="fa-solid fa-book-open"></i></span>
                <p class="sn-course-empty-title">Chưa có tài liệu chuẩn bị cho buổi học này.</p>
            </div>
        @endif
    </div>
@endif
