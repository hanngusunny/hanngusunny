@php
    $sessions = $sessions ?? [];
    $sessionId = (int) ($sessionId ?? 0);
    $courseId = (int) ($course->id ?? 0);
    $userId = (int) ($userId ?? 0);
    $activeTab = (string) ($activeTab ?? 'tong-quan');
    $phaseLabels = $phaseLabels ?? [];
    $currentTitle = 'Chọn buổi học';
    $currentSchedule = '';
    foreach ($sessions as $sessionRow) {
        if ((int) ($sessionRow['id'] ?? 0) === $sessionId) {
            $currentTitle = trim((string) ($sessionRow['title'] ?? 'Buổi học'));
            $displayRow = $sessionRow['display'] ?? [];
            if (! empty($displayRow['hasSchedule'])) {
                $currentSchedule = trim((string) ($displayRow['dateLabel'] ?? '')).' · '.trim((string) ($displayRow['timeLabel'] ?? ''));
            }
            break;
        }
    }
@endphp

<details class="sw-dd">
    <summary>
        <span class="sw-dd-ico"><i class="fa-solid fa-calendar-week"></i></span>
        <span>
            <strong>{{ $currentTitle }}</strong>
            <small>Danh sách buổi · {{ count($sessions) }} buổi @if ($currentSchedule !== '') · {{ $currentSchedule }} @endif</small>
        </span>
        <i class="fa-solid fa-chevron-down"></i>
    </summary>
    <div class="sw-dd-card">
        <div class="sw-dd-head">
            <strong>Danh sách buổi</strong>
            <span>{{ count($sessions) }} buổi</span>
        </div>
        @if ($sessions === [])
            <p class="sw-dd-empty">Chưa có buổi học trong khóa này.</p>
        @else
            <div class="sw-dd-list" id="student-course-session-list">
                @foreach ($sessions as $sessionRow)
                    @php
                        $rowId = (int) ($sessionRow['id'] ?? 0);
                        $displayRow = $sessionRow['display'] ?? [];
                        $phase = (string) ($displayRow['phase'] ?? $sessionRow['phase'] ?? 'upcoming');
                        $isActive = $rowId === $sessionId;
                        $itemUrl = $rowId > 0
                            ? route('teacher.course.student', ['id' => $courseId, 'sessionId' => $rowId, 'userId' => $userId, 'muc' => $activeTab])
                            : '#';
                        $schedule = ! empty($displayRow['hasSchedule'])
                            ? trim((string) ($displayRow['dateLabel'] ?? '')).' · '.trim((string) ($displayRow['timeLabel'] ?? ''))
                            : 'Chưa lên lịch';
                    @endphp
                    <a
                        class="sw-dd-item{{ $isActive ? ' is-on' : '' }}{{ $phase === 'live' ? ' is-live' : '' }}"
                        href="{{ $itemUrl }}"
                        @if ($isActive) id="student-course-session-active" @endif
                    >
                        <span class="sw-dd-item-ico"><i class="fa-solid fa-calendar-day"></i></span>
                        <span>
                            <strong>{{ $sessionRow['title'] ?? 'Buổi học' }}</strong>
                            <small>{{ $schedule }}</small>
                            @if ($phase === 'live')
                                <em>Đang live</em>
                            @else
                                <em>{{ $phaseLabels[$phase] ?? $phase }}</em>
                            @endif
                        </span>
                        @if ($phase === 'live')
                            <b class="sw-phase sw-phase--live">Live</b>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</details>
