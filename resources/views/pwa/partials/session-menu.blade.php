@php
    use App\Services\PwaCourseService;
    use App\Services\StudentCourseNavService;

    $sessions = is_array($sessionMenu ?? null) ? $sessionMenu : [];
    $selectedPublicCode = (string) ($selectedPublicCode ?? '');
    $nearestSessionId = (int) ($nearestSessionId ?? 0);
    $phaseLabels = $phaseLabels ?? \App\Models\CourseSession::phaseLabels();
    $activeMuc = (string) ($activeMuc ?? '');
    $nav = app(StudentCourseNavService::class);

    $currentTitle = 'Chọn buổi học';
    $currentSchedule = '';
    $groups = [];

    foreach ($sessions as $session) {
        $code = (string) ($session['public_code'] ?? '');
        $display = is_array($session['display'] ?? null) ? $session['display'] : [];
        $levelId = (int) ($session['level_id'] ?? 0);
        $levelTitle = trim((string) ($session['level_title'] ?? ''));
        $levelTitle = $levelTitle !== '' ? $levelTitle : 'Trình độ học';

        if (! isset($groups[$levelId])) {
            $groups[$levelId] = [
                'title' => $levelTitle,
                'items' => [],
            ];
        }

        $groups[$levelId]['items'][] = $session;

        if ($code !== '' && $code === $selectedPublicCode) {
            $currentTitle = trim((string) ($session['title'] ?? '')) ?: 'Buổi học';
            if (! empty($display['hasSchedule'])) {
                $currentSchedule = trim((string) ($display['dateLabel'] ?? ''));
                if (($display['timeLabel'] ?? '') !== '') {
                    $currentSchedule = $currentSchedule !== ''
                        ? $currentSchedule.' · '.$display['timeLabel']
                        : (string) $display['timeLabel'];
                }
            }
        }
    }

    $summaryMeta = count($sessions).' buổi'.($currentSchedule !== '' ? ' · '.$currentSchedule : '');
@endphp

@if ($sessions !== [])
<details class="pwa-dd">
    <summary>
        <span class="pwa-dd-ico"><i class="fa-solid fa-calendar-week"></i></span>
        <span class="pwa-dd-copy">
            <strong>{{ $currentTitle }}</strong>
            <small>{{ $summaryMeta }}</small>
        </span>
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </summary>
    <div class="pwa-dd-card">
        <div class="pwa-dd-head">
            <strong>Danh sách buổi</strong>
            <span>{{ count($sessions) }} buổi</span>
        </div>
        <div class="pwa-dd-list" id="pwa-course-session-list">
            @foreach ($groups as $group)
                @if (count($groups) > 1)
                    <p class="pwa-dd-group">{{ $group['title'] }}</p>
                @endif
                @foreach ($group['items'] as $session)
                    @php
                        $code = (string) ($session['public_code'] ?? '');
                        $display = is_array($session['display'] ?? null) ? $session['display'] : [];
                        $phase = (string) ($display['phase'] ?? $session['phase'] ?? 'upcoming');
                        $isActive = $code !== '' && $code === $selectedPublicCode;
                        $isNearest = (int) ($session['id'] ?? 0) === $nearestSessionId;
                        $isLocked = empty($session['is_viewable']);
                        $linkTab = in_array($activeMuc, [
                            PwaCourseService::MUC_INFO,
                            StudentCourseNavService::TAB_PRE,
                            StudentCourseNavService::TAB_POST,
                            StudentCourseNavService::TAB_KNOWLEDGE_REVIEW,
                        ], true)
                            ? $activeMuc
                            : $nav->defaultTabForSession($session);
                        $itemUrl = $code !== '' && ! $isLocked
                            ? route('pwa.courses.session', ['code' => $code, 'muc' => $linkTab])
                            : '';
                        $schedule = ! empty($display['hasSchedule'])
                            ? trim((string) ($display['dateLabel'] ?? '')).((($display['timeLabel'] ?? '') !== '') ? ' · '.$display['timeLabel'] : '')
                            : 'Chưa lên lịch';
                        $itemClass = 'pwa-dd-item'.($isActive ? ' is-on' : '').($phase === 'live' ? ' is-live' : '').($isLocked ? ' is-locked' : '');
                        $statusText = $isNearest ? 'Gần nhất' : ($isLocked ? 'Chưa mở' : ($phase === 'live' ? 'Đang học' : (string) ($phaseLabels[$phase] ?? $phase)));
                    @endphp
                    @if ($itemUrl !== '')
                        <a class="{{ $itemClass }}" href="{{ $itemUrl }}" @if ($isActive) id="pwa-course-session-active" @endif>
                            <span class="pwa-dd-item-ico"><i class="fa-solid fa-calendar-day"></i></span>
                            <span>
                                <strong>{{ $session['title'] ?: 'Buổi học' }}</strong>
                                <small>{{ $schedule }}</small>
                                <em>{{ $statusText }}</em>
                            </span>
                        </a>
                    @else
                        <div class="{{ $itemClass }}">
                            <span class="pwa-dd-item-ico"><i class="fa-solid fa-calendar-day"></i></span>
                            <span>
                                <strong>{{ $session['title'] ?: 'Buổi học' }}</strong>
                                <small>{{ $schedule }}</small>
                                <em>{{ $statusText }}</em>
                            </span>
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>
    </div>
</details>
@endif
