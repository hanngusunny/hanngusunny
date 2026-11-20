# KẾ HOẠCH TRIỂN KHAI: TỐI ƯU HIỆU NĂNG, ĐÁNH GIÁ HÀNH VI FIGMA & KIỂM THỬ TOÀN DIỆN

> **Tuân thủ nghiêm ngặt `.antigravity/rules.md`:**
> - **Nguyên tắc Plan-First:** Kế hoạch này phân tích toàn bộ hướng làm, đánh giá hành vi tương tác trên Figma và chiến lược tối ưu hiệu năng trước khi chỉnh sửa code.
> - **Strict File Scope:** Chỉ chỉnh sửa đúng danh sách file trong mục **Target Files**.
> - **Không phá vỡ chức năng cũ:** Toàn bộ logic đăng nhập, xác thực Session, điều hướng PWA, cũng như dữ liệu khóa học, lịch học, buổi học tiếp theo được giữ trọn vẹn và an toàn.

---

## 1. Đánh Giá Hành Vi & Tương Tác Người Dùng Trên Figma (Behavior Analysis)

Dựa trên việc bóc tách thuộc tính, trạng thái và component sets từ Figma MCP (`sYb0uSNxD7B2ZdPOaYM6MF`):

### 1.1. Màn Đăng Nhập (`#317:13203` / `#223:10984`)
* **Input Focus & Blur**:
  - Focus state: Viền chuyển màu cam thương hiệu `#FF7614`, đổ bóng vòng ngoài `box-shadow: 0 0 0 3px rgba(255, 118, 20, 0.15)`.
  - Icon trái giữ màu trung tính `#63758D`, đổi nhẹ theo focus state để tạo điểm nhấn thị giác.
* **Nút Đăng nhập Neo-brutalist**:
  - Trạng thái mặc định: Nền `#FF7614`, đổ bóng 3D nổi khối `0px 4px 0px 0px #E95308`.
  - Hover state: Nền sáng nhẹ `#FF8B38`.
  - Active (Press) state: Chìm xuống `transform: translateY(3px)`, đổ bóng thu nhỏ `0px 1px 0px 0px #E95308`.
* **Responsive Behavior**:
  - Màn hình rộng (> 1024px): Chia 2 cột cân xứng (Cột trái mascot Anh Cam với speech bubble chào hỏi; Cột phải card form đăng nhập).
  - Màn hình nhỏ (< 1024px): Card tự động co giãn `max-width: 440px`, căn giữa màn hình, speech bubble và mascot thu nhỏ thích ứng không gây vỡ khung hình.

### 1.2. Thanh Điều Hướng Top Bar (`#505:28360`)
* **Avatar & Role Badge**:
  - Vòng tròn ring 46px với viền xám nhạt `#CCD4DC`, chứa avatar 42px.
  - Role badge pill màu xanh lá `Green / 05` (`#33A957`) chữ trắng "Học sinh" (tự động đổi màu tương ứng khi là Giáo viên / Quản trị viên).
* **Nút tròn Thông báo (`#I505:28360;505:28232`)**:
  - Hình tròn 44x44px nền `#EFF5FD`.
  - Badge đỏ góc trên bên phải (`top: -2px, right: -4px`) kích thước 20x20px nền `#E01F1F`.
  - Hành vi click: Mở Bảng thông báo nổi (`#1020:6149`). Nếu Menu Cài đặt đang mở, tự động đóng Menu Cài đặt.
* **Nút tròn Cài đặt (`#I505:28360;505:28236`)**:
  - Hình tròn 44x44px nền `#F4F5F7`.
  - Hành vi click: Mở Menu Cài đặt (`#1023:26242`). Nếu Bảng thông báo đang mở, tự động đóng Bảng thông báo.
* **Hành vi Click Outside**:
  - Khi nhấp chuột ra ngoài phạm vi Topbar hoặc Dropdown, dropdown đang mở sẽ tự động đóng lại nhẹ nhàng.

### 1.3. Menu Cài Đặt Dropdown (`#1023:26242` / ComponentSet `#1023:26063`)
* **Trạng thái theo trang tương ứng (Active indicator)**:
  - Khi đang ở trang Hồ sơ cá nhân (`route('profile.show')`): Item "Hồ sơ cá nhân" tự động đổi sang nền xanh nhạt `Accent-Subtle` (`#EFF5FD`) và viền xanh `#0D99FF`.
  - Khi đang ở trang Đổi mật khẩu (`route('profile.password')`): Item "Đổi mật khẩu" đổi sang nền `#EFF5FD` và viền `#0D99FF`.
  - Khi ở các trang khác (Tổng quan, Khóa học...): Cả 2 item ở trạng thái nền trắng `#FFFFFF` (`Mục đang chọn=Không`).
* **Hiệu ứng Thao tác**:
  - Các option thiết kế theo dạng Answer Option với viền 2px `#E4E8ED` và đổ bóng 3D `0 4px 0 0 #E4E8ED`.
  - Hover: Nâng nhẹ `transform: translateY(-1px)`, viền `#CCD4DC`.
  - Option "Đăng xuất": Chữ đỏ `#E01F1F`, icon logout đặt trong vòng tròn đỏ nhạt `#FCE9E9`. Khi hover đổi sang viền đỏ mờ `#FA553C`.

