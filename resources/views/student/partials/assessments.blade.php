@php
    $assessments = $assessments ?? [];
    $assessmentPhaseLabels = $assessmentPhaseLabels ?? \App\Models\CourseAssessment::studentPhaseLabels();
@endphp

<section class="op-panel">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-list-check"></i></span>
        <span class="op-panel-title">Bài kiểm tra khóa học</span>
    </div>
    <div class="op-panel-body" style="padding:20px">
        @if ($assessments === [])
            <div class="sw-empty">
                <span class="sw-empty-ico"><i class="fa-solid fa-clipboard"></i></span>
                <p>Chưa có bài kiểm tra nào trong khóa học.</p>
                <p class="text-sm text-slate-500">Bài kiểm tra sẽ hiện ở đây khi giáo viên lên lịch tại Thời gian học.</p>
            </div>
        @else
            <div class="sw-assess-list">
                @foreach ($assessments as $assessment)
                    @php
                        $display = $assessment['display'] ?? [];
                        $phase = (string) ($display['phase'] ?? ($assessment['phase'] ?? 'upcoming'));
                        $phaseLabel = $assessmentPhaseLabels[$phase] ?? $phase;
                        $canOpen = ! empty($assessment['can_open']);
                        $isTakeable = ! empty($assessment['is_takeable']);
                        $takeUrl = (string) ($assessment['take_url'] ?? '#');
                        $description = trim((string) ($assessment['description'] ?? ''));
                    @endphp
                    <article class="sw-assess sw-assess--{{ $phase }}">
                        <div class="sw-assess-date{{ empty($display['hasSchedule']) ? ' is-empty' : '' }}" aria-hidden="true">
                            @if (! empty($display['hasSchedule']))
                                <strong>{{ $display['dateDay'] }}</strong>
                                <span>{{ $display['weekdayShort'] }}</span>
                                <em>{{ $display['dateMonth'] }}</em>
                            @else
                                <i class="fa-solid fa-calendar-plus"></i>
                            @endif
                        </div>
                        <div class="sw-assess-body">
                            <div class="sw-assess-head">
                                <h3>
                                    @if ($canOpen)
                                        <a href="{{ $takeUrl }}">{{ $assessment['title'] ?? 'Bài kiểm tra' }}</a>
                                    @else
                                        <span>{{ $assessment['title'] ?? 'Bài kiểm tra' }}</span>
                                    @endif
                                </h3>
                                <span class="sw-phase sw-phase--{{ $phase }}">{{ $phaseLabel }}</span>
                            </div>
                            <p class="sw-assess-meta">
                                @if (! empty($display['hasSchedule']))
                                    <span><i class="fa-regular fa-clock"></i> {{ $display['timeLabel'] }}</span>
                                @else
                                    <span><i class="fa-regular fa-calendar-xmark"></i> Chưa lên lịch</span>
                                @endif
                                @if ((int) ($assessment['duration_minutes'] ?? 0) > 0)
                                    <span><i class="fa-solid fa-hourglass-half"></i> {{ (int) $assessment['duration_minutes'] }} phút</span>
                                @endif
                                <span><i class="fa-solid fa-list"></i> {{ (int) ($assessment['question_count'] ?? 0) }} câu</span>
                                <span class="sw-assess-count"><i class="fa-regular fa-bell"></i> {{ $display['countdownLabel'] ?? '' }}</span>
                            </p>
                            @if ($description !== '')
                                <p class="sw-assess-desc">{{ \App\Support\HtmlContent::plain($description) }}</p>
                            @endif
                        </div>
                        <div class="sw-assess-side">
                            @if ($isTakeable)
                                <a class="op-btn" href="{{ $takeUrl }}"><i class="fa-solid fa-pen-to-square"></i> Làm bài</a>
                            @elseif ($canOpen)
                                <a class="op-btn-ghost" href="{{ $takeUrl }}"><i class="fa-solid fa-arrow-up-right-from-square"></i> Chi tiết</a>
                            @else
                                <span class="sw-assess-lock"><i class="fa-solid fa-lock"></i> Chưa tới giờ</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
