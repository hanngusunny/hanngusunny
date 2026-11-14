@php
    $session = $sessionDetail['session'];
    $phase = (string) ($session['phase'] ?? 'upcoming');
    $display = $sessionDetail['display'] ?? $session['display'] ?? [];
    $publicCode = (string) ($session['public_code'] ?? '');
@endphp

<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-circle-info"></i></span>
        <span class="op-panel-title">{{ $session['title'] ?? 'Buổi học' }}</span>
        <span class="sw-phase sw-phase--{{ $phase }}">{{ $sessionDetail['phaseLabels'][$phase] ?? $phase }}</span>
    </div>
    <div class="op-panel-body" style="padding:20px">
        <p class="sw-session-sub">
            {{ $sessionDetail['course']['title'] ?? '' }}
            @if (! empty($display['hasSchedule']))
                · {{ $display['weekdayFull'] ?? '' }}, {{ $display['dateLabel'] ?? '' }}
                · {{ $display['timeLabel'] ?? '' }}
            @endif
        </p>

        <div class="sw-stat-grid">
            <article>
                <span>Điểm danh</span>
                <strong>{{ ! empty($session['has_attended']) ? 'Đã điểm danh' : 'Chưa điểm danh' }}</strong>
            </article>
            <article>
                <span>Trước buổi học</span>
                <strong>{{ ! empty($sessionDetail['preSubmitted']) ? 'Đã nộp' : 'Chưa nộp' }}</strong>
                @if (! empty($sessionDetail['preSubmitted']) && ($sessionDetail['preResult'] ?? null))
                    <small>{{ (int) $sessionDetail['preResult']['score'] }}% · {{ (int) $sessionDetail['preResult']['correct'] }}/{{ (int) $sessionDetail['preResult']['total'] }} câu</small>
                @endif
            </article>
            <article>
                <span>Sau buổi học</span>
                <strong>{{ ! empty($sessionDetail['quizSubmitted']) ? 'Đã nộp' : 'Chưa nộp' }}</strong>
                @if (! empty($sessionDetail['quizSubmitted']) && ($sessionDetail['quizResult'] ?? null))
                    <small>{{ (int) $sessionDetail['quizResult']['score'] }}% · {{ (int) $sessionDetail['quizResult']['correct'] }}/{{ (int) $sessionDetail['quizResult']['total'] }} câu</small>
                @endif
            </article>
            <article>
                <span>Ôn tập kiến thức</span>
                <strong>{{ ! empty($sessionDetail['knowledgeReviewSubmitted']) ? 'Đã nộp' : 'Chưa nộp' }}</strong>
                @if (! empty($sessionDetail['knowledgeReviewSubmitted']) && ($sessionDetail['knowledgeReviewResult'] ?? null))
                    <small>{{ (int) $sessionDetail['knowledgeReviewResult']['score'] }}% · {{ (int) $sessionDetail['knowledgeReviewResult']['correct'] }}/{{ (int) $sessionDetail['knowledgeReviewResult']['total'] }} câu</small>
                @endif
            </article>
        </div>

        @include('student.partials.join-room', ['session' => $session, 'publicCode' => $publicCode])
    </div>
</section>
