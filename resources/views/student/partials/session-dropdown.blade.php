@php
    use App\Services\StudentCourseNavService;
    $navService = $navService ?? app(StudentCourseNavService::class);
    $currentTitle = 'Chọn buổi học';
    $currentSchedule = '';
    foreach ($sessions as $session) {
        if ((string) ($session['public_code'] ?? '') === (string) ($selectedPublicCode ?? '')) {
            $currentTitle = trim((string) ($session['title'] ?? 'Buổi học'));
            $display = $session['display'] ?? [];
            if (! empty($display['hasSchedule'])) {
                $currentSchedule = trim((string) ($display['dateLabel'] ?? '')).' · '.trim((string) ($display['timeLabel'] ?? ''));
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
            <p class="sw-dd-empty">Chưa có buổi học trong trình độ này.</p>
        @else
            <div class="sw-dd-list" id="student-course-session-list">
                @foreach ($sessions as $session)
                    @php
                        $code = (string) ($session['public_code'] ?? '');
                        $display = $session['display'] ?? [];
                        $phase = (string) ($display['phase'] ?? $session['phase'] ?? 'upcoming');
                        $isActive = $code !== '' && $code === (string) ($selectedPublicCode ?? '');
                        $isNearest = (int) ($session['id'] ?? 0) === (int) ($nearestSessionId ?? 0);
                        $isLocked = empty($session['is_viewable']);
                        $linkTab = in_array($workspaceTab, [StudentCourseNavService::TAB_PRE, StudentCourseNavService::TAB_POST, StudentCourseNavService::TAB_KNOWLEDGE_REVIEW], true)
                            ? $workspaceTab
                            : $navService->defaultTabForSession($session);
                        $itemUrl = $code !== '' ? $navService->dashboardSessionUrl($activeLevelId, $code, $linkTab) : '#';
                        $schedule = ! empty($display['hasSchedule'])
                            ? trim((string) ($display['dateLabel'] ?? '')).' · '.trim((string) ($display['timeLabel'] ?? ''))
                            : 'Chưa lên lịch';
                    @endphp
                    <a
                        class="sw-dd-item{{ $isActive ? ' is-on' : '' }}{{ $isLocked ? ' is-locked' : '' }}{{ $phase === 'live' ? ' is-live' : '' }}"
                        href="{{ $itemUrl }}"
                        @if ($isActive) id="student-course-session-active" @endif
                    >
                        <span class="sw-dd-item-ico"><i class="fa-solid fa-calendar-day"></i></span>
                        <span>
                            <strong>{{ $session['title'] ?? 'Buổi học' }}</strong>
                            <small>{{ $schedule }}</small>
                            @if ($isNearest)
                                <em>Gần nhất</em>
                            @elseif ($isLocked)
                                <em>Chưa mở</em>
                            @elseif ($phase === 'live')
                                <em>Đang live</em>
                            @else
                                <em>{{ $phaseLabels[$phase] ?? $phase }}</em>
                            @endif
                        </span>
                        @if ($phase === 'live')
                            <b class="sw-phase sw-phase--live">Live</b>
                        @elseif ($isLocked)
                            <i class="fa-solid fa-lock sw-dd-lock"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</details>
