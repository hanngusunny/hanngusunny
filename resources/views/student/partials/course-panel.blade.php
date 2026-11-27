@php
    use App\Services\StudentCourseNavService;
    $workspaceTab = (string) ($workspaceTab ?? StudentCourseNavService::TAB_OVERVIEW);
    $contentTab = (string) ($contentTab ?? StudentCourseNavService::TAB_PRE);
@endphp

@if ($sessionLockedMessage ?? null)
    <div class="sn-course-surface-card">
        <div class="sn-course-empty">
            <span class="sn-course-empty-ico"><i class="fa-solid fa-lock"></i></span>
            <p class="sn-course-empty-title">{{ $sessionLockedMessage }}</p>
        </div>
    </div>
@elseif ($sessionDetail === null)
    <div class="sn-course-surface-card">
        <div class="sn-course-empty">
            <span class="sn-course-empty-ico"><i class="fa-solid fa-hand-pointer"></i></span>
            <p class="sn-course-empty-title">Chọn một buổi học từ danh sách buổi phía trên để xem nội dung.</p>
        </div>
    </div>
@elseif ($workspaceTab === StudentCourseNavService::TAB_OVERVIEW)
    @include('student.partials.session-overview')
@else
    @include('student.partials.session-detail')
@endif
