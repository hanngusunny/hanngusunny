@php
    $courseId = (int) ($course?->id ?? 0);
    $sessions = $sessions ?? [];
    $selectedSessionId = (int) ($selectedSessionId ?? 0);
    $phaseLabels = $phaseLabels ?? [];
@endphp

<article class="teacher-course-session-list-panel">
    <div class="teacher-course-panel-title teacher-course-session-list-head">
        <h2><i class="fa-solid fa-calendar-week"></i> Buổi học</h2>
        <span>{{ count($sessions) }} buổi trong khóa</span>
    </div>

    <div class="teacher-course-session-list">
        @foreach ($sessions as $session)
            @php
                $sessionId = (int) ($session['id'] ?? 0);
                $isActive = $sessionId === $selectedSessionId;
                $phase = (string) ($session['phase'] ?? '');
                $display = is_array($session['display'] ?? null) ? $session['display'] : [];
                $index = (int) ($session['session_index'] ?? 0);
            @endphp
            <a
                class="teacher-course-session-tile{{ $isActive ? ' is-active' : '' }}{{ $phase === 'live' ? ' is-live' : '' }}{{ $phase === 'ended' ? ' is-ended' : '' }}"
                href="{{ route('teacher.course.show', ['id' => $courseId, 'buoi' => $sessionId]) }}"
                aria-current="{{ $isActive ? 'page' : 'false' }}"
                @if ($isActive) id="teacher-course-session-active" @endif
            >
                @if (! empty($display['hasSchedule']))
                    <div class="teacher-course-session-tile-date" aria-hidden="true">
                        <span>{{ $display['weekdayShort'] ?? '' }}</span>
                        <strong>{{ $display['dateDay'] ?? '' }}</strong>
                        <em>{{ $display['dateMonth'] ?? '' }}</em>
                    </div>
                @else
                    <div class="teacher-course-session-tile-date teacher-course-session-tile-date--empty" aria-hidden="true">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                @endif

                <div class="teacher-course-session-tile-body">
                    <em>Buổi {{ $index }}</em>
                    <strong>{{ $session['title'] ?? 'Buổi học' }}</strong>
                    <span>
                        @if (! empty($display['hasSchedule']))
                            {{ $display['timeLabel'] ?? '' }}
                        @else
                            Chưa lên lịch
                        @endif
                    </span>
                    <small>
                        {{ $phaseLabels[$phase] ?? $phase }}
                        @if ((int) ($session['attendance_count'] ?? 0) > 0)
                            · {{ (int) $session['attendance_count'] }} điểm danh
                        @endif
                    </small>
                </div>

                @if ($phase === 'live')
                    <span class="teacher-course-session-tile-live" aria-hidden="true"><i class="fa-solid fa-tower-broadcast"></i></span>
                @endif
            </a>
        @endforeach
    </div>
</article>
