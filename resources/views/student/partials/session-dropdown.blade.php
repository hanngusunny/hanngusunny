@php
    use App\Services\StudentCourseNavService;
    $navService = $navService ?? app(StudentCourseNavService::class);
    $sessions = $sessions ?? [];
@endphp

<details class="sn-session-dd sw-dd" id="studentCourseSessionDropdown">
    <summary class="sn-session-dd-trigger" aria-label="Danh sách buổi học" title="Danh sách buổi học">
        <svg class="sn-session-dd-chevron" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </summary>
    <div class="sn-session-dd-popover sw-dd-card" role="region" aria-label="Danh sách buổi học">
        <div class="sn-session-dd-head">
            <h2 class="sn-session-dd-title">Danh sách buổi học</h2>
            <span class="sn-session-dd-count">{{ count($sessions) }} buổi</span>
        </div>
        @if ($sessions === [])
            <p class="sn-session-dd-empty">Chưa có buổi học trong trình độ này.</p>
        @else
            <div class="sn-session-dd-list" id="student-course-session-list">
                @foreach ($sessions as $idx => $session)
                    @php
                        $code = (string) ($session['public_code'] ?? '');
                        $display = $session['display'] ?? [];
                        $phase = (string) ($display['phase'] ?? $session['phase'] ?? 'upcoming');
                        $isActive = $code !== '' && $code === (string) ($selectedPublicCode ?? '');
                        $isNearest = (int) ($session['id'] ?? 0) === (int) ($nearestSessionId ?? 0);
                        $isLocked = empty($session['is_viewable']);
                        $isDone = ! $isLocked && ($phase === 'ended' || ! empty($session['has_attended']));
                        $isLearning = ! $isLocked && ($isActive || $phase === 'live' || ($isNearest && ! $isDone));

                        $linkTab = in_array($workspaceTab, [
                            StudentCourseNavService::TAB_PRE,
                            StudentCourseNavService::TAB_POST,
                            StudentCourseNavService::TAB_KNOWLEDGE_REVIEW,
                        ], true)
                            ? $workspaceTab
                            : $navService->defaultTabForSession($session);
                        $itemUrl = $code !== '' ? $navService->dashboardSessionUrl($activeLevelId, $code, $linkTab) : '#';

                        $rawTitle = trim((string) ($session['title'] ?? ''));
                        $sessionHeading = 'Buổi '.($idx + 1);
                        $sessionSubtitle = $rawTitle;
                        if (preg_match('/^(Buổi\s+\d+)\s*[:\-–]\s*(.+)$/u', $rawTitle, $m)) {
                            $sessionHeading = trim($m[1]);
                            $sessionSubtitle = trim($m[2]);
                        }
                        if ($sessionSubtitle === '') {
                            $sessionSubtitle = ! empty($display['hasSchedule'])
                                ? trim((string) ($display['dateLabel'] ?? '')).' · '.trim((string) ($display['timeLabel'] ?? ''))
                                : 'Chưa lên lịch';
                        }

                        if ($isLocked) {
                            $statusKind = 'locked';
                            $statusLabel = 'Chưa mở';
                        } elseif ($isLearning) {
                            $statusKind = 'learning';
                            $statusLabel = 'Đang học';
                        } elseif ($isDone) {
                            $statusKind = 'done';
                            $statusLabel = 'Đã học';
                        } else {
                            $statusKind = 'upcoming';
                            $statusLabel = (string) ($phaseLabels[$phase] ?? 'Sắp tới');
                        }
                    @endphp
                    <a
                        class="sn-session-dd-item{{ $isActive ? ' is-active' : '' }}{{ $isLocked ? ' is-locked' : '' }}"
                        href="{{ $itemUrl }}"
                        @if ($isActive) id="student-course-session-active" aria-current="page" @endif
                    >
                        <div class="sn-session-dd-item-left">
                            <span class="sn-session-dd-num">{{ $idx + 1 }}</span>
                            <div class="sn-session-dd-copy">
                                <strong class="sn-session-dd-name">{{ $sessionHeading }}</strong>
                                <span class="sn-session-dd-sub">{{ $sessionSubtitle }}</span>
                            </div>
                        </div>
                        <span class="sn-session-status sn-session-status--{{ $statusKind }}">
                            @if ($statusKind === 'done')
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" fill="currentColor"/>
                                    <path d="M8.5 12.2L10.8 14.5L15.8 9.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @elseif ($statusKind === 'learning')
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" fill="currentColor"/>
                                    <path d="M10.2 8.7L15.4 12L10.2 15.3V8.7Z" fill="#FFFFFF"/>
                                </svg>
                            @elseif ($statusKind === 'locked')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 10V8C7 5.23858 9.23858 3 12 3C14.7614 3 17 5.23858 17 8V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <rect x="4" y="10" width="16" height="11" rx="3" fill="currentColor"/>
                                    <circle cx="12" cy="15.5" r="1.5" fill="#FFFFFF"/>
                                </svg>
                            @endif
                            <span>{{ $statusLabel }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</details>
