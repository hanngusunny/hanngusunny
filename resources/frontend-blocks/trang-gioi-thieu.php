<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 5,
  'slug' => 'trang-gioi-thieu',
  'name' => 'Trang giới thiệu',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:16:33',
  'config' => 
  array (
    '_fields' => 
    array (
    ),
    'html' => '  <main id="noi-dung-chinh">
    <header class="about-intro-hero page-about-hero bg-soft-orange">
      <div class="about-intro-hero__deco" aria-hidden="true"></div>
      <div class="container py-4 lg:py-5 relative">
        <nav class="page-about-breadcrumb page-about-breadcrumb--pill mb-3 lg:mb-4" aria-label="Breadcrumb">
          <ol class="page-about-breadcrumb__list">
            <li class="page-about-breadcrumb__step">
              <a href="/" class="page-about-breadcrumb__link">
                <i class="bi bi-house-door-fill page-about-breadcrumb__home-ico" aria-hidden="true"></i>
                Trang chủ
              </a>
            </li>
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">Giới thiệu</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">Giới thiệu về <span class="text-primary-sunny">SUNNY</span></h1>
            <p class="about-intro-hero__lead">
              Trung tâm du học Hàn Quốc với <strong class="about-intro-hero__strong">hệ sinh thái liền mạch</strong>
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              <strong class="about-intro-hero__strong">minh bạch chi phí</strong>, đồng hành có trách nhiệm cùng học sinh và gia đình.
            </p>
            <div class="about-intro-hero__chips" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-diagram-3" aria-hidden="true"></i> Quy trình khép kín</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i> Chi phí rõ ràng</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-people-fill" aria-hidden="true"></i> Phụ huynh &amp; học sinh</span>
            </div>
            <a href="#heading-gioi-thieu" class="about-intro-hero__cta">
              <span>Khám phá câu chuyện SUNNY</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Tại sao chọn SUNNY</p>
              <p class="about-intro-hero__panel-title">Đối tác tin cậy trên hành trình du học</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-layers"></i></span>
                  <span><strong>Một đầu mối phối hợp</strong> — từ tư vấn, tiếng Hàn đến hồ sơ và hỗ trợ sau nhập học, tránh xé lẻ thông tin.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                  <span><strong>Cam kết minh bạch</strong> — cập nhật tiến độ và chi phí để phụ huynh và học sinh chủ động quyết định.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-hand-thumbs-up"></i></span>
                  <span><strong>Trách nhiệm lâu dài</strong> — ưu tiên định hướng đúng năng lực thay vì chạy theo số lượng hồ sơ.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="intro-sunny-section py-5 bg-white" aria-labelledby="heading-gioi-thieu">
      <span class="intro-sunny-watermark" aria-hidden="true">SUNNY</span>
      <div class="container">
        <div class="intro-sunny-grid">
          <div class="intro-sunny-grid__copy">
            <div class="title-line mb-3"></div>
            <h2 id="heading-gioi-thieu" class="section-title mb-3">Trung tâm du học hàn quốc SUNNY</h2>
            <p class="sunny-tagline mb-3">
              <span class="sunny-tagline__part">Minh bạch chi phí</span>
              <span class="sunny-tagline__sep" aria-hidden="true"></span>
              <span class="sunny-tagline__part sunny-tagline__part--accent">An tâm du học</span>
            </p>
            <p class="text-black mb-3">
              SUNNY là trung tâm du học Hàn Quốc được xây dựng với một nguyên tắc cốt lõi: minh bạch về chi phí và thông tin trong mọi lộ trình du học. Trong một thị trường còn nhiều thiếu rõ ràng, SUNNY lựa chọn con đường rõ ràng hơn — tư vấn đúng, nói thật và đồng hành có trách nhiệm đến cùng với học sinh.
            </p>
            <p class="text-black mb-4">
              Chúng tôi tin rằng du học không chỉ là việc &quot;đi được&quot;, mà là một hành trình dài hạn cần định hướng đúng ngay từ đầu. Vì vậy, SUNNY không chạy theo số lượng, mà tập trung giúp mỗi học sinh hiểu rõ năng lực của mình, lựa chọn phù hợp và chuẩn bị đầy đủ cho hành trình phía trước.
            </p>
            <a href="/#vi-sao" class="btn btn-sunny btn-sunny-pulse text-white">Xem chi tiết</a>
          </div>
          <div class="intro-sunny-grid__media">
            <div class="intro-section-bg rounded-4 shadow" role="img" aria-label="Trung tâm du học Hàn Quốc SUNNY"></div>
            <div class="intro-ceo-block">
              <p class="intro-ceo-label">Thông tin CEO</p>
              <div class="intro-ceo-line" aria-hidden="true"></div>
              <p class="intro-ceo-name">Trần Thị Hồng Nhung</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-soft-orange" aria-labelledby="heading-eco">
      <div class="container">
        <header class="mb-4 lg:mb-5">
          <div class="title-line mb-3"></div>
          <h2 id="heading-eco" class="section-title mb-3">Hệ sinh thái du học liền mạch</h2>
          <p class="text-black mb-0 max-w-about about-eco-lead">
            SUNNY thiết kế một chuỗi dịch vụ khép kín — từ nền tảng tiếng Hàn, tư vấn và xử lý hồ sơ tại Việt Nam đến đồng hành khi học viên đã nhập cảnh — nhằm đảm bảo một đầu mối phối hợp, quy trình thống nhất và trách nhiệm rõ ràng trong suốt hành trình.
          </p>
        </header>
        <div class="flex flex-wrap gap-4">
          <div class="w-full md:w-4/12">
            <article class="about-eco-card h-full">
              <span class="about-eco-card__icon" aria-hidden="true"><i class="bi bi-translate"></i></span>
              <h3 class="about-eco-card__title">Nền tảng tiếng Hàn</h3>
              <p class="about-eco-card__text mb-0">
                Đào tạo trực tiếp và trực tuyến theo lộ trình có mục tiêu rõ ràng, gắn với yêu cầu thi cử và môi trường học tập thực tế tại Hàn Quốc.
              </p>
            </article>
          </div>
          <div class="w-full md:w-4/12">
            <article class="about-eco-card h-full">
              <span class="about-eco-card__icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
              <h3 class="about-eco-card__title">Tư vấn &amp; hồ sơ tại Việt Nam</h3>
              <p class="about-eco-card__text mb-0">
                Đánh giá năng lực và định hướng chương trình phù hợp; chuẩn bị và theo dõi hồ sơ theo quy trình minh bạch, cập nhật tiến độ và chi phí cho phụ huynh và học viên.
              </p>
            </article>
          </div>
          <div class="w-full md:w-4/12">
            <article class="about-eco-card h-full">
              <span class="about-eco-card__icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
              <h3 class="about-eco-card__title">Đồng hành tại Hàn Quốc</h3>
              <p class="about-eco-card__text mb-0">
                Hỗ trợ chuyển tiếp chuyên ngành, cập nhật quy định và đồng hành trong giai đoạn thích nghi học tập, hạn chế đứt gãy thông tin giữa các bước.
              </p>
            </article>
          </div>
        </div>
        <p class="text-black mb-0 mt-4 lg:mt-5 max-w-about about-eco-closing">
          Một hệ sinh thái liền mạch giúp học viên không phải tự phối hợp nhiều đơn vị rời rạc; SUNNY giữ vai trò điều phối trung tâm, đảm bảo tính liên tục và trách nhiệm giải thích từ lúc chuẩn bị cho đến khi ổn định học tập.
        </p>
      </div>
    </section>

    <section class="py-5 bg-white">
      <div class="container">
        <div class="flex flex-wrap gap-4 lg:gap-5">
          <div class="w-full md:w-6/12">
            <div class="title-line mb-3"></div>
            <h2 class="section-title mb-3">Tầm nhìn</h2>
            <p class="text-black mb-0">
              Sunny mở rộng cơ hội tiếp cận nền giáo dục Hàn Quốc cho học sinh Việt Nam, hướng tới cuộc sống tốt đẹp hơn.
            </p>
          </div>
          <div class="w-full md:w-6/12">
            <div class="title-line mb-3"></div>
            <h2 class="section-title mb-3">Sứ mệnh</h2>
            <p class="text-black mb-0">
              Sunny xây dựng hệ sinh thái du học Hàn Quốc liền mạch, đồng hành cùng học sinh Việt Nam.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-soft-orange" aria-labelledby="heading-values">
      <div class="container">
        <div class="title-line mb-3"></div>
        <h2 id="heading-values" class="section-title mb-4">Giá trị cốt lõi</h2>
        <ul class="about-value-grid list-none p-0 mb-0">
          <li class="about-value-item"><span class="about-value-item__icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span> Minh bạch — rõ ràng trong thông tin và chi phí</li>
          <li class="about-value-item"><span class="about-value-item__icon" aria-hidden="true"><i class="bi bi-award"></i></span> Chuyên nghiệp — làm việc có quy trình, có trách nhiệm</li>
          <li class="about-value-item"><span class="about-value-item__icon" aria-hidden="true"><i class="bi bi-heart"></i></span> Tận tâm, tử tế — đặt học sinh và gia đình làm trung tâm</li>
          <li class="about-value-item"><span class="about-value-item__icon" aria-hidden="true"><i class="bi bi-people"></i></span> Tôn trọng con người — lắng nghe và thấu hiểu từng hoàn cảnh</li>
          <li class="about-value-item about-value-item--wide"><span class="about-value-item__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span> Chuẩn mực chuyên môn cao — không ngừng học tập và nâng cao năng lực</li>
        </ul>
      </div>
    </section>

    <section class="py-5 bg-white">
      <div class="container text-center">
        <div class="title-line mx-auto mb-3"></div>
        <h2 class="section-title mb-3">Slogan</h2>
        <p class="sunny-tagline sunny-tagline--inline inline-flex mb-0">
          <span class="sunny-tagline__part">Minh bạch chi phí</span>
          <span class="sunny-tagline__sep" aria-hidden="true"></span>
          <span class="sunny-tagline__part sunny-tagline__part--accent">An tâm du học</span>
        </p>
      </div>
    </section>

    <section class="py-5 bg-white about-team-section about-team-section--six" aria-labelledby="heading-team">
      <div class="container">
        <header class="text-center mb-4 lg:mb-5">
          <div class="title-line mx-auto mb-3"></div>
          <h2 id="heading-team" class="section-title mb-3">Đội ngũ nhân sự</h2>
          <p class="why-sunny-intro mb-4 max-w-about mx-auto">
            Đội ngũ SUNNY quy tụ cố vấn và quản lý có kinh nghiệm hợp tác với đối tác Hàn Quốc, mạng lưới rộng các trường đại học và quy trình tư vấn nhất quán — đồng hành học viên, phụ huynh tại cả ba miền.
          </p>
        </header>

        <!-- 6-card layout: ban lãnh đạo (ảnh tròn) + 4 trưởng bộ phận -->
        <div class="flex flex-wrap justify-center items-start gap-4 lg:gap-5 mb-5">
          <div class="w-full md:w-6/12 xl:w-5/12 text-center">
            <article class="about-team-exec-card">
              <div
                class="about-team-exec-card__circle about-team-exec-card__circle--interactive mx-auto"
                tabindex="0"
                role="group"
                aria-label="Trần Thị Hồng Nhung — di chuột vào hoặc chọn ô này bằng Tab để xem các điểm nổi bật"
              >
                <img
                  src="../assets/img/avatar-ceo-02.jpg"
                  alt="Trần Thị Hồng Nhung (Rainy Tran) — Giám đốc Điều hành Du học SUNNY"
                  class="about-team-exec-card__img"
                  width="320"
                  height="320"
                  loading="lazy"
                  decoding="async"
                />
                <div class="about-team-exec-card__overlay">
                  <div class="about-team-exec-card__overlay-scrim"></div>
                  <div class="about-team-exec-card__overlay-body">
                    <ul class="about-team-exec-card__facts list-none p-0 text-left mb-0">
                      <li>Kinh nghiệm 10 năm làm việc trong các lĩnh vực kinh doanh, kinh tế với đối tác Hàn Quốc</li>
                      <li>Thông thạo ngoại ngữ tiếng Anh, tiếng Hàn</li>
                      <li>Đối tác tin cậy của 50+ trường ĐH top đầu Hàn Quốc</li>
                    </ul>
                  </div>
                </div>
              </div>
              <h3 class="about-team-exec-card__name font-semibold">
                Trần Thị Hồng Nhung <span class="about-team-exec-card__aka">(Rainy Tran)</span>
              </h3>
              <p class="about-team-exec-card__role">Giám đốc Điều hành Du học SUNNY</p>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-5/12 text-center">
            <article class="about-team-exec-card">
              <div
                class="about-team-exec-card__circle about-team-exec-card__circle--interactive mx-auto"
                tabindex="0"
                role="group"
                aria-label="Mr. Kim Yong — di chuột vào hoặc chọn ô này bằng Tab để xem các điểm nổi bật"
              >
                <img
                  src="../assets/img/kim-yong.jpg"
                  alt="Mr. Kim Yong — Giám đốc Đối ngoại Du học SUNNY"
                  class="about-team-exec-card__img about-team-exec-card__img--kim-yong"
                  width="320"
                  height="320"
                  loading="lazy"
                  decoding="async"
                />
                <div class="about-team-exec-card__overlay">
                  <div class="about-team-exec-card__overlay-scrim"></div>
                  <div class="about-team-exec-card__overlay-body">
                    <ul class="about-team-exec-card__facts list-none p-0 text-left mb-0">
                      <li>Giám đốc đối ngoại của Trung tâm du học Hàn Quốc SUNNY</li>
                      <li>Thạc sĩ trường đại học Kwangwoon</li>
                      <li>Là đối tác tin cậy của nhiều trường đại học tại Hàn Quốc</li>
                      <li>Luyện phỏng vấn đại sứ quán cho hơn 4000 học sinh tại SUNNY</li>
                    </ul>
                  </div>
                </div>
              </div>
              <h3 class="about-team-exec-card__name font-semibold">Mr. Kim Yong</h3>
              <p class="about-team-exec-card__role">Giám đốc Đối ngoại Du học SUNNY</p>
            </article>
          </div>
        </div>

        <hr class="about-team-branch-divider border-0 mx-auto my-5" />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 about-team-branch-row justify-center">
          <div class="min-w-0 flex">
            <article class="about-team-branch-card flex-1">
              <div class="about-team-branch-card__media about-team-branch-card__media--photo" role="img" aria-label="Trần Thị Thu Minh — Trưởng Bộ phận Sales CN Hà Nội" style="--branch-photo:url(../assets/tran-thi-thu-minh-e1779682079366.jpg)"></div>
              <div class="about-team-branch-card__accent" aria-hidden="true"></div>
              <div class="about-team-branch-card__foot text-center">
                <h3 class="about-team-branch-card__name font-semibold">Trần Thị Thu Minh</h3>
                <p class="about-team-branch-card__role mb-0">Trưởng Bộ phận Sales CN Hà Nội</p>
              </div>
            </article>
          </div>
          <div class="min-w-0 flex">
            <article class="about-team-branch-card flex-1">
              <div class="about-team-branch-card__media about-team-branch-card__media--photo" role="img" aria-label="Hồ Kim Yến — Trưởng Bộ phận Sales CN TP. Hồ Chí Minh" style="--branch-photo:url(../assets/img/2.jpg)"></div>
              <div class="about-team-branch-card__accent" aria-hidden="true"></div>
              <div class="about-team-branch-card__foot text-center">
                <h3 class="about-team-branch-card__name font-semibold">Hồ Kim Yến</h3>
                <p class="about-team-branch-card__role mb-0">Trưởng Bộ phận Sales CN TP.&nbsp;Hồ Chí Minh</p>
              </div>
            </article>
          </div>
          <div class="min-w-0 flex">
            <article class="about-team-branch-card flex-1">
              <div class="about-team-branch-card__media about-team-branch-card__media--photo" role="img" aria-label="Phạm Thị Bích Hồng — Trưởng Bộ phận Sales CN Đà Nẵng" style="--branch-photo:url(../assets/img/3.jpg)"></div>
              <div class="about-team-branch-card__accent" aria-hidden="true"></div>
              <div class="about-team-branch-card__foot text-center">
                <h3 class="about-team-branch-card__name font-semibold">Phạm Thị Bích Hồng</h3>
                <p class="about-team-branch-card__role mb-0">Trưởng Bộ phận Sales CN Đà Nẵng</p>
              </div>
            </article>
          </div>
          <div class="min-w-0 flex">
            <article class="about-team-branch-card flex-1">
              <div class="about-team-branch-card__media about-team-branch-card__media--photo" role="img" aria-label="Hoàng Thị Hiệp — Trưởng Bộ phận Giáo viên" style="--branch-photo:url(../assets/img/4.jpg)"></div>
              <div class="about-team-branch-card__accent" aria-hidden="true"></div>
              <div class="about-team-branch-card__foot text-center">
                <h3 class="about-team-branch-card__name font-semibold">Hoàng Thị Hiệp</h3>
                <p class="about-team-branch-card__role mb-0">Trưởng Bộ phận Giáo viên</p>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-white" aria-labelledby="heading-teachers">
      <div class="container">
        <div class="mb-4 lg:mb-5">
          <div class="text-center mb-4">
            <div class="title-line mx-auto mb-3"></div>
            <h2 id="heading-teachers" class="section-title mb-3">Đội ngũ giáo viên</h2>
          </div>
        </div>
        <div class="flex flex-wrap gap-4 about-teacher-grid">
          <div class="w-full md:w-6/12">
            <article class="about-teacher-pane about-teacher-pane--lead h-full">
              <div class="about-teacher-pane__avatar-wrap">
                <div class="about-teacher-pane__circle">
                  <img
                    class="about-teacher-pane__photo"
                    src="../assets/img/kim-yong.jpg"
                    alt="Chân dung — Thầy Kim"
                    width="176"
                    height="176"
                    loading="lazy"
                    decoding="async"
                  />
                </div>
              </div>
              <div class="about-teacher-pane__body">
                <h3 class="about-teacher-pane__name">Thầy Kim</h3>
                <p class="about-teacher-pane__role mb-3">Giám đốc đối ngoại của Trung tâm Du học Hàn Quốc SUNNY</p>
                <ul class="about-teacher-pane__facts list-none p-0 mb-0">
                  <li>Thạc sĩ trường đại học Kwangwoon</li>
                  <li>Đối tác tin cậy của nhiều trường đại học lớn tại Hàn Quốc</li>
                  <li>Luyện phỏng vấn đại sứ quán cho hơn 4000 học sinh tại SUNNY</li>
                </ul>
              </div>
            </article>
          </div>
          <div class="w-full md:w-6/12">
            <article class="about-teacher-pane about-teacher-pane--lead h-full">
              <div class="about-teacher-pane__avatar-wrap">
                <div class="about-teacher-pane__circle">
                  <img
                    class="about-teacher-pane__photo"
                    src="../assets/avatar-teacher-01.jpg"
                    width="176"
                    height="176"
                    loading="lazy"
                    decoding="async"
                  />
                </div>
              </div>
              <div class="about-teacher-pane__body">
                <h3 class="about-teacher-pane__name">Thầy Yoo</h3>
                <p class="about-teacher-pane__role mb-3">Giáo viên người Hàn thành thạo 3 thứ tiếng: Tiếng Hàn, Tiếng Việt, Tiếng Anh</p>
                <ul class="about-teacher-pane__facts list-none p-0 mb-0">
                  <li>Am hiểu văn hóa Việt Nam và Hàn Quốc</li>
                  <li>Nhiều năm kinh nghiệm trong việc giảng dạy tiếng Hàn cho người Việt</li>
                  <li>Am hiểu về các trường đại học tại Hàn Quốc để tư vấn chọn trường, chọn ngành cho học viên</li>
                </ul>
              </div>
            </article>
          </div>
          <div class="w-full md:w-6/12">
            <article class="about-teacher-pane about-teacher-pane--tier2 h-full">
              <div class="about-teacher-pane__avatar-wrap">
                <div class="about-teacher-pane__circle">
                  <img
                    class="about-teacher-pane__photo"
                    src="../assets/avatar-teacher-02.jpg"
                    width="160"
                    height="160"
                    loading="lazy"
                    decoding="async"
                  />
                </div>
                <span class="about-teacher-pane__badge">TOPIK&nbsp;6</span>
              </div>
              <div class="about-teacher-pane__body">
                <h3 class="about-teacher-pane__name">Cô Nguyễn Thảo</h3>
                <p class="about-teacher-pane__role mb-3">Giáo viên tiếng Hàn</p>
                <ul class="about-teacher-pane__facts list-none p-0 mb-0">
                  <li>Chứng chỉ TOPIK 6</li>
                  <li>Cử nhân khoa tiếng Hàn, chuyên ngành Biên - Phiên dịch của trường Đại học Ngoại ngữ - Đại học Quốc gia Hà Nội</li>
                </ul>
              </div>
            </article>
          </div>
          <div class="w-full md:w-6/12">
            <article class="about-teacher-pane about-teacher-pane--tier2 h-full">
              <div class="about-teacher-pane__avatar-wrap">
                <div class="about-teacher-pane__circle">
                  <img
                    class="about-teacher-pane__photo"
                    src="../assets/img/4.jpg"
                    alt="Chân dung — Cô Hoàng Thị Hiệp"
                    width="160"
                    height="160"
                    loading="lazy"
                    decoding="async"
                  />
                </div>
                <span class="about-teacher-pane__badge">TOPIK&nbsp;5</span>
              </div>
              <div class="about-teacher-pane__body">
                <h3 class="about-teacher-pane__name">Cô Hoàng Thị Hiệp</h3>
                <p class="about-teacher-pane__role mb-3">Giáo viên tiếng Hàn</p>
                <ul class="about-teacher-pane__facts list-none p-0 mb-0">
                  <li>Chứng chỉ Topik 5</li>
                  <li>Cử nhân Khoa Ngôn ngữ và văn hóa Hàn Quốc trường ĐH Ngoại ngữ - ĐHQG Hà Nội</li>
                  <li>Chứng chỉ bồi dưỡng giáo viên tiếng Hàn tại Việt Nam</li>
                </ul>
              </div>
            </article>
          </div>
          <div class="w-full md:w-6/12">
            <article class="about-teacher-pane about-teacher-pane--tier2 h-full">
              <div class="about-teacher-pane__avatar-wrap">
                <div class="about-teacher-pane__circle">
                  <img
                    class="about-teacher-pane__photo"
                    src="../assets/avatar-staff-04.jpg"
                    width="160"
                    height="160"
                    loading="lazy"
                    decoding="async"
                  />
                </div>
                <span class="about-teacher-pane__badge">TOPIK&nbsp;6</span>
              </div>
              <div class="about-teacher-pane__body">
                <h3 class="about-teacher-pane__name">Thầy Nguyễn Minh Sơn</h3>
                <p class="about-teacher-pane__role mb-3">Giáo viên tiếng Hàn</p>
                <ul class="about-teacher-pane__facts list-none p-0 mb-0">
                  <li>Chứng chỉ Topik 6</li>
                  <li>Cử nhân loại giỏi trường Đại học Khoa học Xã hội và Nhân Văn, ĐHQG HCM khoa Hàn Quốc học</li>
                  <li>Đạt Topik 6 nhiều kỳ liên tục. Có nhiều kinh nghiệm ôn luyện phỏng vấn LSQ và ôn thi Topik I và II</li>
                </ul>
              </div>
            </article>
          </div>
          <div class="w-full md:w-6/12">
            <article class="about-teacher-pane about-teacher-pane--tier2 h-full">
              <div class="about-teacher-pane__avatar-wrap">
                <div class="about-teacher-pane__circle">
                  <img
                    class="about-teacher-pane__photo"
                    src="../assets/avatar-teacher-04.jpg"
                    alt="Chân dung — Cô Thu Hiền"
                    width="160"
                    height="160"
                    loading="lazy"
                    decoding="async"
                  />
                </div>
                <span class="about-teacher-pane__badge">TOPIK&nbsp;5</span>
              </div>
              <div class="about-teacher-pane__body">
                <h3 class="about-teacher-pane__name">Cô Thu Hiền</h3>
                <p class="about-teacher-pane__role mb-3">Giáo viên tiếng Hàn</p>
                <ul class="about-teacher-pane__facts list-none p-0 mb-0">
                  <li>Chứng chỉ Topik 5</li>
                  <li>Cử nhân ĐH Sài Gòn</li>
                  <li>Chứng chỉ nghiệp vụ sư phạm loại giỏi</li>
                </ul>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-soft-orange about-services-section" aria-labelledby="heading-services">
      <div class="container">
        <div class="text-center mb-5">
          <div class="title-line mx-auto mb-3"></div>
          <h2 id="heading-services" class="section-title">Các dịch vụ tại SUNNY</h2>
          <p class="section-sub mx-auto mb-0">Thông tin tham khảo theo nội dung công bố tại thời điểm cập nhật — vui lòng liên hệ để được tư vấn chi tiết.</p>
        </div>
        <div class="flex flex-wrap gap-4">
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
                <h3 class="about-service-card__title">Tư vấn du học</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Tư vấn: <strong>Miễn phí</strong></span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Thông tin: <strong>Chính xác</strong></span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Thái độ: <strong>Tận tình</strong></span></li>
              </ul>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
                <h3 class="about-service-card__title">Xử lý hồ sơ tại Việt Nam</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chi phí hệ chuyên ngành D2: <strong>39.000.000</strong> (VNĐ)</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chi phí hệ tiếng D4: <strong>40.500.000</strong> (VNĐ)</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span><strong>Minh bạch, không phát sinh</strong> (theo cam kết hợp đồng)</span></li>
              </ul>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-journal-richtext"></i></span>
                <h3 class="about-service-card__title">Khóa học tiếng Hàn</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Học phí: <strong>8.000.000</strong> VNĐ/khóa (4 tháng/khóa)</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Thời gian: <strong>5 buổi/tuần</strong> (4 giờ/buổi)</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Giáo viên: <strong>Việt Nam — Hàn Quốc</strong></span></li>
              </ul>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-calendar2-event"></i></span>
                <h3 class="about-service-card__title">Workshop</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Thời gian: <strong>Định kỳ/tháng</strong></span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span><strong>Bổ ích — miễn phí</strong></span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Gắn kết cộng đồng du học sinh</span></li>
              </ul>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-house-heart"></i></span>
                <h3 class="about-service-card__title">Ký túc xá</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chi phí: <strong>6.600.000</strong> VNĐ/4 tháng</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span><strong>Tiện nghi — an toàn</strong></span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Hỗ trợ học sinh làm quen cuộc sống xa nhà</span></li>
              </ul>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-file-earmark-richtext"></i></span>
                <h3 class="about-service-card__title">XLHS lên chuyên ngành tại Hàn</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chi phí: <strong>450.000</strong> KRW</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Văn phòng SUNNY tại Hàn Quốc</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Thái độ: <strong>Nhiệt tình, nhanh chóng, chuyên nghiệp</strong></span></li>
              </ul>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-airplane"></i></span>
                <h3 class="about-service-card__title">Đưa đón sân bay tại Hàn</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chi phí: <strong>1,5 triệu</strong> VNĐ</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Dịch vụ: <strong>Tùy chọn</strong></span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Phong cách: <strong>Tận tâm, thân thiện</strong></span></li>
              </ul>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-4/12">
            <article class="about-service-card h-full">
              <div class="about-service-card__head">
                <span class="about-service-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                <h3 class="about-service-card__title">Tìm nhà trọ tại Hàn</h3>
              </div>
              <ul class="about-service-card__list list-none p-0">
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Chi phí: <strong>1 triệu</strong> VNĐ</span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Dịch vụ: <strong>Tùy chọn</strong></span></li>
                <li><span class="about-service-card__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span><span>Phong cách: <strong>Tận tâm, thân thiện</strong></span></li>
              </ul>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-white about-facilities-section hidden" aria-labelledby="heading-facilities" hidden aria-hidden="true">
      <div class="container">
        <header class="text-center mb-4 lg:mb-5">
          <div class="title-line mx-auto mb-3"></div>
          <h2 id="heading-facilities" class="section-title mb-3">Cơ sở vật chất tại SUNNY</h2>
          <p class="section-sub mx-auto mb-0">
            Không gian học tập hiện đại, trang thiết bị đầy đủ phục vụ tốt nhất cho học viên.
          </p>
        </header>

        <div
          id="facilitiesCarouselSUNNY"
          class="carousel slide carousel-facilities"
          data-tw-ride="carousel"
          data-tw-interval="6000"
          data-tw-touch="true"
          aria-label="Khám phá cơ sở vật chất SUNNY"
        >
          <div class="carousel-inner rounded-4 about-facilities-section__carousel-inner shadow-sm border border-light">
            <div class="carousel-item active">
              <div class="about-facility-slide">
                <div class="flex flex-wrap gap-0 items-stretch flex-col lg:flex-row">
                  <div class="w-full lg:w-6/12">
                    <div class="about-facility-slide__visual">
                      <img
                        src="../assets/img/video-01.jpg"
                        alt="Không gian lớp học tại SUNNY"
                        width="1280"
                        height="720"
                        loading="eager"
                        decoding="async"
                        class="about-facility-slide__img"
                      />
                    </div>
                  </div>
                  <div class="w-full lg:w-6/12 flex items-center">
                    <div class="about-facility-slide__body grow">
                      <span class="about-facility-slide__badge">Lớp học</span>
                      <h3 class="about-facility-slide__title">Không gian lớp đa chức năng</h3>
                      <p class="about-facility-slide__lead">
                        Lớp học được trang bị màn hình, loa và bảng tương tác — phù hợp cả ôn nhóm và luyện phát âm, giúp bạn nhìn nghe rõ từ mọi vị trí ngồi.
                      </p>
                      <ul class="about-facility-slide__facts list-none p-0 mb-0">
                        <li>Điều hoà không khí thoáng, ánh sáng dịu để học tập thoải mái nhiều giờ liền.</li>
                        <li>Bàn ghế linh hoạt cho các tiết giảng, thảo luận và hỗ trợ học một-một.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="carousel-item">
              <div class="about-facility-slide">
                <div class="flex flex-wrap gap-0 items-stretch flex-col lg:flex-row">
                  <div class="w-full lg:w-6/12">
                    <div class="about-facility-slide__visual">
                      <img
                        src="../assets/img/video-02.jpg"
                        alt="Khu tự học và không gian chung tại SUNNY"
                        width="1280"
                        height="720"
                        loading="lazy"
                        decoding="async"
                        class="about-facility-slide__img"
                      />
                    </div>
                  </div>
                  <div class="w-full lg:w-6/12 flex items-center">
                    <div class="about-facility-slide__body grow">
                      <span class="about-facility-slide__badge">Không gian chung</span>
                      <h3 class="about-facility-slide__title">Khu học nhóm &amp; tự ôn</h3>
                      <p class="about-facility-slide__lead">
                        Khu vực chờ và sinh hoạt chung thoáng, thuận tiện thảo luận nhóm hoặc ôn bài ngoài giờ — không khí thoải mái, kết nối học viên với nhau và với thầy cô.
                      </p>
                      <ul class="about-facility-slide__facts list-none p-0 mb-0">
                        <li>Thiết bị chia sẻ tài liệu &amp; luyện nghe có hình khi tổ chức ôn nhóm nhỏ.</li>
                        <li>Mạng nội bộ đáp ứng tra cứu tài liệu và kết nối bổ trợ lớp học.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="carousel-item">
              <div class="about-facility-slide">
                <div class="flex flex-wrap gap-0 items-stretch flex-col lg:flex-row">
                  <div class="w-full lg:w-6/12">
                    <div class="about-facility-slide__visual">
                      <img
                        src="../assets/img/1.jpg"
                        alt="Khu vực lễ tân và tư vấn tại SUNNY"
                        width="800"
                        height="600"
                        loading="lazy"
                        decoding="async"
                        class="about-facility-slide__img"
                      />
                    </div>
                  </div>
                  <div class="w-full lg:w-6/12 flex items-center">
                    <div class="about-facility-slide__body grow">
                      <span class="about-facility-slide__badge">Tiếp đón</span>
                      <h3 class="about-facility-slide__title">Lễ tân &amp; khu chờ</h3>
                      <p class="about-facility-slide__lead">
                        Không gian đón học viên và phụ huynh rõ luồng đi — thuận tiện hẹn lịch, trao đổi nhanh với cố vấn và nắm thông báo của lớp.
                      </p>
                      <ul class="about-facility-slide__facts list-none p-0 mb-0">
                        <li>Quầy tiếp đón không gây chen chúc vào các khung giờ cao điểm.</li>
                        <li>Góc đọc tài liệu &amp; thông báo được cập nhật định kỳ cho học viên.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="carousel-item">
              <div class="about-facility-slide">
                <div class="flex flex-wrap gap-0 items-stretch flex-col lg:flex-row">
                  <div class="w-full lg:w-6/12">
                    <div class="about-facility-slide__visual">
                      <img
                        src="../assets/img/2.jpg"
                        alt="Trang thiết bị hỗ trợ giảng dạy tại SUNNY"
                        width="800"
                        height="600"
                        loading="lazy"
                        decoding="async"
                        class="about-facility-slide__img"
                      />
                    </div>
                  </div>
                  <div class="w-full lg:w-6/12 flex items-center">
                    <div class="about-facility-slide__body grow">
                      <span class="about-facility-slide__badge">Đồng bộ</span>
                      <h3 class="about-facility-slide__title">Trang thiết bị hỗ trợ học tập</h3>
                      <p class="about-facility-slide__lead">
                        Chuẩn hoá hình và âm thanh theo các lớp ôn TOPIK, giao tiếp và phỏng vấn — giúp học viên thích nghi nhịp bài và giảm mệt nhìn và mệt nghe khi học dài.
                      </p>
                      <ul class="about-facility-slide__facts list-none p-0 mb-0">
                        <li>Hạ tầng hỗ trợ lớp đông và lớp nhóm nhỏ, kèm các buổi workshop định kỳ.</li>
                        <li>Tài nguyên tài liệu in và tư liệu bổ trợ được sắp xếp theo cấp trình học viên.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <button
            class="carousel-control-prev carousel-facilities__ctrl"
            type="button"
            data-tw-target="#facilitiesCarouselSUNNY"
            data-tw-slide="prev"
          >
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Slide trước</span>
          </button>
          <button
            class="carousel-control-next carousel-facilities__ctrl"
            type="button"
            data-tw-target="#facilitiesCarouselSUNNY"
            data-tw-slide="next"
          >
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Slide sau</span>
          </button>
          <div class="carousel-indicators carousel-facilities__dots">
            <button
              type="button"
              data-tw-target="#facilitiesCarouselSUNNY"
              data-tw-slide-to="0"
              class="active"
              aria-current="true"
              aria-label="Slide 1"
            ></button>
            <button type="button" data-tw-target="#facilitiesCarouselSUNNY" data-tw-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-tw-target="#facilitiesCarouselSUNNY" data-tw-slide-to="2" aria-label="Slide 3"></button>
            <button type="button" data-tw-target="#facilitiesCarouselSUNNY" data-tw-slide-to="3" aria-label="Slide 4"></button>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-white">
      <div class="container">
        <div class="about-legal-box rounded-4 p-4 p-lg-5">
          <h2 class="h5 font-bold text-bs-dark mb-3">Thông tin pháp lý</h2>
          <ul class="list-none p-0 text-sm text-black mb-0 about-legal-list">
            <li><strong>Chủ sở hữu website:</strong> Công ty TNHH Hợp Tác Phát Triển Sunny International</li>
            <li><strong>Người đại diện pháp lý:</strong> Trần Thị Hồng Nhung</li>
            <li><strong>Giấy phép kinh doanh số:</strong> 0108189720 (cấp tại Sở Kế hoạch và Đầu tư TP. Hà Nội)</li>
            <li><strong>Giấy phép tư vấn du học:</strong> 2779/GCN-SGDĐT (cấp bởi Sở Giáo dục và Đào Tạo Hà Nội)</li>
          </ul>
        </div>
        <div class="text-center mt-5">
          <a href="/#dang-ky" class="btn btn-sunny btn-sunny-pulse text-white btn-lg">Đăng ký tư vấn miễn phí</a>
        </div>
      </div>
    </section>
  </main>
',
  ),
);
