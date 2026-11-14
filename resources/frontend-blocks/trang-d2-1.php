<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 10,
  'slug' => 'trang-d2-1',
  'name' => 'Trang D2-1',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:30:18',
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
              <span class="page-about-breadcrumb__current">Hệ cao đẳng (D2-1)</span>
            </li>
          </ol>
        </nav>

        <div class="flex flex-wrap gap-4 xl:gap-5 items-stretch">
          <div class="w-full lg:w-6/12 xl:w-7/12 flex flex-col">
            <p class="program-d4-hero__eyebrow">
              <span class="program-d4-hero__eyebrow-dot" aria-hidden="true"></span>
              Chương trình du học · Visa cao đẳng
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title mb-0">Du học hệ cao đẳng (Visa D2-1)</h1>

            <div class="program-d4-hero__lang-card">
              <p class="program-d4-hero__lang-vi">
                Chương trình cao đẳng / junior college 2–3 năm — thiên thực hành nghề, thời gian ngắn hơn cử nhân và phù hợp đi làm sớm hoặc chuyển tiếp ĐH.
              </p>
            </div>

            <p class="program-d4-hero__lead text-black">
              Visa <strong>D2-1</strong> dành cho sinh viên quốc tế theo học <strong>bậc cao đẳng</strong> (전문대학) tại các cơ sở được công nhận; thường cần <strong>TOPIK</strong> ở mức cơ bản — trung cấp và hồ sơ theo từng trường — ngành nghề.
            </p>

            <div class="flex flex-wrap gap-2 mt-3 lg:mt-4">
              <a href="#gioi-thieu-d21" class="btn btn-sunny btn-sunny-pulse text-white">Tìm hiểu chương trình</a>
              <a href="#dang-ky" class="btn btn-outline-dark">Đăng ký tư vấn</a>
            </div>
          </div>

          <div class="w-full lg:w-6/12 xl:w-5/12 flex">
            <div class="program-d4-hero__stats-card w-full">
              <p class="program-d4-hero__stats-kicker">D2-1 trong một cái nhìn</p>
              <p class="program-d4-hero__stats-title">Thông tin tóm tắt</p>
              <div class="program-d4-stats" role="list">
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">D2-1</span>
                    <span class="program-d4-stat__label">Loại visa</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">2–3 năm</span>
                    <span class="program-d4-stat__label">Cao đẳng (thường)</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">Theo ĐSQ</span>
                    <span class="program-d4-stat__label">Tài chính</span>
                    <span class="program-d4-stat__hint">Sổ tiết kiệm / bảo lãnh — mức cụ thể theo hướng dẫn từng thời điểm</span>
                  </span>
                </div>
                <div class="program-d4-stat" role="listitem">
                  <span class="program-d4-stat__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                  <span class="program-d4-stat__body">
                    <span class="program-d4-stat__val">TOPIK 2–3</span>
                    <span class="program-d4-stat__label">Tham khảo</span>
                    <span class="program-d4-stat__hint">Ngành nghề thực hành; có lộ trình qua D4-1 trước</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <nav class="uni-detail-toc border-b bg-white" aria-label="Mục lục trang — Hệ cao đẳng D2-1">
      <div class="container py-2 py-lg-3">
        <ul class="uni-detail-toc__list list-none p-0 mb-0 flex flex-wrap gap-2 lg:gap-3">
          <li><a href="#gioi-thieu-d21">Giới thiệu</a></li>
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

    <article class="py-5 bg-white news-article-page program-d4-page program-d2-page program-d21-page">
      <div class="container">
        <div class="news-article text-black program-d4-article">
              <section id="gioi-thieu-d21" class="program-d4-section program-d4-intro-section" aria-labelledby="gioi-thieu-d21-heading">
                <div class="program-d4-intro-panel">
                  <header class="program-d4-intro-head">
                    <p class="program-d4-intro-kicker"><span class="program-d4-intro-kicker__dot" aria-hidden="true"></span> Junior college · Visa sinh viên cao đẳng Hàn Quốc</p>
                    <div class="title-line program-d4-intro-head__line"></div>
                    <h2 id="gioi-thieu-d21-heading" class="program-d4-intro-title">Giới thiệu hệ du học cao đẳng D2-1</h2>
                    <p class="program-d4-intro-deck">
                      Chương trình <strong>cao đẳng</strong> (전문대학) toàn thời gian — đào tạo nghề, thực hành nhiều, thời gian ngắn hơn bậc cử nhân và phù hợp mục tiêu đi làm sớm hoặc chuyển tiếp.
                    </p>
                  </header>

                  <div class="program-d4-intro-prose">
                    <p class="program-d4-intro-lead">
                      <strong>Visa D2-1</strong> là diện visa dành cho sinh viên quốc tế theo học <strong>bậc cao đẳng</strong> tại các <strong>trường cao đẳng / junior college</strong> được công nhận tại Hàn Quốc. Chương trình thường nhấn mạnh <strong>kỹ năng nghề</strong>, thực tập và ứng dụng; khung thời gian phổ biến <strong>khoảng 2–3 năm</strong> tùy ngành và quy định tín chỉ. Yêu cầu đầu vào thường gồm <strong>học lực</strong>, <strong>TOPIK</strong> ở mức cơ bản — trung cấp (từng trường quy định khác nhau), và hồ sơ xét tuyển theo đợt.
                    </p>
                    <p>
                      Ngành điển hình gồm công nghệ — kỹ thuật ứng dụng, điều dưỡng — chăm sóc sức khỏe, du lịch — khách sạn — ẩm thực, thiết kế — làm đẹp, kinh doanh — logistics, CNTT thực hành… Mỗi cơ sở công bố <strong>kỳ tuyển sinh</strong>, <strong>học phí</strong> và đôi khi <strong>học bổng nhập học</strong> riêng. SUNNY giúp bạn đối chiếu hồ sơ với tiêu chí thực tế tại thời điểm nộp.
                    </p>
                    <p>
                      Lộ trình phổ biến: <strong>D4-1 (tiếng) → D2-1 (cao đẳng)</strong> sau khi đủ TOPIK; hoặc <strong>nộp thẳng D2-1</strong> nếu đã đạt ngưỡng ngôn ngữ và học vấn. Sau cao đẳng, nhiều bạn <strong>đi làm</strong> theo ngành đã học, hoặc <strong>chuyển tiếp lên cử nhân</strong> (<a href="he-dai-hoc-d2.php">D2-2</a>) khi đủ điều kiện tín chỉ — tuyển sinh của trường đại học.
                    </p>
                    <p class="program-d4-intro-prose__foot">
                      Bằng tốt nghiệp cao đẳng do cơ sở được công nhận cấp có giá trị trong tuyển dụng kỹ thuật — dịch vụ tại Hàn và khu vực; sau tốt nghiệp bạn có thể <strong>gia hạn — chuyển đổi visa</strong> theo quy định từng thời kỳ (học tiếp, tìm việc), hoặc <strong>về nước</strong> với chứng chỉ nghề và tiếng Hàn đã qua môi trường thực tế.
                    </p>
                  </div>

                  <div class="program-d4-intro-highlights" aria-label="Tóm tắt nhanh chương trình D2-1">
                    <p class="program-d4-intro-highlights__label">Tóm tắt chương trình</p>
                    <ul class="program-d4-highlight-grid list-none p-0 mb-0" role="list">
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-passport"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Diện visa</span>
                          <strong class="program-d4-highlight__title">Visa D2-1</strong>
                          <p class="program-d4-highlight__text">Sinh viên bậc cao đẳng (junior college)</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-calendar3-range"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Thời lượng</span>
                          <strong class="program-d4-highlight__title">~2–3 năm</strong>
                          <p class="program-d4-highlight__text">Tín chỉ — thực hành — thực tập (tùy ngành)</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Ngôn ngữ</span>
                          <strong class="program-d4-highlight__title">TOPIK 2–3</strong>
                          <p class="program-d4-highlight__text">Tham khảo; có thể qua <a href="he-tieng-d4.php">D4-1</a> trước</p>
                        </div>
                      </li>
                      <li class="program-d4-highlight program-d4-highlight--accent" role="listitem">
                        <span class="program-d4-highlight__icon" aria-hidden="true"><i class="bi bi-arrow-right-circle"></i></span>
                        <div class="program-d4-highlight__body">
                          <span class="program-d4-highlight__eyebrow">Lộ trình</span>
                          <strong class="program-d4-highlight__title">Nghề sớm hoặc lên ĐH</strong>
                          <p class="program-d4-highlight__text">Đi làm sau tốt nghiệp hoặc chuyển tiếp <a href="he-dai-hoc-d2.php">D2-2</a></p>
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
                      Visa <strong>D2-1</strong> phù hợp khi bạn muốn <strong>học nghề — cao đẳng chính quy</strong> tại Hàn Quốc, ưu tiên thực hành và thời gian đào tạo ngắn hơn cử nhân. Dưới đây là các nhóm điển hình; tiêu chí cụ thể tùy trường — kỳ tuyển sinh — SUNNY hỗ trợ rà soát hồ sơ cá nhân.
                    </p>
                  </div>
                </header>

                <div class="program-d4-audience__grid" role="list">
                  <article class="program-d4-audience-card program-d4-audience-card--thpt" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-backpack"></i></span>
                    <h3 class="program-d4-audience-card__title">Học sinh tốt nghiệp THPT (hoặc tương đương)</h3>
                    <p class="program-d4-audience-card__text">
                      Muốn <strong>vào cao đẳng</strong> với TOPIK đạt ngưỡng cơ bản, hoặc <strong>D4-1 trước — D2-1 sau</strong> để củng cố tiếng rồi nhập học ngành nghề mục tiêu.
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--uni" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
                    <h3 class="program-d4-audience-card__title">Người đã học một thời gian, muốn chuyển hướng nghề</h3>
                    <p class="program-d4-audience-card__text">
                      Cần <strong>bằng cao đẳng</strong> thực hành tại Hàn; nộp hồ sơ xét miễn — chuyển tín chỉ theo quy định từng cơ sở (khi áp dụng).
                    </p>
                  </article>

                  <article class="program-d4-audience-card program-d4-audience-card--career" role="listitem">
                    <span class="program-d4-audience-card__icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
                    <h3 class="program-d4-audience-card__title">Người muốn đi làm sớm trong lĩnh vực kỹ thuật — dịch vụ</h3>
                    <p class="program-d4-audience-card__text">
                      Ưu tiên <strong>thực tập — kỹ năng nghề</strong> và chi phí — thời gian hợp lý; có thể nối tiếp lên <a href="he-dai-hoc-d2.php">cử nhân</a> sau này.
                    </p>
                  </article>
                </div>

                <div class="program-d4-audience__note">
                  <i class="bi bi-info-circle-fill program-d4-audience__note-ico" aria-hidden="true"></i>
                  <p class="program-d4-audience__note-text mb-0">
                    <strong>Lưu ý:</strong> Một số ngành (ví dụ điều dưỡng, kỹ thuật đặc thù) có thể có <strong>yêu cầu sức khỏe — chứng chỉ bổ sung</strong>. Hãy kiểm tra sớm với tư vấn SUNNY trước kỳ nộp hồ sơ.
                  </p>
                </div>
              </section>

              <section id="dieu-kien" class="program-d4-section program-d4-requirements-section" aria-labelledby="dieu-kien-heading">
                <header class="program-d4-requirements__head">
                  <div class="title-line mb-3"></div>
                  <h2 id="dieu-kien-heading" class="program-d4-requirements__title">Điều kiện du học hệ cao đẳng D2-1</h2>
                </header>
                <div class="program-d4-conditions" role="list">
                  <article class="program-d4-condition program-d4-condition--edu" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                      <h3 class="program-d4-condition__title">Học vấn</h3>
                    </div>
                    <p class="program-d4-condition__text">Tốt nghiệp THPT hoặc tương đương; học bạ — bảng điểm theo yêu cầu từng trường cao đẳng. Một số ngành nghề yêu cầu nền tảng môn học liên quan.</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--finance" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                      <h3 class="program-d4-condition__title">Tài chính</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Chứng minh khả năng chi trả <strong>học phí và sinh hoạt</strong> theo hướng dẫn ĐSQ/LSQ và nhà trường từng thời điểm (sổ tiết kiệm, bảo lãnh, học bổng…). Mức cụ thể SUNNY cập nhật khi bạn chuẩn bị hồ sơ.
                    </p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--health" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-translate"></i></span>
                      <h3 class="program-d4-condition__title">Ngôn ngữ</h3>
                    </div>
                    <p class="program-d4-condition__text"><strong>TOPIK</strong> thường từ mức cơ bản — trung cấp (tùy trường — ngành). Có thể học <a href="he-tieng-d4.php">D4-1</a> trước để đạt ngưỡng; một số chương trình có phần song ngữ hoặc tiếng Anh hỗ trợ.</p>
                  </article>
                  <article class="program-d4-condition program-d4-condition--interview" role="listitem">
                    <div class="program-d4-condition__top">
                      <span class="program-d4-condition__icon" aria-hidden="true"><i class="bi bi-mic"></i></span>
                      <h3 class="program-d4-condition__title">Phỏng vấn &amp; visa</h3>
                    </div>
                    <p class="program-d4-condition__text">
                      Một số cơ sở phỏng vấn hoặc kiểm tra năng khiếu (ngành nghệ thuật); khi xin visa sinh viên cần tuân thủ quy định ĐSQ/LSQ. SUNNY đồng hành checklist hồ sơ và chuẩn bị phỏng vấn (khi có).
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
                      <span class="program-d4-curriculum__panel-kicker">Khung thời gian</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      Cao đẳng Hàn Quốc thường tổ chức theo <strong>hai học kỳ chính</strong> mỗi năm; sinh viên tích lũy <strong>tín chỉ</strong> với tỷ lệ <strong>thực hành — thực tập</strong> cao hơn so với nhiều chương trình cử nhân. Thời gian tốt nghiệp phổ biến <strong>khoảng 2–3 năm</strong> tùy ngành và số tín chỉ.
                    </p>
                  </div>
                  <div class="program-d4-curriculum__panel program-d4-curriculum__panel--schedule">
                    <div class="program-d4-curriculum__panel-head">
                      <span class="program-d4-curriculum__panel-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                      <span class="program-d4-curriculum__panel-kicker">Nghề &amp; hỗ trợ</span>
                    </div>
                    <p class="program-d4-curriculum__panel-text mb-0">
                      Ngoài lý thuyết, nhiều ngành có <strong>xưởng thực hành, lab ứng dụng, thực tập doanh nghiệp</strong>. Văn phòng hỗ trợ sinh viên quốc tế hỗ trợ visa, chỗ ở và hòa nhập — bạn nên chủ động tham gia hoạt động nghề nghiệp (career) của trường.
                    </p>
                  </div>
                </div>

                <h3 class="program-d4-curriculum__levels-heading">Định hướng theo năm (tham khảo)</h3>
                <ul class="program-d4-levels program-d4-levels--grid list-none p-0" role="list">
                  <li class="program-d4-level program-d4-level--1" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">N1</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm nhất — nền tảng nghề</p>
                      <p class="program-d4-level__desc mb-0">Môn nền, an toàn — quy trình ngành, tiếng Hàn chuyên ngành cơ bản; làm quen môi trường thực hành.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--2" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">N2</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm hai — kỹ năng cốt lõi</p>
                      <p class="program-d4-level__desc mb-0">Học phần chuyên môn chính, dự án nhóm, thực hành tăng dần; có thể bắt đầu thực tập ngắn (tùy chương trình).</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--3" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">N3</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Năm ba — thực tập &amp; tốt nghiệp</p>
                      <p class="program-d4-level__desc mb-0">Hoàn thiện tín chỉ, <strong>thực tập dài hơn hoặc đồ án tốt nghiệp</strong> (nếu có); chuẩn bị chứng chỉ nghề, việc làm hoặc hồ sơ chuyển tiếp ĐH.</p>
                    </div>
                  </li>
                  <li class="program-d4-level program-d4-level--4" role="listitem">
                    <span class="program-d4-level__badge" aria-hidden="true">+</span>
                    <div class="program-d4-level__body">
                      <p class="program-d4-level__title">Sau cao đẳng</p>
                      <p class="program-d4-level__desc mb-0">Ở lại làm việc theo ngành đã học, hoặc nộp hồ sơ <a href="he-dai-hoc-d2.php">D2-2</a> / <a href="he-thac-si-d2-3.php">D2-3</a> khi đủ điều kiện — tùy mục tiêu.</p>
                    </div>
                  </li>
                </ul>

                <div class="program-d4-curriculum__foot">
                  <p class="program-d4-curriculum__foot-text mb-0">
                    <strong>Chọn ngành:</strong> CNTT ứng dụng, điện — cơ khí, du lịch — ẩm thực, làm đẹp, logistics, điều dưỡng… SUNNY giúp bạn <strong>đối chiếu năng lực — nguyện vọng</strong> với yêu cầu tuyển sinh thực tế.
                    <span class="program-d4-curriculum__foot-muted">Chi tiết môn học và phân bổ tín chỉ lấy theo chương trình đào tạo chính thức của từng khoa.</span>
                  </p>
                </div>
              </section>

              <section id="chi-phi" class="program-d4-section">
                <h2>Chi phí du học hệ cao đẳng D2-1</h2>
                <p class="text-black mb-3">
                  Dưới đây là <strong>các khoản chi thường gặp</strong> khi theo học <strong>cao đẳng / junior college</strong> (visa <strong>D2-1</strong>). Học phí bậc cao đẳng thường <strong>thấp hơn cử nhân</strong> trong cùng khu vực, nhưng tổng ngân sách vẫn phụ thuộc <strong>thành phố</strong>, <strong>ngành</strong> (có ngành tốn vật tư, thực hành) và <strong>lối sống</strong>. Số liệu tham khảo — luôn đối chiếu thông báo tuyển sinh và hợp đồng từng trường.
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
                        <td colspan="2" class="text-bs-secondary text-sm">Niên khóa tính theo năm học trường công bố; có thể đóng theo học kỳ.</td>
                      </tr>
                      <tr>
                        <td>Học phí cao đẳng / năm học</td>
                        <td>~4.000.000 – 9.000.000+ KRW</td>
                        <td>Quy đổi thường <strong>~75 – 170+ triệu VNĐ/năm</strong> (tùy tỷ giá); ngành kỹ thuật — y tế — thực phẩm đôi khi cao hơn mức nền.</td>
                      </tr>
                      <tr>
                        <td>Phí nhập học — xét hồ sơ (một lần hoặc theo kỳ)</td>
                        <td>~100.000 – 350.000+ KRW</td>
                        <td>Mỗi trường quy định riêng; có nơi gộp vào học kỳ đầu.</td>
                      </tr>
                      <tr>
                        <td>Sách, đồng phục, vật tư thực hành / năm</td>
                        <td>~500.000 – 2.000.000+ KRW</td>
                        <td>Ngành nghề — số môn thực hành càng nhiều, chi phí vật liệu càng tăng.</td>
                      </tr>
                      <tr>
                        <td>Học bổng nhập học / giảm học phí</td>
                        <td>Theo trường</td>
                        <td>Không phải ai cũng đủ điều kiện; có thể <strong>giảm đáng kể</strong> học phí năm đầu — cần đọc kỹ điều kiện TOPIK, GPA, hạn nộp.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm chỗ ở &amp; sinh hoạt</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Seoul, Incheon, Busan… thường đắt hơn các thành phố vừa và nhỏ.</td>
                      </tr>
                      <tr>
                        <td>Ký túc xá trường / tháng</td>
                        <td>~600.000 – 1.400.000+ KRW</td>
                        <td>Ước <strong>~3 – 8+ triệu VNĐ/tháng</strong>; chỗ có hạn, đăng ký sớm sau khi có thư nhập học.</td>
                      </tr>
                      <tr>
                        <td>Thuê nhà trọ — phòng chia sẻ / tháng</td>
                        <td>~400.000 – 1.100.000+ KRW</td>
                        <td>Thường có <strong>cọc 1–3 tháng</strong>; giá phụ thuộc khoảng cách đến trường và tiện ích.</td>
                      </tr>
                      <tr>
                        <td>Bảo hiểm y tế bắt buộc (sinh viên) / tháng</td>
                        <td>~55.000 – 100.000 KRW</td>
                        <td>Sau nhập cảnh, đăng ký theo hướng dẫn; mức đóng có thể thay đổi theo chính sách hằng năm.</td>
                      </tr>
                      <tr>
                        <td>Ăn uống / tháng</td>
                        <td>3,5 – 10 triệu VNĐ</td>
                        <td>Tự nấu tiết kiệm hơn; nhà trường có căng-tin — quán khu trường giúp kiểm soát chi.</td>
                      </tr>
                      <tr>
                        <td>Đi lại &amp; liên lạc / tháng</td>
                        <td>~1,5 – 4 triệu VNĐ</td>
                        <td>Thẻ tàu xe + data điện thoại; ở xa trung tâm có thể tăng thời gian đi lại nhưng giảm tiền thuê nhà.</td>
                      </tr>
                      <tr>
                        <td><strong>Tổng sinh hoạt gợi ý / tháng</strong> (ăn + ở + đi + phụ)</td>
                        <td>~8 – 22+ triệu VNĐ</td>
                        <td>Khoảng <strong>~8 – 18 triệu</strong> phổ biến với sinh viên tiết kiệm; Seoul hoặc lối sống năng động dễ vượt mức này.</td>
                      </tr>
                      <tr>
                        <td>Chi phí tháng đầu (cọc, đồ dùng, thiết lập chỗ ở)</td>
                        <td>~5 – 18 triệu VNĐ</td>
                        <td>Tháng đầu thường <strong>cao hơn bình quân</strong> vì cọc nhà và mua sắm ban đầu.</td>
                      </tr>
                      <tr>
                        <th scope="row" class="font-semibold text-left">Nhóm hành chính &amp; di chuyển</th>
                        <td colspan="2" class="text-bs-secondary text-sm">Biến động theo đợt nộp hồ sơ và giá vé.</td>
                      </tr>
                      <tr>
                        <td>Hồ sơ: dịch thuật, công chứng, chứng minh tài chính</td>
                        <td>~8 – 25 triệu VNĐ</td>
                        <td>Tùy khối lượng giấy tờ và yêu cầu chứng nhận; kèm phí dịch vụ nộp (nếu dùng).</td>
                      </tr>
                      <tr>
                        <td>Lệ phí visa D2-1 &amp; xét duyệt</td>
                        <td>Theo biểu phí ĐSQ</td>
                        <td>Áp dụng theo <strong>từng thời điểm</strong> — SUNNY cập nhật khi bạn chuẩn bị nộp.</td>
                      </tr>
                      <tr>
                        <td>Vé máy bay khứ hồi</td>
                        <td>~10 – 26 triệu VNĐ</td>
                        <td>Mùa cao điểm và đặt sát ngày thường đắt hơn; có thể dự trù chiều về linh hoạt theo kỳ nghỉ.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p class="program-cost-disclaimer" role="note">
                  Bảng mang tính tham khảo; tỷ giá và học phí có thể thay đổi. Liên hệ <a href="tel:0981698292">0981 698 292</a> hoặc <a href="lien-he.php">đăng ký tư vấn</a>.
                </p>
              </section>

              <section id="truong" class="program-d4-section">
                <h2>Trường cao đẳng &amp; lựa chọn ngành nghề</h2>
                <p>
                  Sinh viên visa <strong>D2-1</strong> nhập học vào các <strong>chương trình cao đẳng / junior college</strong> (전문대학) được công nhận. Mỗi cơ sở công bố <strong>kỳ xét tuyển</strong>, <strong>ngành nghề</strong>, <strong>học phí</strong> và đôi khi <strong>học bổng nhập học</strong> cho sinh viên quốc tế.
                </p>
                <p>
                  Khi chọn trường, nên cân nhắc <strong>độ sát thực tập — tỷ lệ việc làm ngành</strong>, học phí, vị trí và khả năng <strong>chuyển tiếp lên <a href="he-dai-hoc-d2.php">cử nhân</a></strong> sau này (nếu có kế hoạch). Nếu bạn đang học <a href="he-tieng-d4.php">D4-1</a>, có thể nối tiếp <strong>D2-1</strong> khi đủ TOPIK và điều kiện tuyển sinh. SUNNY so sánh phương án theo hồ sơ thực tế của bạn.
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
                        <span class="program-d4-uni-card__name">Yeungjin University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Howon University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Gunsan</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Songwon University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Gwangju</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Dongyang Mirae University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Incheon Technical College</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Incheon</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Daegu Health College</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Daegu</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Seojeong University</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Pyeongtaek</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Chungkang College of Cultural Industries</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Seoul</span>
                      </div>
                    </div>
                  </li>
                  <li class="w-full md:w-6/12 xl:w-4/12">
                    <div class="program-d4-uni-card h-full">
                      <span class="program-d4-uni-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                      <div class="program-d4-uni-card__body">
                        <span class="program-d4-uni-card__name">Jeonju Kijeon College</span>
                        <span class="program-d4-uni-card__loc"><i class="bi bi-geo-alt" aria-hidden="true"></i> Jeonju</span>
                      </div>
                    </div>
                  </li>
                </ul>
                <div class="uni-detail-note text-sm mt-4 mb-0">
                  <strong>Gợi ý:</strong> Kết hợp xem thêm <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học</a> và <a href="truong-dai-hoc.php">danh sách trường</a> trên SUNNY; xác nhận với tư vấn <strong>ngành D2-1</strong> còn tuyển từng kỳ.
                </div>
                <div class="sunny-explore-universities-cta mt-4">
                  <a href="truong-dai-hoc.php" class="sunny-explore-universities-cta__link">
                    <span class="sunny-explore-universities-cta__icon" aria-hidden="true"><i class="bi bi-buildings"></i></span>
                    <span class="sunny-explore-universities-cta__body">
                      Xem thêm <span class="sunny-explore-universities-cta__strong">trường đối tác</span> &amp; <span class="sunny-explore-universities-cta__strong">hướng dẫn chọn cơ sở</span><span class="sunny-explore-universities-cta__accent"> trên SUNNY</span>
                    </span>
                    <span class="sunny-explore-universities-cta__go" aria-hidden="true"><i class="bi bi-arrow-right-short"></i></span>
                  </a>
                </div>
              </section>

              <section id="sau-tot-nghiep" class="program-d4-section program-d4-after">
                <h2>Sau khi tốt nghiệp cao đẳng</h2>
                <p class="program-d4-after__lead text-black">
                  Bằng cao đẳng (associate) từ Hàn Quốc hỗ trợ <strong>ứng tuyển vị trí kỹ thuật — dịch vụ</strong> (theo visa — giấy phép từng giai đoạn), <strong>chuyển tiếp lên cử nhân</strong> hoặc <strong>về Việt Nam</strong> với chứng chỉ nghề và tiếng Hàn thực chiến.
                </p>
                <div class="flex flex-wrap gap-4 program-d4-after__grid">
                  <div class="w-full lg:w-6/12">
                    <article class="program-d4-outcome-card h-full">
                      <header class="program-d4-outcome-card__head">
                        <span class="program-d4-outcome-card__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                        <h3 class="program-d4-outcome-card__title">Làm việc tại Hàn hoặc học tiếp bậc ĐH</h3>
                      </header>
                      <p class="program-d4-outcome-card__text">
                        Nhiều bạn tìm việc đúng <strong>ngành đã học</strong> hoặc nộp <strong>chuyển tiếp D2-2</strong> khi đủ tín chỉ — điều kiện của trường đại học; cần cập nhật <strong>visa</strong> theo quy định hiện hành.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chuyển đổi từ <strong>D2-1</strong> sang diện phù hợp khi tốt nghiệp / có việc / học tiếp</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Tận dụng <strong>hỗ trợ việc làm</strong> của trường cao đẳng</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Nâng <strong>TOPIK</strong> nếu hướng cử nhân hoặc ngành yêu cầu cao</span></li>
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
                        Bằng cao đẳng nghề kết hợp tiếng Hàn phù hợp tuyển dụng <strong>kỹ thuật viên, điều dưỡng, du lịch — F&amp;B, vận hành</strong> trong doanh nghiệp Hàn — liên doanh.
                      </p>
                      <ul class="program-d4-outcome-card__checks list-none p-0 mb-0">
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Vận hành sản xuất — chất lượng — bảo trì</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Dịch vụ khách hàng — logistics — nhà hàng khách sạn</span></li>
                        <li><span class="program-d4-outcome-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Tiếp tục học liên thông trong nước (khi được công nhận tương đương)</span></li>
                      </ul>
                    </article>
                  </div>
                </div>
              </section>

              <section id="quy-trinh" class="program-d4-section program-d4-process">
                <h2>Quy trình nộp hồ sơ cao đẳng (D2-1)</h2>
                <p class="program-d4-process__lead mb-4">Thời gian thường <strong>3–7 tháng</strong> (tùy kỳ tuyển sinh, thời gian xử lý của trường và lịch visa ĐSQ / LSQ).</p>
                <ol class="program-d4-steps mb-0">
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">1</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Liên hệ SUNNY</strong>
                      <p class="program-d4-steps__desc mb-0">Đăng ký tư vấn, nêu ngành nghề dự kiến, học bạ và mục tiêu TOPIK (hoặc lộ trình D4-1).</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">2</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Định hướng trường — ngành nghề</strong>
                      <p class="program-d4-steps__desc mb-0">So sánh 2–3 phương án phù hợp năng lực; kế hoạch D4 trước hoặc nộp thẳng D2-1.</p>
                    </div>
                  </li>
                  <li class="program-d4-steps__item">
                    <div class="program-d4-steps__rail">
                      <span class="program-d4-steps__num">3</span>
                    </div>
                    <div class="program-d4-steps__card">
                      <strong class="program-d4-steps__title">Chuẩn bị hồ sơ xét tuyển</strong>
                      <p class="program-d4-steps__desc mb-0">Học bạ, bằng tốt nghiệp THPT, chứng chỉ ngôn ngữ, sơ yếu lý lịch, portfolio / bài kiểm tra năng khiếu (nếu ngành yêu cầu)…</p>
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
                      <strong class="program-d4-steps__title">Xin visa D2-1 &amp; nhập học</strong>
                      <p class="program-d4-steps__desc mb-0">Hồ sơ visa tại ĐSQ / LSQ → nhận visa sinh viên → xuất cảnh, nhập học và đăng ký tạm trú tại Hàn.</p>
                    </div>
                  </li>
                </ol>
                <p class="program-d4-process__foot text-sm mt-4 mb-0">
                  Xem thêm: <a href="/#visa">mục Visa</a> trên trang chủ · <a href="tin-visa-d2-ho-so-chuan-bi.php">Chuẩn bị hồ sơ visa D-2</a> · <a href="he-tieng-d4.php">Hệ tiếng D4-1</a> nếu bạn chọn lộ trình qua <span lang="ko">어학당</span>.
                </p>
              </section>

              <section id="dich-vu" class="program-d4-section">
                <h2>Làm hồ sơ du học hệ cao đẳng D2-1 cùng SUNNY</h2>
                <p>
                  SUNNY tư vấn và hỗ trợ hồ sơ <strong>cao đẳng tại Hàn Quốc</strong> trọn gói: từ chọn ngành nghề — cơ sở đào tạo đến visa và các bước trước ngày khởi hành.
                </p>
                <ul class="mb-0">
                  <li><strong>Chiến lược nộp hồ sơ:</strong> Tối ưu lộ trình D4 → D2-1 hoặc nộp thẳng theo TOPIK và học lực.</li>
                  <li><strong>Chuẩn bị hồ sơ:</strong> Checklist, dịch — công chứng, rà soát mục tiêu nghề nghiệp (khi cần).</li>
                  <li><strong>Phối hợp trường đối tác:</strong> Theo dõi deadline, kết quả và học bổng nhập học (khi có).</li>
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
