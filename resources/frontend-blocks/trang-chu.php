<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 12,
  'slug' => 'trang-chu',
  'name' => 'Trang chủ',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:32:50',
  'config' => 
  array (
    '_fields' => 
    array (
    ),
    'html' => '<!-- Cảm nhận học sinh -->
  <section id="cam-nhan" class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <div class="title-line mx-auto mb-3"></div>
        <h2 class="section-title" id="cam-nhan-heading">Cảm nhận học sinh</h2>
        <p class="section-sub mt-2">
          Lắng nghe học viên qua video và những dòng chia sẻ chân thực sau hành trình du học Hàn Quốc.
        </p>
      </div>

      <div class="testimonial-video-wrap mb-5">
        <div class="testimonial-video-grid">
          <div>
            <article class="testimonial-video-card">
              <div class="testimonial-video-badge"><i class="bi bi-play-circle-fill me-1"></i> Học viên chia sẻ</div>
              <div class="testimonial-video-frame relative w-full aspect-video">
                <button
                  type="button"
                  class="testimonial-video-poster absolute top-0 start-0 w-full h-full"
                  data-tw-toggle="modal"
                  data-tw-target="#modalVideoTestimonial1"
                  aria-label="Xem video cảm nhận học viên SUNNY — video 1"
                >
                  <img src="<?= $e($assetPath) ?>/img/video-01.jpg" alt="" width="1280" height="720" loading="lazy" decoding="async" />
                  <span class="testimonial-yt-play" aria-hidden="true">
                    <svg class="testimonial-yt-play-icon" viewBox="0 0 68 48" width="68" height="48" focusable="false" aria-hidden="true">
                      <path fill="#f00" d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" />
                      <polygon fill="#fff" points="27,14 45,24 27,34" />
                    </svg>
                  </span>
                </button>
              </div>
              <div class="testimonial-video-body">
                <p class="testimonial-video-quote">“Em từng trượt ở trung tâm khác, rồi gửi lại hồ sơ vào SUNNY. Cảm ơn các chị đã động viên em khi em mất niềm tin vào bản thân.”</p>
                <div class="flex items-center gap-3">
                  <div class="testimonial-avatar" aria-hidden="true">NH</div>
                  <div>
                    <div class="font-bold text-bs-dark">Nguyễn Huyền</div>
                    <small class="text-black">Sinh viên Đại học Hankuk</small>
                  </div>
                </div>
              </div>
            </article>
          </div>
          <div>
            <article class="testimonial-video-card">
              <div class="testimonial-video-badge"><i class="bi bi-play-circle-fill me-1"></i> Phụ huynh &amp; học viên</div>
              <div class="testimonial-video-frame relative w-full aspect-video">
                <button
                  type="button"
                  class="testimonial-video-poster absolute top-0 start-0 w-full h-full"
                  data-tw-toggle="modal"
                  data-tw-target="#modalVideoTestimonial2"
                  aria-label="Xem video cảm nhận học viên SUNNY — video 2"
                >
                  <img src="<?= $e($assetPath) ?>/img/video-02.jpg" alt="" width="1280" height="720" loading="lazy" decoding="async" />
                  <span class="testimonial-yt-play" aria-hidden="true">
                    <svg class="testimonial-yt-play-icon" viewBox="0 0 68 48" width="68" height="48" focusable="false" aria-hidden="true">
                      <path fill="#f00" d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" />
                      <polygon fill="#fff" points="27,14 45,24 27,34" />
                    </svg>
                  </span>
                </button>
              </div>
              <div class="testimonial-video-body">
                <p class="testimonial-video-quote">“Em chuyển tiếp từ đại học Việt Nam sang Hàn; SUNNY đồng hành hồ sơ ngay từ đầu. Ở Hàn gặp bao Sunnier — như gặp người nhà giữa đất khách.”</p>
                <div class="flex items-center gap-3">
                  <div class="testimonial-avatar" aria-hidden="true">NC</div>
                  <div>
                    <div class="font-bold text-bs-dark">Nguyễn Châu</div>
                    <small class="text-black">Sinh viên Đại học Kwangwoon</small>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </div>
      </div>

      <div class="testimonial-text-wrap">
        <div class="testimonial-text-grid">
          <div>
            <div class="testimonial-card testimonial-text-card">
              <div class="testimonial-card-icon" aria-hidden="true"><i class="bi bi-chat-quote-fill"></i></div>
              <div class="stars mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
              <p class="testimonial-card-text mb-4">“Em từng trượt ở 1 trung tâm khác và đã quyết định gửi lại hồ sơ vào SUNNY. Cảm ơn trung tâm đã giúp đỡ em rất nhiều. Bởi với em khoảng thời gian đó thực sự khó khăn, em mất niềm tin vào bản thân và từng nghĩ mình là kẻ thất bại. Cảm ơn cô giáo, cảm ơn chị Nhung và chị sale đã luôn động viên em ạ!”</p>
              <div class="flex items-center mt-auto">
                <div class="testimonial-avatar" aria-hidden="true">NH</div>
                <div class="ms-3">
                  <div class="font-bold">Nguyễn Huyền</div>
                  <small class="text-black">Sinh viên Đại học Hankuk</small>
                </div>
              </div>
            </div>
          </div>
          <div>
            <div class="testimonial-card testimonial-text-card">
              <div class="testimonial-card-icon" aria-hidden="true"><i class="bi bi-chat-quote-fill"></i></div>
              <div class="stars mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
              <p class="testimonial-card-text mb-4">“Em từng học đại học ở Việt Nam nhưng rồi lại quyết định chuyển tiếp sang Hàn để theo đuổi giấc mơ của mình. Cảm ơn SUNNY từ những ngày đầu đã rất nhiệt tình tư vấn, xử lý hồ sơ chuyển tiếp cho em. Sang Hàn học mới thấy, hỏi 10 bạn thì có tới 8 bạn cũng đi từ SUNNY luôn ạ: từ Hà Nội, Đà Nẵng, Sài Gòn,... đều rất đông. Mỗi khi tìm thấy 1 bạn Sunnier em cảm thấy như tìm được người nhà ở nơi đất khách quê người ấy.”</p>
              <div class="flex items-center mt-auto">
                <div class="testimonial-avatar" aria-hidden="true">NC</div>
                <div class="ms-3">
                  <div class="font-bold">Nguyễn Châu</div>
                  <small class="text-black">Sinh viên Đại học Kwangwoon</small>
                </div>
              </div>
            </div>
          </div>
          <div>
            <div class="testimonial-card testimonial-text-card">
              <div class="testimonial-card-icon" aria-hidden="true"><i class="bi bi-chat-quote-fill"></i></div>
              <div class="stars mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
              <p class="testimonial-card-text mb-4">“Sau khi tìm hiểu rất nhiều trung tâm du học trên thị trường, tôi đã lựa chọn SUNNY vì ở đây các chi phí đều rất minh bạch, làm việc trực tiếp không qua trung gian nên tiết kiệm được khoảng 100 triệu đồng. Trước khi chốt, chúng tôi còn ghé qua ký túc xem kỹ hơn. Thấy nơi ở của các cháu sạch sẽ, an toàn, có đủ đồ phòng cháy chữa cháy,.. nên mới yên tâm đặt bút ký.”</p>
              <div class="flex items-center mt-auto">
                <div class="testimonial-avatar" aria-hidden="true">TH</div>
                <div class="ms-3">
                  <div class="font-bold">Phụ huynh bạn Thu Hằng</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Thông tin du học HQ -->
  <section id="thong-tin" class="section-thong-tin py-5">
    <div class="container">
      <div class="text-center mb-4 lg:mb-5">
        <div class="title-line mx-auto mb-3"></div>
        <h2 class="section-title" id="thong-tin-heading">Thông tin du học Hàn Quốc</h2>
        <p class="section-thong-tin__lead mx-auto mb-0">
          Lộ trình gọn: điều kiện, chọn trường, chi phí, học bổng và chuẩn bị visa — xem nhanh trước khi đi sâu từng mục dưới đây.
        </p>
      </div>
      <div class="section-thong-tin__grid">
        <div>
          <article class="info-route-card h-full">
            <div class="info-route-card__head">
              <span class="info-route-card__icon" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></span>
              <h3 class="info-route-card__title">Điều Kiện</h3>
            </div>
            <p class="info-route-card__text mb-0">
              Điều kiện để đi Du học Hàn Quốc 2026 là gì? Sức khỏe, tài chính, học lực yêu cầu ra sao? Tất cả sẽ được giải đáp trong lộ trình của SUNNY dành cho bạn.
            </p>
            <div class="info-route-card__foot">
              <a href="<?= $e($linkPrefix) ?>dieu-kien-du-hoc-han-quoc-2026.php" class="info-route-card__link"><span class="info-route-card__link-text">Xem chi tiết</span><span class="info-route-card__link-arrow" aria-hidden="true">→</span></a>
            </div>
          </article>
        </div>
        <div>
          <article class="info-route-card h-full">
            <div class="info-route-card__head">
              <span class="info-route-card__icon" aria-hidden="true"><i class="bi bi-buildings"></i></span>
              <h3 class="info-route-card__title">Chọn Trường</h3>
            </div>
            <p class="info-route-card__text mb-0">
              Tìm trường du học Hàn Quốc 2026 phù hợp bằng cách nào? Nên học tại khu vực Seoul, Busan hay Incheon? Các tiêu chí như chuyên ngành, học phí, chất lượng giảng dạy, cơ hội việc làm có ảnh hưởng như thế nào? Có nên chọn các trường TOP ưu tiên?
            </p>
            <div class="info-route-card__foot">
              <a href="<?= $e($linkPrefix) ?>chon-truong-du-hoc-han-quoc-2026.php" class="info-route-card__link"><span class="info-route-card__link-text">Xem chi tiết</span><span class="info-route-card__link-arrow" aria-hidden="true">→</span></a>
            </div>
          </article>
        </div>
        <div>
          <article class="info-route-card h-full">
            <div class="info-route-card__head">
              <span class="info-route-card__icon" aria-hidden="true"><i class="bi bi-piggy-bank"></i></span>
              <h3 class="info-route-card__title">Chi Phí</h3>
            </div>
            <p class="info-route-card__text mb-0">
              Du học Hàn Quốc 2026 cần những chi phí gì? Để đi du học Hàn Quốc với tài chính tiết kiệm và phù hợp nhất, gia đình và học viên sẽ cần chuẩn bị như thế nào?
            </p>
            <div class="info-route-card__foot">
              <a href="<?= $e($linkPrefix) ?>chi-phi-du-hoc-han-quoc-2026.php" class="info-route-card__link"><span class="info-route-card__link-text">Xem chi tiết</span><span class="info-route-card__link-arrow" aria-hidden="true">→</span></a>
            </div>
          </article>
        </div>
        <div>
          <article class="info-route-card h-full">
            <div class="info-route-card__head">
              <span class="info-route-card__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
              <h3 class="info-route-card__title">Học Bổng</h3>
            </div>
            <p class="info-route-card__text mb-0">
              Có những loại học bổng nào khi đi du học Hàn Quốc 2026? Làm cách nào để nhận được học bổng? Bí quyết để săn học bổng du học Hàn Quốc là gì?
            </p>
            <div class="info-route-card__foot">
              <a href="<?= $e($linkPrefix) ?>hoc-bong-du-hoc-han-quoc-2026.php" class="info-route-card__link"><span class="info-route-card__link-text">Xem chi tiết</span><span class="info-route-card__link-arrow" aria-hidden="true">→</span></a>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- Các hệ du học -->
  <section id="he-du-hoc" class="section-he-du-hoc py-5">
    <div class="container">
      <div class="text-center mb-4 lg:mb-5">
        <div class="title-line mx-auto mb-3"></div>
        <h2 class="section-title" id="he-du-hoc-heading">Các hệ du học Hàn Quốc</h2>
        <p class="section-thong-tin__lead mx-auto mb-0">
          Chọn hệ phù hợp mục tiêu: nền tảng tiếng, cao đẳng nghề, bậc đại học hay sau đại học — SUNNY đồng hành xây lộ trình rõ ràng theo từng visa.
        </p>
      </div>
      <div class="section-he-du-hoc__grid">
        <div>
          <article class="he-program-card">
            <div class="he-program-card__media">
              <img
                src="<?= $e($assetPath) ?>/img/du-hoc-han-quoc-he-d4-1.jpg"
                alt="Du học hệ tiếng Hàn (D4-1)"
                width="1184"
                height="1184"
                loading="lazy"
                decoding="async"
              />
              <span class="he-program-card__tag">D4-1 · Visa ngôn ngữ</span>
            </div>
            <div class="he-program-card__body">
              <h3 class="he-program-card__title">
                <a href="<?= $e($linkPrefix) ?>du-hoc-han-quoc-he-tieng-d4-1.php" class="he-program-card__title-link stretched-link">Du học hệ tiếng (D4-1)</a>
              </h3>
              <p class="he-program-card__text">
                Khóa tiếng tại viện ngôn ngữ đối tác — nền tảng TOPIK, thích nghi văn hóa và lộ trình visa ngôn ngữ rõ ràng.
              </p>
              <span class="he-program-card__cta" aria-hidden="true">
                <span>Du học hệ tiếng (D4-1)</span>
                <i class="bi bi-arrow-right-short he-program-card__cta-icon" aria-hidden="true"></i>
              </span>
            </div>
          </article>
        </div>
        <div>
          <article class="he-program-card">
            <div class="he-program-card__media">
              <img
                src="<?= $e($assetPath) ?>/img/du-hoc-han-quoc-he-dai-hoc-d2-2.jpg"
                alt="Du học hệ đại học (D2-2)"
                width="1184"
                height="1184"
                loading="lazy"
                decoding="async"
              />
              <span class="he-program-card__tag">D2-2 · Visa cử nhân</span>
            </div>
            <div class="he-program-card__body">
              <h3 class="he-program-card__title">
                <a href="<?= $e($linkPrefix) ?>du-hoc-han-quoc-he-dai-hoc-d2-2.php" class="he-program-card__title-link stretched-link">Du học hệ đại học (D2-2)</a>
              </h3>
              <p class="he-program-card__text">
                Chương trình cử nhân tại đại học Hàn Quốc — đa ngành STEM, kinh tế, nghệ thuật; hồ sơ và visa cử nhân theo từng kỳ.
              </p>
              <span class="he-program-card__cta" aria-hidden="true">
                <span>Du học hệ đại học (D2-2)</span>
                <i class="bi bi-arrow-right-short he-program-card__cta-icon" aria-hidden="true"></i>
              </span>
            </div>
          </article>
        </div>
        <div>
          <article class="he-program-card">
            <div class="he-program-card__media">
              <img
                src="<?= $e($assetPath) ?>/img/du-hoc-han-quoc-he-thac-si-d2-3.jpg"
                alt="Du học hệ thạc sĩ (D2-3)"
                width="1184"
                height="1184"
                loading="lazy"
                decoding="async"
              />
              <span class="he-program-card__tag">D2-3 · Visa sau ĐH</span>
            </div>
            <div class="he-program-card__body">
              <h3 class="he-program-card__title">
                <a href="<?= $e($linkPrefix) ?>du-hoc-han-quoc-he-thac-si-d2-3.php" class="he-program-card__title-link stretched-link">Du học hệ thạc sĩ (D2-3)</a>
              </h3>
              <p class="he-program-card__text">
                Thạc sĩ, tiến sĩ và hướng nghiên cứu — kết nối giáo sư hướng dẫn, học bổng lab khi đủ điều kiện; visa sau đại học.
              </p>
              <span class="he-program-card__cta" aria-hidden="true">
                <span>Du học hệ thạc sĩ (D2-3)</span>
                <i class="bi bi-arrow-right-short he-program-card__cta-icon" aria-hidden="true"></i>
              </span>
            </div>
          </article>
        </div>
        <div>
          <article class="he-program-card">
            <div class="he-program-card__media">
              <img
                src="<?= $e($assetPath) ?>/img/du-hoc-han-quoc-he-cao-dang-d2-1.jpg"
                alt="Du học hệ cao đẳng (D2-1)"
                width="1184"
                height="1184"
                loading="lazy"
                decoding="async"
              />
              <span class="he-program-card__tag">D2-1 · Visa cao đẳng</span>
            </div>
            <div class="he-program-card__body">
              <h3 class="he-program-card__title">
                <a href="<?= $e($linkPrefix) ?>du-hoc-han-quoc-he-cao-dang-d2-1.php" class="he-program-card__title-link stretched-link">Du học hệ cao đẳng (D2-1)</a>
              </h3>
              <p class="he-program-card__text">
                Cao đẳng nghề — thực hành nhiều, thời gian đào tạo linh hoạt; phù hợp định hướng việc làm sớm cùng visa cao đẳng.
              </p>
              <span class="he-program-card__cta" aria-hidden="true">
                <span>Du học hệ cao đẳng (D2-1)</span>
                <i class="bi bi-arrow-right-short he-program-card__cta-icon" aria-hidden="true"></i>
              </span>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- Trường ĐH hàng đầu -->
  <section id="truong-dh" class="section-uni-partners py-5">
    <div class="container">
      <div class="text-center mb-5">
        <div class="title-line mx-auto mb-3"></div>
        <h2 class="section-title" id="truong-dh-heading">Đại học hàng đầu Hàn Quốc</h2>
        <p class="section-sub mt-2">
          Mạng lưới đối tác uy tín giúp tăng cơ hội học bổng và việc làm.
        </p>
      </div>
      <div class="section-uni-partners__grid">
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/9/9e/Seoul_national_university_logotype.svg"
                alt="Logo Đại học Quốc gia Seoul"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">SNU</p>
              <p class="uni-partner-card__name">Seoul National University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/f/f9/KOOKMINLOGO.jpg"
                alt="Logo Đại học Quốc gia Kookmin"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">KMU</p>
              <p class="uni-partner-card__name">Kookmin University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/4/49/Korea_University_logotype.svg"
                alt="Logo Đại học Korea"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">Korea Univ.</p>
              <p class="uni-partner-card__name">Korea University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/4/44/Sungkyunkwan_University_-_logotype_%28ko%2Ben%29.svg"
                alt="Logo Đại học Sungkyunkwan (SKKU)"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">SKKU</p>
              <p class="uni-partner-card__name">Sungkyunkwan University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/5/51/Hanyang_University_KORENG_logo.svg"
                alt="Logo Đại học Hanyang"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">Hanyang</p>
              <p class="uni-partner-card__name">Hanyang University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/2/2b/Kyung_Hee_University_Logo.svg"
                alt="Logo Đại học Kyung Hee"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">Kyung Hee</p>
              <p class="uni-partner-card__name">Kyung Hee University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/7/7b/Ewha_Womans_University_logotype.png"
                alt="Logo Đại học Ewha"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">Ewha</p>
              <p class="uni-partner-card__name">Ewha Womans University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/1/17/Chung-Ang_University_logo.png"
                alt="Logo Đại học Chung-Ang"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">CAU</p>
              <p class="uni-partner-card__name">Chung-Ang University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/f/fe/Konkuk_University_logotype.png"
                alt="Logo Đại học Konkuk"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">Konkuk</p>
              <p class="uni-partner-card__name">Konkuk University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/7/75/Inha_University_logotype.png"
                alt="Logo Đại học Inha"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">Inha</p>
              <p class="uni-partner-card__name">Inha University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/4/45/Pusan_national_university_logotype.png"
                alt="Logo Đại học Quốc gia Pusan"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">PNU</p>
              <p class="uni-partner-card__name">Pusan National University</p>
            </div>
          </article>
        </div>
        <div>
          <article class="uni-partner-card">
            <div class="uni-partner-card__logo">
              <img
                src="https://upload.wikimedia.org/wikipedia/commons/1/11/Dongguk_University_logo.png"
                alt="Logo Đại học Dongguk"
                width="200"
                height="80"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="uni-partner-card__body">
              <p class="uni-partner-card__abbr">Dongguk</p>
              <p class="uni-partner-card__name">Dongguk University</p>
            </div>
          </article>
        </div>
      </div>
      <div class="section-uni-partners__foot text-center">
        <a href="<?= $e($linkPrefix) ?>truong-dai-hoc.php" class="btn btn-sunny btn-sunny-pulse text-white section-uni-partners__btn">
          Xem tất cả các trường
          <i class="bi bi-arrow-right-short ms-1" aria-hidden="true"></i>
        </a>
      </div>
    </div>
  </section>

  <!-- Chúc mừng Visa -->
  <section id="visa" class="section-visa py-5">
    <div class="section-visa__glow" aria-hidden="true"></div>
    <div class="container">
      <header class="flex flex-wrap section-visa__head items-start lg:items-center gap-4 mb-4 lg:mb-5">
        <div class="col-lg">
          <div class="title-line mb-3"></div>
          <h2 class="section-title mb-2" id="visa-heading">Chúc mừng học viên nhận Visa</h2>
          <p class="section-sub mt-2 mb-0">
            Niềm vui chung của SUNNY và gia đình — cột mốc mở ra hành trình mới tại Hàn Quốc.
          </p>
        </div>
        <div class="w-full lg:w-auto">
          <a href="#dang-ky" class="btn btn-sunny btn-sunny-pulse text-white w-full lg:w-auto">Bạn là học viên tiếp theo</a>
        </div>
      </header>

      <div class="visa-swiper-shell">
        <div class="swiper visa-swiper">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-1.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-2.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-3.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-4.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-5.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-6.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-7.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-8.jpg\')"
                ></div>
              </article>
            </div>
            <div class="swiper-slide">
              <article class="visa-celebrate-card">
                <div
                  class="visa-celebrate-card__media visa-celebrate-card__media--bg"
                  role="img"
                  aria-label="<?= $e("Học viên SUNNY nhận visa du học Hàn Quốc") ?>"
                  style="background-image: url(\'<?= $e($assetPath) ?>/img/dat-toppik/dat-topik-cung-sunny-9.jpg\')"
                ></div>
              </article>
            </div>
          </div>
        </div>
        <button type="button" class="visa-swiper-btn visa-swiper-btn--prev" aria-label="Ảnh trước">
          <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" class="visa-swiper-btn visa-swiper-btn--next" aria-label="Ảnh sau">
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
      </div>
    </div>
  </section>

  <!-- Hoạt động Sunny -->
  <section id="hoat-dong" class="section-hoat-dong py-5">
    <div class="container">
      <div class="section-hoat-dong__row">
        <div class="section-hoat-dong__aside flex flex-col">
          <div class="title-line mb-3"></div>
          <h2 class="section-title section-hoat-dong__title mb-2">Hoạt động của Sunny</h2>
          <p class="section-hoat-dong__lead mb-4">Workshop, gặp gỡ cựu du học sinh và sự kiện tuyển sinh trực tiếp.</p>
          <div class="section-hoat-dong__filters flex flex-wrap gap-2" role="group" aria-label="Lọc theo loại hoạt động">
            <button type="button" class="hoat-dong-filter is-active" data-hoat-dong-filter="all">Tất cả</button>
            <button type="button" class="hoat-dong-filter" data-hoat-dong-filter="workshop">Workshop</button>
            <button type="button" class="hoat-dong-filter" data-hoat-dong-filter="talkshow">Talkshow</button>
            <button type="button" class="hoat-dong-filter" data-hoat-dong-filter="tuyen-sinh">Tuyển sinh</button>
          </div>
          <div class="section-hoat-dong__nav flex items-center gap-2 mt-4 mt-lg-auto lg:pt-3">
            <button type="button" class="hoat-dong-nav-btn hoat-dong-nav-btn--prev" aria-label="Slide trước">
              <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="hoat-dong-nav-btn hoat-dong-nav-btn--toggle" aria-label="Tạm dừng tự chạy" aria-pressed="false">
              <i class="bi bi-pause-fill hoat-dong-nav-btn__icon-pause" aria-hidden="true"></i>
              <i class="bi bi-play-fill hoat-dong-nav-btn__icon-play hidden" aria-hidden="true"></i>
            </button>
            <button type="button" class="hoat-dong-nav-btn hoat-dong-nav-btn--next" aria-label="Slide sau">
              <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        </div>
        <div class="section-hoat-dong__slider-col">
          <div class="swiper hoat-dong-swiper">
            <div class="swiper-wrapper">
              <div class="swiper-slide" data-hoat-dong-cat="workshop">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&q=80" alt="" width="600" height="375" loading="lazy" decoding="async" />
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge hoat-dong-card__badge--sun" title="Workshop">WS</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">18</span>
                        <span class="hoat-dong-card__ym">2026.04</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="#" class="stretched-link no-underline text-inherit">Workshop “Lộ trình 12 tháng” du học Hàn</a>
                    </h3>
                    <p class="hoat-dong-card__desc">Lộ trình học tiếng, hồ sơ visa và nhập học — checklist theo từng quý.</p>
                    <footer class="hoat-dong-card__foot">2026-04-16 ~ 2026-04-18</footer>
                  </div>
                </article>
              </div>
              <div class="swiper-slide" data-hoat-dong-cat="talkshow">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=600&q=80" alt="" width="600" height="375" loading="lazy" decoding="async" />
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge hoat-dong-card__badge--violet" title="Talkshow">TS</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">22</span>
                        <span class="hoat-dong-card__ym">2026.04</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="#" class="stretched-link no-underline text-inherit">Talkshow cựu du học sinh — kinh nghiệm thực tế</a>
                    </h3>
                    <p class="hoat-dong-card__desc">Giao lưu với anh chị đang học và làm việc tại Seoul, Busan.</p>
                    <footer class="hoat-dong-card__foot">2026-04-20 ~ 2026-04-20</footer>
                  </div>
                </article>
              </div>
              <div class="swiper-slide" data-hoat-dong-cat="tuyen-sinh">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=600&q=80" alt="" width="600" height="375" loading="lazy" decoding="async" />
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge hoat-dong-card__badge--teal" title="Tuyển sinh">TH</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">05</span>
                        <span class="hoat-dong-card__ym">2026.05</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="#" class="stretched-link no-underline text-inherit">Ngày hội tuyển sinh — đại diện 15+ trường</a>
                    </h3>
                    <p class="hoat-dong-card__desc">Phỏng vấn sơ bộ, học bổng và học phí từng kỳ nhập học.</p>
                    <footer class="hoat-dong-card__foot">2026-05-03 ~ 2026-05-04</footer>
                  </div>
                </article>
              </div>
              <div class="swiper-slide" data-hoat-dong-cat="workshop">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=600&q=80" alt="" width="600" height="375" loading="lazy" decoding="async" />
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge hoat-dong-card__badge--sun" title="Workshop">WS</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">12</span>
                        <span class="hoat-dong-card__ym">2026.05</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="#" class="stretched-link no-underline text-inherit">Workshop chứng minh tài chính &amp; phỏng vấn ĐSQ</a>
                    </h3>
                    <p class="hoat-dong-card__desc">Diễn giải sổ tiết kiệm, sao kê và câu hỏi thường gặp.</p>
                    <footer class="hoat-dong-card__foot">2026-05-10 ~ 2026-05-10</footer>
                  </div>
                </article>
              </div>
              <div class="swiper-slide" data-hoat-dong-cat="talkshow">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=600&q=80" alt="" width="600" height="375" loading="lazy" decoding="async" />
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge hoat-dong-card__badge--violet" title="Talkshow">TS</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">28</span>
                        <span class="hoat-dong-card__ym">2026.05</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="#" class="stretched-link no-underline text-inherit">Gặp gỡ phụ huynh — đồng hành cùng con</a>
                    </h3>
                    <p class="hoat-dong-card__desc">Chia sẻ về chi phí, nhà ở và hỗ trợ từ Sunny.</p>
                    <footer class="hoat-dong-card__foot">2026-05-28 ~ 2026-05-28</footer>
                  </div>
                </article>
              </div>
              <div class="swiper-slide" data-hoat-dong-cat="tuyen-sinh">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="https://images.unsplash.com/photo-1434030216411-0b793fccb5c8?w=600&q=80" alt="" width="600" height="375" loading="lazy" decoding="async" />
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge hoat-dong-card__badge--teal" title="Tuyển sinh">TH</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">14</span>
                        <span class="hoat-dong-card__ym">2026.06</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="#" class="stretched-link no-underline text-inherit">Hội thảo online: TOPIK &amp; nhập học kỳ thu</a>
                    </h3>
                    <p class="hoat-dong-card__desc">Đăng ký trực tuyến, tư vấn 1-1 sau sự kiện.</p>
                    <footer class="hoat-dong-card__foot">2026-06-14 ~ 2026-06-14</footer>
                  </div>
                </article>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Tin tức -->
  <section id="tin-tuc" class="section-tin-tuc py-5">
    <div class="section-tin-tuc__decor" aria-hidden="true">SUNNY</div>
    <div class="container relative">
      <div class="flex flex-wrap items-end justify-between gap-3 mb-4 lg:mb-5">
        <div class="grow min-w-0 basis-0">
          <div class="title-line mb-3"></div>
          <h2 class="section-title mb-2" id="tin-tuc-heading">Tin tức</h2>
          <p class="section-tin-tuc__lead mb-0">Chính sách, học bổng và mẹo hồ sơ — cập nhật thường xuyên.</p>
        </div>
        <div class="w-auto shrink-0">
          <div class="tin-tuc-toolbar flex items-center gap-2">
            <button type="button" class="tin-tuc-toolbar__btn tin-tuc-toolbar__btn--arrow tin-tuc-toolbar__btn--prev" aria-label="Tin trước">
              <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="tin-tuc-toolbar__btn tin-tuc-toolbar__btn--arrow tin-tuc-toolbar__btn--next" aria-label="Tin sau">
              <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
            <a href="<?= $e($linkPrefix) ?>tin-tuc.php" class="tin-tuc-toolbar__btn tin-tuc-toolbar__btn--all" aria-label="Xem tất cả tin">
              <i class="bi bi-plus-lg" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>

      <div class="swiper tin-tuc-swiper">
            <div class="swiper-wrapper">
              <div class="swiper-slide">
                <article class="news-card news-card--compact relative h-full">
                  <div class="news-card__media">
                    <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=640&q=80" alt="" width="640" height="400" loading="lazy" decoding="async" />
                  </div>
                  <div class="news-card__body">
                    <h3 class="news-card__title">
                      <a href="<?= $e($linkPrefix) ?>chon-truong-du-hoc-han-quoc-2026.php" class="stretched-link no-underline">Chọn trường du học Hàn Quốc 2026</a>
                    </h3>
                    <div class="news-card__meta">
                      <time class="news-card__date" datetime="2026-04-27">27/04/2026</time>
                      <span class="news-card__views" aria-label="3.856 lượt xem"><i class="bi bi-eye" aria-hidden="true"></i>3.856</span>
                    </div>
                  </div>
                </article>
              </div>
              <div class="swiper-slide">
                <article class="news-card news-card--compact relative h-full">
                  <div class="news-card__media">
                    <img src="https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=640&q=80" alt="" width="640" height="400" loading="lazy" decoding="async" />
                  </div>
                  <div class="news-card__body">
                    <h3 class="news-card__title">
                      <a href="<?= $e($linkPrefix) ?>tin-visa-d2-ho-so-chuan-bi.php" class="stretched-link no-underline">Những thay đổi cần lưu ý khi chuẩn bị hồ sơ visa D-2</a>
                    </h3>
                    <div class="news-card__meta">
                      <time class="news-card__date" datetime="2026-04-17">17/04/2026</time>
                      <span class="news-card__views" aria-label="2.431 lượt xem"><i class="bi bi-eye" aria-hidden="true"></i>2.431</span>
                    </div>
                  </div>
                </article>
              </div>
              <div class="swiper-slide">
                <article class="news-card news-card--compact relative h-full">
                  <div class="news-card__media">
                    <img src="https://images.unsplash.com/photo-1434030216411-0b793fccb5c8?w=640&q=80" alt="" width="640" height="400" loading="lazy" decoding="async" />
                  </div>
                  <div class="news-card__body">
                    <h3 class="news-card__title">
                      <a href="<?= $e($linkPrefix) ?>tin-tuc.php" class="stretched-link no-underline">Học bổng kỳ mùa thu: ngành nào đang “hot”?</a>
                    </h3>
                    <div class="news-card__meta">
                      <time class="news-card__date" datetime="2026-04-12">12/04/2026</time>
                      <span class="news-card__views" aria-label="1.089 lượt xem"><i class="bi bi-eye" aria-hidden="true"></i>1.089</span>
                    </div>
                  </div>
                </article>
              </div>
              <div class="swiper-slide">
                <article class="news-card news-card--compact relative h-full">
                  <div class="news-card__media">
                    <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=640&q=80" alt="" width="640" height="400" loading="lazy" decoding="async" />
                  </div>
                  <div class="news-card__body">
                    <h3 class="news-card__title">
                      <a href="<?= $e($linkPrefix) ?>tin-tuc.php" class="stretched-link no-underline">10 điều nên làm trong tuần đầu ở Hàn Quốc</a>
                    </h3>
                    <div class="news-card__meta">
                      <time class="news-card__date" datetime="2026-04-05">05/04/2026</time>
                      <span class="news-card__views" aria-label="734 lượt xem"><i class="bi bi-eye" aria-hidden="true"></i>734</span>
                    </div>
                  </div>
                </article>
              </div>
              <div class="swiper-slide">
                <article class="news-card news-card--compact relative h-full">
                  <div class="news-card__media">
                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=640&q=80" alt="" width="640" height="400" loading="lazy" decoding="async" />
                  </div>
                  <div class="news-card__body">
                    <h3 class="news-card__title">
                      <a href="<?= $e($linkPrefix) ?>tin-tuc.php" class="stretched-link no-underline">Kỳ nhập học mùa xuân: deadline hồ sơ &amp; lệ phí</a>
                    </h3>
                    <div class="news-card__meta">
                      <time class="news-card__date" datetime="2026-03-28">28/03/2026</time>
                      <span class="news-card__views" aria-label="521 lượt xem"><i class="bi bi-eye" aria-hidden="true"></i>521</span>
                    </div>
                  </div>
                </article>
              </div>
              <div class="swiper-slide">
                <article class="news-card news-card--compact relative h-full">
                  <div class="news-card__media">
                    <img src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=640&q=80" alt="" width="640" height="400" loading="lazy" decoding="async" />
                  </div>
                  <div class="news-card__body">
                    <h3 class="news-card__title">
                      <a href="<?= $e($linkPrefix) ?>tin-tuc.php" class="stretched-link no-underline">Mẹo phỏng vấn ĐSQ: câu hỏi thường gặp</a>
                    </h3>
                    <div class="news-card__meta">
                      <time class="news-card__date" datetime="2026-03-15">15/03/2026</time>
                      <span class="news-card__views" aria-label="1.402 lượt xem"><i class="bi bi-eye" aria-hidden="true"></i>1.402</span>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </div>
    </div>
  </section>
',
  ),
);
