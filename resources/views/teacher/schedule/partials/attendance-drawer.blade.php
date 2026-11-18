<!-- Drawer / Modal: Điểm danh buổi học (Figma Frame 03 & 04) -->
<div class="attendance-drawer-overlay" id="attendanceDrawer" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 99999; display: flex; justify-content: flex-end; opacity: 0; pointer-events: none; transition: opacity 0.25s ease;">
    <div class="attendance-drawer-panel" style="background: #ffffff; width: 100%; max-width: 500px; height: 100%; box-shadow: -10px 0 30px rgba(0,0,0,0.15); display: flex; flex-direction: column; transform: translateX(100%); transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); font-family: 'Nunito', sans-serif;">
        <!-- Header -->
        <div style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #ffffff;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #fff7ed; color: #9a4700; display: flex; align-items: center; justify-content: center; font-size: 17px;">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>
                <div>
                    <h3 id="drawerSessionTitle" style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">Điểm danh buổi học</h3>
                    <p id="drawerCourseTitle" style="font-size: 13px; color: #64748b; margin: 2px 0 0 0;">Khóa học: —</p>
                </div>
            </div>
            <button type="button" id="btnCloseAttendanceDrawer" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer; padding: 6px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Quick Action Bar -->
        <div style="padding: 12px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 13px; color: #475569; font-weight: 600;">
                Sĩ số: <strong id="drawerStudentCount">0</strong> học viên
            </span>
            <button type="button" id="btnMarkAllPresent" style="padding: 6px 14px; border-radius: 8px; background: #f0fdf4; color: #166534; font-size: 12.5px; font-weight: 700; border: 1px solid #bbf7d0; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-check-double"></i>
                <span>Điểm danh tất cả có mặt</span>
            </button>
        </div>

        <!-- Student List Container -->
        <div id="drawerStudentList" style="flex: 1; overflow-y: auto; padding: 16px 24px;">
            <div id="drawerLoadingState" style="text-align: center; padding: 40px 20px;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #9a4700;"></i>
                <p style="margin-top: 10px; font-size: 13.5px; color: #64748b;">Đang tải danh sách học viên...</p>
            </div>
            <div id="drawerStudentItems" style="display: none; flex-direction: column; gap: 10px;"></div>
        </div>

        <!-- Drawer Footer -->
        <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; background: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <span id="drawerSaveStatus" style="font-size: 13px; color: #16a34a; font-weight: 600; display: none; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle-check"></i> Đã lưu tự động
            </span>
            <button type="button" id="btnDoneAttendance" style="margin-left: auto; padding: 9px 20px; border-radius: 10px; background: #9a4700; color: #ffffff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(154,71,0,0.25);">
                Hoàn tất
            </button>
        </div>
    </div>
</div>
