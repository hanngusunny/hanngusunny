@php
    $attendanceService = app(\App\Services\AttendanceCheckService::class);
    $courseId = (int) ($course->id ?? 0);
    $activeCount = (int) ($course->active_members_count ?? $course->members()->where('status', 'active')->count());
    $sessionsCount = (int) ($course->sessions_count ?? $course->sessions()->count());
@endphp

<section class="op-panel" style="border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden; font-family: 'Nunito', sans-serif;">
    <div class="op-panel-head is-static" style="padding: 18px 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="op-panel-ico"><i class="fa-solid fa-clipboard-user" style="color: #9a4700;"></i></span>
            <span class="op-panel-title" style="font-weight: 800; font-size: 16px; color: #0f172a;">Sổ điểm danh khóa học</span>
        </div>
        <a href="{{ route('activity-attendance.show', $courseId) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; background: #9a4700; color: #ffffff; font-size: 13.5px; font-weight: 700; text-decoration: none; box-shadow: 0 2px 8px rgba(154,71,0,0.25);">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Mở bảng điểm danh đầy đủ</span>
        </a>
    </div>

    <div class="op-panel-body" style="padding: 24px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                <div style="color: #64748b; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Tổng số học viên</div>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a;">{{ $activeCount }} học viên</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                <div style="color: #64748b; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Số buổi học</div>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a;">{{ $sessionsCount }} buổi</div>
            </div>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px;">
                <div style="color: #166534; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Trạng thái điểm danh</div>
                <div style="font-size: 16px; font-weight: 700; color: #15803d; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-circle-check"></i> Đang theo dõi
                </div>
            </div>
        </div>

        <p style="font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 16px;">
            Giáo viên và Quản trị viên có thể theo dõi sự chuyên cần, đánh dấu có mặt/vắng theo từng buổi học hoặc điểm danh nhanh toàn bộ học viên bằng một cú nhấp chuột.
        </p>

        <div style="text-align: right;">
            <a href="{{ route('activity-attendance.show', $courseId) }}" style="color: #9a4700; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span>Đi đến giao diện điểm danh từng buổi</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
