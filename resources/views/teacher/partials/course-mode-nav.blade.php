@php
    $courseId = (int) ($course?->id ?? 0);
    $courseMode = (string) ($courseMode ?? 'sessions');
    $sessionsUrl = route('teacher.course.show', ['id' => $courseId]);
    $assessmentsUrl = app(\App\Services\TeacherAssessmentGradingService::class)->pageUrl($courseId);
@endphp

<nav class="teacher-course-mode-nav" aria-label="Loại nội dung khóa học">
    <a class="teacher-course-mode-nav-link{{ $courseMode === 'sessions' ? ' is-active' : '' }}" href="{{ $sessionsUrl }}">
        <i class="fa-solid fa-calendar-week" aria-hidden="true"></i>
        <span>Buổi học</span>
    </a>
    <a class="teacher-course-mode-nav-link{{ $courseMode === 'assessments' ? ' is-active' : '' }}" href="{{ $assessmentsUrl }}">
        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
        <span>Bài kiểm tra</span>
    </a>
</nav>
