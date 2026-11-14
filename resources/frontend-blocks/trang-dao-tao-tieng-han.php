<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 11,
  'slug' => 'trang-dao-tao-tieng-han',
  'name' => 'Trang đào tạo tiếng hàn',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:31:29',
  'config' => 
  array (
    '_fields' => 
    array (
    ),
    'html' => '  <main id="noi-dung-chinh">
    <header class="kr-training-hero">
      <div class="kr-training-hero__bg" aria-hidden="true"></div>
      <div class="container relative py-4 lg:py-5">
        <nav class="page-about-breadcrumb page-about-breadcrumb--pill mb-3 lg:mb-4" aria-label="Breadcrumb">
          <ol class="page-about-breadcrumb__list">
            <li class="page-about-breadcrumb__step">
              <a href="/" class="page-about-breadcrumb__link">
                <i class="bi bi-house-door-fill page-about-breadcrumb__home-ico" aria-hidden="true"></i>
                Trang chủ
              </a>
            </li>
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">Đào tạo tiếng Hàn</span>
            </li>
          </ol>
        </nav>

        <div class="flex flex-wrap gap-4 xl:gap-5 items-center">
          <div class="w-full lg:w-7/12">
            <p class="kr-training-hero__kicker">
              <span class="kr-training-hero__kicker-icon" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></span>
              Học tại Việt Nam · Chuẩn bị cho du học &amp; chứng chỉ
            </p>
            <div class="title-line mb-3 kr-training-hero__line"></div>
            <h1 class="section-title kr-training-hero__title mb-3">Đào tạo tiếng Hàn chuyên nghiệp</h1>
            <p class="kr-training-hero__ko" lang="ko">한국어 교육 · Lộ trình rõ ràng từ nền tảng đến TOPIK &amp; phỏng vấn visa.</p>
            <p class="kr-training-hero__lead mb-4 lg:mb-0">
              SUNNY tổ chức các khóa tiếng Hàn bài bản tại Việt Nam, giúp bạn làm quen Hangul, phát triển giao tiếp, luyện thi <strong>TOPIK</strong> và chuẩn bị tâm thế trước khi sang Hàn học tại <span lang="ko">어학당</span> hoặc bậc đại học — đồng bộ với hồ sơ du học do tư vấn viên SUNNY hướng dẫn.
            </p>
            <div class="flex flex-wrap gap-2 mt-3 lg:mt-4">
              <a href="#khoa-hoc" class="btn btn-sunny btn-sunny-pulse text-white">Xem các khóa học</a>
              <a href="#lop-dinh-huong-topik4" class="btn btn-outline-dark">Lớp định hướng TOPIK 4 · 8 tháng</a>
              <a href="he-tieng-d4.php" class="btn btn-outline-dark">Du học tiếng tại Hàn (D4-1)</a>
            </div>
          </div>
          <div class="w-full lg:w-5/12">
            <div class="kr-training-hero__panel">
              <p class="kr-training-hero__panel-title">Tại sao học trước tại SUNNY?</p>
              <ul class="kr-training-hero__checks list-none p-0 mb-0">
                <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Giáo trình chia cấp độ, phù hợp người mới và người đã có nền</span></li>
                <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Ôn thi TOPIK &amp; mẹo làm bài theo đề thật</span></li>
                <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Luyện phản xạ phỏng vấn visa &amp; giao tiếp bản địa</span></li>
                <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Kết nối liền mạch với tư vấn du học &amp; hồ sơ D4 / D2</span></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="kr-training-section kr-training-section--light py-5" aria-labelledby="loi-the-heading">
      <div class="container">
        <div class="text-center mb-4 lg:mb-5">
          <div class="title-line mx-auto mb-3"></div>
          <h2 id="loi-the-heading" class="section-title">Lợi thế khi học cùng SUNNY</h2>
          <p class="kr-training-section__lead mx-auto mb-0">Một địa chỉ cho cả tiếng Hàn và định hướng du học — giảm rủi ro “học lệch” so với mục tiêu visa và trường.</p>
        </div>
        <div class="flex flex-wrap gap-4">
          <div class="w-full md:w-4/12">
            <article class="kr-training-feature h-full">
              <span class="kr-training-feature__icon" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
              <h3 class="kr-training-feature__title">Lộ trình cá nhân hóa</h3>
              <p class="kr-training-feature__text mb-0">Xác định mốc Hangul — giao tiếp — TOPIK phù hợp thời điểm dự định nộp hồ sơ và kỳ nhập học.</p>
            </article>
          </div>
          <div class="w-full md:w-4/12">
            <article class="kr-training-feature h-full">
              <span class="kr-training-feature__icon" aria-hidden="true"><i class="bi bi-people"></i></span>
              <h3 class="kr-training-feature__title">Lớp vừa sức, theo sát</h3>
              <p class="kr-training-feature__text mb-0">Ưu tiên tương tác và sửa lỗi phát âm — tránh lớp đông, mất động lực khi mới bắt đầu.</p>
            </article>
          </div>
          <div class="w-full md:w-4/12">
            <article class="kr-training-feature h-full">
              <span class="kr-training-feature__icon" aria-hidden="true"><i class="bi bi-link-45deg"></i></span>
              <h3 class="kr-training-feature__title">Liền mạch du học</h3>
              <p class="kr-training-feature__text mb-0">Đội tư vấn nắm lộ trình tiếng của bạn để khớp với chọn trường, visa và thời gian ôn thi.</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section id="hinh-thuc-hoc" class="kr-training-section kr-training-section--soft py-5 border-t border-opacity-10" aria-labelledby="hinh-thuc-heading">
      <div class="container">
        <div class="text-center mb-4 lg:mb-5">
          <div class="title-line mx-auto mb-3"></div>
          <h2 id="hinh-thuc-heading" class="section-title">Học online &amp; offline cùng SUNNY</h2>
          <p class="kr-training-section__lead mx-auto mb-0">
            Trung tâm triển khai song song <strong>lớp trực tuyến</strong> và <strong>lớp tại lớp</strong> — cùng đội ngũ giảng dạy và lộ trình chuẩn, để bạn chọn hình thức phù hợp địa lý, thời gian và thói quen học.
          </p>
        </div>
        <div class="flex flex-wrap gap-4 items-stretch">
          <div class="w-full lg:w-6/12">
            <article class="kr-training-mode kr-training-mode--online h-full" aria-labelledby="mode-online-title">
              <div class="kr-training-mode__head">
                <span class="kr-training-mode__icon" aria-hidden="true"><i class="bi bi-laptop"></i></span>
                <span class="kr-training-mode__badge">Trực tuyến</span>
              </div>
              <h3 id="mode-online-title" class="kr-training-mode__title">Lớp online</h3>
              <p class="kr-training-mode__lead mb-3">
                Học live qua nền tảng ổn định, tương tác hỏi — đáp trực tiếp với giảng viên; phù hợp bạn ở xa văn phòng, đi công tác hoặc muốn linh hoạt giờ học.
              </p>
              <ul class="kr-training-mode__list list-none p-0 mb-0">
                <li><i class="bi bi-check-lg" aria-hidden="true"></i>Tham gia mọi nơi chỉ cần thiết bị &amp; kết nối mạng</li>
                <li><i class="bi bi-check-lg" aria-hidden="true"></i>Tài liệu &amp; bài tập số đồng bộ sau mỗi buổi</li>
                <li><i class="bi bi-check-lg" aria-hidden="true"></i>Giữ nhịp lớp với nhắc lịch &amp; hỗ trợ kỹ thuật trước giờ học</li>
              </ul>
            </article>
          </div>
          <div class="w-full lg:w-6/12">
            <article class="kr-training-mode kr-training-mode--offline h-full" aria-labelledby="mode-offline-title">
              <div class="kr-training-mode__head">
                <span class="kr-training-mode__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                <span class="kr-training-mode__badge">Tại lớp</span>
              </div>
              <h3 id="mode-offline-title" class="kr-training-mode__title">Lớp offline</h3>
              <p class="kr-training-mode__lead mb-3">
                Học trực tiếp tại cơ sở SUNNY — sửa phát âm, ngữ điệu ngay tại chỗ, không gian tập trung và gắn kết cùng lớp.
              </p>
              <ul class="kr-training-mode__list list-none p-0 mb-0">
                <li><i class="bi bi-check-lg" aria-hidden="true"></i>Quan sát cử động miệng — hơi thở khi luyện nói</li>
                <li><i class="bi bi-check-lg" aria-hidden="true"></i>Thảo luận nhóm &amp; hoạt động lớp học trực diện</li>
                <li><i class="bi bi-check-lg" aria-hidden="true"></i>Dễ trao đổi với tư vấn viên sau buổi học khi cần</li>
              </ul>
            </article>
          </div>
        </div>
        <p class="kr-training-mode__foot text-center text-sm mb-0 mt-4 pt-1">
          Có thể <strong>kết hợp hoặc chuyển đổi</strong> hình thức theo từng giai đoạn lộ trình — thống nhất cùng tư vấn viên khi đăng ký hoặc giữa các khóa.
        </p>
      </div>
    </section>

    <section id="khoa-hoc" class="kr-training-tracks py-5 border-t border-opacity-10" aria-labelledby="khoa-hoc-heading">
      <div class="container">
        <header class="kr-training-tracks__head text-center mb-4 lg:mb-5">
          <div class="title-line mx-auto mb-3"></div>
          <h2 id="khoa-hoc-heading" class="section-title">Các hướng đào tạo</h2>
          <p class="kr-training-tracks__lead mx-auto mb-0">
            Chọn khóa theo mục tiêu — có thể <strong>kết hợp nhiều giai đoạn</strong> trong cùng lộ trình cùng tư vấn viên SUNNY.
          </p>
        </header>

        <div class="kr-training-tracks__grid" role="list">
          <article class="kr-training-path kr-training-path--hangul" role="listitem">
            <div class="kr-training-path__meta">
              <span class="kr-training-path__step" aria-hidden="true">01</span>
              <span class="kr-training-path__icon" aria-hidden="true"><i class="bi bi-fonts"></i></span>
            </div>
            <span class="kr-training-path__badge">Nền tảng</span>
            <h3 class="kr-training-path__title">Phát âm</h3>
            <p class="kr-training-path__text">
              Làm quen bảng chữ cái, ghép âm — vần, đọc đúng ngay từ đầu; từ vựng &amp; mẫu câu thiết yếu hằng ngày.
            </p>
          </article>

          <article class="kr-training-path kr-training-path--talk" role="listitem">
            <div class="kr-training-path__meta">
              <span class="kr-training-path__step" aria-hidden="true">02</span>
              <span class="kr-training-path__icon" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
            </div>
            <span class="kr-training-path__badge">Ứng dụng</span>
            <h3 class="kr-training-path__title">Giao tiếp thực tế</h3>
            <p class="kr-training-path__text">
              Nghe — nói theo tình huống sinh hoạt, lớp học và chuẩn bị sống tại Hàn; củng cố phản xạ tự tin.
            </p>
          </article>

          <article class="kr-training-path kr-training-path--topik" role="listitem">
            <div class="kr-training-path__meta">
              <span class="kr-training-path__step" aria-hidden="true">03</span>
              <span class="kr-training-path__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
            </div>
            <span class="kr-training-path__badge">Chứng chỉ</span>
            <h3 class="kr-training-path__title">Luyện thi <span lang="ko">TOPIK</span></h3>
            <p class="kr-training-path__text">
              Ôn ngữ pháp — từ vựng, chiến lược làm đề nghe — đọc — viết; phù hợp TOPIK I &amp; II tùy ngưỡng.
            </p>
            <div class="kr-training-path__cta">
              <a href="#lop-dinh-huong-topik4" class="kr-training-path__link">
                <span>Lớp định hướng TOPIK 4 · 8 tháng</span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </article>

          <article class="kr-training-path kr-training-path--study" role="listitem">
            <div class="kr-training-path__meta">
              <span class="kr-training-path__step" aria-hidden="true">04</span>
              <span class="kr-training-path__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
            </div>
            <span class="kr-training-path__badge">Du học</span>
            <h3 class="kr-training-path__title">Học thuật &amp; phỏng vấn</h3>
            <p class="kr-training-path__text mb-0">
              Tiếng phục vụ bài luận, thư giới thiệu, phỏng vấn visa &amp; phỏng vấn nhập học — gắn với checklist SUNNY.
            </p>
          </article>
        </div>
      </div>
    </section>

    <section id="lop-dinh-huong-topik4" class="kr-training-flagship py-5 border-t border-opacity-10" aria-labelledby="topik4-heading">
      <div class="container">
        <div class="kr-training-flagship__card">
          <div class="flex flex-wrap gap-4 xl:gap-5 items-start">
            <div class="w-full lg:w-6/12">
              <p class="kr-training-flagship__badge">
                <i class="bi bi-award-fill" aria-hidden="true"></i>
                Chương trình trọng điểm
              </p>
              <h2 id="topik4-heading" class="kr-training-flagship__title section-title mb-3">
                <span lang="ko">TOPIK</span> II Level 4 — Lộ trình định hướng <strong>8 tháng</strong> tập trung cao độ
              </h2>
              <p class="kr-training-flagship__ko" lang="ko">8개월 집중 로드맵 · TOPIK II 4급 역량 완성</p>
              <p class="kr-training-flagship__lead mb-4">
                Dành cho học viên đã định hướng <strong>du học Hàn Quốc</strong> hoặc cần <strong>chứng chỉ <span lang="ko">TOPIK</span></strong> trong khung thời gian cố định.
                Chương trình gói gọn một hành trình có điểm tựa: <strong>đánh giá &amp; định vị đầu vào</strong>, <strong>giảng — luyện theo mốc năng lực</strong>,
                <strong>luyện đề — chấm bài — tinh chỉnh phần viết</strong> và <strong>báo cáo tiến độ định kỳ</strong> để mỗi tháng đều đo được lượng tăng trưởng thực tế.
                SUNNY <strong>cam kết đồng hành xuyên suốt 8 tháng</strong> hướng tới ngưỡng Level 4; học viên <strong>cam kết</strong> duy trì tham gia đầy đủ buổi học và hoàn thành bài tập theo yêu cầu giảng viên — <strong>hai phía cùng chuẩn, cùng mục tiêu, trách nhiệm rõ ràng</strong>.
              </p>
              <ul class="kr-training-flagship__checks list-none p-0 mb-4">
                <li>
                  <i class="bi bi-check2-circle" aria-hidden="true"></i>
                  <span><strong>Baseline rõ ràng:</strong> đánh giá năng lực &amp; phân nhóm trước khi vào lớp — tránh học trùng hoặc nhảy cóc kiến thức.</span>
                </li>
                <li>
                  <i class="bi bi-check2-circle" aria-hidden="true"></i>
                  <span><strong>Nội dung bám đề:</strong> ngữ pháp, từ vựng, đọc — nghe — viết thiết kế theo cấu trúc <span lang="ko">TOPIK</span> II.</span>
                </li>
                <li>
                  <i class="bi bi-check2-circle" aria-hidden="true"></i>
                  <span><strong>Luyện thi có độ sâu:</strong> mock test định kỳ, chấm chi tiết bài viết, ôn trọng tâm trước kỳ đăng ký thi.</span>
                </li>
                <li>
                  <i class="bi bi-check2-circle" aria-hidden="true"></i>
                  <span><strong>Đồng bộ du học:</strong> tư vấn viên SUNNY nắm lộ trình tiếng để khớp visa &amp; kế hoạch nhập học khi bạn cần.</span>
                </li>
              </ul>
              <div class="flex flex-wrap gap-2">
                <a href="/#dang-ky" class="btn btn-sunny btn-sunny-pulse text-white">Đăng ký tư vấn lớp này</a>
                <a href="tel:0981698292" class="btn btn-outline-dark"><i class="bi bi-telephone me-1" aria-hidden="true"></i>0981 698 292</a>
              </div>
            </div>
            <div class="w-full lg:w-6/12">
              <p class="kr-training-flagship__timeline-kicker">Lộ trình gợi ý 8 tháng</p>
              <div class="kr-training-flagship__timeline" role="list" aria-label="Các giai đoạn lộ trình 8 tháng">
                <article class="kr-training-flagship__phase" role="listitem">
                  <span class="kr-training-flagship__phase-mo">Tháng 1 — 2</span>
                  <h3 class="kr-training-flagship__phase-title">Khởi động &amp; nền tảng TOPIK II</h3>
                  <p class="kr-training-flagship__phase-text mb-0">
                    Ôn tập — mở rộng ngữ pháp trọng tâm, vốn từ học thuật; đọc hiểu văn bản dài; làm quen dạng bài nghe đối thoại &amp; thông báo.
                  </p>
                </article>
                <article class="kr-training-flagship__phase" role="listitem">
                  <span class="kr-training-flagship__phase-mo">Tháng 3 — 4</span>
                  <h3 class="kr-training-flagship__phase-title">Tăng tốc đọc — nghe</h3>
                  <p class="kr-training-flagship__phase-text mb-0">
                    Luyện chiến lược làm bài trong thời gian thi; chuyên sâu đoạn báo — quảng cáo — bài phỏng vấn; chấm lỗi thường gặp.
                  </p>
                </article>
                <article class="kr-training-flagship__phase" role="listitem">
                  <span class="kr-training-flagship__phase-mo">Tháng 5 — 6</span>
                  <h3 class="kr-training-flagship__phase-title">Viết — nói &amp; hoàn thiện bài luận ngắn</h3>
                  <p class="kr-training-flagship__phase-text mb-0">
                    Khung bài viết mô tả biểu đồ — ý kiến; luyện viết có giới hạn từ; sửa bài cá nhân hóa theo từng học viên.
                  </p>
                </article>
                <article class="kr-training-flagship__phase" role="listitem">
                  <span class="kr-training-flagship__phase-mo">Tháng 7 — 8</span>
                  <h3 class="kr-training-flagship__phase-title">Tổng ôn &amp; sát hạch</h3>
                  <p class="kr-training-flagship__phase-text mb-0">
                    Full mock test, phân tích điểm yếu — điểm mạnh; lịch ôn nước rút; hướng dẫn đăng ký kỳ thi phù hợp.
                  </p>
                </article>
              </div>
            </div>
          </div>
          <p class="kr-training-flagship__note text-sm mb-0 mt-4 pt-3 border-t">
            Điểm số cụ thể tại kỳ thi phụ thuộc đề, lịch thi và nỗ lực cá nhân. Chương trình được thiết kế hướng đến chuẩn đầu ra
            <strong lang="ko">TOPIK</strong> II Level 4 trong khung 8 tháng đã thống nhất giữa trung tâm và học viên.
          </p>
        </div>
      </div>
    </section>

    <section class="kr-training-section kr-training-section--soft py-5" aria-labelledby="phuong-phap-heading">
      <div class="container">
        <div class="flex flex-wrap gap-4 lg:gap-5 items-start">
          <div class="w-full lg:w-5/12">
            <div class="title-line mb-3"></div>
            <h2 id="phuong-phap-heading" class="section-title">Phương pháp &amp; cam kết chất lượng</h2>
            <p class="text-black mb-0">Kết hợp giảng dạy có cấu trúc, bài tập về nhà có kiểm tra và mô phỏng tình huống — bạn luôn biết mình đang ở bước nào và cần cải thiện gì trước khi sang Hàn.</p>
          </div>
          <div class="w-full lg:w-7/12">
            <ul class="kr-training-pill-list list-none p-0 mb-0">
              <li class="kr-training-pill"><i class="bi bi-volume-up-fill" aria-hidden="true"></i><span>Nhấn mạnh phát âm &amp; ngữ điệu chuẩn từ sớm</span></li>
              <li class="kr-training-pill"><i class="bi bi-journal-text" aria-hidden="true"></i><span>Theo dõi tiến độ qua bài kiểm tra định kỳ &amp; nhận xét cá nhân</span></li>
              <li class="kr-training-pill"><i class="bi bi-translate" aria-hidden="true"></i><span>Tài liệu song ngữ hỗ trợ, dần chuyển sang môi trường tiếng Hàn thuần</span></li>
              <li class="kr-training-pill"><i class="bi bi-camera-video" aria-hidden="true"></i><span>Hỗ trợ luyện nói &amp; mock phỏng vấn khi bạn chuẩn bị visa / nhập học</span></li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="kr-training-bridge py-5">
      <div class="container">
        <div class="kr-training-bridge__inner text-center lg:text-left">
          <div class="flex flex-wrap gap-4 items-center">
            <div class="w-full lg:w-8/12">
              <p class="kr-training-bridge__kicker mb-2">Bước tiếp theo</p>
              <h2 class="kr-training-bridge__title mb-2">Từ lớp tiếng tại Việt Nam đến du học tiếng tại Hàn</h2>
              <p class="kr-training-bridge__text mb-0">
                Khi đã đủ nền hoặc muốn học toàn thời gian tại Hàn, SUNNY hỗ trợ chuyển tiếp sang chương trình <strong>visa D4-1</strong> (viện ngôn ngữ trường đại học). Thông tin visa tổng hợp vẫn có trên <a href="/#visa" class="kr-training-bridge__link">trang chủ — mục Visa</a>.
              </p>
            </div>
            <div class="w-full lg:w-4/12 lg:text-right">
              <a href="he-tieng-d4.php" class="btn btn-sunny btn-sunny-pulse text-white btn-lg w-full lg:w-auto">Tìm hiểu hệ tiếng D4-1</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-white border-t border-opacity-10">
      <div class="container text-center">
        <h2 class="h5 font-bold text-bs-dark mb-2">Đăng ký tư vấn lộ trình tiếng Hàn</h2>
        <p class="text-black text-sm mb-4 mx-auto kr-training-cta__lead">Cho biết mục tiêu TOPIK / thời điểm dự định du học — SUNNY đề xuất khóa và lịch phù hợp.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
          <a href="tel:0981698292" class="btn btn-outline-dark btn-lg"><i class="bi bi-telephone me-2" aria-hidden="true"></i>0981 698 292</a>
          <a href="/#dang-ky" class="btn btn-sunny btn-sunny-pulse text-white btn-lg">Đăng ký tư vấn</a>
        </div>
      </div>
    </section>
  </main>
',
  ),
);
