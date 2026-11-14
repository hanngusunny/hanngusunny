@php
    $courseSummaries = $courseSummaries ?? [];
    $gradingService = $gradingService ?? app(\App\Services\TeacherAssessmentGradingService::class);
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

@if ($courseSummaries !== [])
<div class="teacher-assessment-course-switch">
    <label class="teacher-assessment-course-label" for="assessmentCourseSwitcher">
        <i class="fa-solid fa-chalkboard"></i>
        Khóa học
    </label>
    <select
        class="teacher-assessment-course-select"
        id="assessmentCourseSwitcher"
        data-course-switcher
        aria-label="Chọn khóa học"
    >
        @foreach ($courseSummaries as $summary)
            @php
                $row = $summary['course'] ?? null;
                $id = (int) ($row?->id ?? 0);
                $title = trim((string) ($row?->title ?? 'Khóa học'));
                $pending = (int) ($summary['pending_total'] ?? 0);
                $label = $title.($pending > 0 ? ' · '.$pending.' chờ chấm' : '');
                $itemUrl = $gradingService->pageUrl($id, $assessmentId, $studentId);
            @endphp
            <option value="{{ $itemUrl }}" @selected($id === (int) $courseId)>{{ $label }}</option>
        @endforeach
    </select>
    @if ($currentPending > 0)
        <span class="teacher-assessment-toolbar-badge">{{ $currentPending }}</span>
    @endif
</div>
@endif
