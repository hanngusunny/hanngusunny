<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 4,
  'slug' => 'thong-tin-lien-he',
  'name' => 'Thông tin liên hệ',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:10:08',
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
              <span class="page-about-breadcrumb__current">Liên hệ</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">Liên hệ <span class="text-primary-sunny">SUNNY</span></h1>
            <p class="about-intro-hero__lead">
              <strong class="about-intro-hero__strong">Hotline, email và hệ thống văn phòng</strong>
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              SUNNY phản hồi trong <strong class="about-intro-hero__strong">giờ hành chính</strong>, hỗ trợ bạn chọn lộ trình du học phù hợp.
            </p>
            <div class="about-intro-hero__chips" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-telephone-fill" aria-hidden="true"></i> Gọi toàn quốc</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-envelope-fill" aria-hidden="true"></i> Email hỗ trợ</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> 4 văn phòng</span>
            </div>
            <a href="#heading-nhanh" class="about-intro-hero__cta">
              <span>Kênh liên hệ nhanh</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Kết nối có hệ thống</p>
              <p class="about-intro-hero__panel-title">Một đầu mối — nhiều kênh tiện lợi</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-clock"></i></span>
                  <span><strong>Nhóm tư vấn theo miền</strong> — Bắc, Trung, Nam; phản hồi và hẹn làm việc rõ ràng trong giờ hành chính.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-building"></i></span>
                  <span><strong>Hà Nội, Đà Nẵng, TP.HCM và Seoul</strong> — học viên và phụ huynh dễ chọn điểm gặp hoặc hỗ trợ xa.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
                  <span><strong>Hướng đúng nhu cầu</strong> — thông tin học phí và lộ trình minh bạch, không thổi phồng cam kết.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="py-5 bg-white" aria-labelledby="heading-nhanh">
      <div class="container">
        <h2 id="heading-nhanh" class="h4 font-bold text-bs-dark mb-4">Kênh liên hệ nhanh</h2>
        <div class="flex flex-wrap gap-4">
          <div class="w-full md:w-4/12">
            <div class="border rounded-4 p-4 h-full shadow-sm bg-white border-opacity-10">
              <div class="flex items-start gap-3">
                <span class="uni-detail-prog-card__icon shrink-0" aria-hidden="true"><i class="bi bi-telephone-fill"></i></span>
                <div>
                  <h3 class="h6 font-bold text-bs-dark mb-2">Hotline toàn quốc</h3>
                  <p class="text-black text-sm mb-2">Gọi trực tiếp để được hướng dẫn kết nối tư vấn viên theo khu vực.</p>
                  <a href="tel:0981698292" class="font-bold no-underline text-primary-sunny">0981 698 292</a>
                </div>
              </div>
            </div>
          </div>
          <div class="w-full md:w-4/12">
            <div class="border rounded-4 p-4 h-full shadow-sm bg-white border-opacity-10">
              <div class="flex items-start gap-3">
                <span class="uni-detail-prog-card__icon shrink-0" aria-hidden="true"><i class="bi bi-envelope-fill"></i></span>
                <div>
                  <h3 class="h6 font-bold text-bs-dark mb-2">Email</h3>
                  <p class="text-black text-sm mb-2">Gửi câu hỏi hoặc hồ sơ tóm tắt — chúng tôi phản hồi trong 24h làm việc.</p>
                  <a href="mailto:info@duhocsunny.edu.vn" class="font-semibold no-underline text-primary-sunny text-sm">info@duhocsunny.edu.vn</a>
                </div>
              </div>
            </div>
          </div>
          <div class="w-full md:w-4/12">
            <div class="border rounded-4 p-4 h-full shadow-sm bg-white border-opacity-10">
              <div class="flex items-start gap-3">
                <span class="uni-detail-prog-card__icon shrink-0" aria-hidden="true"><i class="bi bi-share"></i></span>
                <div>
                  <h3 class="h6 font-bold text-bs-dark mb-2">Mạng xã hội</h3>
                  <p class="text-black text-sm mb-3">Cập nhật sự kiện, học bổng và hoạt động cộng đồng.</p>
                  <div class="flex flex-wrap gap-2">
                    <a href="https://huhocsunny.com/" class="btn btn-sm btn-outline-secondary rounded-full" target="_blank" rel="noopener noreferrer">Website</a>
                    <a href="https://www.facebook.com/huhocsunny.com" class="btn btn-sm btn-outline-secondary rounded-full" target="_blank" rel="noopener noreferrer">Facebook</a>
                    <a href="https://www.tiktok.com/@duhocsunny" class="btn btn-sm btn-outline-secondary rounded-full" target="_blank" rel="noopener noreferrer">TikTok</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-soft-orange" aria-labelledby="heading-khu-vuc">
      <div class="container">
        <div class="title-line mb-3"></div>
        <h2 id="heading-khu-vuc" class="section-title mb-4">Tư vấn viên theo khu vực</h2>
        <div class="flex flex-wrap gap-4">
          <div class="w-full md:w-4/12">
            <article class="about-person-card about-person-card--compact h-full border-0">
              <h3 class="about-person-card__name h6">Miền Bắc</h3>
              <p class="text-black text-sm mb-2"><a href="tel:0355892252" class="font-semibold no-underline text-bs-dark">0355.892.252</a></p>
              <p class="text-bs-secondary text-sm mb-0">Trần Thị Thu Minh — hỗ trợ tư vấn trực tiếp tại Hà Nội và các tỉnh phía Bắc.</p>
            </article>
          </div>
          <div class="w-full md:w-4/12">
            <article class="about-person-card about-person-card--compact h-full border-0">
              <h3 class="about-person-card__name h6">Miền Trung</h3>
              <p class="text-black text-sm mb-2"><a href="tel:0762885604" class="font-semibold no-underline text-bs-dark">076.288.5604</a></p>
              <p class="text-bs-secondary text-sm mb-0">Ms. Bích Hồng — Đà Nẵng và các tỉnh khu vực miền Trung.</p>
            </article>
          </div>
          <div class="w-full md:w-4/12">
            <article class="about-person-card about-person-card--compact h-full border-0">
              <h3 class="about-person-card__name h6">Miền Nam</h3>
              <p class="text-black text-sm mb-2"><a href="tel:0393725155" class="font-semibold no-underline text-bs-dark">039.372.5155</a></p>
              <p class="text-bs-secondary text-sm mb-0">Ms. Hồ Yến — TP. Hồ Chí Minh và các tỉnh phía Nam.</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-white contact-offices-section" aria-labelledby="heading-van-phong">
      <div class="container">
        <header class="mb-4 lg:mb-5 text-center lg:text-left">
          <div class="title-line mx-auto mx-lg-0 mb-3"></div>
          <h2 id="heading-van-phong" class="section-title mb-2">Hệ thống văn phòng</h2>
          <p class="contact-offices-section__intro text-black mb-0 mx-auto mx-lg-0">
            Bốn điểm tại Việt Nam và Hàn Quốc — xem vị trí trên Google Maps và <strong>nên gọi trước</strong> khi ghé để đặt lịch tư vấn (đặc biệt cuối tuần và giờ cao điểm).
          </p>
        </header>

        <div class="flex flex-wrap lg:items-start lg:gap-x-5 gap-y-5 contact-offices-layout">
          <div class="w-full lg:w-6/12 xl:w-5/12 hidden lg:block">
            <div class="contact-offices-nav-wrap">
              <p class="contact-offices-nav-label" id="offices-tablist-heading">Danh sách văn phòng</p>
              <div
                class="nav flex-col gap-2 contact-offices-nav"
                id="offices-tablist"
                role="tablist"
                aria-labelledby="offices-tablist-heading"
                aria-orientation="vertical"
              >
                <button
                  type="button"
                  class="nav-link contact-offices-nav__btn active"
                  id="office-tab-hn"
                  data-tw-toggle="pill"
                  data-tw-target="#office-pane-hn"
                  role="tab"
                  aria-controls="office-pane-hn"
                  aria-selected="true"
                >
                  SUNNY Hà Nội
                  <span class="contact-offices-nav__hint">Miền Bắc</span>
                </button>
                <button
                  type="button"
                  class="nav-link contact-offices-nav__btn"
                  id="office-tab-dn"
                  data-tw-toggle="pill"
                  data-tw-target="#office-pane-dn"
                  role="tab"
                  aria-controls="office-pane-dn"
                  aria-selected="false"
                  tabindex="-1"
                >
                  SUNNY Đà Nẵng
                  <span class="contact-offices-nav__hint">Miền Trung</span>
                </button>
                <button
                  type="button"
                  class="nav-link contact-offices-nav__btn"
                  id="office-tab-hcm"
                  data-tw-toggle="pill"
                  data-tw-target="#office-pane-hcm"
                  role="tab"
                  aria-controls="office-pane-hcm"
                  aria-selected="false"
                  tabindex="-1"
                >
                  SUNNY TP. HCM
                  <span class="contact-offices-nav__hint">Miền Nam</span>
                </button>
                <button
                  type="button"
                  class="nav-link contact-offices-nav__btn"
                  id="office-tab-kr"
                  data-tw-toggle="pill"
                  data-tw-target="#office-pane-kr"
                  role="tab"
                  aria-controls="office-pane-kr"
                  aria-selected="false"
                  tabindex="-1"
                >
                  SUNNY Seoul
                  <span class="contact-offices-nav__hint">Hàn Quốc</span>
                </button>
              </div>
            </div>
          </div>
          <div class="w-full lg:w-6/12 xl:w-7/12">
            <div class="tab-content contact-offices-tab-content contact-offices-tab-content--stack-sm">
              <div
                class="tab-pane fade show active"
                id="office-pane-hn"
                role="tabpanel"
                aria-labelledby="office-tab-hn"
                tabindex="0"
              >
                <article class="contact-office-card mb-0">
                  <header class="contact-office-card__head">
                    <span class="contact-office-card__icon" aria-hidden="true"><i class="bi bi-buildings-fill"></i></span>
                    <div class="contact-office-card__titles">
                      <h3 class="contact-office-card__name mb-1">SUNNY Hà Nội</h3>
                      <span class="contact-office-card__region">Miền Bắc · Trần Thị Thu Minh</span>
                    </div>
                    <a href="tel:0355892252" class="contact-office-card__phone">0355.892.252</a>
                  </header>
                  <div class="contact-office-card__body">
                    <p class="contact-office-card__addr mb-0">
                      Số nhà 3 ngách 376/19 đường Bưởi, P. Ngọc Hà, Hà Nội.
                    </p>
                  </div>
                  <p class="contact-office-card__actions mb-0">
                    <a
                      class="contact-office-card__dir"
                      href="https://www.google.com/maps/dir/?api=1&amp;destination=3%20ng%C3%A1ch%20376%2F19%20%C4%91%C6%B0%E1%BB%9Dng%20B%C6%B0%E1%BB%9Fi%20Ng%E1%BB%8Dc%20H%C3%A0%20H%C3%A0%20N%E1%BB%99i"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Chỉ đường
                    </a>
                  </p>
                  <div class="contact-office-card__map">
                    <iframe
                      src="https://www.google.com/maps?q=3%20ng%C3%A1ch%20376%2F19%20%C4%91%C6%B0%E1%BB%9Dng%20B%C6%B0%E1%BB%9Fi%2C%20P.%20Ng%E1%BB%8Dc%20H%C3%A0%2C%20H%C3%A0%20N%E1%BB%99i&amp;hl=vi&amp;z=17&amp;output=embed"
                      width="600"
                      height="450"
                      loading="eager"
                      title="Google Maps — SUNNY Hà Nội"
                      referrerpolicy="no-referrer-when-downgrade"
                      allowfullscreen=""
                    ></iframe>
                  </div>
                </article>
              </div>
              <div class="tab-pane fade" id="office-pane-dn" role="tabpanel" aria-labelledby="office-tab-dn" tabindex="0">
                <article class="contact-office-card mb-0">
                  <header class="contact-office-card__head">
                    <span class="contact-office-card__icon" aria-hidden="true"><i class="bi bi-buildings-fill"></i></span>
                    <div class="contact-office-card__titles">
                      <h3 class="contact-office-card__name mb-1">SUNNY Đà Nẵng</h3>
                      <span class="contact-office-card__region">Miền Trung · Ms. Bích Hồng</span>
                    </div>
                    <a href="tel:0762885604" class="contact-office-card__phone">076.288.5604</a>
                  </header>
                  <div class="contact-office-card__body">
                    <p class="contact-office-card__addr mb-0">63 Hà Huy Giáp, phường Hoà Cường, Đà Nẵng.</p>
                  </div>
                  <p class="contact-office-card__actions mb-0">
                    <a
                      class="contact-office-card__dir"
                      href="https://www.google.com/maps/dir/?api=1&amp;destination=63%20H%C3%A0%20Huy%20Gi%C3%A1p%20Ho%C3%A0%20C%C6%B0%E1%BB%9Dng%20%C4%90%C3%A0%20N%E1%BA%B5ng"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Chỉ đường
                    </a>
                  </p>
                  <div class="contact-office-card__map">
                    <iframe
                      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3873885.0768499407!2d104.37021934951491!3d18.52479417225518!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x314219c14389f53b%3A0xfb39f1e20b068e62!2zVHJ1bmcgVMOibSBEdSBI4buNYyBIw6BuIFF14buRYyBTdW5ueSDEkMOgIE7hurVuZw!5e0!3m2!1svi!2s!4v1777709033569!5m2!1svi!2s"
                      width="600"
                      height="450"
                      loading="lazy"
                      title="Google Maps — SUNNY Đà Nẵng"
                      referrerpolicy="no-referrer-when-downgrade"
                      allowfullscreen=""
                    ></iframe>
                  </div>
                </article>
              </div>
              <div class="tab-pane fade" id="office-pane-hcm" role="tabpanel" aria-labelledby="office-tab-hcm" tabindex="0">
                <article class="contact-office-card mb-0">
                  <header class="contact-office-card__head">
                    <span class="contact-office-card__icon" aria-hidden="true"><i class="bi bi-buildings-fill"></i></span>
                    <div class="contact-office-card__titles">
                      <h3 class="contact-office-card__name mb-1">SUNNY TP. Hồ Chí Minh</h3>
                      <span class="contact-office-card__region">Miền Nam · Ms. Hồ Yến</span>
                    </div>
                    <a href="tel:0393725155" class="contact-office-card__phone">039.372.5155</a>
                  </header>
                  <div class="contact-office-card__body">
                    <p class="contact-office-card__addr mb-0">
                      40 đường số 8 KDC Cityland Park Hills, Phường Gò Vấp, TP. Hồ Chí Minh.
                    </p>
                  </div>
                  <p class="contact-office-card__actions mb-0">
                    <a
                      class="contact-office-card__dir"
                      href="https://www.google.com/maps/dir/?api=1&amp;destination=40%20%C4%91%C6%B0%E1%BB%9Dng%20s%E1%BB%91%208%20Cityland%20Park%20Hills%20G%C3%B2%20V%E1%BA%A5p%20TP%20H%E1%BB%93%20Ch%C3%AD%20Minh"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Chỉ đường
                    </a>
                  </p>
                  <div class="contact-office-card__map">
                    <iframe
                      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3918.669860517986!2d106.66567437570383!3d10.836557058080423!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317529f7274dd325%3A0x676295b7fedfdab8!2zVHJ1bmcgVMOibSBEdSBI4buNYyBIw6BuIFF14buRYyBTdW5ueSBI4buTIENow61gTWluaA!5e0!3m2!1svi!2s!4v1777709057235!5m2!1svi!2s"
                      width="600"
                      height="450"
                      loading="lazy"
                      title="Google Maps — SUNNY TP.HCM"
                      referrerpolicy="no-referrer-when-downgrade"
                      allowfullscreen=""
                    ></iframe>
                  </div>
                </article>
              </div>
              <div class="tab-pane fade" id="office-pane-kr" role="tabpanel" aria-labelledby="office-tab-kr" tabindex="0">
                <article class="contact-office-card contact-office-card--no-map mb-0">
                  <header class="contact-office-card__head">
                    <span class="contact-office-card__icon" aria-hidden="true"><i class="bi bi-globe-americas"></i></span>
                    <div class="contact-office-card__titles">
                      <h3 class="contact-office-card__name mb-1">SUNNY Hàn Quốc · Seoul</h3>
                      <span class="contact-office-card__region">Đại diện tại Seoul · Hotline toàn cục</span>
                    </div>
                    <a href="tel:0981698292" class="contact-office-card__phone">0981 698 292</a>
                  </header>
                  <div class="contact-office-card__body">
                    <p class="contact-office-card__addr mb-0 lang-en">
                      Room 611, 6F, Ssangnim Building, 286 Toegye-ro, Jung-gu, Seoul, Korea.
                    </p>
                  </div>
                  <p class="contact-office-card__actions mb-0">
                    <a
                      class="contact-office-card__dir"
                      href="https://www.google.com/maps/dir/?api=1&amp;destination=Ssangnim%20Building%20286%20Toegye-ro%20Jung-gu%20Seoul"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Chỉ đường
                    </a>
                  </p>
                </article>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-5 bg-soft-orange border-t border-opacity-10">
      <div class="container text-center">
        <h2 class="h5 font-bold text-bs-dark mb-3">Muốn nhận lộ trình tư vấn chi tiết?</h2>
        <p class="text-black text-sm mb-4 lg:mb-0 mx-auto" style="max-width: 36rem">
          Để lại thông tin tại biểu mẫu đăng ký — SUNNY liên hệ lại và không chia sẻ dữ liệu của bạn cho bên thứ ba.
        </p>
        <a href="/#dang-ky" class="btn btn-sunny btn-sunny-pulse text-white btn-lg mt-3">Đăng ký tư vấn miễn phí</a>
      </div>
    </section>
  </main>
',
  ),
);
