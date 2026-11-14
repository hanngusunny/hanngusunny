@php
    $courseSummaries = $courseSummaries ?? [];
    $evaluationService = $evaluationService ?? app(\App\Services\TeacherEvaluationService::class);
@endphp

<article class="op-panel teacher-eval-course-card">
    <div class="op-panel-head is-static">
        <span class="op-panel-ico"><i class="fa-solid fa-chalkboard"></i></span>
        <span class="op-panel-title">Khóa đang dạy</span>
        <span class="te-side-meta">{{ count($courseSummaries) }} khóa</span>
    </div>

    @if ($courseSummaries === [])
        <div class="teacher-eval-empty-note">
            <p>Chưa có khóa học đang dạy.</p>
        </div>
    @else
        <div class="teacher-eval-course-list">
            @foreach ($courseSummaries as $summary)
                @php
                    $row = $summary['course'] ?? null;
                    $id = (int) ($row?->id ?? 0);
                    $isActive = $id === (int) $courseId;
                    $title = trim((string) ($row?->title ?? 'Khóa học'));
                    $memberCount = (int) ($summary['member_count'] ?? 0);
                    $evaluated = (int) ($summary['evaluated'] ?? 0);
                    $sessionCount = (int) ($summary['session_count'] ?? 0);
                    $progressPct = $memberCount > 0 ? (int) round(($evaluated / $memberCount) * 100) : 0;
                    $itemUrl = $evaluationService->pageUrl($id, 0, (string) $evalWeek);
                @endphp
                <a
                    class="teacher-eval-course-item{{ $isActive ? ' is-active' : '' }}"
                    href="{{ $itemUrl }}"
                    aria-current="{{ $isActive ? 'page' : 'false' }}"
                >
                    <div class="teacher-eval-course-item-main">
                        <strong>{{ $title }}</strong>
                        <span>{{ $memberCount }} học viên</span>
                        @if ($sessionCount > 0)
                            <small>{{ $sessionCount }} buổi trong tuần</small>
                        @else
                            <small class="muted">Chưa có buổi trong tuần</small>
                        @endif
                    </div>
                    <div class="teacher-eval-course-item-progress">
                        <span>{{ $evaluated }}/{{ $memberCount }}</span>
                        <div class="teacher-eval-course-item-progress-bar" aria-hidden="true">
                            <span style="width: {{ $progressPct }}%"></span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</article>
