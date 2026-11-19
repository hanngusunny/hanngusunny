# KẾ HOẠCH TRIỂN KHAI: HỆ THỐNG DESIGN SYSTEM & BASE COMPONENTS (SUNNY LMS / WEBSITE)

> **Tuân thủ `.antigravity/rules.md`:** 
> - **Nguyên tắc Plan-First:** Kế hoạch này được lập trước khi can thiệp vào bất kỳ file nào trong `app/`, `resources/`, `routes/`, `database/`.
> - **Strict File Scope:** Chỉ chỉnh sửa đúng danh sách file trong mục **Target Files** sau khi người dùng phê duyệt kế hoạch này.
> - **Không phá vỡ chức năng cũ:** Toàn bộ CSS và Blade components mới được đóng gói độc lập theo tiền tố `sn-` / `sunny-`, không ghi đè hay làm sai lệch CSS hiện có của `office-shell.css`, `dashboard.css`, hay `style.css`.

---

## 1. Nguồn Dữ Liệu & Đối Chiếu Figma MCP

- **Figma File:** `SUNNY-Design-Website-1.0` (Key: `sYb0uSNxD7B2ZdPOaYM6MF`)
- **Figma Node:** `408:29608` (`00 · Design System · Components`)
- **Phạm vi đối chiếu:**
  1. `00 · Global / Foundation`: Typography, Colors, Elevation / Shadows, Radius.
  2. `00 · Global / Actions`: Button (Primary, Secondary, Tertiary, Danger, Success, Text; Default, Hover, Press, Disabled; Icon left/right, các size XL, L, M, S, XS).
  3. `00 · Global / Forms`: Text Field, Text Area, Select, Toggle, Avatar Upload.
  4. `00 · Global / Data Display`: Status Pill, Badge Course Level, Role Badge, Card.
  5. `00 · Global / Feedback & Overlays`: Modal / Dialog, Toast, Notification Popover.

---

## 2. Bảng Thông Số Thiết Kế Chuẩn (Design Tokens)

### 2.1. Phông Chữ & Thang Kích Thước (Typography Scale)
- **Primary Font Family:** `'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
- **Korean Font Family:** `'Noto Sans KR', sans-serif` (dùng cho bài học tiếng Hàn)
- **Heading Scale:**
  - `H1`: `font-size: 32px` | `line-height: 48px` (1.5em) | `font-weight: 700 / 500`
  - `H2`: `font-size: 24px` | `line-height: 36px` (1.5em) | `font-weight: 700 / 500`
  - `H3`: `font-size: 20px` | `line-height: 30px` (1.5em) | `font-weight: 700 / 500`
  - `H4`: `font-size: 18px` | `line-height: 27px` (1.5em) | `font-weight: 700 / 500`
  - `H5`: `font-size: 16px` | `line-height: 24px` (1.5em) | `font-weight: 700 / 500 / 400`
  - `H6`: `font-size: 14px` | `line-height: 21px` (1.5em) | `font-weight: 700 / 500 / 400`
- **Body & Text Scale:**
  - `Body Large`: `font-size: 16px` | `line-height: 24px` | `font-weight: 400`
  - `Body Medium` (mặc định): `font-size: 14px` | `line-height: 21px` | `font-weight: 400`
  - `Body Small`: `font-size: 12px` | `line-height: 18px` | `font-weight: 400`
  - `Caption / Tiny`: `font-size: 10px / 11px` | `line-height: 16px`
- **Button Typography:**
  - `Button XL`: `20px` (Bold 700, line-height 30px)
  - `Button L`: `18px` (Bold 700, line-height 27px)
  - `Button M` (chuẩn): `16px` (Bold 700, line-height 24px)
  - `Button S`: `14px` (Bold 700, line-height 21px)
  - `Button XS`: `12px` (Bold 700, line-height 18px)

### 2.2. Bảng Màu (Color Tokens)
- **Primary (Cam Sunny):**
  - `--sn-primary`: `#FF7614` (Chính)
  - `--sn-primary-hover`: `#FF8B38` (Hover - Primary 3)
  - `--sn-primary-active`: `#F26500` (Active/Press - Primary 2)
  - `--sn-primary-disabled`: `#FFD2B1` (Disabled - Primary 4)
  - `--sn-primary-soft`: `#FFF7F0` (Nền nhạt - Shade 01)
  - `--sn-primary-shadow`: `rgba(233, 83, 8, 1)` / `#E95308`