### 1.4. Bảng Thông Báo Nổi Popover (`#1020:6149` / ComponentSet `#1020:6983`)
* **Cấu trúc Popover**:
  - Chiều rộng cố định 468px, bo góc `20px`, đổ bóng `Elevation/Popover` (`0 12px 24px rgba(48,59,71,0.16)`).
  - Không có header gradient thừa hay icon chuông lớn như bản cũ. Danh sách bắt đầu trực tiếp với các thẻ thông báo dạng cuộn (chiều cao 398px).
* **Trạng thái Thẻ Thông Báo**:
  - **Chưa đọc (`is-unread`)**: Nền xanh nhạt dịu `#EFF5FD`.
  - **Đã đọc (`is-read`)**: Nền trắng `#FFFFFF`, viền mờ `1px solid #E4E8ED`.
* **Tương tác Đọc & Đánh dấu đã đọc**:
  - Bấm vào một thông báo chưa đọc: Thẻ lập tức chuyển từ nền xanh (`#EFF5FD`) sang nền trắng viền xám (`#FFFFFF`), số đếm trên badge giảm 1.
  - Bấm nút "Đánh dấu đã đọc": Toàn bộ các thẻ chưa đọc chuyển sang thẻ đã đọc (nền trắng viền xám), badge đỏ biến mất, nút "Đánh dấu đã đọc" chuyển sang trạng thái disabled (`State=Disabled` trong Figma).
  - Thao tác gửi dữ liệu qua AJAX nền (`route('notifications.read')` hoặc `route('notifications.read-all')`), không reload lại toàn bộ trang.

### 1.6. Tab Danh Sách Cấp Độ Học Viên (Figma Node `#488:26450` / `#488:26455` / Component `Data Display/Cards/Course Card` `#488:26806`)
* **Container `#paneLevels`**:
  - Nền xám nhạt `#F4F5F7`, bo góc `20px`, padding `20px`, khoảng cách giữa các card `gap: 20px`.
* **Cấu trúc Thẻ Cấp Độ (Course Card - Horizontal Layout)**:
  - Bố cục ngang (`display: flex; flex-direction: row; gap: 20px; padding: 20px;`), nền trắng `#FFFFFF`, bo góc `20px`, đổ bóng 3D `0px 4px 0px 0px #E4E8ED`.
  - **Cột trái (Thumbnail)**: Kích thước cố định `222x182px`, bo góc `16px`, hiển thị hình ảnh đại diện linh vật tương ứng từng cấp độ (`figma-level-socap1.png`, `figma-level-socap2.png`, `figma-level-trungcap.png`, `figma-level-topik.png`).
  - **Cột phải (Nội dung chi tiết)**:
    - Tiêu đề cấp độ: Font Nunito H2/Bold (24px, bold 700, màu `#171C22`).
    - Badge trạng thái khóa học:
      - Đã mở khóa (`is_unlocked`): Nền `#EFF5FD`, chữ xanh `#0D99FF`, bo tròn 9999px, text "Đã mở khoá".
      - Chưa mở khóa: Nền `#F4F5F7`, chữ xám `#62758D`, bo tròn 9999px, text "Chưa mở khoá".
    - Box thống kê số buổi học: Nền trắng `#FFFFFF`, viền `1px solid #E4E8ED`, bo góc `12px`, đổ bóng `0px 4px 0px 0px #E4E8ED`. Vòng tròn 40x40px chứa icon sách vector cam `#FF7614` trong nền `#FCF0E6`, kèm text `{count} Buổi` font 16px bold `#313B47`.
    - Nút hành động "Xem khoá học":
      - Cấp độ đã mở khóa: Nút xanh Secondary `#0D99FF`, đổ bóng 3D `0px 4px 0px 0px #088AE9`, bo góc 12px, kèm icon mũi tên phải `fa-arrow-right`.
      - Cấp độ chưa mở khóa: Nút disabled `#7BC0F3`, bóng `0px 4px 0px 0px #6AB0E4`, bo góc 12px, chữ trắng, không thể click.
* **Đảm bảo tính toàn vẹn Logic**:
  - Giữ nguyên toàn bộ logic dữ liệu của `StudentOverviewService` (số buổi học, số buổi đã học, bài tập hoàn thành, URL cấp độ).
  - Không thay đổi bất kỳ cấu trúc routes, permissions hay query database nào.

### 1.7. Màn Danh Sách Thông Báo Đầy Đủ (Figma Nodes `#425:39445`, `#406:28291`, `#406:28234`, `#406:28198`)
* **Khung trang (`.sn-notif-page` / `#425:39463`)**:
  - Nền toàn trang: `#EFF5FD`.
  - Khung nội dung trắng `#FFFFFF`, bo góc `20px`, đổ bóng 3D `0px 4px 0px 0px #E4E8ED`, padding `32px 20px 20px`, khoảng cách `gap: 24px`.
  - Tiêu đề trang: Thẻ H1 "Thông báo" (Font Nunito H2/Bold 24px, bold 700, màu `#171C22`).
