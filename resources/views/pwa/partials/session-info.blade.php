@php
    use App\Support\HtmlContent;

    $session = $sessionDetail['session'] ?? $sessionRow ?? [];
    $phase = (string) ($session['phase'] ?? 'upcoming');
    $display = $sessionDetail['display'] ?? $session['display'] ?? [];
    $publicCode = trim((string) ($session['public_code'] ?? ''));
    $phaseLabel = $phaseLabels[$phase] ?? ($sessionDetail['phaseLabels'][$phase] ?? $phase);
    $isLearner = ! empty($isLearner);
@endphp

<section class="pwa-info">
    <div class="pwa-info-grid">
        <article>
            <span>Điểm danh</span>
            <strong>{{ ! empty($session['has_attended']) ? 'Đã điểm danh' : 'Chưa điểm danh' }}</strong>
        </article>
        @if ($isLearner)
            <article>
                <span>Trước buổi</span>
                <strong>{{ ! empty($sessionDetail['preSubmitted']) ? 'Đã nộp' : 'Chưa nộp' }}</strong>
                @if (! empty($sessionDetail['preSubmitted']) && ($sessionDetail['preResult'] ?? null))
                    <small>{{ (int) $sessionDetail['preResult']['score'] }}% · {{ (int) $sessionDetail['preResult']['correct'] }}/{{ (int) $sessionDetail['preResult']['total'] }} câu</small>
                @endif
            </article>
            <article>
                <span>Sau buổi</span>
                <strong>{{ ! empty($sessionDetail['quizSubmitted']) ? 'Đã nộp' : 'Chưa nộp' }}</strong>
                @if (! empty($sessionDetail['quizSubmitted']) && ($sessionDetail['quizResult'] ?? null))
                    <small>{{ (int) $sessionDetail['quizResult']['score'] }}% · {{ (int) $sessionDetail['quizResult']['correct'] }}/{{ (int) $sessionDetail['quizResult']['total'] }} câu</small>
                @endif
            </article>
            <article>
                <span>Ôn tập</span>
                <strong>{{ ! empty($sessionDetail['knowledgeReviewSubmitted']) ? 'Đã nộp' : 'Chưa nộp' }}</strong>
                @if (! empty($sessionDetail['knowledgeReviewSubmitted']) && ($sessionDetail['knowledgeReviewResult'] ?? null))
                    <small>{{ (int) $sessionDetail['knowledgeReviewResult']['score'] }}% · {{ (int) $sessionDetail['knowledgeReviewResult']['correct'] }}/{{ (int) $sessionDetail['knowledgeReviewResult']['total'] }} câu</small>
                @endif
            </article>
        @else
            <article>
                <span>Trạng thái</span>
                <strong>{{ $phaseLabel }}</strong>
            </article>
        @endif
    </div>

    @if (! empty($display['hasSchedule']))
        <p class="pwa-meta"><i class="fa-regular fa-calendar"></i> {{ $display['weekdayFull'] ?? '' }}{{ ($display['dateLabel'] ?? '') !== '' ? ', '.$display['dateLabel'] : '' }}</p>
        @if (($display['timeLabel'] ?? '') !== '')
            <p class="pwa-meta"><i class="fa-regular fa-clock"></i> {{ $display['timeLabel'] }}@if (($display['durationLabel'] ?? '') !== '') · {{ $display['durationLabel'] }}@endif</p>
        @endif
    @endif

    @if (($session['teacher_name'] ?? $sessionDetail['teacherName'] ?? '') !== '')
        <p class="pwa-meta"><i class="fa-solid fa-chalkboard-user"></i> {{ $session['teacher_name'] ?? $sessionDetail['teacherName'] }}</p>
    @endif

    @if (($session['level_title'] ?? '') !== '')
        <p class="pwa-meta"><i class="fa-solid fa-layer-group"></i> {{ $session['level_title'] }}</p>
    @endif

    @if (HtmlContent::has($session['description'] ?? ''))
        <article class="pwa-info-rich">
            <h3>Mô tả buổi học</h3>
            <div class="sw-rich">{!! HtmlContent::render($session['description']) !!}</div>
        </article>
    @endif

    @if (! $isLearner && (HtmlContent::has($session['materials'] ?? '') || HtmlContent::has($session['session_summary'] ?? '')))
        <article class="pwa-info-rich">
            <h3>Tài liệu & tóm tắt</h3>
            @if (HtmlContent::has($session['materials'] ?? ''))
                <div class="sw-rich">{!! HtmlContent::render($session['materials']) !!}</div>
            @endif
            @if (HtmlContent::has($session['session_summary'] ?? ''))
                <h4>Tóm tắt</h4>
                <div class="sw-rich">{!! HtmlContent::render($session['session_summary']) !!}</div>
            @endif
        </article>
    @endif

    @if ($isLearner && (! empty($session['is_joinable']) || ! empty($session['waiting_teacher']) || ! empty($session['has_attended'])))
        <div class="pwa-info-join">
            <div>
                <strong>{{ $session['room_title'] ?: 'Phòng học online' }}</strong>
                @if (! empty($session['waiting_teacher']))
                    <small>Giáo viên chưa vào lớp — bạn có thể vào phòng và đợi.</small>
                @elseif (! empty($session['is_joinable']))
                    <small>Buổi học đang mở. Vào lớp để điểm danh.</small>
                @elseif (! empty($session['has_attended']))
                    <small>Bạn đã điểm danh buổi này.</small>
                @endif
            </div>
            @if (! empty($session['is_joinable']) || ! empty($session['waiting_teacher']))
                <form method="post" action="{{ route('pwa.join') }}" data-pwa-wait="Đang vào lớp…">
                    @csrf
                    <input type="hidden" name="public_code" value="{{ $publicCode }}">
                    <button type="submit" class="pwa-btn">
                        <i class="fa-solid fa-video"></i>
                        Vào Zoom
                    </button>
                </form>
            @endif
        </div>
    @elseif (! $isLearner)
        <a class="pwa-btn" href="{{ route('pwa.zoom') }}">
            <i class="fa-solid fa-video"></i>
            Mở Zoom
        </a>
    @endif
</section>
