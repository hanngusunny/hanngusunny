@php
    $activityTab = $activityTab ?? 'logs';
@endphp
<a href="{{ route('activity-logs.index') }}" class="os-tab{{ $activityTab === 'logs' ? ' is-on' : '' }}">
    <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử hoạt động
</a>
<a href="{{ route('activity-attendance.index') }}" class="os-tab{{ $activityTab === 'attendance' ? ' is-on' : '' }}">
    <i class="fa-solid fa-user-check"></i> Check điểm danh
</a>
<a href="{{ route('activity-comments.index') }}" class="os-tab{{ $activityTab === 'comments' ? ' is-on' : '' }}">
    <i class="fa-solid fa-comments"></i> Nhận xét của giáo viên
</a>
<a href="{{ route('activity-reflections.index') }}" class="os-tab{{ $activityTab === 'reflections' ? ' is-on' : '' }}">
    <i class="fa-solid fa-star"></i> Nhận xét của học viên
</a>