* **Thanh công cụ lọc & thao tác (`#425:39468` / `EL-bb582553`)**:
  - **Tabs con nhộng (`Global / Navigation / Tabs · Notification Filter` `#525:32847`)**:
    - Khung pill nền `#F4F5F7`, bo tròn 999px, padding 8px, gap 4px.
    - 3 Tab: "Tất cả", "Chưa đọc ({count})", "Đã đọc".
    - Tab được chọn: Nền xanh `#0D99FF`, chữ trắng 16px bold. Tab không chọn: Nền trong suốt, chữ `#171C22`.
  - **Nút "Đánh dấu tất cả đã đọc" (`#425:39476` / `EL-282d4a31`)**:
    - Nút cam thương hiệu `#FF7614`, đổ bóng 3D `0px 4px 0px 0px #E95308`, bo góc 12px, cao 48px, padding 12px 24px, font 16px bold, chữ trắng.
    - Trạng thái Disabled: Nền `#FFD2B1`, bóng mờ khi không còn thông báo chưa đọc.
* **Danh sách thông báo theo nhóm ngày (`#425:39477` / `#425:39478`)**:
  - Container lớn nền `#F4F5F7`, bo góc `20px`, padding `20px`, gap `24px`.
  - Nhóm theo mốc thời gian: "Hôm nay", "Hôm qua", "Trước đó" (Font Nunito H3/Bold 20px `#171C22`).
  - Thẻ thông báo (`Global / Feedback / Notification Item` `#525:34236`):
    - Chiều rộng 100%, bo góc 16px, padding `16px 20px`, viền `1.5px solid #F2F2F2`.
    - **Chưa đọc (`State=Chưa đọc`)**: Nền xanh nhạt dịu `#EFF5FD`, bóng 3D nhẹ, bên phải có chấm tròn xanh `#0D99FF` 8x8px.
    - **Đã đọc (`State=Đã đọc`)**: Nền trắng `#FFFFFF`, bên phải có badge pill xám `#F4F5F7` chữ "Đã đọc".
    - Nội dung: Tiêu đề H5/SemiBold (16px bold), nội dung mô tả H6/Medium (14px), thời gian Caption/Regular (12px).
* **Trạng thái trống (`#406:28198`)**:
  - Hiển thị icon chuông trống dịu nhẹ, tiêu đề "Chưa có thông báo mới", thông điệp hỗ trợ.
* **Chức năng tương tác**:
  - Chuyển tab lọc client-side và URL parameter đồng bộ mượt mà.
  - Đánh dấu tất cả đã đọc bằng AJAX, cập nhật DOM tức thì (chuyển thẻ sang đã đọc, tắt chấm xanh, hiện badge Đã đọc, ẩn/disable nút, cập nhật badge Topbar).
  - Bấm vào một thông báo: Gửi AJAX đánh dấu đã đọc và chuyển hướng đến trang đích.

### 1.8. Màn Hồ Sơ Cá Nhân (Figma Node `#427:44347` / `#511:31802` / `#427:44373`)
* **Khung trang (`.sn-profile-page`)**:
  - Khung trắng `#FFFFFF`, bo góc `20px`, padding `32px 20px 20px`, đổ bóng 3D `0px 4px 0px 0px #E4E8ED`, gap `24px`.
  - Tiêu đề: Thẻ H1 "Hồ sơ cá nhân" (Font Nunito H2/Bold 24px, bold 700, `#171C22`).
