@php
    use App\Services\StudentCourseNavService;
    $navService = $navService ?? app(StudentCourseNavService::class);
    $sessionCode = (string) ($selectedPublicCode ?? '');
    $assessmentsUrl = $activeLevelId > 0 ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_ASSESSMENTS) : '#';
    $preUrl = $sessionCode !== '' ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_PRE) : '#';
    $postUrl = $sessionCode !== '' ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_POST) : '#';
    $reviewUrl = $sessionCode !== '' ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_KNOWLEDGE_REVIEW) : '#';
    $menuDisabled = $sessionCode === '';
@endphp

<div class="sn-course-outline-wrap">
    <nav class="sn-course-outline-card" aria-label="Điều hướng buổi học">
        <a
            class="sn-outline-item{{ $workspaceTab === StudentCourseNavService::TAB_PRE ? ' is-active' : '' }}{{ $menuDisabled ? ' is-disabled' : '' }}"
            href="{{ $preUrl }}"
        >
            <span class="sn-outline-ico sn-outline-ico--pre" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M4 19.5V5.5C4 3.5 5.2 2.5 7.2 2.5H17.8C19.3 2.5 20 3.2 20 4.7V16.8C20 17.7 19.3 18.4 18.4 18.4H6.5C5.1 18.4 4 19.5 4 20.9V19.5Z" fill="currentColor"/>
                    <path d="M8 7.5H16" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M8 11H13.5" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M4 19.5C4 18.1 5.1 17 6.5 17H20V19.8C20 20.7 19.3 21.5 18.3 21.5H6.5C5.1 21.5 4 20.6 4 19.5Z" fill="currentColor" opacity="0.72"/>
                </svg>
            </span>
            <span class="sn-outline-label">Trước buổi học</span>
        </a>

        <a
            class="sn-outline-item{{ $workspaceTab === StudentCourseNavService::TAB_POST ? ' is-active' : '' }}{{ $menuDisabled ? ' is-disabled' : '' }}"
            href="{{ $postUrl }}"
        >
            <span class="sn-outline-ico sn-outline-ico--post" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M2 6.5C2 4.8 3.3 3.5 5 3.5H8.5C10.2 3.5 11.5 4.8 11.5 6.5V19.5C11.5 18.4 10.6 17.5 9.5 17.5H4C2.9 17.5 2 16.6 2 15.5V6.5Z" fill="currentColor"/>
                    <path d="M22 6.5C22 4.8 20.7 3.5 19 3.5H15.5C13.8 3.5 12.5 4.8 12.5 6.5V19.5C12.5 18.4 13.4 17.5 14.5 17.5H20C21.1 17.5 22 16.6 22 15.5V6.5Z" fill="currentColor" opacity="0.82"/>
                </svg>
            </span>
            <span class="sn-outline-label">Sau buổi học</span>
        </a>

        <a
            class="sn-outline-item{{ $workspaceTab === StudentCourseNavService::TAB_ASSESSMENTS ? ' is-active' : '' }}"
            href="{{ $assessmentsUrl }}"
        >
            <span class="sn-outline-ico sn-outline-ico--test" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M12 3L2 8.2L12 13.4L22 8.2L12 3Z" fill="currentColor"/>
                    <path d="M6 11.5V16.2C6 17.9 8.7 19.5 12 19.5C15.3 19.5 18 17.9 18 16.2V11.5L12 14.6L6 11.5Z" fill="currentColor" opacity="0.82"/>
                    <path d="M21 9.5V15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="sn-outline-label">Bài kiểm tra</span>
        </a>

        @if ($hasSessionKnowledgeReview ?? false)
            <a
                class="sn-outline-item{{ $workspaceTab === StudentCourseNavService::TAB_KNOWLEDGE_REVIEW ? ' is-active' : '' }}{{ $menuDisabled ? ' is-disabled' : '' }}"
                href="{{ $reviewUrl }}"
            >
                <span class="sn-outline-ico sn-outline-ico--kr" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2.5C7.86 2.5 4.5 5.86 4.5 10C4.5 12.68 5.91 15.04 8.03 16.36C8.51 16.66 8.85 17.16 8.92 17.73L9 18.4C9.08 19.03 9.61 19.5 10.24 19.5H13.76C14.39 19.5 14.92 19.03 15 18.4L15.08 17.73C15.15 17.16 15.49 16.66 15.97 16.36C18.09 15.04 19.5 12.68 19.5 10C19.5 5.86 16.14 2.5 12 2.5Z" fill="currentColor"/>
                        <path d="M9.5 21.5H14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="sn-outline-label">Ôn tập kiến thức</span>
            </a>
        @endif
    </nav>

    {{-- Mục lục bài học tự động gắn dưới menu khi xem Tài liệu chuẩn bị (#317:12189) --}}
    <nav class="sn-course-toc-card" id="snCourseTocCard" aria-label="Mục lục bài học" hidden></nav>
</div>
