<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 7,
  'slug' => 'trang-d4-1',
  'name' => 'Trang D4-1',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:23:44',
  'config' => 
  array (
    '_fields' => 
    array (
    ),
    'html' => '  <main id="noi-dung-chinh">
    <header class="program-d4-hero">
      <div class="program-d4-hero__bg" aria-hidden="true"></div>
      <div class="container relative py-4 lg:py-5">
        <nav class="page-about-breadcrumb page-about-breadcrumb--pill mb-3 lg:mb-4" aria-label="Breadcrumb">
          <ol class="page-about-breadcrumb__list">
            <li class="page-about-breadcrumb__step">
              <a href="/" class="page-about-breadcrumb__link">
                <i class="bi bi-house-door-fill page-about-breadcrumb__home-ico" aria-hidden="true"></i>
                Trang chủ
              </a>
            </li>
            <li class="page-about-breadcrumb__step">
              <a href="/#he-du-hoc" class="page-about-breadcrumb__link">Chương trình du học</a>
            </li>
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">Hệ tiếng (D4-1)</span>
            </li>
          </ol>
        </nav>

        <div class="flex flex-wrap gap-4 xl:gap-5 items-stretch">
          <div class="w-full lg:w-6/12 xl:w-7/12 flex flex-col">
            <p class="program-d4-hero__eyebrow">
              <span class="program-d4-hero__eyebrow-dot" aria-hidden="true"></span>
              Chương trình du học · Visa ngôn ngữ
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title mb-0">Du học hệ tiếng Hàn (Visa D4-1)</h1>

            <div class="program-d4-hero__lang-card">
              <p class="program-d4-hero__lang-vi">
                Học tiếng Hàn 1–2 năm tại Hàn Quốc — nền tảng để lên đại học hoặc làm việc với tiếng Hàn.
              </p>
            </div>

            <p class="program-d4-hero__lead text-black">
              Du học <strong>D4-1</strong> là loại hình phổ biến nhất, giúp bạn hướng tới <strong>TOPIK 2–3</strong> và chuyển tiếp lên đại học hoặc sử dụng tiếng Hàn trong công việc.
            </p>

            <div class="flex flex-wrap gap-2 mt-3 lg:mt-4">
              <a href="#gioi-thieu-d4" class="btn btn-sunny btn-sunny-pulse text-white">Tìm hiểu chương trình</a>
              <a href="#dang-ky" class="btn btn-outline-dark">Đăng ký tư vấn</a>
            </div>
          </div>

          <div class="w-full lg:w-6/12 xl:w-5/12 flex">
            <div class="program-d4-hero__stats-card w-full">
              <p class="program-d4-hero__stats-kicker">D4-1 trong một cái nhìn</p>
              <p class="program-d4-hero__stats-title">Thông tin tóm tắt</p>
              <div class="program-d4-stats" role="list">
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">D4-1</span>
                    <span class="program-d4-stat__label">Loại visa</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">1–2 năm</span>
                    <span class="program-d4-stat__label">Thời gian học</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">10.000 USD</span>
                    <span class="program-d4-stat__label">Sổ tiết kiệm</span>
                    <span class="program-d4-stat__hint">Tối thiểu 6 tháng (tham khảo điều kiện tài chính)</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">TOPIK 2–3</span>
                    <span class="program-d4-stat__label">Kết quả mong đợi</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <nav class="uni-detail-toc border-b bg-white" aria-label="Mục lục trang — Hệ tiếng D4-1">
      <div class="container py-2 py-lg-3">
        <ul class="uni-detail-toc__list list-none p-0 mb-0 flex flex-wrap gap-2 lg:gap-3">
          <li><a href="#gioi-thieu-d4">Giới thiệu</a></li>
          <li><a href="#doi-tuong">Đối tượng</a></li>
          <li><a href="#dieu-kien">Điều kiện</a></li>
          <li><a href="#chuong-trinh">Chương trình học</a></li>
          <li><a href="#chi-phi">Chi phí</a></li>
          <li><a href="#truong">Trường đào tạo</a></li>
          <li><a href="#sau-tot-nghiep">Sau tốt nghiệp</a></li>
          <li><a href="#quy-trinh">Quy trình hồ sơ</a></li>
          <li><a href="#dich-vu">Dịch vụ SUNNY</a></li>
        </ul>
      </div>
    </nav>

    <article class="py-5 bg-white news-article-page program-d4-page">
      <div class="container">
        <div class="news-article text-black program-d4-article">
              <section id="gioi-thieu-d4" class="program-d4-section program-d4-intro-section" aria-labelledby="gioi-thieu-d4-heading">
                <div class="program-d4-intro-panel">
                  <header class="program-d4-intro-head">
                    <div class="title-line program-d4-intro-head__line"></div>
                    <h2 id="gioi-thieu-d4-heading" class="program-d4-intro-title">Giới thiệu hệ du học tiếng D4-1</h2>
                    <p class="program-d4-intro-deck">
                      Chương trình toàn thời gian tại <strong>viện ngôn ngữ</strong> trường đại học — nền tảng tiếng Hàn bài bản trước khi bạn chuyển tiếp ĐH hoặc đi làm.
                    </p>
                  </header>

                  <div class="program-d4-intro-prose">
                    <p class="program-d4-intro-lead">
                      <strong>Visa D4-1</strong> là diện visa dành cho học viên quốc tế theo học chương trình tiếng Hàn tại Viện / Trung tâm ngôn ngữ trực thuộc các trường đại học Hàn Quốc (Korean Language Institute — <span lang="ko">어학당</span>, gọi tắt KLI). Hình thức đào tạo toàn thời gian, phát triển đồng đều bốn kỹ năng nghe — nói — đọc — viết; khi theo đúng lộ trình và kết quả học tập ổn định, học viên có thể hướng tới <strong>TOPIK cấp 2 đến cấp 3</strong> — nền giao tiếp và học thuật cơ bản; nhiều bạn tiếp tục nâng cấp để đáp ứng ngưỡng xét tuyển đại học hoặc nghề nghiệp tùy mục tiêu.
                    </p>
                    <p>
                      Đây là một trong những lộ trình du học ngôn ngữ được lựa chọn phổ biến tại Hàn Quốc, nằm trong khung quản lý của cơ quan chức năng và được triển khai bài bản tại các cơ sở đào tạo được công nhận. Học tập trực tiếp trong môi trường sử dụng tiếng Hàn hằng ngày giúp rút ngắn thời gian hình thành phản xạ ngôn ngữ và thói quen giao tiếp tự nhiên so với chỉ học trong nước. Chứng chỉ <strong>TOPIK</strong> đạt được có giá trị tham chiếu rộng rãi trong xét tuyển đầu vào và tuyển dụng tại Hàn Quốc, cũng như trong các lĩnh vực sử dụng tiếng Hàn tại Việt Nam và khu vực.
                    </p>
                    <p>
                      Chương trình được thiết kế theo cấp độ từ sơ cấp đến nâng cao; mỗi kỳ học thường kéo dài khoảng mười tuần và kết thúc bằng đánh giá năng lực. Quy mô lớp học được kiểm soát để giảng viên theo dõi sát tiến độ từng học viên. Bên cạnh giờ lên lớp, nhiều trường tổ chức hoạt động ngoại khóa, giao lưu với sinh viên Hàn Quốc và trải nghiệm văn hóa — giúp củng cố ngôn ngữ trong ngữ cảnh thực tế và làm quen môi trường sống tại Hàn.
                    </p>
                    <p class="program-d4-intro-prose__foot">
                      Sau khi hoàn thành khóa tiếng, học viên có thể <strong>chuyển tiếp sang visa D2</strong> để theo học bậc đại học hoặc cao đẳng tại Hàn Quốc khi đáp ứng điều kiện tuyển sinh và quy định visa; hoặc trở về Việt Nam phát triển sự nghiệp với năng lực tiếng Hàn và chứng chỉ đã được kiểm chứng qua quá trình học tập và thi cử chính thức.
                    </p>
                  </div>

                  <div class="program-d4-intro-highlights" aria-label="Tóm tắt nhanh chương trình D4-1">
                    <p class="program-d4-intro-highlights__label">Tóm tắt chương trình</p>
                    <ul class="program-d4-highlight-grid list-none p-0 mb-0" role="list">
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Diện visa</span>
                          <strong class="program-d4-highlight__title">Visa D4-1</strong>
                          <p class="program-d4-highlight__text">Du học ngắn hạn · Đào tạo tiếng (language training)</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Thời lượng</span>
                          <strong class="program-d4-highlight__title">1–2 năm</strong>
                          <p class="program-d4-highlight__text">Thường 3–4 kỳ/năm, ~10 tuần/kỳ</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Mục tiêu TOPIK</span>
                          <strong class="program-d4-highlight__title">TOPIK 2–3</strong>
                          <p class="program-d4-highlight__text">Mục tiêu tham chiếu khi học đều, đúng lộ trình</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight program-d4-highlight--accent" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-arrow-right-circle"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Hướng đi sau khóa</span>
                          <strong class="program-d4-highlight__title">Chuyển tiếp</strong>
                          <p class="program-d4-highlight__text">Visa D2 (ĐH / CĐ) hoặc phát triển nghề nghiệp tại VN</p>
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>
              </section>

              <section id="doi-tuong" class="program-d4-section program-d4-audience-section" aria-labelledby="doi-tuong-heading">
                <header class="program-d4-audience__head">
                  <div class="title-line mb-3"></div>
                  <h2 id="doi-tuong-heading" class="program-d4-audience__title">Đối tượng phù hợp</h2>
                  <div class="program-d4-audience__intro">
                    <p class="program-d4-audience__intro-text mb-0">
                      Visa <strong>D4-1</strong> thường được lựa chọn khi bạn cần một chương trình tiếng Hàn <strong>có lộ trình rõ ràng</strong>, học tập toàn thời gian tại Hàn Quốc và được công nhận trong hệ thống các viện ngôn ngữ (KLI) của trường đại học. Dưới đây là các nhóm đối tượng điển hình — tùy từng trường và kỳ tuyển sinh, yêu cầu cụ thể có thể khác nhau; SUNNY hỗ trợ rà soát hồ sơ và định hướng phù hợp.
                    </p>
                  </div>
                </header>

                <div class="program-d4-audience__grid" role="list">
                  <article class="program-d4-audience-card program-d4-audience-card--thpt" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-backpack"></i></span>
                    <h3 class="program-d4-audience-card__title">Người học sau tốt nghiệp THPT (hoặc tương đương)</h3>
                    <p class="program-d4-audience-card__text">
                      Phù hợp với học sinh vừa hoàn thành THPT hoặc bằng cấp tương đương, mong muốn <strong>học tiếng Hàn bài bản trong môi trường bản ngữ</strong>, sau đó có thể chuyển tiếp xét tuyển vào đại học tại Hàn Quốc hoặc tích lũy năng lực ngôn ngữ làm nền cho việc làm có sử dụng tiếng Hàn.
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--uni" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
                    <h3 class="program-d4-audience-card__title">Người chuẩn bị bậc đại học hoặc sau đại học tại Hàn Quốc</h3>
                    <p class="program-d4-audience-card__text">
                      Dành cho bạn đã xác định lộ trình cử nhân, thạc sĩ hoặc tiến sĩ tại Hàn và cần <strong>đáp ứng ngưỡng TOPIK cùng nền học thuật bằng tiếng Hàn</strong> theo yêu cầu từng trường — kỳ tiếng D4-1 thường đóng vai trò là giai đoạn chuẩn bị trực tiếp trước khi nộp hồ sơ xét tuyển chính thức.
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--career" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                    <h3 class="program-d4-audience-card__title">Người định hướng nghề nghiệp sử dụng tiếng Hàn</h3>
                    <p class="program-d4-audience-card__text">
                      Phù hợp nếu bạn hướng tới các vị trí như <strong>biên — phiên dịch</strong>, làm việc trong <strong>doanh nghiệp Hàn Quốc</strong> hoặc môi trường đòi hỏi tiếng Hàn thương mại, <strong>giảng dạy tiếng Hàn</strong>, hoặc các vai trò phối hợp chuyên môn thường xuyên với đối tác Hàn — cần chứng chỉ và khả năng giao tiếp ổn định, được đào tạo có hệ thống tại Hàn Quốc.
                    </p>
                  </article>
                </div>

                <div class="program-d4-audience__note">
                  <i class="bi bi-info-circle-fill program-d4-audience__note-ico" aria-hidden="true"></i>
                  <p class="program-d4-audience__note-text mb-0">
                    <strong>Lưu ý:</strong> Một số trường giới hạn thời gian tốt nghiệp THPT (ví dụ trong vòng 2–3 năm) và độ tuổi (thường dưới 25–26); nhiều cơ sở áp dụng tiêu chí linh hoạt hơn tùy hồ sơ. SUNNY đồng hành phân tích năng lực, nguyện vọng và lựa chọn trường — kỳ nhập học phù hợp.
                  </p>
                </div>
              </section>

              <section id="dieu-kien" class="program-d4-section program-d4-requirements-section" aria-labelledby="dieu-kien-heading">
                <header class="program-d4-requirements__head">
                  <div class="title-line mb-3"></div>
                  <h2 id="dieu-kien-heading" class="program-d4-requirements__title">Điều kiện du học hệ tiếng D4-1</h2>
                </header>
                <div class="program-d4-conditions" role="list">
                  <article class="program-d4-condition program-d4-condition--edu" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                      <h3 class="program-d4-condition__title">Học vấn</h3>
                    </div>
                    <p class="program-d4-condition__text">Tốt nghiệp THPT trở lên. Một số trường yêu cầu bảng điểm tối thiểu (ví dụ &gt; 6.0–7.0/10).</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--finance" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                      <h3 class="program-d4-condition__title">Tài chính</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Sổ tiết kiệm tối thiểu <strong>10.000 USD</strong>, gửi <strong>tối thiểu 6 tháng</strong> (hoặc sổ đóng băng tùy trường). Chứng minh thu nhập bảo lãnh (thường từ khoảng 30 triệu/tháng) và cam kết bảo lãnh.
                    </p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--health" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-heart-pulse"></i></span>
                      <h3 class="program-d4-condition__title">Sức khỏe</h3>
                    </div>
                    <p class="program-d4-condition__text">Không mắc các bệnh truyền nhiễm theo quy định (lao phổi, cúm gia cầm, HIV…).</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--interview" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mic"></i></span>
                      <h3 class="program-d4-condition__title">Phỏng vấn</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Trong 6 tháng gần nhất không trượt phỏng vấn visa với Đại sứ quán / Lãnh sự quán Hàn. Nên học tiếng Hàn cơ bản 3–4 tháng tại Việt Nam trước phỏng vấn để tăng tỷ lệ đỗ.
                    </p>
                  </article>
                </div>
              </section>

              <section id="chuong-trinh" class="program-d4-section program-d4-curriculum-section" aria-labelledby="chuong-trinh-heading">
                <header class="program-d4-curriculum__head">
                  <div class="title-line mb-3"></div>
                  <h2 id="chuong-trinh-heading" class="program-d4-curriculum__title">Chương trình học &amp; thời gian</h2>
                </header>

                <div class="program-d4-curriculum__intro-grid">
                  <div class="program-d4-curriculum__panel program-d4-curriculum__panel--terms">
                    <div class="program-d4-curriculum__panel-head">
                      <span class="program-d4-curriculum__panel-icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                      <span class="program-d4-curriculum__panel-kicker">Cấu trúc kỳ học</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      Giáo trình tiếng Hàn tại các viện ngôn ngữ (KLI) thường được tổ chức theo <strong>năm học nhiều kỳ</strong>: phổ biến là <strong>bốn kỳ mỗi năm</strong> (Xuân — Hè — Thu — Đông). Mỗi kỳ có <strong>thời lượng xấp xỉ mười tuần</strong>, kết thúc bằng kiểm tra đánh giá năng lực để xếp lớp hoặc chuyển cấp độ cho kỳ tiếp theo. Số kỳ tối đa và cách đặt tên cấp độ có thể khác nhau giữa các trường — học viên cần tham chiếu quy định của cơ sở đang theo học.
                    </p>
                  </div>
                  <div class="program-d4-curriculum__panel program-d4-curriculum__panel--schedule">
                    <div class="program-d4-curriculum__panel-head">
                      <span class="program-d4-curriculum__panel-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                      <span class="program-d4-curriculum__panel-kicker">Thời lượng &amp; lịch lớp</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      <strong>Thời lượng tiếp xúc trên lớp</strong> thường ở mức <strong>4 tiết/ngày, năm ngày/tuần</strong> (một &quot;tiết&quot; thường 50 phút, tùy trường). Lịch học <strong>sáng hoặc chiều</strong> do nhà trường phân công theo lớp; chi tiết khung giờ và phân nhóm được thông báo khi nhập học hoặc đầu mỗi kỳ.
                    </p>
                  </div>
                </div>

                <h3 class="program-d4-curriculum__levels-heading">Định hướng nội dung theo cấp độ (tham khảo)</h3>
                <ul class="program-d4-levels program-d4-levels--grid list-none p-0" role="list">
                  <li class="program-d4-level program-d4-level--1" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">L1</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Cấp nhập môn (thường tương đương Level 1 — kỳ đầu)</p>
                      <p class="program-d4-level__desc mb-0">Làm quen hệ thống chữ viết, phát âm và mẫu câu cơ bản; bắt đầu rèn bốn kỹ năng nghe — nói — đọc — viết ở mức nền; kiểm tra định kỳ cuối kỳ.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--2" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">L2</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Cấp sơ cấp tiếp theo (ví dụ Level 2)</p>
                      <p class="program-d4-level__desc mb-0">Mở rộng vốn từ, cấu trúc ngữ pháp và tình huống giao tiếp thường gặp; tăng dần độ dài bài nghe — đọc và bài nói — viết có kiểm soát.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--3" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">L3</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Cấp trung gian (ví dụ Level 3)</p>
                      <p class="program-d4-level__desc mb-0">Hướng tới khả năng giao tiếp ổn định hơn trong đời sống và một phần bối cảnh học thuật; chuẩn bị nền tảng cho các cấp độ tiếp theo.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--4" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">L4+</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Cấp trung cao — cao cấp (ví dụ Level 4–6, tùy trường)</p>
                      <p class="program-d4-level__desc mb-0">Nội dung học thuật và biểu đạt phức tạp hơn; định hướng <strong>ôn luyện TOPIK</strong> và kỹ năng viết — trình bày phục vụ xét tuyển đại học hoặc môi trường sử dụng tiếng Hàn chuyên sâu.</p>
                    </div>
                  </li>
                </ul>

                <div class="program-d4-curriculum__foot">
                  <p class="program-d4-curriculum__foot-text mb-0">
                    <strong>Kết quả sau khoảng 1–2 năm học tập liên tục và đạt yêu cầu đánh giá:</strong> nhiều học viên hướng tới <strong>TOPIK cấp 2–3</strong> — thường là mục tiêu nền trong giai đoạn đầu; nộp hồ sơ đại học hoặc ứng tuyển vị trí cần tiếng Hàn chuyên sâu thường yêu cầu <strong>TOPIK cao hơn</strong> tùy trường — ngành.
                    <span class="program-d4-curriculum__foot-muted">Mức đạt được phụ thuộc điểm xuất phát, tốc độ tiếp thu và yêu cầu cụ thể của từng trường — ngành hoặc nhà tuyển dụng.</span>
                  </p>
                </div>
              </section>

              <section id="chi-phi" class="program-d4-section">
                <h2>Chi phí du học hệ tiếng D4-1</h2>
                <p class="text-black mb-3">
                  Bảng dưới đây tổng hợp <strong>các khoản chi phổ biến</strong> khi học chương trình tiếng toàn thời gian (KLI) với visa <strong>D4-1</strong>. Mức tiền <strong>tham khảo theo thực tế nhiều trường đại học</strong>; từng cơ sở và từng kỳ nhập học có biểu phí, hạn đóng học phí và ưu đãi riêng — SUNNY hỗ trợ đối chiếu theo thư mời và hợp đồng bạn nhận được.
                </p>
                <div class="table-responsive program-d4-table-wrap">
                  <table class="table table-bordered align-middle program-d4-table mb-0">
                    <thead>
                      <tr>
                        <th scope="col">Hạng mục</th>
                        <th scope="col">Mức ước tính</th>
                        <th scope="col">Ghi chú</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm học tập</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Một kỳ tiếng thường ~10 tuần; năm học nhiều nơi gồm 4 kỳ.</td>
                      </tr>
                      <tr>
                        <td>Học phí chương trình tiếng / kỳ</td>
                        <td>1.200.000 – 1.800.000 KRW</td>
                        <td>Quy đổi thường <strong>~22 – 36 triệu VNĐ/kỳ</strong> (tùy tỷ giá, trường và cấp độ lớp).</td>
                      </tr>
                      <tr>
                        <td>Phí xét hồ sơ — nhập học (một lần)</td>
                        <td>~80.000 – 200.000+ KRW</td>
                        <td>Không phải trường nào cũng thu; kiểm tra thông báo tuyển sinh từng kỳ.</td>
                      </tr>
                      <tr>
                        <td>Sách, tài liệu &amp; lệ phí lớp / kỳ</td>
                        <td>~150.000 – 450.000 KRW</td>
                        <td>Phụ thuộc bộ sách bắt buộc của KLI; có thể mua combo hoặc từng quyển.</td>
                      </tr>
                      <tr>
                        <td><strong>Tổng học phí gộp / năm (4 kỳ)</strong></td>
                        <td>~4,8 – 7,2 triệu KRW</td>
                        <td>Ước <strong>~90 – 145 triệu VNĐ/năm</strong> phần học (chưa gồm chỗ ở, ăn uống).</td>
                      </tr>
                      <tr>
                        <td>Phí thi TOPIK / lần</td>
                        <td>~40.000 – 80.000 KRW</td>
                        <td>Theo đợt thi và địa điểm; nên dự trù 1–2 lần/năm nếu ôn thi cấp tốc.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm chỗ ở &amp; sinh hoạt</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Seoul và vùng thủ đô thường cao hơn Busan, Daegu, Daejeon…</td>
                      </tr>
                      <tr>
                        <td>Ký túc xá trường / tháng</td>
                        <td>~600.000 – 1.500.000 KRW</td>
                        <td>Chỗ hạn; đăng ký theo đợt sau khi có thư nhập học. Quy đổi ~11 – 28 triệu VNĐ/tháng.</td>
                      </tr>
                      <tr>
                        <td>Thuê trọ (phòng chia sẻ — studio) / tháng</td>
                        <td>~400.000 – 1.200.000+ KRW</td>
                        <td>Thường có <strong>cọc 1–3 tháng</strong>; giá phụ thuộc khu, cách trường và tiện ích.</td>
                      </tr>
                      <tr>
                        <td>Bảo hiểm y tế (sinh viên) / tháng</td>
                        <td>~55.000 – 100.000 KRW</td>
                        <td>Sau nhập cảnh — đăng ký theo quy định; mức đóng có thể điều chỉnh hằng năm.</td>
                      </tr>
                      <tr>
                        <td>Ăn uống / tháng</td>
                        <td>4 – 9 triệu VNĐ</td>
                        <td>Tự nấu tiết kiệm hơn mua sẵn; ăn ngoài nhiều (đặc biệt khu trung tâm) sẽ tăng nhanh.</td>
                      </tr>
                      <tr>
                        <td>Đi lại &amp; liên lạc / tháng</td>
                        <td>~1,5 – 3,5 triệu VNĐ</td>
                        <td>Thẻ giao thông công cộng + gói data điện thoại; ở gần trường thì giảm chi phí đi lại.</td>
                      </tr>
                      <tr>
                        <td>Chi phí tháng đầu (thiết lập chỗ ở)</td>
                        <td>~3 – 12 triệu VNĐ</td>
                        <td>Cọc nhà, chăn ga, đồ dùng cơ bản; tháng đầu thường cao hơn các tháng sau.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm hành chính &amp; di chuyển</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Biến động mạnh theo thời điểm nộp hồ sơ và mùa vé.</td>
                      </tr>
                      <tr>
                        <td>Hồ sơ: dịch thuật, công chứng, chứng minh tài chính</td>
                        <td>~8 – 22 triệu VNĐ</td>
                        <td>Tùy số bản dịch và yêu cầu chứng nhận; không gồm phí lưu ký quỹ ngân hàng (nếu có).</td>
                      </tr>
                      <tr>
                        <td>Lệ phí visa &amp; xét duyệt (D4-1)</td>
                        <td>Theo biểu phí ĐSQ</td>
                        <td>Áp dụng theo <strong>từng thời điểm</strong> và nơi nộp — khi tư vấn SUNNY cập nhật mức hiện hành.</td>
                      </tr>
                      <tr>
                        <td>Vé máy bay khứ hồi</td>
                        <td>~10 – 25 triệu VNĐ</td>
                        <td>Phụ thuộc hãng, đặt sớm và mùa cao điểm (hè, lễ Tết).</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p class="program-cost-disclaimer" role="note">
                  Bảng mang tính tham khảo; tỷ giá và học phí có thể thay đổi. Liên hệ <a href="tel:0981698292">0981 698 292</a> hoặc <a href="lien-he.php">đăng ký tư vấn</a><span class="program-cost-disclaimer__hint"> — nhận bảng chi phí theo đúng trường và kỳ nhập học bạn chọn.</span>
                </p>
              </section>

              <section id="truong" class="program-d4-section">
                <h2>Trường đào tạo tiếng Hàn</h2>
                <p>
                  Phần lớn học viên visa <strong>D4-1</strong> theo học tại <strong>Viện / Trung tâm ngôn ngữ</strong> trực thuộc các trường đại học Hàn Quốc — thường gọi là <strong>Korean Language Institute (KLI)</strong> hoặc <span lang="ko">어학당</span>. Đây là các đơn vị được tổ chức trong khuôn viên hoặc liên kết chặt với nhà trường, có lịch kỳ nhập học, khung chương trình theo cấp độ và quy trình xét duyệt hồ sơ riêng — không đồng nhất giữa các cơ sở.
                </p>
                <p>
                  Khi lựa chọn trường, học viên thường cân nhắc <strong>khu vực</strong> (ví dụ thủ đô so với các thành phố lớn khác — chi phí sinh hoạt và nhịp sống khác nhau), <strong>học phí và lịch kỳ</strong> phù hợp ngân sách — thời gian dự kiến, <strong>quy mô lớp — hỗ trợ sinh viên quốc tế</strong>, cũng như <strong>định hướng sau khóa tiếng</strong> (chuyển tiếp nội bộ lên bậc đại học cùng trường hay chuyển sang cơ sở khác). SUNNY hỗ trợ so sánh các phương án trên cơ sở hồ sơ và mục tiêu cá nhân, không áp đặt một &quot;ranking&quot; cố định cho mọi trường hợp.
                </p>
                <p class="font-semibold text-bs-dark mb-2">Một số cơ sở tham khảo (danh sách rút gọn, minh họa)</p>
                <p class="text-sm text-bs-secondary mb-3">
                  Các tên dưới đây chỉ mang tính <strong>tham khảo</strong> — tình trạng tuyển sinh, học phí và kỳ nhập học thay đổi theo thời điểm. Thông tin chính thức luôn lấy theo thông báo của từng trường và hướng dẫn từ đội ngũ tư vấn SUNNY tại thời điểm bạn nộp hồ sơ.
                </p>
                <ul class="flex flex-wrap list-none p-0 program-d4-uni-grid gap-3 lg:gap-4 mb-0">
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Soongsil University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Dong-A University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Busan</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">HUFS</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul · Yongin</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Korea University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Ajou University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Suwon</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Hansung University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Chonnam National University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Gwangju</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Kyonggi University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul · Suwon</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Keimyung University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Daegu</span>
                      </div>
                    </div>
                  </li>
                </ul>
                <div class="uni-detail-note text-sm mt-4 mb-0">
                  <strong>Gợi ý:</strong> Nếu bạn đã định hướng <strong>chuyển tiếp lên đại học</strong> sau khóa tiếng, nên thảo luận sớm với tư vấn viên về mối liên hệ giữa chương trình KLI và các ngành — campus mục tiêu, để lộ trình visa và hồ sơ sau này nhất quán.
                </div>
                <div class="sunny-explore-universities-cta mt-4">
                  <a href="truong-dai-hoc.php" class="sunny-explore-universities-cta__link">
                    <span class="sunny-explore-universities-cta__icon" aria-hidden="true"><i class="bi bi-buildings"></i></span>
                    <span class="sunny-explore-universities-cta__body">
                      Khám phá thêm trường đại học Hàn Quốc <span class="sunny-explore-universities-cta__accent">trên hệ thống SUNNY</span>
                    </span>
                    <span class="sunny-explore-universities-cta__go" aria-hidden="true"><i class="bi bi-arrow-right-short"></i></span>
                  </a>
                </div>
              </section>

              <section id="sau-tot-nghiep" class="program-d4-section program-d4-after">
                <h2>Sau khi tốt nghiệp khóa tiếng</h2>
                <p class="program-d4-after__lead text-black">
                  Khi đạt <strong>TOPIK</strong> — với khóa tiếng D4-1 nhiều học viên đạt <strong>level 2–3</strong> làm nền; xét tuyển đại học thường cần <strong>level cao hơn</strong> (tùy ngành và trường) — bạn có thể định hướng một trong hai nhóm lộ trình chính dưới đây. Chi tiết điều kiện visa và tuyển sinh cần đối chiếu quy định tại thời điểm nộp hồ sơ.
                </p>
                <div class="flex flex-wrap gap-4 program-d4-after__grid">
                  <div class="w-full lg:w-6/12">
                    <article class="program-d4-outcome-card h-full">
                      <header class="program-d4-outcome-card__head">
                        <span class="program-d4-outcome-card__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                        <h3 class="program-d4-outcome-card__title">Học lên đại học / cao đẳng tại Hàn</h3>
                      </header>
                      <p class="program-d4-outcome-card__text">
                        Chuyển từ visa <strong>D4-1</strong> sang visa <strong>D2</strong> khi đủ điều kiện, nộp hồ sơ xét tuyển vào các trường đại học hoặc cao đẳng. Nhiều cơ sở <strong>ưu tiên</strong> học viên đã hoàn thành khóa tiếng tại chính trường đó.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Lộ trình chuyển đổi <strong>D4-1 → D2</strong> theo quy định hiện hành</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Lợi thế khi đã học KLI tại trường mục tiêu (khi áp dụng)</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span><strong>TOPIK 2–3</strong> làm nền; nhiều ngành cần <strong>cấp cao hơn</strong> (tùy trường)</span></li>
                      </ul>
                    </article>
                  </div>
                  <div class="w-full lg:w-6/12">
                    <article class="program-d4-outcome-card program-d4-outcome-card--vn h-full">
                      <header class="program-d4-outcome-card__head">
                        <span class="program-d4-outcome-card__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                        <h3 class="program-d4-outcome-card__title">Về Việt Nam phát triển nghề nghiệp</h3>
                      </header>
                      <p class="program-d4-outcome-card__text">
                        Tận dụng năng lực tiếng Hàn và chứng chỉ <strong>TOPIK</strong> trong các lĩnh vực có nhu cầu tuyển dụng tại Việt Nam và hợp tác với đối tác Hàn.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Biên — phiên dịch tiếng Hàn</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Môi trường doanh nghiệp Hàn Quốc tại Việt Nam</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Giảng dạy — đào tạo tiếng Hàn</span></li>
                      </ul>
                    </article>
                  </div>
                </div>
              </section>

              <section id="quy-trinh" class="program-d4-section program-d4-process">
                <h2>Quy trình nộp hồ sơ D4-1</h2>
                <p class="program-d4-process__lead mb-4">Tổng thời gian thường <strong>3–5 tháng</strong> (tùy kỳ nhập học và thời gian xử lý của trường — Đại sứ quán / Lãnh sự quán).</p>
                <ol class="program-d4-steps mb-0">
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">1</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Liên hệ SUNNY</strong>
                      <p class="program-d4-steps__desc mb-0">Đăng ký tư vấn, nêu nguyện vọng và thời gian dự kiến.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">2</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Tư vấn chọn trường tiếng Hàn</strong>
                      <p class="program-d4-steps__desc mb-0">Gợi ý trường phù hợp ngân sách, khu vực và kỳ nhập học.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">3</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Chuẩn bị hồ sơ</strong>
                      <p class="program-d4-steps__desc mb-0">Bằng cấp, học bạ, chứng minh tài chính, ảnh, hộ chiếu… theo checklist SUNNY.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">4</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">SUNNY nộp hồ sơ lên trường</strong>
                      <p class="program-d4-steps__desc mb-0">Trường xét duyệt; có thể mời phỏng vấn (tùy trường).</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">5</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Nhận thư mời &amp; nộp học phí</strong>
                      <p class="program-d4-steps__desc mb-0">Sau khi được chấp nhận, nhận thư mời và chuyển học phí theo hướng dẫn.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">6</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Xin visa D4-1 &amp; xuất cảnh</strong>
                      <p class="program-d4-steps__desc mb-0">Nộp hồ sơ tại ĐSQ / LSQ Hàn Quốc → nhận visa → xuất cảnh nhập học.</p>
                    </div>
                  </li>
                </ol>
                <p class="program-d4-process__foot text-sm mt-4 mb-0">
                  Xem thêm: <a href="/#visa">mục Visa</a> trên trang chủ · <a href="tin-visa-d2-ho-so-chuan-bi.php">Bài visa D-2</a> (tham khảo quy trình hồ sơ; yêu cầu D4 khác D2 — SUNNY hướng dẫn theo từng hồ sơ).
                </p>
              </section>

              <section id="dich-vu" class="program-d4-section">
                <h2>Làm hồ sơ du học hệ D4-1 cùng SUNNY</h2>
                <p>
                  Trung tâm du học SUNNY tư vấn và hỗ trợ làm hồ sơ du học tiếng Hàn (visa D4-1) trọn gói, đồng hành từ chọn trường đến khi bạn sang Hàn Quốc nhập học.
                </p>
                <ul class="mb-0">
                  <li><strong>Tư vấn chọn trường:</strong> Gợi ý phù hợp ngân sách, khu vực (Seoul, Busan…), kỳ nhập học.</li>
                  <li><strong>Chuẩn bị hồ sơ:</strong> Checklist, chứng minh tài chính, dịch — công chứng, rà soát trước khi nộp.</li>
                  <li><strong>Nộp hồ sơ lên trường:</strong> SUNNY phối hợp trường đối tác, theo dõi tiến độ và thông báo kết quả.</li>
                  <li><strong>Hỗ trợ visa &amp; xuất cảnh:</strong> Hướng dẫn hồ sơ xin visa D4-1, luyện phỏng vấn và các bước trước khi khởi hành.</li>
                </ul>
              </section>
            </div>

        <nav class="program-d4-page-nav" aria-label="Điều hướng cuối trang">
          <a href="/#he-du-hoc" class="program-d4-page-nav__back">
            <span class="program-d4-page-nav__back-icon" aria-hidden="true"><i class="bi bi-arrow-left"></i></span>
            <span>Về các hệ du học</span>
          </a>
          <div class="program-d4-page-nav__actions">
            <a href="lien-he.php" class="program-d4-page-nav__btn program-d4-page-nav__btn--outline">
              <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
              Liên hệ
            </a>
            <a href="#dang-ky" class="program-d4-page-nav__btn program-d4-page-nav__btn--primary">
              Tư vấn ngay
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </nav>
      </div>
    </article>
  </main>
',
  ),
);