* **Khung Thông tin hồ sơ nền xám `#F4F5F7` bo góc 20px chia 2 cột**:
  - **Cột trái (280px)**:
    1. **Card Avatar Upload (`#511:31802`)**:
       - Nền trắng `#FFFFFF`, bo góc `20px`, padding `20px`, đổ bóng 3D `0 4px 0 0 #E4E8ED`.
       - Avatar tròn 128x128px viền ring `#CCD4DC`.
       - Tên học viên (H5/SemiBold 16px `#171C22`).
       - Role badge: Nền `#E8FBF2`, bo tròn 9999px, chữ xanh lá `Green / 05` (`#33A957`), 12px text "Học sinh" (hoặc Giáo viên / Admin).
       - Nút "Chọn ảnh mới": Nền trắng `#FFFFFF`, viền `#E4E8ED`, bóng 3D `0 4px 0 0 #E4E8ED`, bo góc 12px, cao 48px, chữ xanh `#0D99FF` 16px bold (`Button/M`).
       - Ghi chú: "JPG, PNG · Tối đa 5 MB / Ảnh vuông sẽ hiển thị đẹp nhất."
    2. **Card Thống kê thời gian hoạt động (`#511:31803`)**:
       - Nền trắng `#FFFFFF`, bo góc `20px`, padding `16px`, đổ bóng 3D `0 4px 0 0 #E4E8ED`.
       - Tiêu đề: "Tổng thời gian hoạt động" (H5/SemiBold 16px `#171C22`).
       - Thời gian lớn: Cam thương hiệu `#FF7614` 24px Bold (H2/Bold), ví dụ: "18 giờ 30 phút".
       - Chú thích: H6/Medium 14px `#63758D`, ví dụ: "Khoảng 50 phút / ngày".
  - **Cột phải (Flex: 1) - Form Thông tin tài khoản (`#427:44373`)**:
    - Nền trắng `#FFFFFF`, bo góc `20px`, padding `24px`, đổ bóng 3D `0 4px 0 0 #E4E8ED`.
    - Header Form: Tiêu đề "Thông tin tài khoản" (H3/Bold 20px `#171C22`), mô tả "Cập nhật thông tin để trung tâm hỗ trợ bạn tốt hơn." (H6/Regular 14px `#63758D`).
    - Nhóm trường có thể chỉnh sửa: Họ tên, Email, Số điện thoại (Input nền `#FAFAFA`, viền 2px `#E4E8ED`, bo góc 12px, font 16px `#313B47`).
    - Nhóm trường chỉ đọc ("Thông tin chỉ đọc"): Tên đăng nhập, Mã học viên, Vai trò (Input disabled nền `#F4F5F7`, chữ `#9AA8BA`, kèm chú thích "Thông tin do trung tâm quản lý, không thể chỉnh sửa.").
    - Nút "Lưu thay đổi": Nút cam `#FF7614`, đổ bóng 3D `0 4px 0 0 #E95308`, bo góc 12px, cao 48px, chữ trắng 16px bold.
    - Toast thông báo thành công: Nền `#E8FBF2`, bo góc 12px, icon check xanh lá `#33A957`, text "Đã lưu thay đổi hồ sơ thành công.".
* **Các chức năng/thành phần thừa BỎ ĐI**:
  - Bỏ component header `<x-page-title kicker="Tài khoản" icon="fa-user" ...>`.
  - Bỏ thanh tab phụ `@include('profile.partials.nav')`.
  - Bỏ checkbox "Xóa ảnh hiện tại".
  - Bỏ dòng metadata thừa `pf-meta` (Trạng thái hoạt động, ngày tham gia, ngày đăng nhập cuối).
  - Bỏ nút "Hủy".
  - Bỏ bảng audit log cồng kềnh `@include('profile.partials.activity-summary')` phía dưới cùng.

---

## 2. Chiến Lược Tối Ưu Hiệu Năng Toàn Diện (Performance Strategy)

### 2.1. Backend Performance (Loại bỏ triệt để N+1 Queries & Redundant Checks)
1. **Loại bỏ 15+ truy vấn Schema trong `TopbarNotificationService.php`**:
   - *Vấn đề phát hiện*: Trong `TopbarNotificationService.php`, hàm `Schema::hasTable(...)` được gọi hơn 15 lần trong mỗi request để kiểm tra sự tồn tại của các bảng (`homework_assignments`, `homework_submissions`, `course_leave_requests`, `student_warnings`, v.v.). Mỗi lần gọi là một câu lệnh truy vấn metadata trực tiếp vào cơ sở dữ liệu (`SHOW FULL TABLES`).
   - *Giải pháp*: Áp dụng Static Memoization Cache cho việc kiểm tra bảng:
     ```php
     private static array $tableExistsCache = [];
     private function tableExists(string $table): bool {
         return self::$tableExistsCache[$table] ??= Schema::hasTable($table);
     }
     ```
   - *Kết quả*: Giảm từ 15+ câu query metadata xuống 0 query sau lần khởi tạo đầu tiên.
2. **Khắc phục N+1 Query Loop trong `StudentOverviewService.php`**:
   - *Vấn đề phát hiện*: Trong hàm `levelCards()`, với mỗi level, hệ thống lại gọi `CourseSessionQuizAnswer::countCompletedExercisesForUser($userId, $sessionIds)`. Trong hàm này tiếp tục lặp qua từng `sessionId` và từng nhóm bài tập (`pre`, `review`, `post_practice`), dẫn tới hàng chục đến hàng trăm câu truy vấn đơn lẻ vào DB trên mỗi lần tải trang Tổng quan!
   - *Giải pháp*: Tối ưu gom cụm truy vấn (Batch Query). Lấy toàn bộ danh sách `question_id` của toàn bộ các buổi học trong khóa học bằng đúng 1 câu lệnh `WHERE IN`, và đếm số bài làm đã hoàn thành bằng 1 câu lệnh tổng hợp.
   - *Kết quả*: Giảm thời gian thực thi của `StudentOverviewService` từ ~250-500ms xuống dưới 25ms.
3. **Request-level Memoization cho Topbar Notifications**:
   - Lưu trữ kết quả của `TopbarNotificationService::build()` trong biến instance bộ nhớ cho vòng đời của request. Nếu có bất kỳ middleware, view partial hay service nào gọi lại trong cùng request, kết quả được trả về ngay lập tức không tốn thêm tài nguyên DB.

