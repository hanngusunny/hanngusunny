@php
    use App\Support\HtmlContent;
    use App\Support\VideoEmbed;
    $hasMaterials = HtmlContent::has($session['pre_session_materials'] ?? '');
    $hasVideoHtml = HtmlContent::has($session['pre_session_video_content'] ?? '') || HtmlContent::has($session['pre_session_videos'] ?? '');
    $preVideo = VideoEmbed::parse($session['pre_session_video_url'] ?? '');
@endphp

@if ($hasMaterials || $hasVideoHtml || $preVideo)
    <article class="sw-card" id="session-pre-materials">
        <h3>Tài liệu chuẩn bị</h3>
        @if ($hasMaterials)
            <div class="sw-rich">{!! HtmlContent::render($session['pre_session_materials']) !!}</div>
        @endif
        @if (HtmlContent::has($session['pre_session_video_content'] ?? ''))
            <div class="sw-rich">{!! HtmlContent::render($session['pre_session_video_content']) !!}</div>
        @endif
        @if (HtmlContent::has($session['pre_session_videos'] ?? ''))
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
