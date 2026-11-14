<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 8,
  'slug' => 'trang-d2-2',
  'name' => 'Trang D2-2',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:25:23',
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
              <span class="page-about-breadcrumb__current">Hệ đại học (D2-2)</span>
            </li>
          </ol>
        </nav>

        <div class="flex flex-wrap gap-4 xl:gap-5 items-stretch">
          <div class="w-full lg:w-6/12 xl:w-7/12 flex flex-col">
            <p class="program-d4-hero__eyebrow">
              <span class="program-d4-hero__eyebrow-dot" aria-hidden="true"></span>
              Chương trình du học · Visa cử nhân
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title mb-0">Du học hệ đại học (Visa D2-2)</h1>

            <div class="program-d4-hero__lang-card">
              <p class="program-d4-hero__lang-vi">
                Chương trình cử nhân 4 năm tại đại học Hàn Quốc — đa ngành, bằng được công nhận và lộ trình nghề nghiệp rõ ràng.
              </p>
            </div>

            <p class="program-d4-hero__lead text-black">
              Visa <strong>D2-2</strong> dành cho sinh viên quốc tế theo học <strong>bậc cử nhân</strong> tại các trường đại học được công nhận; thường cần <strong>TOPIK</strong> và hồ sơ học thuật theo yêu cầu từng trường — ngành.
            </p>

            <div class="flex flex-wrap gap-2 mt-3 lg:mt-4">
              <a href="#gioi-thieu-d2" class="btn btn-sunny btn-sunny-pulse text-white">Tìm hiểu chương trình</a>
              <a href="#dang-ky" class="btn btn-outline-dark">Đăng ký tư vấn</a>
            </div>
          </div>

          <div class="w-full lg:w-6/12 xl:w-5/12 flex">
            <div class="program-d4-hero__stats-card w-full">
              <p class="program-d4-hero__stats-kicker">D2-2 trong một cái nhìn</p>
              <p class="program-d4-hero__stats-title">Thông tin tóm tắt</p>
              <div class="program-d4-stats" role="list">
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">D2-2</span>
                    <span class="program-d4-stat__label">Loại visa</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">4 năm</span>
                    <span class="program-d4-stat__label">Cử nhân (thường)</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">Theo DSQ</span>
                    <span class="program-d4-stat__label">Tài chính</span>
                    <span class="program-d4-stat__hint">Sổ tiết kiệm / bảo lãnh — mức cụ thể theo hướng dẫn từng thời điểm</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">TOPIK 3–4+</span>
                    <span class="program-d4-stat__label">Tham khảo</span>
                    <span class="program-d4-stat__hint">Nhiều ngành yêu cầu cao hơn; có chương trình tiếng Anh</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <nav class="uni-detail-toc border-b bg-white" aria-label="Mục lục trang — Hệ đại học D2-2">
      <div class="container py-2 py-lg-3">
        <ul class="uni-detail-toc__list list-none p-0 mb-0 flex flex-wrap gap-2 lg:gap-3">
          <li><a href="#gioi-thieu-d2">Giới thiệu</a></li>
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

    <article class="py-5 bg-white news-article-page program-d4-page program-d2-page">
      <div class="container">
        <div class="news-article text-black program-d4-article">
              <section id="gioi-thieu-d2" class="program-d4-section program-d4-intro-section" aria-labelledby="gioi-thieu-d2-heading">
                <div class="program-d4-intro-panel">
                  <header class="program-d4-intro-head">
                    <p class="program-d4-intro-kicker"><span class="program-d4-intro-kicker__dot" aria-hidden="true"></span> Bachelor · Visa sinh viên đại học Hàn Quốc</p>
                    <div class="title-line program-d4-intro-head__line"></div>
                    <h2 id="gioi-thieu-d2-heading" class="program-d4-intro-title">Giới thiệu hệ du học đại học D2-2</h2>
                    <p class="program-d4-intro-deck">
                      Chương trình <strong>cử nhân</strong> toàn thời gian tại đại học Hàn Quốc — học thuật, chứng chỉ và mạng lưới quốc tế phục vụ lộ trình nghề nghiệp dài hạn.
                    </p>
                  </header>

                  <div class="program-d4-intro-prose">
                    <p class="program-d4-intro-lead">
                      <strong>Visa D2-2</strong> là diện visa dành cho sinh viên quốc tế theo học <strong>bậc cử nhân</strong> (undergraduate) tại các trường đại học được công nhận tại Hàn Quốc. Khác với visa tiếng (D4), bạn được nhập học trực tiếp vào <strong>chuyên ngành</strong>, tuân theo quy chế tín chỉ — năm học của nhà trường; yêu cầu đầu vào thường gồm <strong>học lực</strong>, <strong>TOPIK</strong> hoặc chứng chỉ tiếng Anh (với chương trình giảng dạy bằng tiếng Anh), và bộ hồ sơ xét tuyển theo từng đợt.
                    </p>
                    <p>
                      Hệ thống đại học Hàn Quốc có nhiều lựa chọn ngành: kỹ thuật — công nghệ, kinh tế — kinh doanh, khoa học xã hội, truyền thông, nghệ thuật, y — dược (thường có rào cản tuyển sinh cao hơn)… Sinh viên quốc tế cần chủ động theo dõi <strong>kỳ tuyển sinh</strong> (thường có xét tuyển sớm và xét tuyển chính), <strong>ngưỡng điểm</strong> và <strong>học bổng nhập học</strong> (khi có). SUNNY hỗ trợ đối chiếu hồ sơ của bạn với tiêu chí từng trường — ngành tại thời điểm nộp.
                    </p>
                    <p>
                      Nhiều bạn chọn lộ trình <strong>D4-1 (tiếng) → D2-2 (cử nhân)</strong>: hoàn thành khóa tiếng tại <span lang="ko">어학당</span> rồi nộp hồ sơ chuyển tiếp lên bậc đại học cùng trường hoặc trường khác. Bạn cũng có thể <strong>nộp thẳng cử nhân</strong> nếu đã đạt TOPIK / tiếng Anh và học lực theo yêu cầu — không bắt buộc qua KLI.
                    </p>
                    <p class="program-d4-intro-prose__foot">
                      Bằng cử nhân do đại học Hàn Quốc cấp được công nhận trong nhiều ngữ cảnh quốc tế; sau tốt nghiệp bạn có thể <strong>ở lại làm việc</strong> (theo quy định visa — giấy phép từng thời kỳ), <strong>học sau đại học</strong> hoặc <strong>về Việt Nam</strong> với lợi thế song ngữ và bằng cấp minh chứng năng lực học thuật.
                    </p>
                  </div>

                  <div class="program-d4-intro-highlights" aria-label="Tóm tắt nhanh chương trình D2-2">
                    <p class="program-d4-intro-highlights__label">Tóm tắt chương trình</p>
                    <ul class="program-d4-highlight-grid list-none p-0 mb-0" role="list">
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Diện visa</span>
                          <strong class="program-d4-highlight__title">Visa D2-2</strong>
                          <p class="program-d4-highlight__text">Sinh viên bậc cử nhân (undergraduate)</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Thời lượng</span>
                          <strong class="program-d4-highlight__title">~4 năm</strong>
                          <p class="program-d4-highlight__text">Theo khung tín chỉ — chuyên ngành (có ngành 5–6 năm)</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Ngôn ngữ</span>
                          <strong class="program-d4-highlight__title">TOPIK / tiếng Anh</strong>
                          <p class="program-d4-highlight__text">Tùy chương trình tiếng Hàn hoặc track quốc tế</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight program-d4-highlight--accent" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-arrow-right-circle"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Lộ trình</span>
                          <strong class="program-d4-highlight__title">Nộp thẳng hoặc sau D4</strong>
                          <p class="program-d4-highlight__text">SUNNY tối ưu hóa theo điểm xuất phát và mục tiêu ngành</p>
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
                      Visa <strong>D2-2</strong> phù hợp khi bạn muốn <strong>theo học cử nhân chính quy</strong> tại Hàn Quốc, có học lực — chứng chỉ ngôn ngữ phù hợp và kế hoạch tài chính rõ ràng. Dưới đây là các nhóm điển hình; tiêu chí cụ thể tùy trường — kỳ tuyển sinh — SUNNY hỗ trợ rà soát hồ sơ cá nhân.
                    </p>
                  </div>
                </header>

                <div class="program-d4-audience__grid" role="list">
                  <article class="program-d4-audience-card program-d4-audience-card--thpt" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-backpack"></i></span>
                    <h3 class="program-d4-audience-card__title">Học sinh tốt nghiệp THPT (hoặc tương đương)</h3>
                    <p class="program-d4-audience-card__text">
                      Muốn <strong>vào thẳng cử nhân</strong> với TOPIK / tiếng Anh đạt ngưỡng, hoặc chọn lộ trình <strong>tiếng trước — ĐH sau</strong> (D4-1) để tăng cơ hội vào trường — ngành mục tiêu.
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--uni" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
                    <h3 class="program-d4-audience-card__title">Sinh viên đang học, muốn chuyển tiếp sang Hàn Quốc</h3>
                    <p class="program-d4-audience-card__text">
                      Đã có năm học đại học tại Việt Nam hoặc nước khác; cần <strong>chuyển tín chỉ / nộp hồ sơ xét tuyển</strong> theo quy định từng trường Hàn Quốc.
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--career" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                    <h3 class="program-d4-audience-card__title">Người định hướng nghề tại Hàn hoặc tập đoàn Hàn khu vực</h3>
                    <p class="program-d4-audience-card__text">
                      Cần <strong>bằng cử nhân</strong> từ môi trường Hàn Quốc, kết hợp thực tập — mạng lưới alumni để phát triển sau tốt nghiệp.
                    </p>
                  </article>
                </div>

                <div class="program-d4-audience__note">
                  <i class="bi bi-info-circle-fill program-d4-audience__note-ico" aria-hidden="true"></i>
                  <p class="program-d4-audience__note-text mb-0">
                    <strong>Lưu ý:</strong> Ngành y — dược — luật và một số chương trình đặc thù có thể có <strong>chỉ tiêu hạn chế</strong> hoặc yêu cầu bổ sung. Hãy kiểm tra sớm với tư vấn SUNNY trước kỳ nộp hồ sơ.
                  </p>
                </div>
              </section>

              <section id="dieu-kien" class="program-d4-section program-d4-requirements-section" aria-labelledby="dieu-kien-heading">
                <header class="program-d4-requirements__head">
                  <div class="title-line mb-3"></div>
                  <h2 id="dieu-kien-heading" class="program-d4-requirements__title">Điều kiện du học hệ đại học D2-2</h2>
                </header>
                <div class="program-d4-conditions" role="list">
                  <article class="program-d4-condition program-d4-condition--edu" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                      <h3 class="program-d4-condition__title">Học vấn</h3>
                    </div>
                    <p class="program-d4-condition__text">Tốt nghiệp THPT hoặc tương đương; học bạ — bảng điểm theo yêu cầu từng trường. Một số ngành cạnh tranh yêu cầu học lực cao hơn.</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--finance" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                      <h3 class="program-d4-condition__title">Tài chính</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Chứng minh khả năng chi trả <strong>học phí và sinh hoạt</strong> theo hướng dẫn ĐSQ/LSQ và trường từng thời điểm (sổ tiết kiệm, bảo lãnh, học bổng…). Mức cụ thể SUNNY cập nhật khi bạn chuẩn bị hồ sơ.
                    </p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--health" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-translate"></i></span>
                      <h3 class="program-d4-condition__title">Ngôn ngữ</h3>
                    </div>
                    <p class="program-d4-condition__text"><strong>TOPIK</strong> (chương trình tiếng Hàn) hoặc <strong>IELTS / TOEFL</strong> (chương trình tiếng Anh) — ngưỡng tùy trường và ngành. Có thể kết hợp năm dự bị tiếng tại trường (khi được tuyển).</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--interview" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mic"></i></span>
                      <h3 class="program-d4-condition__title">Phỏng vấn &amp; visa</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Một số trường phỏng vấn tuyển sinh; khi xin visa D2 cần tuân thủ quy định ĐSQ/LSQ (lịch sử từ chối, tính xác thực hồ sơ). SUNNY đồng hành chuẩn bị phỏng vấn và checklist visa.
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
                      <span class="program-d4-curriculum__panel-kicker">Năm học &amp; tín chỉ</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      Đại học Hàn Quốc thường tổ chức theo <strong>hai học kỳ chính</strong> (xuân — thu) mỗi năm; sinh viên tích lũy <strong>tín chỉ</strong> theo chương trình ngành, có thể chọn một phần học phần tự chọn — thực tập (internship) khi đủ điều kiện. Thời gian tốt nghiệp cử nhân phổ biến <strong>khoảng 4 năm</strong>; ngành đặc thù (ví dụ kiến trúc, dược, y) có thể dài hơn.
                    </p>
                  </div>
                  <div class="program-d4-curriculum__panel program-d4-curriculum__panel--schedule">
                    <div class="program-d4-curriculum__panel-head">
                      <span class="program-d4-curriculum__panel-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                      <span class="program-d4-curriculum__panel-kicker">Học thuật &amp; hỗ trợ</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      Ngoài giờ lý thuyết, nhiều ngành có <strong>thí nghiệm, studio, seminar</strong> và <strong>Trung tâm hỗ trợ sinh viên quốc tế</strong> (visa, chỗ ở, đời sống). Bạn nên tận dụng <strong>peer tutoring</strong>, câu lạc bộ và chương trình giao lưu để hòa nhập.
                    </p>
                  </div>
                </div>

                <h3 class="program-d4-curriculum__levels-heading">Định hướng theo năm học (tham khảo)</h3>
                <ul class="program-d4-levels program-d4-levels--grid list-none p-0" role="list">
                  <li class="program-d4-level program-d4-level--1" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">N1</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm nhất — nền tảng</p>
                      <p class="program-d4-level__desc mb-0">Môn đại cương, làm quen phương pháp học đại học, tiếng Hàn học thuật (nếu chương trình tiếng Hàn); xây GPA và thói quen tự học.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--2" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">N2</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm hai — chuyên ngành cơ bản</p>
                      <p class="program-d4-level__desc mb-0">Bắt đầu các học phần cốt lõi của ngành; có thể đăng ký hoạt động ngoại khóa, dự án nhóm.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--3" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">N3</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm ba — chuyên sâu / thực tập</p>
                      <p class="program-d4-level__desc mb-0">Học phần nâng cao, hướng nghiên cứu hoặc thực tập doanh nghiệp (tùy ngành); chuẩn bị portfolio / đồ án.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--4" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">N4</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm tư — tốt nghiệp</p>
                      <p class="program-d4-level__desc mb-0">Hoàn thiện tín chỉ, <strong>luận văn / đồ án tốt nghiệp</strong> (nếu bắt buộc), định hướng việc làm hoặc học sau ĐH; cập nhật visa theo quy định.</p>
                    </div>
                  </li>
                </ul>

                <div class="program-d4-curriculum__foot">
                  <p class="program-d4-curriculum__foot-text mb-0">
                    <strong>Chọn ngành:</strong> STEM, kinh tế — kinh doanh, truyền thông, thiết kế, du lịch — khách sạn… đều có lộ trình rõ; SUNNY giúp bạn <strong>đối chiếu năng lực — nguyện vọng</strong> với yêu cầu tuyển sinh thực tế.
                    <span class="program-d4-curriculum__foot-muted">Chi tiết môn học và phân bổ tín chỉ lấy theo chương trình đào tạo chính thức của từng khoa.</span>
                  </p>
                </div>
              </section>

              <section id="chi-phi" class="program-d4-section">
                <h2>Chi phí du học hệ đại học D2-2</h2>
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
                        <td>Học phí cử nhân / năm</td>
                        <td>~6.000.000 – 12.000.000+ KRW</td>
                        <td>Chênh lệch lớn giữa ngành, trường tư — công lập; quy đổi theo tỷ giá.</td>
                      </tr>
                      <tr>
                        <td>Ký túc xá / thuê nhà</td>
                        <td>~3 – 10+ triệu VNĐ / tháng</td>
                        <td>Seoul và các thành phố lớn thường cao hơn vùng khác.</td>
                      </tr>
                      <tr>
                        <td>Sinh hoạt &amp; đi lại</td>
                        <td>~8 – 18 triệu VNĐ / tháng</td>
                        <td>Ăn uống, bảo hiểm, điện thoại — tùy lối sống.</td>
                      </tr>
                      <tr>
                        <td>Hồ sơ, visa, vé máy bay</td>
                        <td>Dự trù thêm</td>
                        <td>Có thể giảm gánh nặng nhờ học bổng nhập học (khi đủ điều kiện).</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p class="program-cost-disclaimer" role="note">
                  Bảng mang tính tham khảo; tỷ giá và học phí có thể thay đổi. Liên hệ <a href="tel:0981698292">0981 698 292</a> hoặc <a href="lien-he.php">đăng ký tư vấn</a>.
                </p>
              </section>

              <section id="truong" class="program-d4-section">
                <h2>Trường đại học &amp; lựa chọn ngành</h2>
                <p>
                  Sinh viên visa <strong>D2-2</strong> nhập học trực tiếp vào các <strong>khoa — chương trình cử nhân</strong> của đại học Hàn Quốc. Mỗi trường công bố <strong>kỳ xét tuyển</strong>, <strong>chỉ tiêu</strong> và <strong>học bổng</strong> riêng; một số có <strong>track quốc tế</strong> giảng dạy bằng tiếng Anh song song với chương trình tiếng Hàn.
                </p>
                <p>
                  Khi chọn trường, nên cân nhắc <strong>độ phù hợp ngành — học phí — vị trí địa lý — mạng lưới doanh nghiệp / thực tập</strong>. Nếu bạn đang học <a href="he-tieng-d4.php">D4-1</a> tại một trường, có thể tận dụng lợi thế <strong>chuyển tiếp nội bộ</strong> khi đủ điều kiện. SUNNY so sánh phương án theo hồ sơ thực tế của bạn.
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
                <h2>Sau khi tốt nghiệp cử nhân</h2>
                <p class="program-d4-after__lead text-black">
                  Bằng cử nhân từ Hàn Quốc mở ra <strong>việc làm tại Hàn</strong> (theo chính sách visa — giấy phép từng giai đoạn), <strong>học thạc sĩ / tiến sĩ</strong> hoặc <strong>quay về Việt Nam</strong> với lợi thế bằng cấp và kinh nghiệm sống — học tập quốc tế.
                </p>
                <div class="flex flex-wrap gap-4 program-d4-after__grid">
                  <div class="w-full lg:w-6/12">
                    <article class="program-d4-outcome-card h-full">
                      <header class="program-d4-outcome-card__head">
                        <span class="program-d4-outcome-card__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                        <h3 class="program-d4-outcome-card__title">Học sau đại học hoặc làm việc tại Hàn</h3>
                      </header>
                      <p class="program-d4-outcome-card__text">
                        Nhiều cử nhân tiếp tục <strong>master</strong> trong cùng hoặc liên ngành; hoặc tìm việc tại doanh nghiệp Hàn / công ty đa quốc gia — cần cập nhật <strong>visa &amp; giấy phép lao động</strong> theo quy định hiện hành.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chuyển đổi từ <strong>D2-2</strong> sang diện phù hợp khi tốt nghiệp / tìm được việc</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Tận dụng <strong>career center</strong> của trường và mạng alumni</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Ôn luyện <strong>TOPIK / tiếng Anh</strong> nếu hướng tới ngành yêu cầu cao</span></li>
                      </ul>
                    </article>
                  </div>
                  <div class="w-full lg:w-6/12">
                    <article class="program-d4-outcome-card program-d4-outcome-card--vn h-full">
                      <header class="program-d4-outcome-card__head">
                        <span class="program-d4-outcome-card__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                        <h3 class="program-d4-outcome-card__title">Về Việt Nam &amp; khu vực</h3>
                      </header>
                      <p class="program-d4-outcome-card__text">
                        Bằng cử nhân được kiểm chứng, kết hợp tiếng Hàn — tiếng Anh, phù hợp các tập đoàn Hàn, startup, FDI và vị trí cần hiểu văn hóa Hàn Quốc.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Vận hành — kinh doanh — marketing xuyên biên giới</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Kỹ thuật — sản xuất trong chuỗi cung ứng Hàn</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Tư vấn — dịch vụ giáo dục — truyền thông</span></li>
                      </ul>
                    </article>
                  </div>
                </div>
              </section>

              <section id="quy-trinh" class="program-d4-section program-d4-process">
                <h2>Quy trình nộp hồ sơ cử nhân (D2-2)</h2>
                <p class="program-d4-process__lead mb-4">Thời gian thường <strong>4–8 tháng</strong> (tùy kỳ xét tuyển sớm / chính, thời gian xử lý của trường và lịch visa ĐSQ / LSQ).</p>
                <ol class="program-d4-steps mb-0">
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">1</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Liên hệ SUNNY</strong>
                      <p class="program-d4-steps__desc mb-0">Đăng ký tư vấn, nêu ngành dự kiến, điểm học bạ và mục tiêu TOPIK / tiếng Anh.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">2</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Định hướng trường — ngành</strong>
                      <p class="program-d4-steps__desc mb-0">So sánh 2–3 phương án phù hợp năng lực; kế hoạch D4 trước hoặc nộp thẳng D2-2.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">3</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Chuẩn bị hồ sơ xét tuyển</strong>
                      <p class="program-d4-steps__desc mb-0">Học bạ, bằng tốt nghiệp, chứng chỉ ngôn ngữ, sơ yếu lý lịch, thư giới thiệu (nếu cần), portfolio (ngành nghệ thuật)…</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">4</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Nộp hồ sơ lên trường</strong>
                      <p class="program-d4-steps__desc mb-0">SUNNY hỗ trợ nộp online / hồ sơ giấy; theo dõi phỏng vấn (nếu có) và kết quả trúng tuyển.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">5</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Giấy nhập học &amp; học phí</strong>
                      <p class="program-d4-steps__desc mb-0">Nhận giấy mời / COE, đóng học phí và các khoản theo hướng dẫn trường.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">6</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Xin visa D2-2 &amp; nhập học</strong>
                      <p class="program-d4-steps__desc mb-0">Hồ sơ visa tại ĐSQ / LSQ → nhận visa sinh viên → xuất cảnh, nhập học và đăng ký tạm trú tại Hàn.</p>
                    </div>
                  </li>
                </ol>
                <p class="program-d4-process__foot text-sm mt-4 mb-0">
                  Xem thêm: <a href="/#visa">mục Visa</a> trên trang chủ · <a href="tin-visa-d2-ho-so-chuan-bi.php">Chuẩn bị hồ sơ visa D-2</a> · <a href="he-tieng-d4.php">Hệ tiếng D4-1</a> nếu bạn chọn lộ trình qua <span lang="ko">어학당</span>.
                </p>
              </section>

              <section id="dich-vu" class="program-d4-section">
                <h2>Làm hồ sơ du học hệ đại học D2-2 cùng SUNNY</h2>
                <p>
                  SUNNY tư vấn và hỗ trợ hồ sơ <strong>cử nhân tại Hàn Quốc</strong> trọn gói: từ chọn ngành — trường đến visa và các bước trước ngày khởi hành.
                </p>
                <ul class="mb-0">
                  <li><strong>Chiến lược nộp hồ sơ:</strong> Tối ưu lộ trình D4 → D2 hoặc nộp thẳng theo điểm mạnh của bạn.</li>
                  <li><strong>Chuẩn bị hồ sơ học thuật:</strong> Checklist, dịch — công chứng, rà soát essay / mục tiêu học tập (khi cần).</li>
                  <li><strong>Phối hợp trường đối tác:</strong> Theo dõi deadline, kết quả và học bổng nhập học.</li>
                  <li><strong>Visa D2 &amp; pre-departure:</strong> Hướng dẫn chứng minh tài chính, phỏng vấn (nếu có), chỗ ở và bảo hiểm.</li>
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