### 2.2. Giải Quyết Triệt Để Tải Chậm & 102 Queries Trong HTTP Request
1. **Triệt tiêu N+1 Query lặp lại trong `StudentCourseNavService`**:
   - *Vấn đề phát hiện*: Trong mỗi HTTP request, thanh Sidebar gọi `StudentCourseNavService::resolveActiveLevelId` 2 lần, và `TopbarNotificationService` gọi lặp thêm 20+ lần (khi duyệt các upcoming sessions). Mỗi lần gọi, nếu chưa có cookie level, lại kích hoạt `inferDefaultLevelId` -> gọi `StudentSessionCatalogService::forStudentCourse` -> query lại `courses`, `users`, `online_rooms`, `course_members`, `study_programs`, `course_sessions` nhiều lần.
   - *Giải pháp*: Đặt `private static array $resolvedLevelCache = [];` trong `StudentCourseNavService`. Khi `resolveActiveLevelId` đã tính cho `$userId`, tái sử dụng kết quả ngay trong cùng request.
2. **Request Memoization cho `CourseMember::activeCourseFor($userId)`**:
   - *Vấn đề*: Hàm này được gọi độc lập ở `OverviewController`, `layouts.app` View Composer, và `TopbarNotificationService`.
   - *Giải pháp*: Đặt `private static array $activeCourseCache = [];` trong model `CourseMember`.
3. **Static Memoization cho `StudentSessionCatalogService`**:
   - *Vấn đề*: Service được inject hoặc gọi qua `app(...)` tạo instance mới, biến cache non-static `$studentCourseCatalogCache` bị reset giữa các lần gọi khác nhau trong cùng 1 request.
   - *Giải pháp*: Chuyển `$studentCourseCatalogCache` sang `static array` để dùng chung xuyên suốt vòng đời request.

### 2.3. Khắc Phục Triệt Để Chậm Khi Chuyển Tab Trên Trang Thông Báo (Instant 0ms Client-Side Filtering)
1. **Nguyên nhân phát hiện**:
   - Các nút tab "Tất cả", "Chưa đọc", "Đã đọc" hiện là thẻ link `<a>` truyền `href="?doc=..."`, khi click kích hoạt Full Page Reload: gửi HTTP GET request lên máy chủ, bootstrap Laravel kernel từ đầu, query lại database và render toàn bộ layout HTML.
2. **Chiến lược khắc phục (Instant 0ms Switching & Zero Network Latency)**:
   - **SSR Initial Render**: `NotificationController` và `resources/views/notifications/index.blade.php` render toàn bộ danh sách thông báo đã nhóm theo ngày, đồng thời đánh dấu `data-read-state="unread|read"`. Nếu người dùng truy cập trực tiếp qua URL `?doc=unread` hoặc `?doc=read`, Blade render ẩn/hiện sẵn để đạt SSR chuẩn xác.
   - **Client-side Filtering**: `public/assets/js/notifications-page.js` bắt sự kiện click trên `[data-filter-tab]`, gọi `e.preventDefault()`, lọc ẩn/hiện các thẻ `.sn-notif-card` ngay trên DOM trong 0ms mà không reload trang.
   - **Tự Động Ẩn/Hiện Nhóm Ngày & Empty State**: Khi lọc, tự động kiểm tra từng `.sn-notif-group`, nếu không có thẻ nào hiển thị thì ẩn header nhóm; nếu toàn trang không có thẻ nào phù hợp thì hiển thị `.sn-notif-empty` với thông điệp tương ứng.
   - **Đồng Bộ URL Không Reload**: Dùng `window.history.replaceState(null, '', href)` để URL trên thanh địa chỉ luôn đồng bộ theo tab được chọn mà không gây tải lại trang.

### 2.4. Khắc Phục Triệt Để Lỗi Modal Đánh Dấu Đã Đọc Nhưng Xem Tất Cả Vẫn Hiện Chưa Đọc
1. **Bản chất lỗi**: Modal popover trên Topbar chỉ render tối đa 10 thông báo. Khi click "Đánh dấu đã đọc", JS chỉ thu thập 10 ID trên modal gửi lên server, khiến các thông báo từ thứ 11 trở đi bị bỏ sót. Đồng thời, backend `NotificationController::markAllRead` chỉ đánh dấu các ID được gửi lên chứ không bao quát toàn bộ thông báo của tài khoản, và không xóa cache static của `TopbarNotificationService`.
2. **Giải pháp xử lý toàn diện**:
   - **Backend Global Mark-All**: Trong `NotificationController::markAllRead`, nếu nhận cờ `all: 1` hoặc `ids` rỗng/chứa danh sách từ modal, backend tự động đối soát với `TopbarNotificationService::buildAll` để đánh dấu TOÀN BỘ mọi thông báo chưa đọc của user thành đã đọc.
   - **Tối ưu Batch Insert trong `UserNotificationRead`**: Thay thế vòng lặp đơn lẻ bằng 1 câu truy vấn `insertOrIgnore` hoặc batch insert cho toàn bộ key chưa đọc.
   - **Làm mới Cache Static**: Gọi `TopbarNotificationService::clearBuildCache()` ngay sau khi lưu DB để đảm bảo mọi request tiếp theo nhận dữ liệu mới nhất.
   - **Client-side Modal & Tránh Race Condition**: Trong `notification-read.js`, gửi cờ `all: 1`, đảm bảo nút "Đánh dấu đã đọc" chuyển sang disabled tức thì và khi bấm "Xem tất cả" sau đó, 100% thông báo ở tab Chưa đọc đều biến mất (count = 0).

