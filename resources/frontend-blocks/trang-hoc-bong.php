<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 14,
  'slug' => 'trang-hoc-bong',
  'name' => 'Trang học bổng',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:42:14',
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
            <li class="page-about-breadcrumb__step">
              <a href="/#thong-tin" class="page-about-breadcrumb__link">Thông tin du học</a>
            </li>
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">Học bổng du học Hàn Quốc năm 2026</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <p class="about-intro-hero__kicker">
              <span class="about-intro-hero__kicker-icon" aria-hidden="true"><i class="bi bi-award"></i></span>
              Học bổng · Du học Hàn Quốc 2026
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">Học bổng du học Hàn Quốc <span class="text-primary-sunny">2026</span></h1>
            <p class="about-intro-hero__lead about-intro-hero__lead--sm mb-0">
              <strong class="about-intro-hero__strong">Học bổng du học Hàn Quốc năm 2026</strong> gồm nhiều dòng: từ
              <strong class="about-intro-hero__strong">học bổng Chính phủ (GKS)</strong>,
              <strong class="about-intro-hero__strong">học bổng của từng trường</strong> (nhập học — thành tích — hỗ trợ KTX), tới các
              <strong class="about-intro-hero__strong">chương trình đối tác</strong> với quota và tiêu chí riêng.
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              Bài viết giúp bạn <strong class="about-intro-hero__strong">phân loại nhanh</strong>, nắm
              <strong class="about-intro-hero__strong">hồ sơ thường cần</strong> (học lực, TOPIK / tiếng Anh, thư giới thiệu, kế hoạch học tập) và
              <strong class="about-intro-hero__strong">mốc thời gian</strong> cần bám sát.
              <strong class="about-intro-hero__strong">Mọi suất học bổng</strong> đều lấy
              <strong class="about-intro-hero__strong">thông báo chính thức</strong> của cơ quan tổ chức và trường tại thời điểm bạn nộp —
              SUNNY hỗ trợ <strong class="about-intro-hero__strong">đối chiếu và tối ưu hồ sơ</strong> theo mục tiêu của bạn.
            </p>
            <div class="about-intro-hero__chips mt-3" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-flag" aria-hidden="true"></i> GKS &amp; Chính phủ</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-building" aria-hidden="true"></i> Suất theo trường</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-folder-symlink" aria-hidden="true"></i> Hồ sơ &amp; deadline</span>
            </div>
            <a href="#tong-quan" class="about-intro-hero__cta mt-3">
              <span>Đọc tổng quan &amp; phân loại học bổng</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Bám sát nguồn chính thức</p>
              <p class="about-intro-hero__panel-title">Bốn nhóm học bổng hay gặp khi lập kế hoạch 2026</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-globe2"></i></span>
                  <span><strong>GKS / Global Korea:</strong> khung đối tác — ngành — bậc học theo từng đợt; xem thêm <a href="hoc-bong-gks-chinh-phu-han-quoc.php">hướng dẫn GKS</a>.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                  <span><strong>Học bổng trường:</strong> nhập học, duy trì điểm, đôi khi gắn KTX hoặc học kỳ đầu — đọc điều khoản từng suất.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
                  <span><strong>Đối tác &amp; quota:</strong> tiêu chí và hạn nộp riêng; so khớp với <a href="chon-truong-du-hoc-han-quoc-2026.php">chọn trường 2026</a> trước khi ôm quá nhiều suất.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                  <span><strong>Hồ sơ &amp; timeline:</strong> GPA, chứng chỉ, thư giới thiệu, study plan — lịch nộp trong bài (mục <a href="#lich-nop">lịch &amp; kỳ nộp</a>, <a href="#ho-so">checklist</a>).</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="news-detail-body py-4 lg:py-5">
      <div class="container">
        <div class="flex flex-wrap gap-4 xl:gap-5 items-start">
          <div class="w-full">
            <div class="news-detail-toc--mobile mb-3 mb-md-4" id="muc-luc-mobile">
              <div class="accordion" id="accordionMucLuc">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingMucLuc">
                    <button
                      class="accordion-button collapsed"
                      type="button"
                      data-tw-toggle="collapse"
                      data-tw-target="#collapseMucLuc"
                      aria-expanded="false"
                      aria-controls="collapseMucLuc"
                    >
                      <i class="bi bi-list-nested me-2 text-bs-primary" aria-hidden="true"></i>
                      Mục lục nhanh
                    </button>
                  </h2>
                  <div
                    id="collapseMucLuc"
                    class="accordion-collapse collapse"
                    aria-labelledby="headingMucLuc"
                    data-tw-parent="#accordionMucLuc"
                  >
                    <div class="accordion-body">
                      <nav class="news-detail-toc" aria-label="Mục lục bài viết (màn hình nhỏ)">
                        <ul class="news-detail-toc__list">
                          <li><a href="#tong-quan">Tổng quan</a></li>
                          <li><a href="#cac-loai">Các loại học bổng</a></li>
                          <li><a href="#dieu-kien">Điều kiện xét</a></li>
                          <li><a href="#lich-nop">Lịch &amp; kỳ nộp</a></li>
                          <li><a href="#ho-so">Checklist hồ sơ</a></li>
                          <li><a href="#meo-nop">Mẹo &amp; sai lầm</a></li>
                          <li><a href="#faq-hoc-bong">FAQ</a></li>
                          <li><a href="#ket-luan">Kết luận</a></li>
                        </ul>
                      </nav>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="news-detail-article">
              <div class="news-detail-figure relative w-full aspect-[21/9] mb-3 mb-md-4">
                <img
                  class="object-fit-cover"
                  src="https://images.unsplash.com/photo-1434030216411-0b793fccb5c8?w=1200&amp;q=80"
                  alt="Học bổng du học Hàn Quốc năm 2026 — du học sinh và cơ hội học bổng"
                  width="1200"
                  height="514"
                  fetchpriority="high"
                  loading="eager"
                  decoding="async"
                />
              </div>

              <div class="news-detail-keypoints" role="list">
                <div class="news-detail-key" role="listitem">
                  <div class="news-detail-key__icon" aria-hidden="true">
                    <i class="bi bi-award"></i>
                  </div>
                  <div class="news-detail-key__text">
                    <strong>GKS &amp; học bổng lớn</strong>
                    <span>Quota, ngành và lộ trình nộp riêng — cập nhật theo năm.</span>
                  </div>
                </div>
                <div class="news-detail-key" role="listitem">
                  <div class="news-detail-key__icon" aria-hidden="true">
                    <i class="bi bi-building"></i>
                  </div>
                  <div class="news-detail-key__text">
                    <strong>Học bổng trường</strong>
                    <span>Nhập học, GPA, TOPIK — mỗi trường một bảng điều kiện.</span>
                  </div>
                </div>
                <div class="news-detail-key" role="listitem">
                  <div class="news-detail-key__icon" aria-hidden="true">
                    <i class="bi bi-file-earmark-text"></i>
                  </div>
                  <div class="news-detail-key__text">
                    <strong>Hồ sơ &amp; deadline</strong>
                    <span>Thư giới thiệu, kế hoạch học tập — chuẩn bị sớm theo kỳ.</span>
                  </div>
                </div>
              </div>

              <div class="news-article text-black">
                <section id="tong-quan" class="news-detail-section news-section-opener" aria-labelledby="hl-tong-quan">
                  <div class="news-section-opener__shell">
                    <header class="news-section-opener__head">
                      <span class="news-section-opener__step-num" aria-hidden="true">01</span>
                      <div class="news-section-opener__head-text">
                        <h2 class="news-section-opener__title" id="hl-tong-quan">Học bổng du học Hàn Quốc năm 2026: bức tranh tổng thể</h2>
                        <p class="news-section-opener__kicker">
                          Năm 2026, “săn học bổng” hiệu quả thường là <strong>chiến lược chồng nhiều lớp</strong> — một suất ưu tiên (ví dụ <strong>GKS</strong>) và các suất <strong>theo trường — khoa</strong> — chứ không phải đặt cược vào một con số điểm “đủ là đậu mọi nơi”.
                        </p>
                      </div>
                    </header>

                    <div class="news-section-opener__flow" role="list">
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-layers"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Đa nguồn: suất ưu tiên + học bổng trường / khoa</p>
                          <p class="news-section-opener__flow-desc">
                            Trong <strong>học bổng du học Hàn Quốc năm 2026</strong>, phần lớn ứng viên sẽ kết hợp <strong>nhiều nguồn</strong>: một suất <strong>ưu tiên cao</strong> (ví dụ <a href="hoc-bong-gks-chinh-phu-han-quoc.php">GKS — Global Korea Scholarship</a>) và các <strong>học bổng của trường hoặc khoa</strong> (nhập học, thành tích, hỗ trợ KTX). Mỗi nguồn có <strong>điều khoản loại trừ</strong> khác nhau — đọc kỹ trước khi kỳ vọng “cộng dồn mọi suất”.
                          </p>
                        </div>
                      </div>
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Quota, ngành mở và timeline — không có ngưỡng chung</p>
                          <p class="news-section-opener__flow-desc">
                            Mỗi chương trình có <strong>quota</strong>, <strong>danh sách ngành được mở</strong> và <strong>mốc nộp — công bố</strong> riêng; <strong>không có “một mức điểm chung”</strong> áp cho mọi suất. Cùng một GPA hay TOPIK có thể <strong>đủ ở suất A</strong> nhưng <strong>chưa đủ ở suất B</strong> — vì hội đồng xét thêm luận, phỏng vấn, quốc tịch hoặc hồ sơ nghiên cứu (đặc biệt <a href="he-thac-si-d2-3.php">sau đại học</a>).
                          </p>
                        </div>
                      </div>
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-intersect"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Hồ sơ “khớp” khi ba trụ song song</p>
                          <p class="news-section-opener__flow-desc">
                            Để hồ sơ học bổng <strong>khớp thực tế</strong>, cần làm song song <a href="dieu-kien-du-hoc-han-quoc-2026.php">điều kiện du học Hàn Quốc 2026</a>, <a href="chon-truong-du-hoc-han-quoc-2026.php">chọn trường</a> và <a href="chi-phi-du-hoc-han-quoc-2026.php">chi phí — ngân sách</a>. Học bổng gần như luôn gắn với <strong>trường — ngành — kỳ nhập học</strong> cụ thể; nếu chỉ tối ưu luận mà lệch <strong>visa, chứng minh tài chính hoặc deadline trường</strong>, bạn dễ bị kẹt ở bước sau dù suất vẫn “trên lý thuyết” phù hợp.
                          </p>
                        </div>
                      </div>
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Danh mục theo đợt trên Học bổng SUNNY</p>
                          <p class="news-section-opener__flow-desc">
                            <a href="hoc-bong.php">Trang Học bổng SUNNY</a> tổng hợp suất và hướng dẫn cập nhật <strong>theo từng đợt</strong> — dùng để lọc nhanh, rồi luôn quay về <strong>thông báo chính thức</strong> của NIIED / trường / đối tác tại thời điểm bạn nộp. SUNNY hỗ trợ <strong>đối chiếu</strong> giữa danh mục và hồ sơ thực tế của bạn.
                          </p>
                        </div>
                      </div>
                    </div>

                    <p>
                      Một <strong>study plan</strong> hay <strong>bài luận</strong> mạnh thường không nói chung chung về “yêu Hàn Quốc”, mà chỉ rõ <strong>vì sao bạn chọn đúng chương trình — đúng giảng viên / hướng nghiên cứu — đúng lộ trình nghề</strong> mà suất học bổng đó đang tài trợ. Vì vậy, việc đọc sớm <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học Hàn Quốc</a> và brochure trường giúp câu chuyện trong hồ sơ <strong>nhất quán</strong> với phần điều kiện xét (xem thêm <a href="#dieu-kien">mục điều kiện</a> và <a href="#ho-so">checklist hồ sơ</a>).
                    </p>
                    <p>
                      Cuối cùng, hãy chuẩn bị <strong>kịch bản tài chính nếu không đậu suất đầu</strong>: nhiều gia đình vẫn du học được nhờ <strong>ngân sách tự túc có kiểm soát</strong> hoặc xét <strong>học bổng kỳ sau</strong> khi đã có GPA tại Hàn — điều này liên quan trực tiếp tới bảng chi trong <a href="chi-phi-du-hoc-han-quoc-2026.php">Chi phí 2026</a> và quyết định shortlist ở <a href="chon-truong-du-hoc-han-quoc-2026.php">Chọn trường 2026</a>.
                    </p>

                    <h3 class="news-section-opener__subheading">Checklist trước khi đi sâu vào từng loại học bổng</h3>
                    <ul class="news-section-opener__checklist">
                      <li>Bạn đã có <strong>2–3 trường / chương trình</strong> cụ thể (hoặc khung GKS) để không viết hồ sơ “trôi” không điểm neo?</li>
                      <li><strong>Chứng chỉ và bảng điểm</strong> có khớp ngưỡng <em>thực tế</em> của suất bạn nhắm — không chỉ ngưỡng tuyển sinh chung?</li>
                      <li><strong>Timeline dịch công chứng — nộp online — phỏng vấn</strong> đã được lùi sớm hơn deadline trường ít nhất vài tuần?</li>
                      <li>Gia đình đã hiểu <strong>quyền lợi suất</strong> (giảm % học phí vs sinh hoạt vs KTX) để không shock khi đọc điều khoản sau trúng tuyển?</li>
                    </ul>

                    <p class="news-section-opener__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Bài mang tính <strong>định hướng</strong>. Quota, ngành, hạn nộp và điều kiện xét lấy theo <strong>văn bản chính thức từng đợt</strong>. Đọc tiếp <a href="#cac-loai">mục các loại học bổng</a> để phân tầng chi tiết hơn.
                    </p>
                  </div>
                </section>

                <section id="cac-loai" class="news-detail-section news-finance-section" aria-labelledby="hl-cac-loai">
                  <div class="news-finance-section__shell">
                    <header class="news-finance-section__head">
                      <span class="news-finance-section__step-num" aria-hidden="true">02</span>
                      <div class="news-finance-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-cac-loai">Các loại học bổng du học Hàn Quốc năm 2026 thường gặp</h2>
                        <p class="news-section-opener__kicker">
                          Phân loại đúng giúp bạn <strong>không nhầm “học bổng trường” với “học bổng Chính phủ”</strong>, biết <strong>thời điểm nộp</strong> và <strong>điều kiện duy trì</strong> — mỗi nhóm dưới đây có logic xét và rủi ro “mất suất” khác nhau sau kỳ đầu.
                        </p>
                      </div>
                    </header>

                    <p class="news-finance-section__intro">
                      Trên thực tế, một hồ sơ mạnh năm 2026 thường được <strong>dựng theo lớp</strong>: ưu tiên một dòng <strong>quy mô lớn</strong> (nếu phù hợp), đồng thời giữ <strong>phương án trường — khoa</strong> để không bị “trắng tay” nếu vòng xét đầu quá cạnh tranh. Dù tên gọi marketing có thể khác nhau, bốn nhóm dưới đây là khung làm việc SUNNY thường dùng khi tư vấn — luôn đối chiếu lại <strong>PDF tuyển sinh</strong> và <strong>điều khoản suất</strong> tại thời điểm bạn nộp.
                    </p>

                    <div class="news-finance-section__sources">
                      <article class="news-finance-source news-finance-source--savings" aria-labelledby="hl-hb-gks">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-flag"></i></span>
                          <h3 class="news-finance-source__title" id="hl-hb-gks">Học bổng Chính phủ (GKS)</h3>
                        </div>
                        <p>
                          <strong>Global Korea Scholarship (GKS)</strong> và các chương trình tương đương thường có <strong>quy mô lớn</strong>, <strong>quota theo quốc gia — bậc học</strong> và <strong>quy trình nộp — sơ tuyển — phỏng vấn</strong> theo hướng dẫn từng năm. Đây không phải “một form giống hệt học bổng trường”: bạn cần bám <strong>timeline NIIED / ĐSQ</strong> và danh mục trường — ngành được mở trong đợt đó.
                        </p>
                        <ul class="news-finance-source__list">
                          <li>Chi tiết lộ trình và tài liệu tham khảo: <a href="hoc-bong-gks-chinh-phu-han-quoc.php">bài GKS</a> + văn bản chính thức năm bạn ứng tuyển.</li>
                          <li>Kết hợp đọc <a href="#tong-quan">tổng quan</a> và <a href="chon-truong-du-hoc-han-quoc-2026.php">chọn trường 2026</a> để trường trong danh sách GKS khớp mục tiêu nghề nghiệp.</li>
                          <li>Một số suất có <strong>điều khoản sau trúng tuyển</strong> (duy trì GPA, khóa học bắt buộc) — đừng chỉ nhìn “toàn phần / một phần” trên poster.</li>
                        </ul>
                      </article>

                      <article class="news-finance-source news-finance-source--income" aria-labelledby="hl-hb-nhap-hoc">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-building"></i></span>
                          <h3 class="news-finance-source__title" id="hl-hb-nhap-hoc">Học bổng nhập học của trường</h3>
                        </div>
                        <p>
                          Đây là nhóm <strong>phổ biến nhất</strong> với ứng viên quốc tế: thường là <strong>giảm phần trăm học phí</strong>, <strong>miễn giảm phí nhập học</strong>, hoặc <strong>gói hỗ trợ KTX / sinh hoạt kỳ đầu</strong>. Xét cạnh tranh dựa trên <strong>học lực</strong>, <strong>TOPIK / IELTS</strong>, bài luận, đôi khi <strong>portfolio</strong> với ngành nghệ thuật — thiết kế — truyền thông.
                        </p>
                        <ul class="news-finance-source__list">
                          <li>So sánh <strong>cùng tên suất</strong> giữa hai trường — giá trị và điều kiện có thể khác hẳn.</li>
                          <li>Kiểm tra suất có bắt buộc <strong>ở KTX</strong> hoặc giới hạn <strong>chuyển ngành</strong> sau nhập học không.</li>
                          <li>Cập nhật tổng chi sau suất trong <a href="chi-phi-du-hoc-han-quoc-2026.php">Chi phí 2026</a> để gia đình thấy rõ “còn phải đóng gì”.</li>
                        </ul>
                      </article>

                      <article class="news-finance-source news-finance-source--sponsor" aria-labelledby="hl-hb-in-course">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                          <h3 class="news-finance-source__title" id="hl-hb-in-course">Học bổng đang học (in-course)</h3>
                        </div>
                        <p>
                          Sau khi đã nhập học, nhiều trường mở đợt xét dựa trên <strong>GPA kỳ trước</strong>, <strong>thành tích nghiên cứu — lab</strong>, giải thưởng trong trường, hoặc vai trò <strong>đại diện sinh viên quốc tế</strong>. Đây là lớp học bổng quan trọng nếu bạn <strong>trượt suất nhập học</strong> nhưng vẫn muốn giảm áp lực tài chính các năm sau.
                        </p>
                        <ul class="news-finance-source__list">
                          <li>Đọc kỹ <strong>ngưỡng tín chỉ tối thiểu</strong> và điều kiện <strong>không nợ môn / không cảnh báo học vụ</strong>.</li>
                          <li>Một số suất yêu cầu <strong>bài luận ngắn</strong> hoặc minh chứng hoạt động — chuẩn bị sớm cuối kỳ.</li>
                          <li>Chiến lược “vào trường trước — xét suất sau” cần <strong>ngân sách năm đầu</strong> chắc chắn; xem thêm <a href="#tong-quan">mục tổng quan</a>.</li>
                        </ul>
                      </article>

                      <article class="news-finance-source news-finance-source--award" aria-labelledby="hl-hb-nganh-doi-tac">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
                          <h3 class="news-finance-source__title" id="hl-hb-nganh-doi-tac">Học bổng theo ngành / đối tác</h3>
                        </div>
                        <p>
                          Một số <strong>khoa, viện nghiên cứu hoặc doanh nghiệp</strong> đồng tài trợ suất có <strong>tiêu chí riêng</strong>: ví dụ gắn với STEM, logistics, game, du lịch — khách sạn, hoặc chương trình song ngữ. <strong>Số suất và điều kiện</strong> thường thay đổi theo năm học và <strong>ngân sách đối tác</strong>, nên thông tin năm cũ chỉ mang tính tham khảo.
                        </p>
                        <ul class="news-finance-source__list">
                          <li>Ưu tiên nguồn từ <strong>website khoa</strong>, thư từ giảng viên hoặc văn phòng hợp tác doanh nghiệp của trường.</li>
                          <li>Đối chiếu với <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học Hàn Quốc</a> để hiểu nhóm ngành trước khi kỳ vọng suất “hot”.</li>
                          <li>Một số suất yêu cầu <strong>cam kết thực tập hoặc định hướng nghề</strong> — đọc kỹ trước khi ký nhận.</li>
                        </ul>
                      </article>
                    </div>

                    <h3 class="news-finance-section__subheading">Ghi nhanh bốn cột trong bảng theo dõi của bạn</h3>
                    <ul class="news-finance-section__principles">
                      <li><strong>Loại &amp; tên suất:</strong> GKS / nhập học / in-course / đối tác — kèm link PDF hoặc mã form.</li>
                      <li><strong>Giá trị &amp; phạm vi:</strong> % học phí, KTX, sinh hoạt, hay chỉ một học kỳ — có hoàn trả khi vi phạm không.</li>
                      <li><strong>Điều kiện đầu vào vs duy trì:</strong> TOPIK, GPA tối thiểu sau kỳ 1, cấm chuyển campus…</li>
                      <li><strong>Deadline:</strong> trùng hay sớm hơn hạn nộp hồ sơ trường — ghi cả mốc nộp online và nộp bản cứng (nếu có).</li>
                    </ul>

                    <p class="news-finance-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Tên gọi học bổng trên mạng xã hội có thể <strong>không trùng</strong> tên trong brochure. Luôn lấy <strong>thông báo chính thức</strong> làm chuẩn; đọc tiếp <a href="#dieu-kien">mục điều kiện xét</a> để biết hội đồng thường nhìn những gì trong hồ sơ.
                    </p>
                  </div>
                </section>

                <section id="dieu-kien" class="news-detail-section news-health-section" aria-labelledby="hl-dieu-kien">
                  <div class="news-health-section__shell">
                    <header class="news-health-section__head">
                      <span class="news-health-section__step-num" aria-hidden="true">03</span>
                      <div class="news-health-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-dieu-kien">Điều kiện xét học bổng du học Hàn Quốc 2026</h2>
                        <p class="news-section-opener__kicker">
                          Không có “công thức một cục” cho mọi suất — nhưng hội đồng xét thường quay lại <strong>cùng một nhóm tín hiệu</strong>: học lực, ngôn ngữ, tính nhất quán của câu chuyện trong hồ sơ và mức độ <strong>khớp với ngành — trường — lộ trình visa</strong> bạn khai.
                        </p>
                      </div>
                    </header>

                    <p class="news-health-section__intro">
                      Dù từng suất khác nhau, phần lớn hồ sơ học bổng năm 2026 được đọc theo hai lớp: <strong>chứng cứ cứng</strong> (bảng điểm, chứng chỉ, minh chứng thành tích) và <strong>phần diễn giải</strong> (luận, study plan / research proposal, thư giới thiệu, portfolio). Hai lớp phải <strong>cùng một hướng</strong> — nếu điểm và chứng chỉ tốt nhưng luận viết chung chung hoặc mâu thuẫn với ngành đã chọn, hồ sơ dễ bị loại sớm dù “số đẹp trên giấy”.
                    </p>

                    <div class="news-health-section__dual">
                      <article class="news-health-panel news-health-panel--body" aria-labelledby="hl-dk-hoc-thuat">
                        <h3 class="news-health-panel__title" id="hl-dk-hoc-thuat">
                          <span class="news-health-panel__ico" aria-hidden="true"><i class="bi bi-journal-richtext"></i></span>
                          Học thuật &amp; chứng chỉ ngôn ngữ
                        </h3>
                        <p>
                          Hội đồng thường nhìn <strong>bảng điểm gốc</strong> (THPT / đại học), xu hướng điểm theo năm, và đôi khi <strong>ranking / học lực</strong> nếu trường bạn có quy ước rõ. Với chương trình tiếng Hàn hoặc song ngữ, <strong>TOPIK</strong> là tín hiệu phổ biến; với track quốc tế, <strong>IELTS Academic / TOEFL iBT</strong> thường được dùng như ngưỡng tối thiểu hoặc để xếp hạng ứng viên.
                        </p>
                        <ul class="news-health-panel__list">
                          <li>Kiểm tra <strong>thời hạn chứng chỉ</strong> còn được suất đó chấp nhận không — tránh thi quá sớm rồi đến lúc nộp đã hết hạn quy định.</li>
                          <li>Một số suất yêu cầu <strong>tuổi tối đa</strong>, <strong>năm tốt nghiệp THPT / ĐH</strong> hoặc <strong>khoảng cách năm</strong> kể từ bằng cấp trước — đọc kỹ trước khi đầu tư dịch công chứng.</li>
                          <li>Điều kiện xét học bổng <strong>không thay</strong> <a href="dieu-kien-du-hoc-han-quoc-2026.php">điều kiện du học &amp; visa</a>; hãy đối chiếu song song để tránh “đậu suất nhưng kẹt hồ sơ lãnh sự”.</li>
                        </ul>
                      </article>
                      <article class="news-health-panel news-health-panel--legal" aria-labelledby="hl-dk-story">
                        <h3 class="news-health-panel__title" id="hl-dk-story">
                          <span class="news-health-panel__ico" aria-hidden="true"><i class="bi bi-pencil-square"></i></span>
                          Luận, thư giới thiệu &amp; portfolio
                        </h3>
                        <p>
                          <strong>Thư giới thiệu</strong> nên do người thật sự hiểu năng lực học tập hoặc nghiên cứu của bạn — tránh mẫu chung chung, ký tên mơ hồ. <strong>Bài luận / kế hoạch học tập</strong> cần nói rõ vì sao bạn chọn <strong>đúng chương trình</strong> và <strong>đúng định hướng nghề</strong>; ở bậc <a href="he-thac-si-d2-3.php">sau đại học</a>, <strong>research proposal</strong> hoặc ý tưởng đề tài thường trọng số cao hơn.
                        </p>
                        <ul class="news-health-panel__list">
                          <li>Ngành <strong>nghệ thuật — thiết kế — kiến trúc</strong> thường cần <strong>portfolio</strong> theo format, dung lượng và deadline riêng — chuẩn bị sớm hơn hạn trường.</li>
                          <li>Tránh <strong>sao chép đoạn</strong> giữa đơn trường và đơn học bổng nếu hai bên hỏi khác góc — dễ bị phát hiện không nhất quán.</li>
                          <li>Gợi ý checklist chi tiết: mục <a href="#ho-so">hồ sơ</a> trong cùng bài và phân loại suất ở <a href="#cac-loai">các loại học bổng</a>.</li>
                        </ul>
                      </article>
                    </div>

                    <h3 class="news-health-section__subheading">Ba góc nhìn dễ bị bỏ qua khi đọc điều kiện</h3>
                    <div class="news-health-section__phases" role="list">
                      <div class="news-health-phase" role="listitem">
                        <span class="news-health-phase__label">Ngành</span>
                        <p class="news-health-phase__title">Cạnh tranh &amp; “ngưỡng ẩn”</p>
                        <p>
                          <strong>Ngành học</strong> ảnh hưởng trực tiếp tới số ứng viên và mức điểm — chứng chỉ “đủ để vào trường” đôi khi <strong>chưa đủ để vào vòng học bổng</strong>. Tham khảo <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học Hàn Quốc</a> để hiểu nhóm ngành trước khi chọn suất; chọn trường có chương trình khớp <a href="he-dai-hoc-d2.php">bậc cử nhân</a> hoặc <a href="he-tieng-d4.php">khóa tiếng D-4</a> nếu học bổng gắn với lộ trình đó.
                        </p>
                      </div>
                      <div class="news-health-phase" role="listitem">
                        <span class="news-health-phase__label">Quota</span>
                        <p class="news-health-phase__title">Quốc tịch, đối tượng &amp; giới hạn suất</p>
                        <p>
                          Nhiều chương trình có <strong>quota theo quốc gia</strong>, <strong>ưu tiên khu vực</strong> hoặc chỉ mở cho <strong>ứng viên quốc tế</strong> / một số bậc học. Một số suất giới hạn <strong>số lần đã từng nhận học bổng</strong> hoặc yêu cầu <strong>chưa từng học tại Hàn</strong> — đọc điều khoản thay vì chỉ nhìn poster tóm tắt.
                        </p>
                      </div>
                      <div class="news-health-phase" role="listitem">
                        <span class="news-health-phase__label">Khớp</span>
                        <p class="news-health-phase__title">Cùng logic với đơn nhập học</p>
                        <p>
                          Tên trường, ngành, kỳ nhập học và địa chỉ liên hệ trong <strong>form học bổng</strong> nên <strong>khớp</strong> với hồ sơ xét tuyển — tránh mâu thuẫn khiến hội đồng nghi ngờ tính xác thực. Khi đã shortlist, xem thêm <a href="chon-truong-du-hoc-han-quoc-2026.php">Chọn trường 2026</a> để đồng bộ timeline.
                        </p>
                      </div>
                    </div>

                    <h3 class="news-health-section__subheading">Nguyên tắc khi tự đối chiếu điều kiện</h3>
                    <ul class="news-health-section__principles">
                      <li><strong>Ưu tiên văn bản chính thức:</strong> PDF trường, FAQ, form GKS — hơn là bài tổng hợp trên mạng không ghi rõ năm.</li>
                      <li><strong>Tách “điều kiện tuyển” và “điều kiện học bổng”:</strong> hai bảng có thể khác nhau; đừng tự suy diễn.</li>
                      <li><strong>Điều kiện duy trì:</strong> GPA tối thiểu, không vi phạm kỷ luật, không vượt quá X tín chỉ nợ — ảnh hưởng cả năm sau.</li>
                      <li><strong>Nhờ rà soát thứ hai:</strong> giáo viên, cố vấn hoặc SUNNY đọc lại luận — thư GT trước khi đóng đơn.</li>
                    </ul>

                    <p class="news-health-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Danh sách điều kiện mang tính <strong>định hướng</strong>; mỗi suất có bộ tiêu chí riêng tại thời điểm nộp. SUNNY hỗ trợ bạn <strong>đối chiếu năng lực thực tế</strong> với suất đang nhắm tới và chỉnh narrative trong hồ sơ.
                    </p>
                  </div>
                </section>

                <section id="lich-nop" class="news-detail-section news-lang-section" aria-labelledby="hl-lich-nop">
                  <div class="news-lang-section__shell">
                    <header class="news-lang-section__head">
                      <span class="news-lang-section__step-num" aria-hidden="true">04</span>
                      <div class="news-lang-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-lich-nop">Lịch nộp và kỳ tuyển sinh 2026</h2>
                        <p class="news-section-opener__kicker">
                          Săn học bổng an toàn là <strong>quản lý deadline như dự án</strong>: một dòng cho trường, một dòng cho suất học bổng, và một dòng cho <strong>các bước chuẩn bị giấy tờ</strong> — nếu chỉ nhớ “hạn cuối trên web” mà quên công chứng, bạn vẫn có thể trễ.
                        </p>
                      </div>
                    </header>

                    <p class="news-lang-section__intro">
                      Hầu hết trường mở <strong>kỳ xuân (thường nhập học tháng 3)</strong> và <strong>kỳ thu (thường tháng 9)</strong> — có nơi thêm <strong>early admission</strong>, <strong>đợt bổ sung</strong> hoặc đợt chỉ dành cho ứng viên quốc tế. <strong>Học bổng du học Hàn Quốc năm 2026</strong> thường <strong>khớp deadline với hồ sơ nhập học</strong> hoặc <strong>sớm hơn vài tuần</strong> (đặc biệt khi có vòng sơ tuyển nội bộ hoặc xét riêng qua khoa). <a href="hoc-bong-gks-chinh-phu-han-quoc.php">GKS</a> và các suất <strong>gắn với cơ quan nhà nước</strong> có <strong>mốc riêng</strong> theo hướng dẫn năm và kênh Đại sứ quán / tổ chức — không được trộn lẫn với lịch regular của một trường bất kỳ.
                    </p>

                    <div class="news-lang-section__dual">
                      <article class="news-lang-panel news-lang-panel--ko" aria-labelledby="hl-lich-truong">
                        <h3 class="news-lang-panel__title" id="hl-lich-truong">
                          <span class="news-lang-panel__ico" aria-hidden="true"><i class="bi bi-calendar3-week"></i></span>
                          Lịch trường: xuân — thu &amp; các đợt phụ
                        </h3>
                        <p>
                          Mỗi trường công bố <strong>calendar tuyển sinh quốc tế</strong> riêng: đợt nộp online, hạn gửi bản cứng (nếu có), thông báo kết quả và hạn xác nhận nhập học. Khi bạn nhắm <strong>học bổng nhập học</strong>, cần kiểm tra suất đó áp dụng cho <strong>đúng đợt early hay regular</strong> — đôi khi suất chỉ mở cho một trong hai.
                        </p>
                        <ul class="news-lang-panel__list">
                          <li>Ghi rõ <strong>múi giờ</strong> nếu nộp online (tránh nhầm 23:59 theo giờ Hàn vs giờ Việt Nam).</li>
                          <li>Một số ngành có <strong>portfolio / test bổ sung</strong> với deadline khác đợt chung — tách cột riêng trong bảng của bạn.</li>
                          <li>Đồng bộ với <a href="chon-truong-du-hoc-han-quoc-2026.php">chọn trường 2026</a> khi bạn có 2–3 trường song song để không chồng phỏng vấn.</li>
                        </ul>
                      </article>
                      <article class="news-lang-panel news-lang-panel--en" aria-labelledby="hl-lich-gks">
                        <h3 class="news-lang-panel__title" id="hl-lich-gks">
                          <span class="news-lang-panel__ico" aria-hidden="true"><i class="bi bi-flag"></i></span>
                          GKS &amp; suất quốc gia — tuyến timeline riêng
                        </h3>
                        <p>
                          Các chương trình như <strong>GKS</strong> thường có <strong>chuỗi mốc</strong>: đăng ký qua đúng kênh, nộp hồ sơ giấy, sơ tuyển, phỏng vấn (nếu có), công bố kết quả — <strong>không trùng</strong> hoàn toàn với lịch nộp hồ sơ trực tiếp vào portal trường. Nếu bạn đi song song GKS và hồ sơ trường, hãy có <strong>hai cột timeline</strong> để thấy chỗ nào cần ưu tiên dịch — công chứng trước.
                        </p>
                        <ul class="news-lang-panel__list">
                          <li>Luôn tải <strong>guideline năm bạn nộp</strong> — mốc năm trước chỉ để ước lượng.</li>
                          <li>Kết hợp đọc <a href="#cac-loai">các loại học bổng</a> và <a href="#tong-quan">tổng quan</a> để không nhầm “đợt trường” với “đợt GKS”.</li>
                          <li>Nếu cần chứng minh tài chính cho visa, đảm bảo <strong>ngày trên chứng từ</strong> và kế hoạch nhập học <strong>cùng logic</strong> với đơn đã nộp (xem <a href="dieu-kien-du-hoc-han-quoc-2026.php">điều kiện 2026</a>).</li>
                        </ul>
                      </article>
                    </div>

                    <h3 class="news-lang-section__subheading">Bốn lớp mốc trong bảng timeline cá nhân (SUNNY khuyên dùng)</h3>
                    <div class="news-lang-section__path" role="list">
                      <div class="news-lang-path-step" role="listitem">
                        <span class="news-lang-path-step__label">Chuẩn bị</span>
                        <p class="news-lang-path-step__title">Dịch công chứng &amp; chứng chỉ còn hạn</p>
                        <p>
                          Lùi <strong>2–4 tuần</strong> (hoặc hơn nếu vùng bạn công chứng chậm) so với hạn nộp để còn thời gian sửa lỗi chính tả trên bản dịch, bổ sung công văn hoặc <strong>thi lại TOPIK / IELTS</strong> kịp có điểm trước deadline.
                        </p>
                      </div>
                      <div class="news-lang-path-step" role="listitem">
                        <span class="news-lang-path-step__label">Nộp</span>
                        <p class="news-lang-path-step__title">Portal online — upload &amp; xác nhận email</p>
                        <p>
                          Sau khi bấm submit, lưu <strong>screenshot / mã hồ sơ</strong>, kiểm tra email xác nhận và thư mục file đặt tên đúng quy định — nhiều ứng viên bị loại vì <strong>thiếu một file phụ lục</strong> dù đã nộp đúng ngày.
                        </p>
                      </div>
                      <div class="news-lang-path-step" role="listitem">
                        <span class="news-lang-path-step__label">Vòng sau</span>
                        <p class="news-lang-path-step__title">Phỏng vấn, bài test bổ sung, nộp bản cứng</p>
                        <p>
                          Một số suất yêu cầu <strong>phỏng vấn video</strong> hoặc gửi thêm tài liệu sau vòng đầu — ghi mốc này <strong>riêng</strong> khỏi “deadline nộp đơn lần một” để không bị động.
                        </p>
                      </div>
                      <div class="news-lang-path-step" role="listitem">
                        <span class="news-lang-path-step__label">Đệm</span>
                        <p class="news-lang-path-step__title">Buffer cho sự cố &amp; sai form</p>
                        <p>
                          Giữ ít nhất <strong>vài ngày đệm</strong> trước hạn chót cho sự cố mạng, lỗi thanh toán phí xét tuyển hoặc phát hiện sai form — tránh nộp sát giờ khiến <strong>thiếu công chứng</strong> hoặc dùng nhầm <strong>form năm cũ</strong>.
                        </p>
                      </div>
                    </div>

                    <p class="news-lang-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> “Kỳ 3 / kỳ 9” trên mạng chỉ là <strong>khung tham khảo</strong>; luôn lấy ngày tháng cụ thể từ <strong>brochure và FAQ trường</strong> hoặc văn bản GKS năm bạn nộp. SUNNY có thể giúp bạn <strong>ghép timeline</strong> khi đã có danh sách suất và trường ưu tiên.
                    </p>
                  </div>
                </section>

                <section id="ho-so" class="news-detail-section news-visa-section" aria-labelledby="hl-ho-so">
                  <div class="news-visa-section__shell">
                    <header class="news-visa-section__head">
                      <span class="news-visa-section__step-num" aria-hidden="true">05</span>
                      <div class="news-visa-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-ho-so">Checklist hồ sơ học bổng (tham khảo)</h2>
                        <p class="news-section-opener__kicker">
                          Checklist dưới đây là <strong>khung làm việc</strong> — bạn vẫn phải <strong>đối chiếu từng dòng</strong> với form năm bạn nộp. Mục tiêu là tránh “thiếu một giấy nhỏ” hoặc “đúng nội dung nhưng sai thứ tự upload” khiến hồ sơ bị loại kỹ thuật.
                        </p>
                      </div>
                    </header>

                    <p class="news-visa-section__intro">
                      Hồ sơ học bổng mạnh thường chia thành hai nhóm có thể chuẩn bị <strong>song song</strong>: nhóm <strong>giấy tờ &amp; chứng chỉ</strong> (cần thời gian dịch — công chứng) và nhóm <strong>narrative</strong> (luận, study plan, thư giới thiệu, portfolio) cần nhiều vòng chỉnh sửa. Nếu bạn để nhóm thứ hai đến sát deadline trong khi nhóm một vẫn đang chờ bản dịch, dễ phải <strong>cắt bớt chất lượng bài viết</strong> hoặc nộp thiếu phụ lục.
                    </p>

                    <div class="news-visa-section__dual">
                      <article class="news-visa-card news-visa-card--d4" aria-labelledby="hl-hs-giay">
                        <span class="news-visa-card__badge">Giấy tờ</span>
                        <h3 class="news-visa-card__title" id="hl-hs-giay">Học thuật, bằng cấp &amp; công chứng</h3>
                        <p>
                          Chuẩn bị <strong>bản dịch công chứng</strong> học bạ, bằng tốt nghiệp, bảng điểm đại học (nếu đã có bậc trước), và các phụ lục trường yêu cầu (ví dụ xếp loại, học bổng trong nước). Kiểm tra form có yêu cầu <strong>bản gốc song song</strong>, <strong>notarized copy</strong> hay chỉ bản scan — mỗi trường một quy ước.
                        </p>
                        <ul class="news-visa-card__list">
                          <li><strong>Chứng chỉ ngôn ngữ</strong> còn hiệu lực theo quy định suất; nếu đang ôn thi bổ sung, ghi rõ <strong>ngày dự kiến có điểm</strong> trong kế hoạch (nếu form cho phép giải thích).</li>
                          <li>Giữ bản scan <strong>đọc được, đủ góc</strong>, tránh ảnh mờ từ điện thoại cho tài liệu quan trọng.</li>
                          <li>Đồng bộ với mục <a href="#dieu-kien">điều kiện xét</a> và <a href="#lich-nop">lịch nộp</a> để không dịch nhầm bộ hồ sơ cho đợt khác.</li>
                        </ul>
                      </article>
                      <article class="news-visa-card news-visa-card--d2" aria-labelledby="hl-hs-narrative">
                        <span class="news-visa-card__badge">Nội dung</span>
                        <h3 class="news-visa-card__title" id="hl-hs-narrative">Luận, study plan, thư GT &amp; portfolio</h3>
                        <p>
                          <strong>Bài luận / study plan</strong> cần thể hiện mục tiêu <strong>khớp ngành và trường</strong>, có lộ trình học tập hoặc nghiên cứu rõ ràng (đặc biệt bậc sau đại học). <strong>Thư giới thiệu</strong> phải đúng <strong>mẫu, ngôn ngữ, số lượng</strong> và thường phải <strong>niêm phong / gửi trực tiếp</strong> từ người ký — đọc kỹ hướng dẫn.
                        </p>
                        <ul class="news-visa-card__list">
                          <li><strong>Portfolio, giải thưởng, hoạt động ngoại khóa</strong> chỉ đính kèm nếu form cho phép; tuân <strong>dung lượng — định dạng — số file</strong>.</li>
                          <li>Tránh dùng chung một file luận cho nhiều trường mà chỉ thay tên — hội đồng thường nhận ra (xem thêm <a href="#meo-nop">mẹo nộp</a>).</li>
                          <li>Với GKS, luôn tải <strong>checklist năm hiện tại</strong> kèm theo văn bản hướng dẫn — không dùng form cũ.</li>
                        </ul>
                      </article>
                    </div>

                    <h3 class="news-visa-section__subheading">Checklist chi tiết (đánh dấu từng mục trong bảng của bạn)</h3>
                    <div class="news-visa-section__flow" role="list">
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Bản dịch công chứng: học bạ, bằng, bảng điểm ĐH (nếu có)</p>
                        <p>
                          Đối chiếu tên trên bằng — học bạ với <strong>hộ chiếu</strong>; nếu khác, cần <strong>công văn giải trình hoặc affidavit</strong> theo hướng dẫn (nếu được phép). Kiểm tra bản dịch có <strong>dấu, chữ ký, số chứng thư</strong> đầy đủ.
                        </p>
                      </div>
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Chứng chỉ ngôn ngữ + kế hoạch thi bổ sung (nếu đang học thêm)</p>
                        <p>
                          Nộp bản có <strong>điểm, ngày thi, mã chứng chỉ</strong> rõ ràng; nếu chờ kết quả, xem form có ô <strong>expected score / test date</strong> không — không tự khai ảo.
                        </p>
                      </div>
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Luận / study plan khớp ngành — trường</p>
                        <p>
                          Gắn câu chuyện với <strong>giảng viên, lab, chương trình tín chỉ</strong> cụ thể; trích dẫn đúng tên chương trình tiếng Anh trên web trường để tránh sai tên khoa.
                        </p>
                      </div>
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Thư giới thiệu đúng mẫu &amp; ngôn ngữ</p>
                        <p>
                          Xác nhận người viết thư có <strong>email công vụ</strong> được chấp nhận; một số trường dùng portal mời giáo viên nộp riêng — bạn không được tự upload bản scan nếu quy định cấm.
                        </p>
                      </div>
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Portfolio &amp; minh chứng ngoại khóa (nếu được phép)</p>
                        <p>
                          Chỉ nộp phần <strong>liên quan trực tiếp</strong> ngành; tránh nén quá nhiều file rời làm ban xét khó mở. Giữ bản sao lưu ngoài máy trước khi upload.
                        </p>
                      </div>
                    </div>

                    <h3 class="news-visa-section__subheading">Kỹ thuật nộp: ít người nhắc nhưng dễ bị loại</h3>
                    <ul class="news-visa-section__tips">
                      <li><strong>Đặt tên file theo đúng quy tắc</strong> (ví dụ <code>HọTên_passport.pdf</code>) — sai tên có thể bị hệ thống bỏ qua.</li>
                      <li><strong>Kiểm tra dung lượng tối đa</strong> và nén PDF đúng cách; tránh file “mở được trên máy mình nhưng lỗi trên portal trường”.</li>
                      <li><strong>In và ký đúng chỗ</strong> trên form giấy — một số ô yêu cầu ký tay từng trang hoặc dấu mộc cơ quan.</li>
                      <li>Mỗi đơn có <strong>form và thứ tự hồ sơ</strong> khác nhau — luôn tải bản <strong>mới nhất</strong> từ website trường hoặc <strong>văn bản GKS năm bạn nộp</strong>, không dùng bản lưu trong máy từ năm trước.</li>
                    </ul>

                    <p class="news-visa-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Checklist không thay cho <strong>danh mục chính thức</strong> trên đơn. Khi đã có shortlist suất, SUNNY có thể giúp bạn <strong>đối chiếu từng dòng</strong> với form và rà lỗi trước khi submit.
                    </p>
                  </div>
                </section>

                <section id="meo-nop" class="news-detail-section news-finance-section" aria-labelledby="hl-meo-nop">
                  <div class="news-finance-section__shell">
                    <header class="news-finance-section__head">
                      <span class="news-finance-section__step-num" aria-hidden="true">06</span>
                      <div class="news-finance-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-meo-nop">Mẹo nộp hồ sơ &amp; sai lầm cần tránh</h2>
                        <p class="news-section-opener__kicker">
                          Hồ sơ học bổng không chỉ “đủ giấy” — mà còn là <strong>trải nghiệm đọc của hội đồng</strong>: rõ ràng, nhất quán, dễ kiểm chứng. Bốn mẹo dưới đây là phần SUNNY thấy <strong>tạo ra chênh lệch lớn nhất</strong> giữa ứng viên cùng mức điểm.
                        </p>
                      </div>
                    </header>

                    <p class="news-finance-section__intro">
                      Trước khi nộp, hãy tự hỏi: <strong>“Nếu tôi chỉ có 3 phút để thuyết phục, câu chuyện nào còn lại?”</strong> — đó thường là phần cần thể hiện trong luận và thư giới thiệu. Đồng thời, kiểm tra lại <strong>phiên bản form</strong>, <strong>tên file</strong> và <strong>thứ tự upload</strong> (đã nêu trong <a href="#ho-so">mục checklist hồ sơ</a>) để không bị loại vì lỗi kỹ thuật dù nội dung tốt.
                    </p>

                    <h3 class="news-finance-section__subheading">Bốn mẹo cốt lõi</h3>
                    <div class="news-finance-section__audit" role="list">
                      <div class="news-finance-audit-card" role="listitem">
                        <span class="news-finance-audit-card__label">Mẹo 1</span>
                        <p class="news-finance-audit-card__title" id="hl-meo-luan">Không “một bài luận cho mọi trường”</p>
                        <p>
                          Mỗi trường — mỗi suất có <strong>câu hỏi gợi ý</strong> và <strong>độ dài</strong> khác nhau. Hãy chỉnh lại <strong>lý do chọn đúng trường — đúng ngành — đúng giảng viên / lab</strong> thay vì copy đoạn giới thiệu chung. Ban xét dễ nhận ra template khi các câu không trả lời trực tiếp prompt hoặc nhầm tên chương trình.
                        </p>
                      </div>
                      <div class="news-finance-audit-card" role="listitem">
                        <span class="news-finance-audit-card__label">Mẹo 2</span>
                        <p class="news-finance-audit-card__title" id="hl-meo-nguong">Đối chiếu điểm &amp; chứng chỉ với suất — không chỉ cổng tuyển sinh</p>
                        <p>
                          Ngưỡng <strong>tuyển sinh chung</strong> và ngưỡng <strong>xét học bổng</strong> có thể khác: suất cạnh tranh thường “kéo” tiêu chí lên hoặc thêm bước <strong>xếp hạng</strong>. Đối chiếu với bảng điều kiện trong <a href="#dieu-kien">mục điều kiện</a> và từng PDF suất; nếu điểm sát ngưỡng, cần <strong>narrative và thư GT</strong> bù đắp có căn cứ.
                        </p>
                      </div>
                      <div class="news-finance-audit-card" role="listitem">
                        <span class="news-finance-audit-card__label">Mẹo 3</span>
                        <p class="news-finance-audit-card__title" id="hl-meo-scan">Scan rõ — đặt tên file đúng quy tắc</p>
                        <p>
                          Giữ bản scan <strong>đủ độ phân giải</strong>, không cắt mất góc, không chồng lớp mờ. Đặt tên file theo đúng hướng dẫn (nhiều hệ thống lọc tự động). Nộp thử trên máy khác hoặc mở lại file PDF để chắc chắn <strong>không lỗi font</strong> — đặc biệt với bản dịch có dấu.
                        </p>
                      </div>
                      <div class="news-finance-audit-card" role="listitem">
                        <span class="news-finance-audit-card__label">Mẹo 4</span>
                        <p class="news-finance-audit-card__title" id="hl-meo-plan-b">Dự phòng kế hoạch B</p>
                        <p>
                          Nếu không đậu suất đầu, bạn vẫn có thể <strong>nhập học</strong> (tự túc có kiểm soát) và xét <strong>học bổng in-course</strong> kỳ sau — cần ngân sách và tâm lý chuẩn bị từ đầu (xem <a href="chi-phi-du-hoc-han-quoc-2026.php">Chi phí 2026</a> và <a href="#cac-loai">các loại học bổng</a>). Kế hoạch B giúp bạn <strong>không nộp ẩu</strong> vì quá sợ “trượt là hết đường”.
                        </p>
                      </div>
                    </div>

                    <h3 class="news-finance-section__subheading">Sai lầm thường gặp — nên tránh</h3>
                    <ul class="news-finance-section__principles">
                      <li><strong>Nộp sát deadline</strong> rồi phát hiện thiếu công chứng hoặc sai phiên bản form — không còn thời gian sửa.</li>
                      <li><strong>Mâu thuẫn giữa các phần hồ sơ</strong> (điểm, năm tốt nghiệp, tên ngành) — hội đồng nghi ngờ tính xác thực.</li>
                      <li><strong>Đính kèm quá nhiều file không liên quan</strong> làm ban xét mất tập trung vào điểm mạnh chính.</li>
                      <li><strong>Ôm 8–10 suất</strong> cùng lúc nhưng không có bảng ưu tiên — dẫn tới luận nông và sai sót khi copy giữa các đơn.</li>
                      <li><strong>Không đọc điều khoản duy trì suất</strong> (GPA, kỷ luật, giờ học tối thiểu) — đến kỳ sau mới phát hiện mất một phần hỗ trợ.</li>
                    </ul>

                    <p class="news-finance-section__bridge mb-0">
                      Kết hợp <a href="#dang-ky">tư vấn SUNNY</a> để <strong>ưu tiên 2–3 suất</strong> sát năng lực thực tế của bạn, sắp xếp thứ tự nộp và rà <strong>mâu thuẫn</strong> giữa các đơn trước khi đóng — tránh dàn trải sức lực mà không có “điểm nhọn” nào đủ sâu.
                    </p>

                    <p class="news-finance-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Mẹo mang tính <strong>kinh nghiệm xử lý hồ sơ</strong>, không thay cho quy định của từng trường hoặc chương trình GKS. Luôn lấy thông báo chính thức làm chuẩn cuối cùng.
                    </p>
                  </div>
                </section>

                <section id="faq-hoc-bong" class="news-detail-section news-faq-section" aria-labelledby="hl-faq">
                  <div class="news-faq-section__shell">
                    <header class="news-faq-section__head">
                      <span class="news-faq-section__step-num" aria-hidden="true">07</span>
                      <div class="news-faq-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-faq">FAQ: học bổng du học Hàn Quốc năm 2026</h2>
                        <p class="news-section-opener__kicker">
                          Bốn câu hỏi “chốt” khi bạn đang cân <strong>chứng chỉ — suất GKS — ngân sách — phương án dự phòng</strong>. Chi tiết hơn nằm ở <a href="#tong-quan">tổng quan</a>, <a href="#cac-loai">các loại học bổng</a>, <a href="#dieu-kien">điều kiện xét</a>, <a href="#lich-nop">lịch nộp</a>, <a href="#ho-so">checklist hồ sơ</a> và <a href="#meo-nop">mẹo nộp</a>.
                        </p>
                      </div>
                    </header>
                    <p class="news-faq-section__lead">
                      Mỗi gia đình có <strong>mức rủi ro tài chính</strong> và <strong>mục tiêu nghề</strong> khác nhau — đừng so sánh “bạn bè đậu suất gì” mà hãy đối chiếu <strong>điều kiện chính thức</strong> của suất bạn đang nhắm tới và lộ trình visa — nhập học của chính bạn.
                    </p>
                    <ul class="news-faq-list">
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q1</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-hb-q-topik">Có bắt buộc TOPIK cao mới xin được học bổng Hàn Quốc 2026?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              <strong>Tùy suất.</strong> Nhiều học bổng nhập học yêu cầu <strong>TOPIK</strong> hoặc <strong>IELTS Academic / TOEFL iBT</strong> ở mức nhất định — đôi khi cao hơn ngưỡng tuyển sinh chung vì suất cạnh tranh cần <strong>xếp hạng ứng viên</strong>. Một số chương trình tiếng cho phép <strong>bổ sung chứng chỉ sau khi nhập học</strong>, nhưng thường kèm điều kiện (khóa tiếng bắt buộc, giới hạn tín chỉ, hoặc không áp dụng cho mọi suất giảm học phí).
                            </p>
                            <p>
                              Cách làm an toàn: đọc song song bảng điều kiện trong <a href="#dieu-kien">mục điều kiện xét</a> và PDF suất; nếu bạn đang ôn thi, ghim <strong>ngày thi — ngày có điểm</strong> vào <a href="#lich-nop">timeline</a> để không lỡ vòng xét.
                            </p>
                            <ul>
                              <li>Chương trình <strong>tiếng Anh</strong> có thể ưu tiên IELTS/TOEFL thay vì TOPIK — đừng tự suy diễn.</li>
                              <li>Nếu suất yêu cầu TOPIK II theo <strong>điểm thành phần</strong>, kiểm tra bạn có đạt từng ngưỡng không.</li>
                            </ul>
                          </div>
                        </div>
                      </li>
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q2</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-hb-q-gks-truong">GKS và học bổng trường có nộp cùng lúc được không?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              <strong>Được trong nhiều trường hợp</strong> nếu lịch nộp, hồ sơ và quy định của từng bên cho phép — nhưng không phải lúc nào cũng “cộng dồn quyền lợi”. Bạn cần đọc <strong>điều khoản loại trừ</strong>: một số suất trường yêu cầu <strong>không nhận đồng thời</strong> hỗ trợ từ nguồn khác, hoặc GKS có quy định riêng về <strong>trường trong danh mục</strong> và thứ tự xét.
                            </p>
                            <p>
                              SUNNY thường giúp bạn dựng <strong>hai cột timeline</strong> (GKS vs trường) và <strong>thứ tự ưu tiên</strong> nộp — tránh ký nhận suất này làm mất điều kiện suất kia. Tham khảo thêm <a href="hoc-bong-gks-chinh-phu-han-quoc.php">hướng dẫn GKS</a> và <a href="#cac-loai">phân loại học bổng</a>.
                            </p>
                          </div>
                        </div>
                      </li>
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q3</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-hb-q-sinh-hoat">Học bổng du học Hàn Quốc năm 2026 có bao trọn sinh hoạt không?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              Một số suất <strong>toàn phần</strong> hoặc <strong>gần toàn phần</strong> (học phí + sinh hoạt + KTX theo điều khoản), nhưng <strong>đa số suất trường</strong> chỉ <strong>giảm học phí một phần</strong> hoặc hỗ trợ có giới hạn thời gian — sinh hoạt tại Seoul / vùng lớn vẫn có thể là <strong>khoản lớn</strong> nếu bạn không ở KTX ưu đãi.
                            </p>
                            <p>
                              Hãy đọc kỹ phần <strong>quyền lợi</strong>: có suất chỉ áp <strong>học kỳ đầu</strong>, có suất yêu cầu <strong>duy trì GPA</strong> mới giữ phần trợ cấp kỳ sau. Luôn chuẩn bị <a href="chi-phi-du-hoc-han-quoc-2026.php">ngân sách dự phòng</a> và ước lượng theo <a href="chon-truong-du-hoc-han-quoc-2026.php">địa điểm trường</a> nếu bạn đã shortlist campus.
                            </p>
                            <ul>
                              <li>Đừng hiểu “học bổng” là <strong>miễn mọi phí</strong> nếu brochure không ghi rõ.</li>
                              <li>Kiểm tra suất có bắt buộc <strong>ở KTX</strong> hoặc giới hạn làm thêm không — ảnh hưởng dòng tiền thực tế.</li>
                            </ul>
                          </div>
                        </div>
                      </li>
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q4</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-hb-q-truot">Trượt học bổng có còn cơ hội du học không?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              <strong>Có.</strong> Nhiều bạn vẫn nhập học theo hình thức <strong>tự túc một phần</strong> (gia đình hỗ trợ học phí / sinh hoạt theo khả năng), sau đó xét <strong>học bổng đang học (in-course)</strong> khi đã có <strong>GPA và hành vi học tập</strong> tại Hàn. Một số lộ trình còn có <strong>suất bổ sung</strong> hoặc hỗ trợ khoa sau kỳ đầu — tùy trường.
                            </p>
                            <p>
                              Điểm mấu chốt là <strong>ngân sách năm đầu</strong> phải đủ an toàn để bạn không phải bỏ dở vì shock tài chính; trao đổi với <a href="#dang-ky">tư vấn SUNNY</a> để chọn phương án <strong>vừa tham vọng học bổng</strong> vừa <strong>khớp khả năng gia đình</strong>. Xem thêm <a href="#meo-nop">mẹo kế hoạch B</a>.
                            </p>
                          </div>
                        </div>
                      </li>
                    </ul>
                    <p class="news-faq-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> FAQ chỉ <strong>định hướng</strong>. Điều kiện, quota và quyền lợi học bổng thay đổi theo từng đợt — luôn xác nhận trên website NIIED (GKS), website trường và với tư vấn SUNNY tại thời điểm bạn nộp.
                    </p>
                  </div>
                </section>

                <section id="ket-luan" class="news-detail-section news-conclusion-section" aria-labelledby="hl-ket-luan">
                  <div class="news-conclusion-section__shell">
                    <header class="news-conclusion-section__head">
                      <span class="news-conclusion-section__step-num" aria-hidden="true">08</span>
                      <div class="news-conclusion-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-ket-luan">Kết luận: săn học bổng du học Hàn Quốc năm 2026 cùng SUNNY</h2>
                        <p class="news-section-opener__kicker">
                          Học bổng là <strong>phần thưởng cho hồ sơ rõ ràng</strong> — không phải may rủi nếu bạn đã chia nhỏ công việc: <strong>chứng chỉ — timeline — narrative — điều khoản suất</strong> cùng một hướng với đơn nhập học và kế hoạch tài chính gia đình.
                        </p>
                      </div>
                    </header>

                    <p class="news-conclusion-section__lead">
                      <strong>Học bổng du học Hàn Quốc năm 2026</strong> là cơ hội <strong>giảm áp lực tài chính</strong> đáng kể, nhưng đồng thời đòi hỏi <strong>kỷ luật nộp đúng deadline</strong>, bản dịch — scan đạt chuẩn và bài luận thể hiện được <strong>vì sao bạn xứng đáng với suất đó</strong>. Chuẩn bị sớm <strong>chứng chỉ</strong>, <strong>luận / study plan</strong> và <strong>danh sách trường — suất ưu tiên</strong> giúp bạn vào vòng xét với <strong>lợi thế rõ rệt</strong> so với ứng viên cùng mức điểm nhưng hồ sơ thiếu mạch lạc.
                    </p>
                    <p class="news-conclusion-section__body">
                      Trước khi đóng đơn, hãy tự kiểm tra nhanh: các mốc trong <a href="#lich-nop">lịch nộp</a> đã <strong>lùi đủ cho công chứng</strong> chưa; <a href="#ho-so">checklist hồ sơ</a> đã khớp <strong>phiên bản form năm hiện tại</strong> chưa; và câu chuyện trong luận có <strong>khớp ngành — trường</strong> bạn chọn trong <a href="chon-truong-du-hoc-han-quoc-2026.php">Chọn trường 2026</a> chưa. Nếu bạn hướng <strong>GKS</strong>, luôn đối chiếu thêm <a href="hoc-bong-gks-chinh-phu-han-quoc.php">hướng dẫn GKS</a> và <a href="dieu-kien-du-hoc-han-quoc-2026.php">điều kiện &amp; visa 2026</a> để tránh mâu thuẫn giữa các phần trong bộ hồ sơ.
                    </p>

                    <h3 class="news-conclusion-section__subheading">Đối chiếu nhanh các mục trong bài</h3>
                    <div class="news-conclusion-section__grid">
                      <a class="news-conclusion-card" href="#tong-quan">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-compass"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Tổng quan &amp; chiến lược đa nguồn</span>
                          <span class="news-conclusion-card__hint">GKS, trường, timeline và danh mục SUNNY.</span>
                          <span class="news-conclusion-card__go">Mục 01 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#cac-loai">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-layers"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Các loại học bổng</span>
                          <span class="news-conclusion-card__hint">Chính phủ, nhập học, in-course, đối tác ngành.</span>
                          <span class="news-conclusion-card__go">Mục 02 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#dieu-kien">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-check2-square"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Điều kiện xét</span>
                          <span class="news-conclusion-card__hint">Điểm, chứng chỉ, luận, thư GT, portfolio.</span>
                          <span class="news-conclusion-card__go">Mục 03 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#lich-nop">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Lịch nộp &amp; kỳ tuyển</span>
                          <span class="news-conclusion-card__hint">Trường vs GKS; timeline cá nhân.</span>
                          <span class="news-conclusion-card__go">Mục 04 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#ho-so">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Checklist hồ sơ</span>
                          <span class="news-conclusion-card__hint">Giấy tờ, scan, form đúng phiên bản.</span>
                          <span class="news-conclusion-card__go">Mục 05 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#meo-nop">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-lightbulb"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Mẹo &amp; sai lầm</span>
                          <span class="news-conclusion-card__hint">Luận riêng từng nơi; kế hoạch B.</span>
                          <span class="news-conclusion-card__go">Mục 06 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#faq-hoc-bong">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-patch-question"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">FAQ học bổng</span>
                          <span class="news-conclusion-card__hint">TOPIK, GKS + trường, sinh hoạt, trượt suất.</span>
                          <span class="news-conclusion-card__go">Mục 07 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="chi-phi-du-hoc-han-quoc-2026.php">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Chi phí du học 2026</span>
                          <span class="news-conclusion-card__hint">Ngân sách dự phòng khi suất chỉ giảm một phần.</span>
                          <span class="news-conclusion-card__go">Bài liên quan <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                    </div>

                    <div class="news-conclusion-section__cta">
                      <p>
                        Đăng ký <a href="#dang-ky">tư vấn miễn phí</a> — SUNNY đồng hành <strong>lọc suất học bổng</strong>, chỉnh hồ sơ, khớp lịch với <strong>kỳ tuyển sinh 2026</strong> và đối chiếu nhanh với <a href="hoc-bong.php">danh mục Học bổng SUNNY</a>, <a href="cac-nganh-du-hoc-han-quoc.php">các ngành</a> cùng lộ trình <a href="he-tieng-d4.php">D-4</a> / <a href="he-dai-hoc-d2.php">D-2</a> phù hợp mục tiêu của bạn.
                      </p>
                      <a href="#dang-ky" class="btn btn-sunny text-white px-4 mt-3 inline-flex items-center justify-center gap-2">
                        <i class="bi bi-chat-dots" aria-hidden="true"></i>
                        Đăng ký tư vấn miễn phí
                      </a>
                    </div>

                    <p class="news-conclusion-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Điều kiện, quota và hạn nộp học bổng <strong>thay đổi theo từng đợt</strong>. Luôn xác nhận trên website <strong>NIIED (GKS)</strong>, website trường và thông báo từ tư vấn SUNNY tại thời điểm nộp; bài viết chỉ mang tính <strong>định hướng</strong>.
                    </p>
                  </div>
                </section>
              </div>

              <div class="flex flex-col sm:flex-row gap-3 justify-between items-stretch sm:items-center mt-4 lg:mt-5 pt-4 border-t">
                <a href="/#thong-tin" class="btn btn-outline-dark font-semibold inline-flex items-center justify-center gap-1">
                  <i class="bi bi-arrow-left" aria-hidden="true"></i>
                  Thông tin du học
                </a>
                <a href="#dang-ky" class="btn btn-sunny text-white px-4 inline-flex items-center justify-center gap-1">
                  <i class="bi bi-chat-dots" aria-hidden="true"></i>
                  Tư vấn học bổng 2026
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
',
  ),
);
