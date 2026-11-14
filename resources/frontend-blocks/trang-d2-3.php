<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 9,
  'slug' => 'trang-d2-3',
  'name' => 'Trang D2-3',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:28:09',
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
              <span class="page-about-breadcrumb__current">Hệ thạc sĩ (D2-3)</span>
            </li>
          </ol>
        </nav>

        <div class="flex flex-wrap gap-4 xl:gap-5 items-stretch">
          <div class="w-full lg:w-6/12 xl:w-7/12 flex flex-col">
            <p class="program-d4-hero__eyebrow">
              <span class="program-d4-hero__eyebrow-dot" aria-hidden="true"></span>
              Chương trình du học · Visa sau đại học
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title mb-0">Du học hệ thạc sĩ (Visa D2-3)</h1>

            <div class="program-d4-hero__lang-card">
              <p class="program-d4-hero__lang-ko" lang="ko">대학원 석사 유학</p>
              <p class="program-d4-hero__lang-vi">
                Chương trình thạc sĩ (master) tại Hàn Quốc — nghiên cứu, luận văn và chuyên môn sâu phục vụ nghề nghiệp hoặc tiến tới tiến sĩ.
              </p>
            </div>

            <p class="program-d4-hero__lead text-black">
              Visa <strong>D2-3</strong> dành cho học viên quốc tế theo học <strong>bậc thạc sĩ</strong> tại các cơ sở đại học được công nhận; thường cần <strong>bằng đại học</strong>, <strong>đề cương / định hướng nghiên cứu</strong>, liên hệ giáo sư (khi áp dụng) và chứng chỉ ngôn ngữ theo từng chương trình.
            </p>

            <div class="flex flex-wrap gap-2 mt-3 lg:mt-4">
              <a href="#gioi-thieu-d23" class="btn btn-sunny btn-sunny-pulse text-white">Tìm hiểu chương trình</a>
              <a href="#dang-ky" class="btn btn-outline-dark">Đăng ký tư vấn</a>
            </div>
          </div>

          <div class="w-full lg:w-6/12 xl:w-5/12 flex">
            <div class="program-d4-hero__stats-card w-full">
              <p class="program-d4-hero__stats-kicker">D2-3 trong một cái nhìn</p>
              <p class="program-d4-hero__stats-title">Thông tin tóm tắt</p>
              <div class="program-d4-stats" role="list">
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">D2-3</span>
                    <span class="program-d4-stat__label">Loại visa</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">2 năm</span>
                    <span class="program-d4-stat__label">Thạc sĩ (thường)</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">Theo DSQ</span>
                    <span class="program-d4-stat__label">Tài chính</span>
                    <span class="program-d4-stat__hint">Học bổng lab / trợ cấp có thể giảm gánh nặng (tùy lab)</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">TOPIK 4–5+</span>
                    <span class="program-d4-stat__label">Tham khảo</span>
                    <span class="program-d4-stat__hint">Track tiếng Anh: IELTS / TOEFL; ngành đặc thù có ngưỡng riêng</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <nav class="uni-detail-toc border-b bg-white" aria-label="Mục lục trang — Hệ thạc sĩ D2-3">
      <div class="container py-2 py-lg-3">
        <ul class="uni-detail-toc__list list-none p-0 mb-0 flex flex-wrap gap-2 lg:gap-3">
          <li><a href="#gioi-thieu-d23">Giới thiệu</a></li>
          <li><a href="#doi-tuong">Đối tượng</a></li>
          <li><a href="#dieu-kien">Điều kiện</a></li>
          <li><a href="#chuong-trinh">Chương trình học</a></li>
          <li><a href="#chi-phi">Chi phí</a></li>
          <li><a href="#truong">Trường đối tác</a></li>
          <li><a href="#sau-tot-nghiep">Sau tốt nghiệp</a></li>
          <li><a href="#quy-trinh">Quy trình hồ sơ</a></li>
          <li><a href="#dich-vu">Dịch vụ SUNNY</a></li>
        </ul>
      </div>
    </nav>

    <article class="py-5 bg-white news-article-page program-d4-page program-d23-page">
      <div class="container">
        <div class="news-article text-black program-d4-article">
              <section id="gioi-thieu-d23" class="program-d4-section program-d4-intro-section" aria-labelledby="gioi-thieu-d23-heading">
                <div class="program-d4-intro-panel">
                  <header class="program-d4-intro-head">
                    <p class="program-d4-intro-kicker"><span class="program-d4-intro-kicker__dot" aria-hidden="true"></span> Master&apos;s · Visa sau đại học Hàn Quốc</p>
                    <div class="title-line program-d4-intro-head__line"></div>
                    <h2 id="gioi-thieu-d23-heading" class="program-d4-intro-title">Giới thiệu hệ du học thạc sĩ D2-3</h2>
                    <p class="program-d4-intro-deck">
                      Chương trình <strong>thạc sĩ</strong> (master) tại các trường đại học Hàn Quốc — kết hợp học thuật, nghiên cứu hoặc chuyên ngành ứng dụng, mở đường nghề nghiệp chuyên sâu hoặc <strong>tiến sĩ</strong>.
                    </p>
                  </header>

                  <div class="program-d4-intro-prose">
                    <p class="program-d4-intro-lead">
                      <strong>Visa D2-3</strong> là diện visa dành cho học viên quốc tế theo học <strong>bậc thạc sĩ</strong> tại các <strong>đại học — viện đào tạo sau đại học</strong> (graduate school) được công nhận. Hồ sơ tuyển sinh thường nặng về <strong>bằng đại học</strong>, <strong>bảng điểm</strong>, <strong>đề cương / kế hoạch nghiên cứu (research proposal)</strong>, thư giới thiệu và trong nhiều trường hợp cần <strong>liên hệ trước với giáo sư hướng dẫn</strong> hoặc lab. Chứng chỉ <strong>TOPIK</strong> hoặc <strong>IELTS / TOEFL</strong> được yêu cầu tùy chương trình giảng dạy bằng tiếng Hàn hay tiếng Anh.
                    </p>
                    <p>
                      Sau đại học Hàn Quốc có nhiều hình thức: <strong>coursework</strong> (tín chỉ + luận văn), <strong>nghiên cứu</strong> (thesis-heavy), hoặc các chương trình liên kết ngành. <strong>Học bổng GKS</strong>, học bổng trường, trợ cấp RA/TA (khi được lab hoặc khoa phê duyệt) là các kênh giảm chi phí phổ biến — tiêu chí cạnh tranh cao hơn bậc cử nhân. SUNNY giúp bạn <strong>đối chiếu hồ sơ</strong> với yêu cầu thực tế từng kỳ tuyển sinh.
                    </p>
                    <p>
                      Lộ trình điển hình: tốt nghiệp đại học tại Việt Nam hoặc nước khác → nộp hồ sơ master tại Hàn; hoặc <strong>sau khi học cử nhân tại Hàn (D2-2)</strong> tiếp tục lên thạc sĩ cùng trường / trường khác khi đủ điều kiện. Một số ứng viên chọn <a href="he-tieng-d4.php">D4-1</a> để nâng TOPIK trước khi nộp graduate school.
                    </p>
                    <p class="program-d4-intro-prose__foot">
                      Bằng thạc sĩ từ Hàn Quốc được đánh giá cao trong tuyển dụng khu vực và khi ứng tuyển <strong>tiến sĩ</strong> hoặc vị trí chuyên gia; sau tốt nghiệp cần cập nhật <strong>visa / giấy phép</strong> theo quy định nếu ở lại làm việc hoặc học tiếp.
                    </p>
                  </div>

                  <div class="program-d4-intro-highlights" aria-label="Tóm tắt nhanh chương trình D2-3">
                    <p class="program-d4-intro-highlights__label">Tóm tắt chương trình</p>
                    <ul class="program-d4-highlight-grid list-none p-0 mb-0" role="list">
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Diện visa</span>
                          <strong class="program-d4-highlight__title">Visa D2-3</strong>
                          <p class="program-d4-highlight__text">Học viên bậc thạc sĩ (master&apos;s)</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Thời lượng</span>
                          <strong class="program-d4-highlight__title">~2 năm</strong>
                          <p class="program-d4-highlight__text">Tùy tín chỉ — luận văn; có chương trình dài hơn</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Đầu vào</span>
                          <strong class="program-d4-highlight__title">Đại học + đề cương</strong>
                          <p class="program-d4-highlight__text">Bài báo / kinh nghiệm lab là lợi thế (ngành STEM)</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight program-d4-highlight--accent" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-arrow-right-circle"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Hướng đi</span>
                          <strong class="program-d4-highlight__title">Nghề / Tiến sĩ</strong>
                          <p class="program-d4-highlight__text">SUNNY đồng hành từ chọn lab đến hoàn thiện hồ sơ visa</p>
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
                      Visa <strong>D2-3</strong> dành cho ứng viên đã có <strong>bằng đại học</strong> (hoặc tương đương) và muốn học <strong>thạc sĩ chính quy</strong> tại Hàn Quốc. Dưới đây là các nhóm điển hình; tiêu chí tuyển sinh và học bổng khác nhau rõ rệt giữa các viện — ngành.
                    </p>
                  </div>
                </header>

                <div class="program-d4-audience__grid" role="list">
                  <article class="program-d4-audience-card program-d4-audience-card--thpt" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                    <h3 class="program-d4-audience-card__title">Cử nhân mới tốt nghiệp hoặc đi làm 1–3 năm</h3>
                    <p class="program-d4-audience-card__text">
                      Muốn nâng bằng cấp, chuyển ngành (khi trường cho phép) hoặc vào <strong>chương trình nghiên cứu / ứng dụng</strong> phù hợp mục tiêu dài hạn.
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--uni" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
                    <h3 class="program-d4-audience-card__title">Ứng viên hướng nghiên cứu — lab (STEM, xã hội học…)</h3>
                    <p class="program-d4-audience-card__text">
                      Có <strong>đề cương</strong>, kinh nghiệm lab, bài báo hoặc dự án; mục tiêu <strong>học bổng / trợ cấp</strong> hoặc tiến tới tiến sĩ.
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--career" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                    <h3 class="program-d4-audience-card__title">Người đi làm cần bằng thạc sĩ uy tín</h3>
                    <p class="program-d4-audience-card__text">
                      Chương trình <strong>MBA, policy, quản lý, thiết kế</strong>… phục vụ thăng tiến; có thể học part-time hoặc intensive tùy trường (cần xác nhận điều kiện visa).
                    </p>
                  </article>
                </div>

                <div class="program-d4-audience__note">
                  <i class="bi bi-info-circle-fill program-d4-audience__note-ico" aria-hidden="true"></i>
                  <p class="program-d4-audience__note-text mb-0">
                    <strong>Lưu ý:</strong> Một số ngành (y khoa lâm sàng, luật sư hành nghề…) có <strong>rào cản tuyển sinh</strong> đặc biệt với ứng viên quốc tế — cần xác minh sớm với tư vấn SUNNY.
                  </p>
                </div>
              </section>

              <section id="dieu-kien" class="program-d4-section program-d4-requirements-section" aria-labelledby="dieu-kien-heading">
                <header class="program-d4-requirements__head">
                  <div class="title-line mb-3"></div>
                  <h2 id="dieu-kien-heading" class="program-d4-requirements__title">Điều kiện du học hệ thạc sĩ D2-3</h2>
                </header>
                <div class="program-d4-conditions" role="list">
                  <article class="program-d4-condition program-d4-condition--edu" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                      <h3 class="program-d4-condition__title">Học vấn</h3>
                    </div>
                    <p class="program-d4-condition__text">Tốt nghiệp <strong>đại học</strong> đúng hoặc gần ngành đăng ký; bảng điểm, luận văn tốt nghiệp (nếu có). Chuyển ngành có thể cần học bổ sung.</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--finance" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                      <h3 class="program-d4-condition__title">Tài chính</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Chứng minh chi trả <strong>học phí — sinh hoạt</strong> hoặc <strong>học bổng / trợ cấp</strong> đủ theo yêu cầu ĐSQ và trường. Học phí sau ĐH thường cao hơn bậc cử nhân (tùy ngành).
                    </p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--health" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-translate"></i></span>
                      <h3 class="program-d4-condition__title">Ngôn ngữ &amp; hồ sơ</h3>
                    </div>
                    <p class="program-d4-condition__text"><strong>TOPIK</strong> hoặc <strong>IELTS / TOEFL</strong> theo chương trình; kèm <strong>research proposal</strong>, CV học thuật, thư giới thiệu — portfolio (nếu ngành sáng tạo).</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--interview" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mic"></i></span>
                      <h3 class="program-d4-condition__title">Phỏng vấn &amp; visa</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Nhiều viện <strong>phỏng vấn</strong> hoặc đánh giá hồ sơ kỹ; visa <strong>D2-3</strong> cần hồ sơ nhất quán với thư mời nhập học. SUNNY hỗ trợ luyện phỏng vấn và checklist.
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
                      <span class="program-d4-curriculum__panel-kicker">Khung chương trình</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      Thạc sĩ thường <strong>2 năm</strong> (4 học kỳ), gồm học phần chuyên ngành, seminar và <strong>luận văn (thesis)</strong> hoặc dự án tốt nghiệp (non-thesis track, nếu trường có). Một số chương trình <strong>liên kết quốc tế</strong> hoặc chuyên sâu có thời lượng khác.
                    </p>
                  </div>
                  <div class="program-d4-curriculum__panel program-d4-curriculum__panel--schedule">
                    <div class="program-d4-curriculum__panel-head">
                      <span class="program-d4-curriculum__panel-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                      <span class="program-d4-curriculum__panel-kicker">Giáo sư &amp; lab</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      Với hướng nghiên cứu, <strong>giáo sư hướng dẫn</strong> đóng vai trò trung tâm: từ đề tài luận văn đến kinh phí lab. Sinh viên thường tham gia <strong>nhóm nghiên cứu</strong>, hội thảo và (khi có) <strong>RA/TA</strong>.
                    </p>
                  </div>
                </div>

                <h3 class="program-d4-curriculum__levels-heading">Giai đoạn điển hình (tham khảo)</h3>
                <ul class="program-d4-levels program-d4-levels--grid list-none p-0" role="list">
                  <li class="program-d4-level program-d4-level--1" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">G1</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm 1 — Kiến thức nền &amp; định hướng đề tài</p>
                      <p class="program-d4-level__desc mb-0">Học phần cốt lõi, làm quen phương pháp nghiên cứu; thu hẹp câu hỏi luận văn cùng giáo sư.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--2" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">G2</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Học kỳ giữa — Thu thập dữ liệu / thí nghiệm</p>
                      <p class="program-d4-level__desc mb-0">Triển khai đề tài, báo cáo tiến độ; có thể chuẩn bị bài báo hoặc hội nghị (tùy ngành).</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--3" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">G3</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm 2 — Hoàn thiện luận văn / dự án</p>
                      <p class="program-d4-level__desc mb-0">Viết — chỉnh sửa luận văn, phản biện nội bộ, bảo vệ (defense) theo quy định viện.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--4" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">+</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Sau tốt nghiệp</p>
                      <p class="program-d4-level__desc mb-0">Ứng tuyển <strong>tiến sĩ</strong>, R&amp;D doanh nghiệp, hoặc vị trí chuyên môn tại Hàn / quốc tế — cập nhật visa khi cần.</p>
                    </div>
                  </li>
                </ul>

                <div class="program-d4-curriculum__foot">
                  <p class="program-d4-curriculum__foot-text mb-0">
                    <strong>Gợi ý:</strong> Nếu hướng <strong>GKS / học bổng lớn</strong>, cần lộ trình chuẩn bị hồ sơ sớm hơn 1 năm; SUNNY phối hợp bạn <strong>ưu tiên trường — lab — deadline</strong>.
                    <span class="program-d4-curriculum__foot-muted">Cấu trúc chương trình lấy theo quy chế đào tạo sau đại học của từng trường.</span>
                  </p>
                </div>
              </section>

              <section id="chi-phi" class="program-d4-section">
                <h2>Chi phí du học hệ thạc sĩ D2-3</h2>
                <p class="text-black mb-3">
                  Chi phí <strong>thạc sĩ / master</strong> (visa <strong>D2-3</strong>) biến thiên mạnh theo <strong>trường — viện</strong>, <strong>ngành</strong> (STEM, y — dược, kinh doanh…), <strong>hình thức chương trình</strong> (coursework — nghiên cứu — lab) và <strong>thành phố</strong>. Bảng dưới giúp <strong>ước lượng từng nhóm khoản</strong>; số tiền chính thức luôn lấy theo thông báo tuyển sinh, hợp đồng học kỳ và tư vấn SUNNY tại thời điểm bạn nộp hồ sơ.
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
                        <th scope="row" class="font-semibold text-left">Nhóm học phí &amp; lệ phí trường</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Nhiều chương trình tính theo <strong>học kỳ</strong> (Spring / Fall); một số viện có học kỳ hè hoặc học phí theo tín chỉ.</td>
                      </tr>
                      <tr>
                        <td>Học phí sau ĐH / học kỳ</td>
                        <td>~7.000.000 – 15.000.000+ KRW</td>
                        <td>Quy đổi thường <strong>~130 – 280+ triệu VNĐ/học kỳ</strong> (tùy tỷ giá). <strong>STEM, y — dược, MBA, luật quốc tế</strong> thường ở nhóm cao.</td>
                      </tr>
                      <tr>
                        <td>Ước học phí / năm học (2 học kỳ)</td>
                        <td>~14.000.000 – 30.000.000+ KRW</td>
                        <td>Tương đương ~<strong>260 – 560+ triệu VNĐ/năm</strong> phần học; chưa gồm chỗ ở và sinh hoạt.</td>
                      </tr>
                      <tr>
                        <td>Phí nhập học — xét hồ sơ (một lần hoặc theo đợt)</td>
                        <td>~100.000 – 500.000+ KRW</td>
                        <td>Tùy trường; có nơi miễn hoặc khấu trừ vào học kỳ đầu.</td>
                      </tr>
                      <tr>
                        <td>Phí lab, studio, môn thực hành đặc thù / học kỳ</td>
                        <td>0 – 1.500.000+ KRW</td>
                        <td>Đặc biệt với ngành kỹ thuật, âm nhạc, thiết kế — kiểm tra syllabus từng kỳ.</td>
                      </tr>
                      <tr>
                        <td>Bảo hiểm y tế sinh viên / tháng</td>
                        <td>~55.000 – 100.000 KRW</td>
                        <td>Sau nhập cảnh; mức đóng theo quy định, có thể đổi hằng năm.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm nghiên cứu &amp; học thuật</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Tùy đề tài, giáo sư hướng dẫn và có được <strong>trợ cấp / RA</strong> hay không.</td>
                      </tr>
                      <tr>
                        <td>Sách, tài liệu, phần mềm, in ấn / năm</td>
                        <td>~800.000 – 3.500.000+ KRW</td>
                        <td>Ngành cần journal — dataset — license phần mềm sẽ tốn hơn.</td>
                      </tr>
                      <tr>
                        <td>Hội nghị, tham quan nghiên cứu, workshop / năm</td>
                        <td>~1 – 8 triệu VNĐ</td>
                        <td>Nhiều lab hỗ trợ một phần khi bạn có bài báo hoặc nhiệm vụ nhóm.</td>
                      </tr>
                      <tr>
                        <td>Luận văn: in, chỉnh sửa, biên dịch tóm tắt (nếu có)</td>
                        <td>~2 – 12 triệu VNĐ</td>
                        <td>Tập trung vào các tháng cuối chương trình; có thể tiết kiệm nếu tự làm và dùng template trường.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm chỗ ở &amp; sinh hoạt</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Seoul, Sungnam, Yongin… thường đắt hơn Busan, Daegu, Daejeon.</td>
                      </tr>
                      <tr>
                        <td>Ký túc xá sau ĐH / tháng</td>
                        <td>~550.000 – 1.500.000+ KRW</td>
                        <td>Ước <strong>~4 – 8+ triệu VNĐ/tháng</strong>; chỗ hạn, cần đăng ký sớm.</td>
                      </tr>
                      <tr>
                        <td>Thuê nhà — phòng chia sẻ / tháng</td>
                        <td>~500.000 – 1.400.000+ KRW</td>
                        <td>Cọc thường <strong>1–3 tháng</strong>; gần campus đắt hơn nhưng tiết kiệm đi lại.</td>
                      </tr>
                      <tr>
                        <td>Ăn uống / tháng</td>
                        <td>4 – 11 triệu VNĐ</td>
                        <td>Tự nấu giúp ổn định ngân sách; khu trường đông đúc thì chi ăn ngoài dễ tăng.</td>
                      </tr>
                      <tr>
                        <td>Đi lại &amp; liên lạc / tháng</td>
                        <td>~1,5 – 4 triệu VNĐ</td>
                        <td>Thẻ giao thông + data; sinh viên sau ĐH thường di chuyển lab — thư viện nhiều hơn.</td>
                      </tr>
                      <tr>
                        <td><strong>Tổng sinh hoạt gợi ý / tháng</strong> (ăn + ở + đi + phụ)</td>
                        <td>~10 – 24+ triệu VNĐ</td>
                        <td>Khoảng <strong>~10 – 20 triệu</strong> phổ biến nếu tiết kiệm; Seoul hoặc nhiều hoạt động xã hội dễ vượt.</td>
                      </tr>
                      <tr>
                        <td>Chi phí tháng đầu (cọc, thiết lập chỗ ở)</td>
                        <td>~6 – 22 triệu VNĐ</td>
                        <td>Tháng đầu thường cao hơn trung bình.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Học bổng &amp; thu nhập hỗ trợ</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Giảm gián tiếp tổng chi phí — cạnh tranh cao, cần GPA, portfolio nghiên cứu, TOPIK / IELTS theo yêu cầu.</td>
                      </tr>
                      <tr>
                        <td>Học bổng nhập học / học bổng viện</td>
                        <td>Giảm học phí một phần — toàn phần</td>
                        <td><strong>GKS</strong>, học bổng trường, học bổng quốc tế — điều kiện và hạn nộp khác nhau.</td>
                      </tr>
                      <tr>
                        <td>Trợ cấp nghiên cứu (RA) / trợ giảng</td>
                        <td>Theo lab &amp; hợp đồng</td>
                        <td>Có thể bù một phần <strong>sinh hoạt</strong> và học phí (nếu chính sách trường cho phép); không phải ai cũng có ngay học kỳ đầu.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm hành chính &amp; di chuyển</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Biến động theo đợt xét visa và giá vé.</td>
                      </tr>
                      <tr>
                        <td>Hồ sơ: dịch thuật, công chứng, chứng minh tài chính</td>
                        <td>~10 – 30 triệu VNĐ</td>
                        <td>Khối lượng lớn hơn bậc ĐH nếu có portfolio nghiên cứu, thư giới thiệu, bài báo.</td>
                      </tr>
                      <tr>
                        <td>Lệ phí visa D2-3 &amp; xét duyệt</td>
                        <td>Theo biểu phí ĐSQ</td>
                        <td>Cập nhật theo <strong>từng thời điểm</strong> khi bạn chuẩn bị nộp.</td>
                      </tr>
                      <tr>
                        <td>Vé máy bay khứ hồi</td>
                        <td>~11 – 28 triệu VNĐ</td>
                        <td>Phụ thuộc hãng, mùa cao điểm và đặt vé sớm.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p class="program-cost-disclaimer" role="note">
                  Bảng mang tính tham khảo; tỷ giá và học phí có thể thay đổi. Liên hệ <a href="tel:0981698292">0981 698 292</a> hoặc <a href="lien-he.php">đăng ký tư vấn</a>.
                </p>
              </section>

              <section id="truong" class="program-d4-section">
                <h2>Trường &amp; viện sau đại học</h2>
                <p>
                  Học viên <strong>D2-3</strong> nhập học tại <strong>graduate school</strong> (đại học quốc lập, tư thục hoặc viện chuyên ngành). Mỗi viện có <strong>danh mục chương trình master</strong>, hình thức tuyển <strong>xét hồ sơ / thi / phỏng vấn</strong> và lịch <strong>Spring / Fall</strong> khác nhau.
                </p>
                <p>
                  Khi chọn nơi học, cân nhắc <strong>nhóm nghiên cứu — giáo sư</strong>, <strong>xuất bản</strong> của lab, <strong>học bổng</strong> và vị trí địa lý. Nếu bạn đã học <a href="he-dai-hoc-d2.php">cử nhân tại Hàn (D2-2)</a>, có thể có lợi thế nội bộ khi lên thạc sĩ. Xem thêm <a href="hoc-bong-gks-chinh-phu-han-quoc.php">GKS</a> nếu hướng học bổng Chính phủ.
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
                  <strong>Gợi ý:</strong> Kết hợp xem thêm <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học</a> và <a href="truong-dai-hoc.php">danh sách trường</a> trên SUNNY để thu hẹp 2–3 phương án trước khi nộp hồ sơ chính thức.
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
                <h2>Sau khi tốt nghiệp thạc sĩ</h2>
                <p class="program-d4-after__lead text-black">
                  Bằng thạc sĩ từ Hàn Quốc hỗ trợ mạnh khi ứng tuyển <strong>R&amp;D</strong>, quản lý, học <strong>tiến sĩ</strong> hoặc làm việc tại tập đoàn đa quốc gia — cần cập nhật <strong>visa / work permit</strong> theo từng giai đoạn chính sách.
                </p>
                <div class="flex flex-wrap gap-4 program-d4-after__grid">
                  <div class="w-full lg:w-6/12">
                    <article class="program-d4-outcome-card h-full">
                      <header class="program-d4-outcome-card__head">
                        <span class="program-d4-outcome-card__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                        <h3 class="program-d4-outcome-card__title">Tiến sĩ hoặc nghề chuyên sâu tại Hàn</h3>
                      </header>
                      <p class="program-d4-outcome-card__text">
                        Nhiều thạc sĩ tiếp tục <strong>Ph.D.</strong> trong cùng lab; hoặc vào doanh nghiệp, viện nghiên cứu — lưu ý chuyển đổi từ <strong>D2-3</strong> sang diện phù hợp khi tốt nghiệp.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Mạng lưới <strong>giáo sư — cố vấn</strong> và cộng tác quốc tế</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Ưu thế khi ứng tuyển vị trí kỹ thuật / phân tích cao cấp</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Theo dõi chính sách <strong>E-7 / D-10</strong> (tham khảo; SUNNY cập nhật khi tư vấn)</span></li>
                      </ul>
                    </article>
                  </div>
                  <div class="w-full lg:w-6/12">
                    <article class="program-d4-outcome-card program-d4-outcome-card--vn h-full">
                      <header class="program-d4-outcome-card__head">
                        <span class="program-d4-outcome-card__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                        <h3 class="program-d4-outcome-card__title">Việt Nam &amp; khu vực ASEAN</h3>
                      </header>
                      <p class="program-d4-outcome-card__text">
                        Thạc sĩ + kinh nghiệm Hàn phù hợp vị trí <strong>đầu tư FDI</strong>, chuỗi cung ứng, giáo dục, công nghệ và các phòng ban hợp tác Hàn — Việt.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Quản lý — chiến lược — phát triển kinh doanh</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Giảng dạy / đào tạo doanh nghiệp</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Khởi nghiệp hoặc tư vấn chuyên môn</span></li>
                      </ul>
                    </article>
                  </div>
                </div>
              </section>

              <section id="quy-trinh" class="program-d4-section program-d4-process">
                <h2>Quy trình nộp hồ sơ thạc sĩ (D2-3)</h2>
                <p class="program-d4-process__lead mb-4">Thời gian thường <strong>6–12 tháng</strong> (đặc biệt nếu cần liên hệ giáo sư, GKS hoặc kỳ tuyển cạnh tranh).</p>
                <ol class="program-d4-steps mb-0">
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">1</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Liên hệ SUNNY</strong>
                      <p class="program-d4-steps__desc mb-0">Chia sẻ bằng cấp, GPA, hướng nghiên cứu, mục tiêu học bổng / tự túc.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">2</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Rà soát trường — lab — giáo sư</strong>
                      <p class="program-d4-steps__desc mb-0">Chọn 2–4 phương án; soạn email / research proposal sơ bộ khi cần tiếp cận lab.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">3</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Hoàn thiện hồ sơ xét tuyển</strong>
                      <p class="program-d4-steps__desc mb-0">Bảng điểm, bằng ĐH, CV học thuật, proposal, thư giới thiệu, chứng chỉ ngôn ngữ, bài báo (nếu có)…</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">4</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Nộp hồ sơ &amp; phỏng vấn</strong>
                      <p class="program-d4-steps__desc mb-0">SUNNY theo dõi portal tuyển sinh, hạn nộp, phỏng vấn (online / trực tiếp).</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">5</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Giấy mời &amp; học phí / học bổng</strong>
                      <p class="program-d4-steps__desc mb-0">Nhận admission, xác nhận học bổng hoặc kế hoạch tài chính.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">6</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Xin visa D2-3 &amp; nhập học</strong>
                      <p class="program-d4-steps__desc mb-0">Hồ sơ visa tại ĐSQ / LSQ → nhận visa → nhập học graduate school, đăng ký tạm trú.</p>
                    </div>
                  </li>
                </ol>
                <p class="program-d4-process__foot text-sm mt-4 mb-0">
                  Xem thêm: <a href="/#visa">mục Visa</a> · <a href="tin-visa-d2-ho-so-chuan-bi.php">Hồ sơ visa D-2</a> · <a href="he-dai-hoc-d2.php">Cử nhân D2-2</a> nếu bạn đang ở lộ trình liên thông.
                </p>
              </section>

              <section id="dich-vu" class="program-d4-section">
                <h2>Làm hồ sơ du học hệ thạc sĩ D2-3 cùng SUNNY</h2>
                <p>
                  SUNNY đồng hành hồ sơ <strong>sau đại học tại Hàn Quốc</strong>: từ định vị lab — viện đến hoàn thiện proposal và visa.
                </p>
                <ul class="mb-0">
                  <li><strong>Định hướng học bổng:</strong> GKS, học bổng trường, RA — so khớp profile với deadline thực tế.</li>
                  <li><strong>Chỉnh sửa học thuật:</strong> Research proposal, CV, thư giới thiệu, portfolio (khi cần).</li>
                  <li><strong>Phối hợp tuyển sinh:</strong> Nộp hồ sơ, phỏng vấn, theo dõi kết quả.</li>
                  <li><strong>Visa D2-3:</strong> Checklist tài chính, lịch ĐSQ, pre-departure.</li>
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
