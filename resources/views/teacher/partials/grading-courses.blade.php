@php
    $courseSummaries = $courseSummaries ?? [];
    $gradingService = $gradingService ?? app(\App\Services\TeacherGradingService::class);
    $currentTitle = 'Khóa học';
    $currentPending = 0;

    foreach ($courseSummaries as $summary) {
        $row = $summary['course'] ?? null;
        if ((int) ($row?->id ?? 0) === (int) $courseId) {
            $currentTitle = trim((string) ($row?->title ?? 'Khóa học'));
            $currentPending = (int) ($summary['pending_total'] ?? 0);
            break;
        }
    }
@endphp

<details class="teacher-grading-course-dropdown">
    <summary class="teacher-grading-course-toggle" aria-label="Chọn khóa học đang dạy">
        <span class="teacher-grading-course-toggle-icon" aria-hidden="true">
            <i class="fa-solid fa-chalkboard"></i>
        </span>
        <span class="teacher-grading-course-toggle-copy">
            <strong>{{ $currentTitle }}</strong>
            <small>Khóa đang dạy · {{ count($courseSummaries) }} khóa</small>
        </span>
        @if ($currentPending > 0)
            <span class="teacher-grading-course-toggle-badge">{{ $currentPending }}</span>
        @endif
        <i class="fa-solid fa-chevron-down teacher-grading-course-toggle-chevron" aria-hidden="true"></i>
    </summary>

    <div class="teacher-grading-course-dropdown-card" role="menu">
        <div class="teacher-grading-course-dropdown-head">
            <strong>Khóa đang dạy</strong>
            <span>{{ count($courseSummaries) }} khóa</span>
        </div>

        @if ($courseSummaries === [])
            <div class="teacher-grading-course-dropdown-empty">
                <p>Chưa có khóa học đang dạy.</p>
            </div>
        @else
            <div class="teacher-grading-course-dropdown-list">
                @foreach ($courseSummaries as $summary)
                    @php
                        $row = $summary['course'] ?? null;
                        $id = (int) ($row?->id ?? 0);
                        $isActive = $id === (int) $courseId;
                        $title = trim((string) ($row?->title ?? 'Khóa học'));
                        $memberCount = (int) ($summary['member_count'] ?? 0);
                        $sessionCount = (int) ($summary['session_count'] ?? 0);
                        $pendingPre = (int) ($summary['pending_pre'] ?? 0);
                        $pendingPost = (int) ($summary['pending_post'] ?? 0);
                        $pendingKr = (int) ($summary['pending_kr'] ?? 0);
                        $pendingTotal = (int) ($summary['pending_total'] ?? 0);
                        $itemUrl = $gradingService->pageUrl($id, $activeTab, $isActive ? $selectedKey : '');
                    @endphp
                    <a
                        class="teacher-grading-course-dropdown-item{{ $isActive ? ' is-active' : '' }}"
                        href="{{ $itemUrl }}"
                        role="menuitem"
                        aria-current="{{ $isActive ? 'page' : 'false' }}"
                    >
                        <span class="teacher-grading-course-dropdown-ico" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></span>
                        <div class="teacher-grading-course-dropdown-item-copy">
                            <strong>{{ $title }}</strong>
                            <small>{{ $memberCount }} học viên · {{ $sessionCount }} buổi</small>
                            @if ($pendingTotal > 0)
                                <em>Chờ chấm: trước {{ $pendingPre }} · sau {{ $pendingPost }} · ôn tập {{ $pendingKr }}</em>
                            @else
                                <em class="is-done">Đã chấm hết</em>
                            @endif
                        </div>
                        @if ($pendingTotal > 0)
                            <span class="teacher-grading-course-badge">{{ $pendingTotal }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</details>