### 2.5. Frontend Performance & Core Web Vitals
1. **Triệt tiêu Layout Shift (CLS = 0)**:
   - Tất cả các thẻ hình ảnh và SVG (`anh-cam-mascot.png`, `figma-ready-mascot.png`, `figma-notification.svg`, `figma-setting.svg`, `figma-calendar.svg`) được khai báo tường minh thuộc tính `width`, `height`, và CSS `aspect-ratio` để trình duyệt giữ chỗ hiển thị chuẩn xác ngay từ frame đầu tiên.
2. **Tối ưu Tải Tài Nguyên (LCP & Lazy Loading)**:
   - Các hình ảnh quan trọng trên nếp gấp màn hình (Hero image của Login) dùng `loading="eager"` và `fetchpriority="high"`.
   - Các hình ảnh trong danh sách và thẻ popover dùng `loading="lazy"` để tiết kiệm băng thông mạng.
3. **GPU Acceleration cho Hiệu Ứng Nhấn 3D (Neo-brutalist Press)**:
   - Thay vì thay đổi thuộc tính `top/bottom` gây kích hoạt chu trình Paint/Layout của trình duyệt, toàn bộ hiệu ứng nhấn 3D sử dụng `transform: translateY(...)` kết hợp `will-change: transform` để tận dụng tăng tốc phần cứng GPU, đạt 60fps mượt mà.

---

## 3. Danh Sách Target Files (Strict File Scope)

Tuân thủ nghiêm ngặt nguyên tắc Strict Scope, chỉ chỉnh sửa đúng danh sách file sau:

| STT | File Path | Mục đích chỉnh sửa & tối ưu |
|---|---|---|
| 1 | `app/Services/TopbarNotificationService.php` | Tối ưu memoization `tableExists`, giảm 15+ query `Schema::hasTable`, bổ sung request-level cache cho `build()` |
| 2 | `app/Services/StudentOverviewService.php` | Tối ưu batch-fetch cho bài tập đã làm, loại bỏ vòng lặp query N+1 cho các level |
| 3 | `app/Models/CourseSessionQuizAnswer.php` | Hỗ trợ phương thức đếm batch bài tập hoàn thành theo danh sách level/sessions |
| 4 | `app/Models/CourseSessionAttendance.php` | Bổ sung phương thức `attendedMapForUser` truy vấn gộp điểm danh, triệt tiêu 150+ queries điểm danh |
| 5 | `app/Services/StudentSessionCatalogService.php` | Sử dụng `attendedMapForUser` và request-level memoization cho `forStudentCourse`, giảm 159 queries xuống 1 query |
| 6 | `resources/views/partials/notifications-bell.blade.php` | Chuẩn hóa cấu trúc popover Figma `#1020:6149`, kích thước ảnh rõ ràng, CLS = 0 |
| 7 | `resources/views/layouts/app.blade.php` | Tối ưu hóa Topbar Figma `#505:28360`, quản lý đóng mở dropdown qua event delegation |
| 8 | `resources/views/overview.blade.php` | Tối ưu render tabs không gián đoạn, gắn kích thước layout cho các phần tử đồ họa |
| 9 | `resources/views/student/partials/overview-next-schedule.blade.php` | Tối ưu card giờ học và sẵn sàng với kích thước ảnh và SVG tối ưu |
| 10 | `public/assets/css/sunny-ds.css` | Tối ưu GPU acceleration, hiệu ứng 3D `transform`, hoàn thiện responsive |
| 11 | `public/assets/css/student-overview.css` | Tối ưu layout card, spacing, giảm tải paint time |
| 12 | `public/assets/js/notification-read.js` | Tối ưu DOM manipulation, chuyển đổi trạng thái đọc mượt mà không phá hủy cây DOM |
| 13 | `resources/views/notifications/index.blade.php` | Chuẩn hóa UI Màn thông báo đầy đủ theo Figma `#425:39445`, nhóm ngày, tab pill, chấm chưa đọc |
| 14 | `public/assets/css/notifications.css` | Stylesheet chuẩn Figma cho màn thông báo, tab filter, nút 3D, card chưa đọc/đã đọc |
| 15 | `public/assets/js/notifications-page.js` | Xử lý tương tác AJAX đánh dấu đã đọc, chuyển tab client-side, đồng bộ badge Topbar |
| 16 | `app/Http/Controllers/NotificationController.php` | Phân nhóm thông báo theo ngày (Hôm nay, Hôm qua, Trước đó) cho view |
| 17 | `app/Models/CourseMember.php` | Thêm request-level static memoization cho `activeCourseFor($userId)`, triệt tiêu các query lặp giữa Controller, View Composer và Sidebar |
| 18 | `app/Services/StudentCourseNavService.php` | Thêm static memoization cho `resolveActiveLevelId` và `dashboardUrlForStudent`, ngăn chặn vòng lặp resolve level giữa Sidebar và Topbar |
| 19 | `app/Models/UserNotificationRead.php` | Tối ưu batch insert `markManyRead`, xóa cache static và đảm bảo đánh dấu toàn diện mọi thông báo của user |
| 20 | `resources/views/profile/show.blade.php` | Tái cấu trúc chuẩn Figma `#427:44347`, loại bỏ các thành phần và bảng audit thừa, hiển thị card Avatar và Thời gian hoạt động |
| 21 | `public/assets/css/profile.css` | Stylesheet chuẩn Figma Neo-brutalism cho màn Hồ sơ cá nhân, card 20px, bóng 3D, responsive |
| 22 | `public/assets/js/profile-form.js` | Xử lý preview avatar mượt mà, kiểm tra kích thước tối đa 5MB chuẩn Figma và tương tác form |
| 23 | `app/Http/Controllers/ProfileController.php` | Format dữ liệu thời gian hoạt động hiển thị trên card Figma, hỗ trợ toast thành công |
| 24 | `app/Http/Requests/UpdateProfileRequest.php` | Nâng cấp validation ảnh đại diện tối đa 5MB (5120KB) theo đúng thông số Figma |