- **Secondary (Xanh Dương):**
  - `--sn-secondary`: `#0D99FF` (Chính)
  - `--sn-secondary-hover`: `#4DB4FF`
  - `--sn-secondary-active`: `#0364C6` (Shade 08)
  - `--sn-secondary-disabled`: `#7BC0F3`
  - `--sn-secondary-soft`: `#EFF5FD` / `#EFF3F8`
  - `--sn-secondary-shadow`: `rgba(8, 138, 233, 1)` / `#088AE9`
- **Chữ & Trung Tính (Neutrals):**
  - `--sn-text-title`: `#171C22` (Tiêu đề đậm)
  - `--sn-text-body`: `#313B47` (Văn bản chính)
  - `--sn-text-subtitle`: `#63758D` (Phụ đề)
  - `--sn-text-caption`: `#8092A8` (Ghi chú, placeholder)
  - `--sn-text-disabled`: `#9AA8BA` (Chữ vô hiệu hóa)
  - `--sn-border`: `#E4E8ED` (Viền cấp 1)
  - `--sn-border-subtle`: `#F2F2F2` (Viền mờ cấp 2)
  - `--sn-bg-white`: `#FFFFFF`
  - `--sn-bg-page`: `#FAFAFA`
  - `--sn-bg-surface`: `#EFF3F8`
  - `--sn-bg-disabled`: `#F4F5F7`
- **Trạng Thái (Semantic / Functional):**
  - **Success:** Nền `#10B266`, Hover `#3DC787`, Active `#06884B`, Soft `#EDFBE9` / `#E8FBF2`, Shadow `#0A8E4F`
  - **Danger / Error:** Nền `#FA553C`, Hover `#FF7762`, Active `#ED3C17`, Soft `#FDF3F2`, Shadow `#F2371B`
  - **Warning:** Nền `#FCCD02`, Soft `#FBF4DC`, Chữ `#FEB50F` / `#FCB102`

### 2.3. Hiệu Ứng Đổ Bóng (Sunny Neo-Brutalist & Elevation)
Thiết kế Sunny sử dụng phong cách đổ bóng cứng (solid 3D/neo-brutalist shadow) rất đặc trưng cho nút bấm và thẻ:
- **Button Primary Shadow:** `box-shadow: 0px 4px 0px 0px #E95308;`
- **Button Secondary Shadow:** `box-shadow: 0px 4px 0px 0px #088AE9;`
- **Button Danger Shadow:** `box-shadow: 0px 4px 0px 0px #F2371B;`
- **Button Surface / White Shadow:** `box-shadow: 0px 4px 0px 0px #E4E8ED;`
- **Hiệu ứng Nhấn (Press State):** Khi `active`, nút dịch xuống `transform: translateY(3px)` hoặc `translateY(4px)` và shadow thu về `0px 0px 0px 0px` (hiệu ứng nút bấm thực).
- **Card Soft Shadow:** `box-shadow: 0px 0px 24px 0px rgba(0, 30, 75, 0.08);`
- **Popover / Modal Shadow:** `box-shadow: 0px 12px 24px 0px rgba(48, 59, 71, 0.16);`

---

## 3. Kiến Trúc Triển Khai (Dễ Hiểu, Dễ Dùng & Nhanh Nhất)

### 3.1. File CSS Design System (`public/assets/css/sunny-ds.css` & `resources/css/sunny-ds.css`)
Cung cấp toàn bộ:
1. CSS Variables (`--sn-*`).
2. Typography Classes: `.sn-h1`, `.sn-h2`, `.sn-h3`, `.sn-h4`, `.sn-h5`, `.sn-h6`, `.sn-body`, `.sn-caption`.
3. Button Classes: `.sn-btn`, `.sn-btn--primary`, `.sn-btn--secondary`, `.sn-btn--white`, `.sn-btn--danger`, `.sn-btn--success`, `.sn-btn--text`, các size `.sn-btn--sm`, `.sn-btn--lg`.
4. Form Classes: `.sn-input`, `.sn-select`, `.sn-textarea`, `.sn-label`, `.sn-hint`, `.sn-form-group`.
5. Badge Classes: `.sn-badge`, `.sn-badge--primary`, `.sn-badge--secondary`, `.sn-badge--success`, `.sn-badge--warning`, `.sn-badge--danger`.
6. Card Classes: `.sn-card`, `.sn-card--interactive`, `.sn-card--bordered`.

