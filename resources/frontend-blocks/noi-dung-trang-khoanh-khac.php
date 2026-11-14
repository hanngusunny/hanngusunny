<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 6,
  'slug' => 'noi-dung-trang-khoanh-khac',
  'name' => 'Nội dung trang khoảnh khắc',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:18:26',
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
              <span class="page-about-breadcrumb__current">Khoảnh khắc đáng nhớ</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <p class="about-intro-hero__kicker">
              <span class="about-intro-hero__kicker-icon" aria-hidden="true"><i class="bi bi-camera-fill"></i></span>
              Du học Hàn Quốc · Khoảnh khắc học viên
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">Khoảnh khắc <span class="text-primary-sunny">đáng nhớ</span></h1>
            <p class="about-intro-hero__lead">
              Hành trình du học Hàn Quốc của <strong class="about-intro-hero__strong">học viên SUNNY</strong>
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              từ chuẩn bị tại <strong class="about-intro-hero__strong">Việt Nam</strong> đến trải nghiệm học tập và cuộc sống tại <strong class="about-intro-hero__strong">xứ sở kim chi</strong>.
            </p>
            <div class="about-intro-hero__chips" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-airplane" aria-hidden="true"></i> Chuẩn bị &amp; cất cánh</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-mortarboard" aria-hidden="true"></i> Học tập tại HQ</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-heart-fill" aria-hidden="true"></i> Sống tại Kim chi</span>
            </div>
            <a href="#heading-buoc-1" class="about-intro-hero__cta">
              <span>Xem từng chặng hành trình</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Qua ống kính SUNNY</p>
              <p class="about-intro-hero__panel-title">Mỗi bước đi đều đáng được ghi nhận</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-house-heart"></i></span>
                  <span><strong>Gốc Việt Nam</strong> — lớp học, hồ sơ và những buổi chia sẻ trước ngày lên đường.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-building"></i></span>
                  <span><strong>Campus &amp; thành phố</strong> — giảng đường, thư viện và nhịp sống nơi bạn học tập mỗi ngày.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-stars"></i></span>
                  <span><strong>Kỷ niệm &amp; cộng đồng</strong> — khoảnh khắc đồng hành cùng SUNNY và bạn bè quốc tế.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="py-5 bg-white border-b">
      <div class="container">
        <p class="text-black lead text-base mb-0 text-center" style="max-width: 48rem; margin-left: auto; margin-right: auto;">
          Mọi hành trình vạn dặm đều bắt đầu từ bước chân đầu tiên. Tại SUNNY, bước chân ấy được đặt vững chắc ngay trên quê hương — với tiếng Hàn, hồ sơ minh bạch và sự đồng hành tận tâm trước ngày cất cánh. Hơn <strong>4.000 học viên</strong> đã và đang học tập tại Hàn Quốc; hành trình của họ đều bắt đầu từ đây.
        </p>
      </div>
    </section>

    <!-- 01 -->
    <section class="py-5 bg-soft-orange" aria-labelledby="heading-buoc-1">
      <div class="container">
        <div class="flex flex-wrap gap-4 lg:gap-5 items-start">
          <div class="w-full lg:w-4/12 lg:text-left text-center">
            <p class="display-4 font-bold text-primary-sunny mb-0 leading-none" aria-hidden="true">01</p>
            <h2 id="heading-buoc-1" class="section-title mt-2 mb-0">Chuẩn bị tại Việt Nam</h2>
          </div>
          <div class="w-full lg:w-8/12">
            <p class="text-black mb-4">
              Tại SUNNY, bạn được định hướng rõ ràng từ tiếng Hàn, chọn trường — ngành, đến hoàn thiện hồ sơ và tham gia các buổi hội thảo — giao lưu trước khi bay.
            </p>
            <h3 class="h5 font-bold text-bs-dark mb-3">Bốn trụ cột chuẩn bị cùng SUNNY</h3>
            <div class="flex flex-wrap gap-3">
              <div class="w-full md:w-6/12">
                <article class="about-person-card about-person-card--compact h-full border-0 bg-white shadow-sm">
                  <p class="text-primary-sunny mb-1"><i class="bi bi-translate fs-4" aria-hidden="true"></i></p>
                  <h4 class="about-person-card__name h6">Học tiếng Hàn</h4>
                  <p class="text-black text-sm mb-0">Lớp học bài bản, luyện phát âm và giao tiếp với giáo viên giàu kinh nghiệm — nền tảng vững trước khi sang Hàn.</p>
                </article>
              </div>
              <div class="w-full md:w-6/12">
                <article class="about-person-card about-person-card--compact h-full border-0 bg-white shadow-sm">
                  <p class="text-primary-sunny mb-1"><i class="bi bi-building-check fs-4" aria-hidden="true"></i></p>
                  <h4 class="about-person-card__name h6">Tư vấn &amp; chọn trường</h4>
                  <p class="text-black text-sm mb-0">Định hướng ngành, so sánh trường và học bổng phù hợp năng lực — chọn đúng hướng ngay từ đầu.</p>
                </article>
              </div>
              <div class="w-full md:w-6/12">
                <article class="about-person-card about-person-card--compact h-full border-0 bg-white shadow-sm">
                  <p class="text-primary-sunny mb-1"><i class="bi bi-folder2-open fs-4" aria-hidden="true"></i></p>
                  <h4 class="about-person-card__name h6">Hoàn thiện hồ sơ</h4>
                  <p class="text-black text-sm mb-0">Dịch thuật, công chứng, chứng minh tài chính — SUNNY đồng hành từng bước, chi phí minh bạch.</p>
                </article>
              </div>
              <div class="w-full md:w-6/12">
                <article class="about-person-card about-person-card--compact h-full border-0 bg-white shadow-sm">
                  <p class="text-primary-sunny mb-1"><i class="bi bi-people fs-4" aria-hidden="true"></i></p>
                  <h4 class="about-person-card__name h6">Hội thảo &amp; giao lưu</h4>
                  <p class="text-black text-sm mb-0">Gặp cựu du học sinh, luyện phỏng vấn và chuẩn bị tâm lý — sẵn sàng trước ngày bay.</p>
                </article>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 02 -->
    <section class="py-5 bg-white" aria-labelledby="heading-buoc-2">
      <div class="container">
        <div class="flex flex-wrap gap-4 lg:gap-5 items-start mb-4">
          <div class="w-full lg:w-4/12 lg:text-left text-center">
            <p class="display-4 font-bold text-primary-sunny mb-0 leading-none" aria-hidden="true">02</p>
            <h2 id="heading-buoc-2" class="section-title mt-2 mb-0">Lên đường &amp; hòa nhập</h2>
          </div>
          <div class="w-full lg:w-8/12">
            <p class="text-black mb-0">
              Những ngày đầu đặt chân tới Hàn Quốc — từ sân bay đến ký túc xá, trường tiếng và thành phố mới. Học viên SUNNY được trường và đội ngũ hỗ trợ tận tình trong từng bước.
            </p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3 lg:gap-4">
          <div class="w-full md:w-6/12 xl:w-3/12">
            <div class="rounded-4 border bg-soft-orange p-4 h-full">
              <span class="badge bg-primary-sunny text-white rounded-full mb-2">1</span>
              <h3 class="h6 font-bold text-bs-dark">Lên đường</h3>
              <p class="text-black text-sm mb-0">Bay từ Việt Nam đến Hàn Quốc. Trường hoặc đơn vị hỗ trợ có thể hướng dẫn đón tại sân bay (Incheon / Gimpo) — học viên được báo trước lịch và điểm đón.</p>
            </div>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <div class="rounded-4 border bg-soft-orange p-4 h-full">
              <span class="badge bg-primary-sunny text-white rounded-full mb-2">2</span>
              <h3 class="h6 font-bold text-bs-dark">Nhận phòng ký túc xá</h3>
              <p class="text-black text-sm mb-0">Vào ở ký túc xá của trường, làm quen bạn cùng phòng và nội quy — tổ ấm đầu tiên tại Hàn.</p>
            </div>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <div class="rounded-4 border bg-soft-orange p-4 h-full">
              <span class="badge bg-primary-sunny text-white rounded-full mb-2">3</span>
              <h3 class="h6 font-bold text-bs-dark">Orientation</h3>
              <p class="text-black text-sm mb-0">Tham gia buổi giới thiệu trường, lớp học và quy định. Làm thẻ sinh viên, mua sắm đồ dùng học tập cơ bản.</p>
            </div>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <div class="rounded-4 border bg-soft-orange p-4 h-full">
              <span class="badge bg-primary-sunny text-white rounded-full mb-2">4</span>
              <h3 class="h6 font-bold text-bs-dark">Tuần đầu học tập</h3>
              <p class="text-black text-sm mb-0">Vào lớp tiếng Hàn, làm quen giáo viên và bạn bè quốc tế; khám phá siêu thị, giao thông và ổn định nhịp sinh hoạt.</p>
            </div>
          </div>
        </div>
        <div class="flex flex-wrap gap-3 mt-2">
          <div class="w-full md:w-6/12">
            <div class="relative w-full aspect-video rounded-4 overflow-hidden shadow-sm">
              <img class="object-fit-cover" src="../assets/img/du-hoc-bay-han-quoc.jpg" alt="Học viên SUNNY tại sân bay — lên đường du học Hàn Quốc" width="960" height="540" loading="lazy" decoding="async" />
            </div>
            <p class="text-sm text-bs-secondary mt-2 mb-0 text-center">Du học Hàn Quốc — Lên đường</p>
          </div>
          <div class="w-full md:w-6/12">
            <div class="relative w-full aspect-video rounded-4 overflow-hidden shadow-sm">
              <img class="object-fit-cover" src="../assets/img/truong-dai-hoc-han-quoc.jpg" alt="Khuôn viên đại học Hàn Quốc mùa đông — tuyết và kiến trúc học đường" width="960" height="540" loading="lazy" decoding="async" />
            </div>
            <p class="text-sm text-bs-secondary mt-2 mb-0 text-center">Trường đại học Hàn Quốc</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 03 -->
    <section class="py-5 bg-soft-orange" aria-labelledby="heading-buoc-3">
      <div class="container">
        <div class="flex flex-wrap gap-4 lg:gap-5 items-start mb-4">
          <div class="w-full lg:w-4/12 lg:text-left text-center">
            <p class="display-4 font-bold text-primary-sunny mb-0 leading-none" aria-hidden="true">03</p>
            <h2 id="heading-buoc-3" class="section-title mt-2 mb-0">Học tập tại Hàn Quốc</h2>
          </div>
          <div class="w-full lg:w-8/12">
            <p class="text-black mb-0">
              Giai đoạn trọng tâm của hành trình — từ lớp tiếng Hàn đến giảng đường chuyên ngành: nền giáo dục chất lượng cao, môi trường quốc tế và kỹ năng gắn với thực tế nghề nghiệp.
            </p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="uni-detail-prog-card h-full">
              <span class="uni-detail-prog-card__icon" aria-hidden="true"><i class="bi bi-book"></i></span>
              <h3 class="uni-detail-prog-card__title">Lớp tiếng Hàn</h3>
              <p class="uni-detail-prog-card__text mb-0">Nghe — nói — đọc — viết bài bản, chuẩn bị cho TOPIK và chương trình chuyên ngành.</p>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="uni-detail-prog-card h-full">
              <span class="uni-detail-prog-card__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
              <h3 class="uni-detail-prog-card__title">Giảng đường chuyên ngành</h3>
              <p class="uni-detail-prog-card__text mb-0">Đại học, cao đẳng hoặc thạc sĩ — đào tạo theo chuẩn quốc tế.</p>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="uni-detail-prog-card h-full">
              <span class="uni-detail-prog-card__icon" aria-hidden="true"><i class="bi bi-people"></i></span>
              <h3 class="uni-detail-prog-card__title">Làm việc nhóm &amp; thuyết trình</h3>
              <p class="uni-detail-prog-card__text mb-0">Bài tập nhóm, seminar — rèn tư duy phản biện và kỹ năng mềm.</p>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="uni-detail-prog-card h-full">
              <span class="uni-detail-prog-card__icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
              <h3 class="uni-detail-prog-card__title">Hoạt động ngoại khóa</h3>
              <p class="uni-detail-prog-card__text mb-0">Câu lạc bộ, lễ hội trường, giao lưu văn hóa — trải nghiệm toàn diện.</p>
            </article>
          </div>
        </div>
        <div class="relative w-full aspect-[21/9] rounded-4 overflow-hidden shadow-sm mt-4">
          <img class="object-fit-cover" src="../assets/img/giang-duong-han-quoc.jpg" alt="Không gian học thuật và sự kiện trường đại học đối tác SUNNY tại Hàn Quốc" width="1400" height="600" loading="lazy" decoding="async" />
        </div>
        <p class="text-sm text-bs-secondary text-center mt-2 mb-0">Giảng đường và môi trường học tập tại các trường đối tác SUNNY</p>
      </div>
    </section>

    <!-- 04 -->
    <section class="py-5 bg-white" aria-labelledby="heading-buoc-4">
      <div class="container">
        <div class="flex flex-wrap gap-4 lg:gap-5 items-start mb-4">
          <div class="w-full lg:w-4/12 lg:text-left text-center">
            <p class="display-4 font-bold text-primary-sunny mb-0 leading-none" aria-hidden="true">04</p>
            <h2 id="heading-buoc-4" class="section-title mt-2 mb-0">Cuộc sống &amp; trải nghiệm</h2>
          </div>
          <div class="w-full lg:w-8/12">
            <p class="text-black mb-3">
              Du học không chỉ là học — đó còn là sống, khám phá và trưởng thành. Ăn tối cùng bạn bè, đi lễ hội mùa thu, trượt tuyết mùa đông — những kỷ niệm góp phần định hình con người bạn sau này.
            </p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="info-route-card h-full">
              <div class="info-route-card__head">
                <span class="info-route-card__badge"><i class="bi bi-cup-hot" aria-hidden="true"></i></span>
                <h3 class="info-route-card__title">Văn hóa &amp; ẩm thực</h3>
              </div>
              <p class="info-route-card__text mb-0">Ẩm thực Hàn, Hanbok, K-pop và những nét văn hóa trong đời sống hàng ngày.</p>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="info-route-card h-full">
              <div class="info-route-card__head">
                <span class="info-route-card__badge"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <h3 class="info-route-card__title">Kết bạn quốc tế</h3>
              </div>
              <p class="info-route-card__text mb-0">Bạn bè từ nhiều quốc gia — mở rộng góc nhìn và mạng lưới quan hệ.</p>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="info-route-card h-full">
              <div class="info-route-card__head">
                <span class="info-route-card__badge"><i class="bi bi-balloon" aria-hidden="true"></i></span>
                <h3 class="info-route-card__title">Lễ hội &amp; du lịch</h3>
              </div>
              <p class="info-route-card__text mb-0">Hoa anh đào, Seoul, Busan, Jeju — những chuyến đi đáng nhớ cùng bạn bè.</p>
            </article>
          </div>
          <div class="w-full md:w-6/12 xl:w-3/12">
            <article class="info-route-card h-full">
              <div class="info-route-card__head">
                <span class="info-route-card__badge"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                <h3 class="info-route-card__title">Cân bằng học — sống</h3>
              </div>
              <p class="info-route-card__text mb-0">Sắp xếp thời gian, tự nấu ăn, quản lý chi tiêu — trưởng thành từng ngày.</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- Gallery -->
    <section class="py-5 bg-soft-orange border-t" aria-labelledby="heading-gallery">
      <div class="container">
        <h2 id="heading-gallery" class="h4 font-bold text-bs-dark text-center mb-4">Khoảnh khắc đáng nhớ</h2>
        <div class="flex flex-wrap gap-3 md:gap-4">
          <div class="w-6/12 lg:w-3/12">
            <figure class="mb-0">
              <div class="relative w-full aspect-square rounded-4 overflow-hidden shadow-sm">
                <img class="object-fit-cover" src="../assets/img/van-hoa-trai-nghiem.jpg" alt="Không khí học đường và sự kiện văn hóa tại đại học đối tác Hàn Quốc" width="640" height="640" loading="lazy" decoding="async" />
              </div>
              <figcaption class="text-sm text-black text-center mt-2">Văn hóa &amp; trải nghiệm</figcaption>
            </figure>
          </div>
          <div class="w-6/12 lg:w-3/12">
            <figure class="mb-0">
              <div class="relative w-full aspect-square rounded-4 overflow-hidden shadow-sm">
                <img class="object-fit-cover" src="../assets/img/sinh-vien-sunny.jpg" alt="Sinh viên SUNNY và đội ngũ trong sự kiện, trang phục áo dài Việt Nam" width="640" height="640" loading="lazy" decoding="async" />
              </div>
              <figcaption class="text-sm text-black text-center mt-2">Sinh viên SUNNY</figcaption>
            </figure>
          </div>
          <div class="w-6/12 lg:w-3/12">
            <figure class="mb-0">
              <div class="relative w-full aspect-square rounded-4 overflow-hidden shadow-sm">
                <img class="object-fit-cover" src="../assets/img/hoat-dong-ngoai-khoa.jpg" alt="Sinh viên trên sân trường đại học — không gian hoạt động ngoại khóa và đời sống học đường Hàn Quốc" width="640" height="640" loading="lazy" decoding="async" />
              </div>
              <figcaption class="text-sm text-black text-center mt-2">Hoạt động ngoại khóa</figcaption>
            </figure>
          </div>
          <div class="w-6/12 lg:w-3/12">
            <figure class="mb-0">
              <div class="relative w-full aspect-square rounded-4 overflow-hidden shadow-sm">
                <img class="object-fit-cover" src="../assets/img/ky-niem-du-hoc.jpg" alt="Kỷ niệm học đường — sinh viên dưới hoa anh đào tại campus Hàn Quốc" width="640" height="640" loading="lazy" decoding="async" />
              </div>
              <figcaption class="text-sm text-black text-center mt-2">Kỷ niệm du học</figcaption>
            </figure>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-5 bg-white">
      <div class="container">
        <div class="rounded-4 border border-2 border-primary-sunny border-opacity-25 bg-soft-orange p-4 p-lg-5 text-center">
          <p class="h5 font-bold text-bs-dark mb-3">Bạn cũng có thể trở thành một phần hành trình này.</p>
          <a class="btn btn-sunny btn-lg text-white px-5" href="/#dang-ky">Đăng ký tư vấn du học</a>
        </div>
      </div>
    </section>
  </main>
',
  ),
);