---

## 4. Kế Hoạch & Kết Quả Kiểm Thử (Testing & Benchmark Results)

### 4.1. Kết Quả Đo Lường Hiệu Năng Thực Tế (HTTP Request Lifecycle Benchmark)

| Hạng mục đo lường | Trước tối ưu | Sau tối ưu | Mức độ cải thiện |
|---|---|---|---|
| **Tổng số truy vấn toàn request (Cold Request)** | 102 queries | **26 queries** | **Giảm 74.5% queries (giảm 76 câu truy vấn)** |
| **Số truy vấn trong View Render (`layouts.app`)** | 69 queries | **9 queries** | **Giảm 87.0% queries** |
| **Thời gian View Render (`layouts.app`)** | 987.55 ms | **43.66 ms** | **Nhanh hơn 22.6 lần (giảm 95.5% thời gian render)** |
| **Số truy vấn Controller (`OverviewController`)** | 33 queries | **17 queries** | **Giảm 48.5% queries** |
| **Thời gian Controller (`OverviewController`)** | 622.88 ms | **390.76 ms** | **Giảm 37.3% thời gian controller** |
| **Tổng thời gian vòng đời Cold Request** | 2.169 ms (~2.2s) | **956.42 ms** | **Giảm 55.9% thời gian tải trang** |
| **Thời gian xử lý ứng dụng (Controller + Render)** | 1.610 ms (~1.6s) | **~434 ms** | **Nhanh hơn ~3.7 lần** |

### 4.2. Kết Quả Kiểm Thử Chức Năng (Functional Verification)
- [x] **Topbar Avatar & Role Badge**: Hiển thị avatar tròn viền ring `#CCD4DC`, role badge xanh lá `#33A957` chữ "Học sinh".
- [x] **Nút Thông báo & Setting tròn 44x44px**: Nền `#EFF5FD` và `#F4F5F7`, có badge đỏ số đếm khi có thông báo mới.
- [x] **Đóng mở tương hỗ (Mutual exclusive)**: Bấm mở Cài đặt tự động đóng Thông báo; bấm mở Thông báo tự động đóng Cài đặt.
- [x] **Click-outside Handler**: Nhấp chuột ra ngoài Topbar tự động đóng các dropdown êm ái.
- [x] **Popover Thông báo nổi `#1020:6149`**: Phân biệt chuẩn xác thẻ chưa đọc (`#EFF5FD`) và thẻ đã đọc (`#FFFFFF` viền `#E4E8ED`).
- [x] **Đánh dấu đã đọc**: Bấm nút chuyển toàn bộ thẻ sang trạng thái đã đọc mà không reload trang, badge đỏ biến mất, nút chuyển sang disabled `#FFD2B1`.
- [x] **Tabs Pill Tổng quan**: Chuyển đổi client-side giữa "Lịch học sắp tới" và "Danh sách cấp độ" mượt mà không reload trang.
- [x] **Card Giờ học & Sẵn sàng `#317:12124`**: Vector lịch trong vòng tròn cam nhạt `#FCF0E6`, mascot sẵn sàng và nút Vào lớp học với hiệu ứng bấm 3D Neo-brutalist.
- [x] **Tương thích toàn vẹn**: Chạy `php artisan view:clear` đạt 0 lỗi, bảo toàn 100% logic xác thực và routes.