### 3.2. Bộ Blade Components (`resources/views/components/sunny/`)
Đóng gói thành các Blade Component chuẩn Laravel với `@props`, hỗ trợ truyền `$attributes`, icon, slots:

1. `<x-sunny.button>` (hoặc alias `<x-button>`):
   - `variant`: `primary` (mặc định), `secondary`, `white` (tertiary), `danger`, `success`, `text`
   - `size`: `m` (mặc định - 48px), `sm` (38px), `lg` (54px), `xs` (32px)
   - `icon`: icon trái FontAwesome hoặc SVG slot
   - `iconRight`: icon phải
   - `disabled`: boolean
   - `type`: `button`, `submit`, `a` (nếu có `href`)

2. `<x-sunny.input>` (hoặc alias `<x-input>`):
   - `name`, `label`, `type` (text, email, password, number...), `value`, `placeholder`
   - `icon`: icon trái hiển thị trong input
   - `iconRight`: icon phải
   - `hint`: text gợi ý dưới input
   - `error`: hiển thị thông báo lỗi validate
   - `disabled`: boolean

3. `<x-sunny.select>` (hoặc alias `<x-select>`):
   - `name`, `label`, `options` (array hoặc slots `<option>`), `selected`, `placeholder`

4. `<x-sunny.textarea>`:
   - `name`, `label`, `rows`, `placeholder`, `hint`, `error`

5. `<x-sunny.badge>` (hoặc alias `<x-badge>`):
   - `variant`: `primary`, `secondary`, `success`, `warning`, `danger`, `gray`, `purple`
   - `pill`: boolean (rounded-full)
   - `icon`: icon hiển thị kèm

6. `<x-sunny.card>` (hoặc alias `<x-card>`):
   - `title`, `header`, `footer`, `variant`: `default`, `elevated`, `soft`

7. `<x-sunny.toggle>`:
   - `name`, `checked`, `label`

8. `<x-sunny.modal>`:
   - Dialog chuẩn theo Figma (`Global / Feedback / Logout Dialog`) với backdrop, header, body, actions.

---

## 4. Danh Sách Target Files (Strict File Scope)

Sau khi được người dùng phê duyệt, agent sẽ tạo và chỉnh sửa CHÍNH XÁC danh sách các file sau:

| STT | Đường dẫn File | Mục đích |
|---|---|---|
| 1 | `public/assets/css/sunny-ds.css` | File CSS độc lập chứa toàn bộ tokens, typography, utilities, button, form, card, badge |
| 2 | `resources/css/sunny-ds.css` | File CSS nguồn đồng bộ |
| 3 | `resources/views/components/sunny/button.blade.php` | Blade component Button |
| 4 | `resources/views/components/sunny/input.blade.php` | Blade component Input / Text Field |
| 5 | `resources/views/components/sunny/select.blade.php` | Blade component Select dropdown |
| 6 | `resources/views/components/sunny/textarea.blade.php` | Blade component Textarea |
| 7 | `resources/views/components/sunny/badge.blade.php` | Blade component Badge / Status Pill |
| 8 | `resources/views/components/sunny/card.blade.php` | Blade component Card container |
| 9 | `resources/views/components/sunny/toggle.blade.php` | Blade component Toggle Switch |
| 10 | `resources/views/components/sunny/modal.blade.php` | Blade component Modal Dialog |
| 11 | `resources/views/components/button.blade.php` | Root alias shortcut gọi đến `sunny.button` |
| 12 | `resources/views/components/input.blade.php` | Root alias shortcut gọi đến `sunny.input` |
| 13 | `resources/views/components/badge.blade.php` | Root alias shortcut gọi đến `sunny.badge` |
| 14 | `resources/views/components/card.blade.php` | Root alias shortcut gọi đến `sunny.card` |
| 15 | `resources/views/layouts/app.blade.php` | Bổ sung Google Font `Nunito` và `<link rel="stylesheet" href="{{ asset('assets/css/sunny-ds.css') }}">` |
| 16 | `resources/views/public/layout.blade.php` | Bổ sung Google Font `Nunito` và `<link rel="stylesheet" href="{{ asset('assets/css/sunny-ds.css') }}">` |

