@php
    $courseSummaries = $courseSummaries ?? [];
    $gradingService = $gradingService ?? app(\App\Services\TeacherGradingService::class);
    $filterQuery = is_array($filterQuery ?? null) ? $filterQuery : [];
    $currentTitle = 'Khóa học';
    $currentLevel = trim((string) ($courseLevelTitle ?? ''));

    foreach ($courseSummaries as $summary) {
        $row = $summary['course'] ?? null;
        if ((int) ($row?->id ?? 0) === (int) $courseId) {
            $currentTitle = trim((string) ($row?->title ?? 'Khóa học'));
            if ($currentLevel === '') {
                $currentLevel = trim((string) ($summary['level_title'] ?? ''));
            }
            break;
        }
    }

    $triggerLabel = $currentLevel !== '' ? $currentTitle.' · '.$currentLevel : $currentTitle;
@endphp

<details class="sn-tg-course-dd">
    <summary class="sn-tg-course-trigger" aria-label="Chọn khóa học đang dạy" title="{{ $triggerLabel }}">
        <span class="sn-tg-course-trigger-label">{{ $triggerLabel }}</span>
        <svg class="sn-tg-course-chevron" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </summary>

    <div class="sn-tg-course-menu" role="menu">
        <div class="sn-tg-course-menu-head">
            <strong>Khóa đang dạy</strong>
            <span>{{ count($courseSummaries) }} khóa</span>
        </div>

        @if ($courseSummaries === [])
            <p class="sn-tg-course-menu-empty">Chưa có khóa học đang dạy.</p>
        @else
            <div class="sn-tg-course-menu-list">
                @foreach ($courseSummaries as $summary)
                    @php
                        $row = $summary['course'] ?? null;
                        $id = (int) ($row?->id ?? 0);
                        $isActive = $id === (int) $courseId;
                        $title = trim((string) ($row?->title ?? 'Khóa học'));
                        $level = trim((string) ($summary['level_title'] ?? ''));
                        $memberCount = (int) ($summary['member_count'] ?? 0);
                        $pendingTotal = (int) ($summary['pending_total'] ?? 0);
                        $itemUrl = $gradingService->pageUrl($id, $activeTab, '', $filterQuery);
                    @endphp
                    <a
                        class="sn-tg-course-item{{ $isActive ? ' is-active' : '' }}"
                        href="{{ $itemUrl }}"
                        role="menuitem"
                        aria-current="{{ $isActive ? 'page' : 'false' }}"
                    >
                        <span class="sn-tg-course-item-copy">
                            <strong>{{ $title }}@if ($level !== '') · {{ $level }}@endif</strong>
                            <small>{{ $memberCount }} học viên</small>
                        </span>
                        @if ($pendingTotal > 0)
                            <span class="sn-tg-course-item-badge">{{ $pendingTotal }} chờ chấm</span>
                        @else
                            <span class="sn-tg-course-item-badge is-done">Đã chấm hết</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</details>
