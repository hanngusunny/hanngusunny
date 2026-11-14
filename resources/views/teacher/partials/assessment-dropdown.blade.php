@php
    $assessmentOptions = $assessmentOptions ?? [];
    $selectedAssessment = is_array($selectedAssessment ?? null) ? $selectedAssessment : null;
    $phaseLabels = $phaseLabels ?? [];
    $gradingService = $gradingService ?? app(\App\Services\TeacherAssessmentGradingService::class);
    $currentTitle = trim((string) ($selectedAssessment['title'] ?? 'Bài kiểm tra'));
    $currentPending = 0;

    foreach ($assessmentOptions as $option) {
        if ((int) ($option['id'] ?? 0) === (int) $assessmentId) {
            $currentPending = (int) ($option['pending_grade'] ?? 0);
            break;
        }
    }
@endphp

@if ($assessmentOptions !== [])
<details class="teacher-grading-course-dropdown teacher-assessment-test-dropdown">
    <summary class="teacher-grading-course-toggle" aria-label="Chọn bài kiểm tra">
        <span class="teacher-grading-course-toggle-icon" aria-hidden="true">
            <i class="fa-solid fa-list-check"></i>
        </span>
        <span class="teacher-grading-course-toggle-copy">
            <strong>{{ $currentTitle }}</strong>
            <small>Bài kiểm tra · {{ count($assessmentOptions) }} bài</small>
        </span>
        @if ($currentPending > 0)
            <span class="teacher-grading-course-toggle-badge">{{ $currentPending }}</span>
        @endif
        <i class="fa-solid fa-chevron-down teacher-grading-course-toggle-chevron" aria-hidden="true"></i>
    </summary>

    <div class="teacher-grading-course-dropdown-card" role="menu">
        <div class="teacher-grading-course-dropdown-head">
            <strong>Bài kiểm tra</strong>
            <span>{{ count($assessmentOptions) }} bài</span>
        </div>
        <div class="teacher-grading-course-dropdown-list">
            @foreach ($assessmentOptions as $option)
                @php
                    $id = (int) ($option['id'] ?? 0);
                    $isActive = $id === (int) $assessmentId;
                    $title = trim((string) ($option['title'] ?? 'Bài kiểm tra'));
                    $pending = (int) ($option['pending_grade'] ?? 0);
                    $phase = trim((string) ($phaseLabels[(string) ($option['phase'] ?? '')] ?? ''));
                    $itemUrl = $gradingService->pageUrl((int) $courseId, $id, (int) $studentId);
                @endphp
                <a
                    class="teacher-grading-course-dropdown-item{{ $isActive ? ' is-active' : '' }}"
                    href="{{ $itemUrl }}"
                    role="menuitem"
                    aria-current="{{ $isActive ? 'page' : 'false' }}"
                >
                    <span class="teacher-grading-course-dropdown-ico" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></span>
                    <div class="teacher-grading-course-dropdown-item-copy">
                        <strong>{{ $title }}</strong>
                        @if ($phase !== '')
                            <small>{{ $phase }}</small>
                        @endif
                        @if ($pending > 0)
                            <em>{{ $pending }} bài chờ chấm</em>
                        @else
                            <em class="is-done">Không còn bài chờ chấm</em>
                        @endif
                    </div>
                    @if ($pending > 0)
                        <span class="teacher-grading-course-badge">{{ $pending }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</details>
@endif