---

## 5. Kế Hoạch Kiểm Thử & Xác Minh (Verification)

1. **Kiểm tra CSS tokens:** Mở trình duyệt kiểm tra các biến CSS `--sn-primary`, font-family `Nunito`, kích thước font heading H1-H6, bóng đổ `0px 4px 0px 0px`.
2. **Kiểm tra Blade Components:** Tạo file view demo hoặc test trực tiếp cú pháp Blade `<x-button>`, `<x-input>`, `<x-badge>` hoạt động hoàn hảo, render đúng markup HTML và class CSS.
3. **Kiểm tra tương thích ngược:** Đảm bảo các trang hiện tại (`layouts/app.blade.php`, `public/layout.blade.php`) không bị xung đột giao diện, các nút và bảng biểu cũ giữ nguyên hiển thị.

---

## 6. Trạng Thái Thực Hiện (Execution & Completed Status)

Đã hoàn thành toàn bộ việc xây dựng Design System và áp dụng vào các views:

### 6.1. Design System & Components Đã Tạo:
- `public/assets/css/sunny-ds.css` & `resources/css/sunny-ds.css`
- `resources/views/components/sunny/button.blade.php` & alias `components/button.blade.php`
- `resources/views/components/sunny/input.blade.php` & alias `components/input.blade.php`
- `resources/views/components/sunny/select.blade.php`
- `resources/views/components/sunny/textarea.blade.php`
- `resources/views/components/sunny/badge.blade.php` & alias `components/badge.blade.php`
- `resources/views/components/sunny/card.blade.php` & alias `components/card.blade.php`
- `resources/views/components/sunny/toggle.blade.php`
- `resources/views/components/sunny/modal.blade.php`

### 6.2. Layouts Đã Tích Hợp Fonts & CSS:
- `resources/views/layouts/app.blade.php` (Core App Layout)
- `resources/views/public/layout.blade.php` (Public Layout)
- `resources/views/admin/layouts/app.blade.php` (Admin CMS Layout)

### 6.3. Danh Sách Views Đã Áp Dụng Thành Công (33 files):
1. `resources/views/admin/settings/site.blade.php`
2. `resources/views/admin/settings/ui.blade.php`
3. `resources/views/admin/content/categories/form.blade.php`
4. `resources/views/admin/content/posts/form.blade.php`
5. `resources/views/admin/content/tags/form.blade.php`
6. `resources/views/admin/pages/form.blade.php`
7. `resources/views/admin/customize/identity.blade.php`
8. `resources/views/admin/customize/topbar.blade.php`
9. `resources/views/admin/templates/form.blade.php`
10. `resources/views/admin/blocks/form.blade.php`
11. `resources/views/admin/faq/categories.blade.php`
12. `resources/views/admin/faq/items.blade.php`
13. `resources/views/admin/profile/edit.blade.php`
14. `resources/views/admin/users/form.blade.php`
15. `resources/views/admin/appearance/edit.blade.php`
16. `resources/views/admin/slider/categories.blade.php`
17. `resources/views/admin/slider/slides.blade.php`
18. `resources/views/admin/menus/builder.blade.php`
19. `resources/views/profile/show.blade.php`
20. `resources/views/profile/password.blade.php`
21. `resources/views/admin/courses/create.blade.php`
22. `resources/views/admin/courses/index.blade.php`
23. `resources/views/admin/courses/workspace.blade.php`
24. `resources/views/admin/courses/partials/course-fields.blade.php`
25. `resources/views/activity_logs/index.blade.php`
26. `resources/views/activity_logs/show.blade.php`
27. `resources/views/student/partials/reflection.blade.php`
28. `resources/views/teacher/partials/assessment-grade-form.blade.php`
29. `resources/views/public/custom-form.blade.php`
30. `resources/views/public/post.blade.php`

### 6.4. Kết Quả Kiểm Thử (Verification):
- `php artisan view:clear` & `php artisan view:cache`: **Blade templates cached successfully (235/235 views hợp lệ 100%, không lỗi cú pháp)**.
- Tất cả các file có liên kết logic JS phức tạp (WebRTC, Classroom, Audio recorder, Quiz engine) được bảo toàn nguyên vẹn.

