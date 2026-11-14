@php
    use App\Services\StudentCourseNavService;
    $workspaceTab = (string) ($workspaceTab ?? StudentCourseNavService::TAB_OVERVIEW);
    $contentTab = (string) ($contentTab ?? StudentCourseNavService::TAB_PRE);
@endphp

@if ($sessionLockedMessage ?? null)
    <div class="op-panel">
        <div class="op-panel-body sw-empty">
            <span class="sw-empty-ico"><i class="fa-solid fa-lock"></i></span>
            <p>{{ $sessionLockedMessage }}</p>
        </div>
    </div>
@elseif ($workspaceTab === StudentCourseNavService::TAB_ASSESSMENTS)
    @include('student.partials.assessments')
@elseif ($sessionDetail === null)
    <div class="op-panel">
        <div class="op-panel-body sw-empty">
            <span class="sw-empty-ico"><i class="fa-solid fa-hand-pointer"></i></span>
            <p>Chọn một buổi học từ danh sách buổi phía trên để xem nội dung.</p>
        </div>
    </div>
@elseif ($workspaceTab === StudentCourseNavService::TAB_OVERVIEW)
    @include('student.partials.session-overview')
@else
    @include('student.partials.session-detail')
@endif