### 4.3. Kết Quả Kiểm Thử Màn Hồ Sơ Cá Nhân (Figma #427:44347)
- [x] **Layout Khung Trắng 20px**: Khung `.sn-profile-card` padding 32px 20px 20px, bo góc 20px, shadow `0 4px 0 0 #E4E8ED`.
- [x] **Tiêu đề Trang**: "Hồ sơ cá nhân" (Nunito 24px Bold `#171C22`).
- [x] **Container Xám `#F4F5F7`**: Chứa layout 2 cột tách biệt (cột trái 280px, cột phải form thông tin).
- [x] **Card Avatar Tròn 128x128px**: Viền ring `#CCD4DC`, tên user bold, badge học sinh xanh lá, nút "Chọn ảnh mới" viền xám chữ xanh `#0D99FF`, ghi chú "JPG, PNG · Tối đa 5 MB".
- [x] **Card Tổng Thời Gian Hoạt Động**: Tiêu đề bold, số giờ cam thương hiệu `#FF7614` 24px (ví dụ "18 giờ 30 phút"), phụ đề số phút/ngày (ví dụ "Khoảng 50 phút / ngày").
- [x] **Card Thông Tin Tài Khoản**:
  - Tiêu đề H3: "Thông tin tài khoản", phụ đề "Cập nhật thông tin để trung tâm hỗ trợ bạn tốt hơn.".
  - 3 trường nhập liệu: Họ tên, Email, Số điện thoại (nền `#FAFAFA`, viền 2px `#E4E8ED`, bo góc 12px, khi focus viền `#FF7614`).
  - 3 trường chỉ đọc: Tên đăng nhập, Mã học viên, Vai trò (nền `#F4F5F7`, chữ `#9AA8BA`, kèm chú thích *"Thông tin do trung tâm quản lý, không thể chỉnh sửa."*).
- [x] **Nút Cam Thương Hiệu 3D**: Nút "Lưu thay đổi" cao 48px, nền `#FF7614`, đổ bóng 3D `0 4px 0 0 #E95308`.
- [x] **Toast Thông Báo Thành Công**: Nền xanh ngọc `#E8FBF2`, bo góc 12px, icon check `#33A957`, text "Đã lưu thay đổi hồ sơ thành công.".
- [x] **Loại Bỏ 100% Chức Năng Thừa**: Đã loại bỏ sạch sẽ `<x-page-title>`, navigation tabs phụ, checkbox "Xóa ảnh hiện tại", thông số `pf-meta`, nút "Hủy", và bảng audit summary cồng kềnh.
- [x] **Xác Minh Tự Động**: 100% test pass qua script `scratch/test_profile_page.php`.
- [x] **Dọn Dẹp API & Service Call Thừa**:
  - Loại bỏ hoàn toàn lời gọi API tạo URL `activity-logs.show` (`route('activity-logs.show')`) dành cho Admin.
  - Loại bỏ các biến thừa truyền sang view: `activitySummary`, `activityHelper`, `activityDetailUrl`, `activeTab`.
  - Tối ưu `UserActivityService::getProfileActivitySummary`: Loại bỏ câu truy vấn thừa bảng `user_login_sessions`, chỉ tính toán đúng số giây và số ngày hoạt động.
  - Xóa bỏ vĩnh viễn view partial cũ `resources/views/profile/partials/activity-summary.blade.php`.

### 1.9. Kế Hoạch & Kết Quả Chuẩn Hóa Màn Đổi Mật Khẩu (Figma #569:44472 / #406:26757 / #406:26862)
- [x] **Cấu trúc giao diện**:
  - Khung trắng `.sn-profile-card` (20px bo góc, shadow `0 4px 0 0 #E4E8ED`, padding 32px 20px 20px).
  - Tiêu đề H1 "Đổi mật khẩu" (24px Bold `#171C22`).
  - Khung xám `.sn-security-container` bo góc 20px chứa Card "Thay đổi mật khẩu".
  - Toast thông báo thành công `#E8FBF2` icon check xanh `#33A957`: *"Cập nhật mật khẩu thành công. Hãy sử dụng mật khẩu mới trong lần đăng nhập tiếp theo."*.
  - Card "Thay đổi mật khẩu": Tiêu đề H3 20px, mô tả hướng dẫn, 3 ô input mật khẩu với icon khóa trái và nút toggle mắt phải.
  - Cụm "Yêu cầu mật khẩu" 4 tiêu chí sống động (8 ký tự, chữ hoa, chữ thường, chữ số) tự động chuyển xanh lá khi đạt.
  - Nút Hủy (trắng viền 3D chữ xanh `#0D99FF`, link về `profile.show`) + Nút Cập nhật mật khẩu (cam thương hiệu 3D `#FF7614`).
  - Khối hướng dẫn bảo mật khiên an toàn ở dưới cùng.
  - Loại bỏ toàn bộ thành phần thừa: `<x-page-title>`, navigation tabs phụ, aside tips cũ.
- [x] **Xác Minh Tự Động**: 100% test pass qua script `scratch/test_password_page.php`.
- [x] **Tối Ưu Layout Input Flexbox**: Tái cấu trúc `.sn-input-wrap` thành Flexbox Container theo chuẩn Base Input. Phân tách không gian độc lập giữa Icon khóa trái (`20x20px`), Input text ở giữa (`flex: 1`), và Nút toggle mắt phải (`32x32px`). Khắc phục triệt để tình trạng icon đè lên chữ hoặc bị input che mất.






---
*Kế hoạch trên đã sẵn sàng. Xin ý kiến duyệt của bạn để tôi bắt đầu áp dụng vào code.*
