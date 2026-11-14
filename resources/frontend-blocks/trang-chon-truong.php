<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 15,
  'slug' => 'trang-chon-truong',
  'name' => 'Trang chọn trường',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 12:43:50',
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
              <span class="page-about-breadcrumb__current">Chọn trường du học Hàn Quốc 2026</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <p class="about-intro-hero__kicker">
              <span class="about-intro-hero__kicker-icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
              Thông tin du học · Chọn trường 2026
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">Chọn trường du học Hàn Quốc <span class="text-primary-sunny">2026</span></h1>
            <p class="about-intro-hero__lead about-intro-hero__lead--sm mb-0">
              <strong class="about-intro-hero__strong">Chọn trường du học Hàn Quốc 2026</strong> cần kết hợp
              <strong class="about-intro-hero__strong">mục tiêu nghề — ngành</strong>,
              <strong class="about-intro-hero__strong">ngân sách</strong> (học phí + sinh hoạt),
              <strong class="about-intro-hero__strong">ngưỡng ngôn ngữ</strong> (TOPIK / tiếng Anh),
              <strong class="about-intro-hero__strong">kỳ tuyển sinh</strong> và mức độ
              <strong class="about-intro-hero__strong">hỗ trợ sinh viên quốc tế</strong>.
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              Bài hướng dẫn giúp bạn có <strong class="about-intro-hero__strong">khung quyết định rõ ràng</strong>, rút gọn còn
              <strong class="about-intro-hero__strong">2–3 phương án</strong> trước khi nộp hồ sơ; thông tin mang tính
              <strong class="about-intro-hero__strong">định hướng</strong> — luôn đối chiếu brochure và thông báo chính thức của từng trường tại thời điểm bạn ứng tuyển.
            </p>
            <div class="about-intro-hero__chips mt-3" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-briefcase" aria-hidden="true"></i> Ngành &amp; nghề nghiệp</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-wallet2" aria-hidden="true"></i> Ngân sách &amp; địa điểm</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-translate" aria-hidden="true"></i> TOPIK / tiếng Anh</span>
            </div>
            <a href="#tong-quan" class="about-intro-hero__cta mt-3">
              <span>Xem khung chọn trường trong bài</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Shortlist thông minh</p>
              <p class="about-intro-hero__panel-title">Năm trụ khi rút gọn danh sách trường</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-bookmark-star"></i></span>
                  <span><strong>Ngành — chương trình:</strong> có khớp mục tiêu nghề nghiệp và năng lực học tập của bạn không?</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                  <span><strong>Tổng chi ước lượng:</strong> học phí + sinh hoạt tại địa bàn campus — tham khảo <a href="chi-phi-du-hoc-han-quoc-2026.php">khung chi phí 2026</a>.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-patch-check"></i></span>
                  <span><strong>Ngôn ngữ &amp; kỳ tuyển:</strong> TOPIK / IELTS có đủ cho đợt nộp; deadline có kịp không?</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-people"></i></span>
                  <span><strong>Hỗ trợ SVQT:</strong> KTX, cố vấn, hoạt động — ảnh hưởng trải nghiệm và chi phí ẩn.</span>
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
                          <li><a href="#tieu-chi">Tiêu chí chọn trường</a></li>
                          <li><a href="#vi-tri-chi-phi">Vị trí &amp; chi phí</a></li>
                          <li><a href="#nganh-tuyen">Ngành &amp; tuyển sinh</a></li>
                          <li><a href="#quy-trinh">Quy trình shortlist</a></li>
                          <li><a href="#faq-chon-truong">FAQ</a></li>
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
                  src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1200&amp;q=80"
                  alt="Chọn trường du học Hàn Quốc 2026 — khuôn viên đại học và môi trường học tập"
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
                    <i class="bi bi-bookmark-star"></i>
                  </div>
                  <div class="news-detail-key__text">
                    <strong>Ngành — nghề trước</strong>
                    <span>Chọn trường bám chương đào tạo &amp; cơ hội thực tập.</span>
                  </div>
                </div>
                <div class="news-detail-key" role="listitem">
                  <div class="news-detail-key__icon" aria-hidden="true">
                    <i class="bi bi-geo-alt"></i>
                  </div>
                  <div class="news-detail-key__text">
                    <strong>Vị trí &amp; sinh hoạt</strong>
                    <span>Seoul / vùng lân cận / tỉnh — chi phí chênh lệch rõ.</span>
                  </div>
                </div>
                <div class="news-detail-key" role="listitem">
                  <div class="news-detail-key__icon" aria-hidden="true">
                    <i class="bi bi-piggy-bank"></i>
                  </div>
                  <div class="news-detail-key__text">
                    <strong>Học bổng &amp; kỳ nhập học</strong>
                    <span>So khối hỗ trợ &amp; deadline xét tuyển 2026.</span>
                  </div>
                </div>
              </div>

              <div class="news-article text-black">
                <section id="tong-quan" class="news-detail-section news-section-opener" aria-labelledby="hl-tong-quan">
                  <div class="news-section-opener__shell">
                    <header class="news-section-opener__head">
                      <span class="news-section-opener__step-num" aria-hidden="true">01</span>
                      <div class="news-section-opener__head-text">
                        <h2 class="news-section-opener__title" id="hl-tong-quan">Chọn trường du học Hàn Quốc 2026: bắt đầu từ đâu?</h2>
                        <p class="news-section-opener__kicker">
                          Đừng mở hàng chục website trường cùng lúc — hãy <strong>đi theo thứ tự</strong>: bậc học → ngành → ngôn ngữ &amp; đầu vào → ngân sách &amp; địa điểm → học bổng &amp; hỗ trợ, rồi mới <strong>shortlist 2–3 trường</strong> để làm hồ sơ thật sâu.
                        </p>
                      </div>
                    </header>

                    <div class="news-section-opener__flow" role="list">
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-signpost-2"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Bậc học &amp; lộ trình visa</p>
                          <p class="news-section-opener__flow-desc">
                            Xác định bạn đang hướng tới <a href="he-tieng-d4.php">tiếng (D-4)</a>, <a href="he-cao-dang-d2-1.php">cao đẳng</a>, <a href="he-dai-hoc-d2.php">cử nhân</a> hay <a href="he-thac-si-d2-3.php">sau ĐH</a> — mỗi bậc có <strong>calendar tuyển</strong> và <strong>yêu cầu hồ sơ</strong> khác nhau. Chốt bậc trước giúp loại ngay nhóm trường không nhận đối tượng của bạn.
                          </p>
                        </div>
                      </div>
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-bookmark-star"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Ngành hoặc nhóm ngành</p>
                          <p class="news-section-opener__flow-desc">
                            Chọn <strong>1–2 hướng nghề</strong> (có thể rộng ở bước đầu: ví dụ “kinh doanh — dữ liệu” hoặc “truyền thông — thiết kế”) rồi xem trường nào có <strong>chương trình cốt lõi</strong>, thực tập hoặc lộ trình tín chỉ phù hợp — không chỉ nhìn tên trường nổi tiếng chung chung.
                          </p>
                        </div>
                      </div>
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-translate"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Ngôn ngữ giảng dạy &amp; ngưỡng đầu vào</p>
                          <p class="news-section-opener__flow-desc">
                            Lọc trường có track <strong>tiếng Hàn</strong> hoặc <strong>tiếng Anh</strong> khớp chứng chỉ bạn có / sẽ có; đối chiếu <strong>TOPIK, IELTS, TOEFL</strong> và các kỳ thi bổ sung (nếu có). Nếu chưa đủ ngưỡng ĐH, cân nhắc <strong>D-4</strong> rồi chuyển tiếp — timeline phải khớp kỳ tuyển sau.
                          </p>
                        </div>
                      </div>
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Ngân sách &amp; địa điểm campus</p>
                          <p class="news-section-opener__flow-desc">
                            Cùng mức học phí có thể <strong>khác biệt lớn</strong> khi cộng tiền nhà và sinh hoạt (Seoul / vùng thủ đô / tỉnh). Ước lượng sớm theo <a href="chi-phi-du-hoc-han-quoc-2026.php">khung chi phí 2026</a> và điều kiện chứng minh tài chính trong <a href="dieu-kien-du-hoc-han-quoc-2026.php">điều kiện du học</a> nếu bạn sắp nộp visa.
                          </p>
                        </div>
                      </div>
                      <div class="news-section-opener__flow-item" role="listitem">
                        <span class="news-section-opener__flow-ico" aria-hidden="true"><i class="bi bi-award"></i></span>
                        <div>
                          <p class="news-section-opener__flow-title">Học bổng &amp; hỗ trợ sinh viên quốc tế</p>
                          <p class="news-section-opener__flow-desc">
                            Đọc mục học bổng nhập học, KTX, office quốc tế, buddy — các yếu tố này ảnh hưởng cả <strong>chi phí thực tế</strong> lẫn <strong>trải nghiệm kỳ đầu</strong>. So sánh khi đã có shortlist, tránh lạc vào danh sách suất quá sớm.
                          </p>
                        </div>
                      </div>
                    </div>

                    <p>
                      Năm <strong>2026</strong>, phần lớn trường tiếp tục tổ chức <strong>xét tuyển theo kỳ xuân — thu</strong> (đôi khi gọi là nhập học mùa xuân / mùa thu); một số chương trình hoặc bậc học có thêm <strong>đợt bổ sung</strong>, <strong>tuyển rolling</strong> hoặc đợt chỉ dành cho ứng viên quốc tế — <strong>không thống nhất</strong> giữa các cơ sở. Vì vậy, “mùa nộp” trên mạng chỉ mang tính tham khảo; luôn mở <strong>brochure PDF</strong> hoặc trang tuyển sinh tiếng Anh của đúng trường và đúng kỳ bạn định nộp.
                    </p>
                    <p>
                      Một lỗi hay gặp là <strong>mở quá nhiều hồ sơ song song</strong> rồi không kịp dịch công chứng, nộp chứng chỉ hoặc hoàn thành bài luận — dẫn tới “tràn” deadline. Cách làm nhẹ đầu: dùng bảng Excel đơn giản với cột <em>trường</em>, <em>deadline</em>, <em>giấy tờ còn thiếu</em>, <em>ưu tiên 1–3</em>; chỉ đẩy mạnh hồ sơ cho vài trường bạn thực sự muốn vào.
                    </p>
                    <p>
                      Bạn có thể tham khảo <a href="truong-dai-hoc.php">danh sách trường đại học</a> và <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học Hàn Quốc</a> trên SUNNY để <strong>khám phá phạm vi</strong>, sau đó quay lại bước trên để <strong>rút gọn</strong> thay vì lưu bookmark vô hạn. Khi đã có 2–3 phương án, đọc tiếp <a href="#tieu-chi">tiêu chí chọn trường</a>, <a href="#vi-tri-chi-phi">vị trí &amp; chi phí</a> và <a href="#quy-trinh">quy trình shortlist</a> trong cùng bài để chốt danh sách cuối.
                    </p>

                    <h3 class="news-section-opener__subheading">Checklist tinh thần trước khi “săn” trường</h3>
                    <ul class="news-section-opener__checklist">
                      <li>Bạn đã biết <strong>mình đang ở bậc nào</strong> (D-4 hay D-2 loại nào) và <strong>kỳ nhập học mục tiêu</strong> chưa?</li>
                      <li>Bạn chấp nhận học <strong>tiếng Hàn</strong>, <strong>tiếng Anh</strong> hay chương trình <strong>song ngữ</strong> — và chứng chỉ hiện tại có khớp không?</li>
                      <li>Ngân sách gia đình đã có <strong>khoảng sàn — trần</strong> (kể cả sinh hoạt) chưa, hay chỉ mới nhìn học phí?</li>
                      <li>Bạn sẵn sàng <strong>từ chối</strong> một trường “tên tuổi” nếu ngành — địa điểm — thời điểm tuyển không khớp lộ trình của bạn chưa?</li>
                    </ul>

                    <p class="news-section-opener__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Bài viết mang tính <strong>định hướng</strong>. Điều kiện tuyển, học phí, học bổng và hạn nộp lấy theo <strong>thông báo chính thức từng trường</strong> tại thời điểm bạn ứng tuyển. SUNNY hỗ trợ bạn <strong>sắp xếp thứ tự ưu tiên</strong> và rà hồ sơ trước khi đóng đơn.
                    </p>
                  </div>
                </section>

                <section id="tieu-chi" class="news-detail-section news-finance-section" aria-labelledby="hl-tieu-chi">
                  <div class="news-finance-section__shell">
                    <header class="news-finance-section__head">
                      <span class="news-finance-section__step-num" aria-hidden="true">02</span>
                      <div class="news-finance-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-tieu-chi">Tiêu chí chọn trường du học Hàn Quốc 2026</h2>
                        <p class="news-section-opener__kicker">
                          Bốn trụ dưới đây giúp bạn <strong>không chọn trường chỉ vì tên tuổi chung chung</strong> — mà vì <strong>ngành, ngôn ngữ, mạng lưới nghề nghiệp và đời sống quốc tế</strong> thực sự phù hợp.
                        </p>
                      </div>
                    </header>

                    <p class="news-finance-section__intro">
                      Khi so sánh trường, hãy tưởng tượng bạn đang lập một <strong>bảng điểm nội bộ</strong>: mỗi tiêu chí có trọng số khác nhau tùy ưu tiên (ví dụ ngành &gt; địa điểm, hoặc ngôn ngữ &gt; thứ hạng). Không có trường “tốt nhất cho mọi người” — chỉ có trường <strong>phù hợp với lộ trình và năng lực</strong> của bạn tại thời điểm ứng tuyển 2026.
                    </p>

                    <div class="news-finance-section__sources">
                      <article class="news-finance-source news-finance-source--savings" aria-labelledby="hl-tc-nganh">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-palette2"></i></span>
                          <h3 class="news-finance-source__title" id="hl-tc-nganh">Phù hợp ngành &amp; chương trình</h3>
                        </div>
                        <p>
                          Đại học mạnh về <strong>STEM</strong> chưa chắc là lựa chọn tốt nếu bạn hướng <strong>thiết kế — nghệ thuật — truyền thông sáng tạo</strong>. Hãy đọc <strong>curriculum</strong>: môn cốt lõi, tín chỉ tự chọn, đồ án cuối kỳ, thực tập bắt buộc hay tự nguyện, và <strong>ngôn ngữ giảng dạy</strong> (tiếng Hàn / tiếng Anh / song ngữ).
                        </p>
                        <ul class="news-finance-source__list">
                          <li>So sánh <strong>cùng tên ngành</strong> giữa hai trường — nội dung môn có thể khác hẳn.</li>
                          <li>Xem <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học Hàn Quốc</a> để hiểu nhóm ngành trước khi chốt trường.</li>
                          <li>Nếu cần <strong>portfolio / audition</strong>, kiểm tra format và deadline sớm — ảnh hưởng lịch nộp.</li>
                        </ul>
                      </article>

                      <article class="news-finance-source news-finance-source--income" aria-labelledby="hl-tc-ngon-ngu">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-translate"></i></span>
                          <h3 class="news-finance-source__title" id="hl-tc-ngon-ngu">Ngưỡng TOPIK &amp; tiếng Anh</h3>
                        </div>
                        <p>
                          Chọn trường có <strong>ngưỡng thực tế</strong> với điểm bạn đang có — hoặc với <strong>lộ trình ôn / thi lại</strong> rõ ràng. Nhiều chương trình tiếng Anh yêu cầu <strong>IELTS Academic / TOEFL iBT</strong> theo bảng riêng; chương trình tiếng Hàn thường gắn <strong>TOPIK II</strong> và đôi khi điểm thành phần.
                        </p>
                        <ul class="news-finance-source__list">
                          <li>Nếu chưa đủ ngưỡng ĐH, cân nhắc <a href="he-tieng-d4.php">D-4 tiếng</a> trước — nhưng phải tính <strong>tổng thời gian &amp; chi phí</strong> giai đoạn (tham khảo <a href="chi-phi-du-hoc-han-quoc-2026.php">chi phí 2026</a>).</li>
                          <li>Kiểm tra <strong>thời hạn chứng chỉ</strong> còn được trường chấp nhận không.</li>
                          <li>Điều kiện trong <a href="dieu-kien-du-hoc-han-quoc-2026.php">bài điều kiện 2026</a> giúp đối chiếu với hồ sơ visa sau này.</li>
                        </ul>
                      </article>

                      <article class="news-finance-source news-finance-source--sponsor" aria-labelledby="hl-tc-uy-tin">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
                          <h3 class="news-finance-source__title" id="hl-tc-uy-tin">Uy tín &amp; mạng lưới nghề nghiệp</h3>
                        </div>
                        <p>
                          Thứ hạng và công nhận là <strong>tham khảo</strong>, nhưng với mục tiêu đi làm sau tốt nghiệp, quan trọng hơn là <strong>tuyển dụng trong ngành</strong>, <strong>cựu sinh viên</strong>, hợp tác doanh nghiệp, career fair và mức độ hỗ trợ <strong>thực tập / CO-OP</strong> (nếu chương trình có).
                        </p>
                        <ul class="news-finance-source__list">
                          <li>Tìm hiểu <strong>ngành tại trường đó</strong>, không chỉ “tên trường” trên bảng xếp hạng tổng hợp.</li>
                          <li>Tham khảo group sinh viên, hội cựu SV — thông tin thực tế bổ sung cho brochure.</li>
                          <li>Nếu hướng <strong>ở lại làm việc</strong>, cần hiểu thêm khung visa &amp; thị trường — ngoài phạm vi một bài chọn trường.</li>
                        </ul>
                      </article>

                      <article class="news-finance-source news-finance-source--award" aria-labelledby="hl-tc-ho-tro">
                        <div class="news-finance-source__head">
                          <span class="news-finance-source__ico" aria-hidden="true"><i class="bi bi-globe2"></i></span>
                          <h3 class="news-finance-source__title" id="hl-tc-ho-tro">Hỗ trợ sinh viên quốc tế</h3>
                        </div>
                        <p>
                          <strong>Office of International Affairs</strong> (hoặc đơn vị tương đương), hướng dẫn <strong>KTX / nhà ở</strong>, orientation nhập học, <strong>buddy program</strong>, tư vấn visa trong campus — giúp giảm <strong>sốc văn hóa kỳ đầu</strong> và ảnh hưởng gián tiếp tới chi phí “phát sinh vì lạc lõng”.
                        </p>
                        <ul class="news-finance-source__list">
                          <li>Hỏi rõ <strong>ai là đầu mối</strong> khi gặp sự cố nhập học, bảo hiểm, chuyển phòng.</li>
                          <li>Một số trường có <strong>khóa tiếng Hàn bắt buộc</strong> cho SVQT — tính thêm vào thời gian &amp; học phí.</li>
                          <li>So sánh mức hỗ trợ khi bạn <strong>shortlist 2–3 trường</strong> song song, không chỉ khi đã trúng tuyển.</li>
                        </ul>
                      </article>
                    </div>

                    <h3 class="news-finance-section__subheading">Gộp bốn tiêu chí vào một bảng so sánh</h3>
                    <ul class="news-finance-section__principles">
                      <li><strong>Mỗi trường một hàng:</strong> ghi ngành cụ thể, ngưỡng ngôn ngữ, học phí niêm yết, ước lượng sinh hoạt (theo <a href="#vi-tri-chi-phi">địa điểm</a>), điểm mạnh hỗ trợ QT.</li>
                      <li><strong>Đánh dấu “deal-breaker”:</strong> ví dụ không đủ TOPIK kịp kỳ, hoặc ngành không có thực tập bắt buộc mà bạn cần.</li>
                      <li><strong>Ưu tiên 2–3 hàng</strong> để đầu tư hồ sơ sâu — tránh kéo dài danh sách vô hạn.</li>
                    </ul>

                    <p class="news-finance-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Tiêu chí có thể <strong>thay đổi theo kỳ tuyển</strong>; luôn lấy brochure và FAQ trường làm chuẩn. SUNNY có thể giúp bạn <strong>điền bảng so sánh</strong> và chỉ ra mâu thuẫn giữa các lựa chọn trước khi nộp.
                    </p>
                  </div>
                </section>

                <section id="vi-tri-chi-phi" class="news-detail-section news-health-section" aria-labelledby="hl-vi-tri">
                  <div class="news-health-section__shell">
                    <header class="news-health-section__head">
                      <span class="news-health-section__step-num" aria-hidden="true">03</span>
                      <div class="news-health-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-vi-tri">Vị trí trường &amp; chi phí sinh hoạt khi chọn trường 2026</h2>
                        <p class="news-section-opener__kicker">
                          <strong>Seoul và vùng đô thị lớn</strong> thường có nhiều lựa chọn ngành và kết nối doanh nghiệp, nhưng <strong>thuê nhà — ăn uống — dịch vụ</strong> thường cao hơn rõ so với nhiều tỉnh. Khi <strong>chọn trường du học Hàn Quốc 2026</strong>, hãy tính <strong>tổng ngân sách năm</strong> (học phí + chỗ ở + sinh hoạt + bảo hiểm và khoản cố định), không dừng ở học phí niêm yết trên website.
                        </p>
                      </div>
                    </header>

                    <p class="news-health-section__intro">
                      Hai trường có <strong>mức học phí gần giống nhau</strong> vẫn có thể cho <strong>tổng chi năm khác biệt lớn</strong> nếu một campus nằm tại khu đắt đỏ và bạn phải thuê ngoài, trong khi trường kia có <strong>KTX ưu đãi</strong> hoặc địa bàn sinh hoạt dễ chịu hơn. Hãy dựng cho mỗi phương án trường một cột: <strong>học phí theo kỳ thu</strong>, <strong>nhà (hoặc KTX) + cọc</strong>, <strong>sinh hoạt tháng × 12</strong>, <strong>bảo hiểm</strong>, rồi cộng <strong>dự phòng</strong> cho tỷ giá và phát sinh — cách này giúp bạn nhìn thấy “trường rẻ trên giấy” có thực sự rẻ với <strong>lối sống và địa điểm</strong> của bạn hay không.
                    </p>

                    <div class="news-health-section__dual">
                      <article class="news-health-panel news-health-panel--body" aria-labelledby="hl-vi-seoul-metro">
                        <h3 class="news-health-panel__title" id="hl-vi-seoul-metro">
                          <span class="news-health-panel__ico" aria-hidden="true"><i class="bi bi-building"></i></span>
                          Seoul &amp; đô thị lớn — nhiều cơ hội, áp lực chi phí
                        </h3>
                        <p>
                          Ưu thế thường là <strong>đa dạng ngành</strong>, <strong>seminar — career fair</strong>, và mạng lưới doanh nghiệp dày hơn — hữu ích nếu bạn cần <strong>thực tập</strong> hoặc làm thêm theo ngành ngay tại trung tâm. Đổi lại, <strong>cọc nhà</strong>, <strong>giá phòng</strong> và <strong>ăn ngoài</strong> dễ đẩy ngân sách tháng lên; nhiều bạn cân nhắc <strong>KTX</strong>, <strong>goshiwon</strong>, hoặc ở <strong>xa trung tâm nhưng sát ga tuyến chính</strong> để giữ kết nối việc làm mà giảm tiền thuê.
                        </p>
                        <ul class="news-health-panel__list">
                          <li><strong>Đi lại:</strong> thẻ giao thông và thời gian chuyển tuyến là chi phí “ẩn” — ở xa tiết kiệm thuê nhưng đổi bằng <strong>phút mỗi ngày</strong> và đôi khi phí cao hơn dự tính.</li>
                          <li><strong>Ngành đặc thù:</strong> thiết kế, lab, dụng cụ có thể cộng thêm chi cố định; đừng chỉ so học phí mà quên <strong>ngân sách ngành</strong>.</li>
                          <li>Khi shortlist, ghi rõ <strong>bạn dự định ở đâu</strong> (KTX / trọ / cùng người quen) để cột sinh hoạt không bị “số mơ hồ”.</li>
                        </ul>
                      </article>
                      <article class="news-health-panel news-health-panel--legal" aria-labelledby="hl-vi-ve-tinh-tinh">
                        <h3 class="news-health-panel__title" id="hl-vi-ve-tinh-tinh">
                          <span class="news-health-panel__ico" aria-hidden="true"><i class="bi bi-train-front"></i></span>
                          Thành phố vệ tinh, tỉnh &amp; tuyến metro tốt
                        </h3>
                        <p>
                          Một lối cân bằng phổ biến là chọn trường tại <strong>thành phố vệ tinh</strong> hoặc khu có <strong>tàu điện — tuyến nhanh</strong> kết nối ổn với trung tâm: <strong>tiết kiệm sinh hoạt</strong> hơn so với ở ngay lõi đắt đỏ, nhưng vẫn có thể lên Seoul khi cần <strong>phỏng vấn, networking, sự kiện ngành</strong>. Cần tính thêm <strong>tần suất đi lại</strong> và xem ngành bạn có bắt buộc hiện diện tại đô thị lớn không.
                        </p>
                        <ul class="news-health-panel__list">
                          <li><strong>Cơ hội việc làm:</strong> một số ngành vẫn tập trung offer tại Seoul — nếu bạn học tỉnh, hãy kiểm tra <strong>thực tập xa campus</strong> có khả thi với lịch học và đi lại không.</li>
                          <li><strong>Trải nghiệm:</strong> khu nhỏ có thể ít tiếng Anh hơn ở dịch vụ đời thường; đây không phải “phí” trực tiếp nhưng ảnh hưởng <strong>thời gian hòa nhập</strong> và nhu cầu học thêm.</li>
                          <li><strong>Hồ sơ &amp; visa:</strong> địa chỉ và mức thuê dự kiến trong hồ sơ nên <strong>khớp</strong> với bảng ngân sách bạn tự lập (xem thêm <a href="dieu-kien-du-hoc-han-quoc-2026.php">điều kiện du học Hàn Quốc 2026</a>).</li>
                        </ul>
                      </article>
                    </div>

                    <h3 class="news-health-section__subheading">Ba góc nhìn khi gắn địa điểm trường vào ngân sách</h3>
                    <div class="news-health-section__phases" role="list">
                      <div class="news-health-phase" role="listitem">
                        <span class="news-health-phase__label">Năm</span>
                        <p class="news-health-phase__title">Tổng ngân sách — không chỉ học phí</p>
                        <p>
                          Cộng <strong>học phí (theo đợt đóng)</strong>, <strong>nhà + cọc</strong>, <strong>sinh hoạt</strong>, <strong>bảo hiểm</strong>, <strong>điện thoại — internet</strong> và khoản cố định khác thành một hàng cho <strong>từng trường — từng kịch bản chỗ ở</strong>. So hai cột song song giúp thấy ngay nơi “học phí thấp hơn một chút” bị san bằng bởi tiền nhà.
                        </p>
                      </div>
                      <div class="news-health-phase" role="listitem">
                        <span class="news-health-phase__label">Cân bằng</span>
                        <p class="news-health-phase__title">Thời gian đi lại đổi lấy tiền thuê</p>
                        <p>
                          Ở xa nhưng <strong>gần metro</strong> có thể giảm thuê; ở trung tâm có thể <strong>tiết kiệm giờ học — làm thêm</strong> nhưng tăng nhà. Ghi rõ ưu tiên của bạn để không <strong>tiết kiệm nhầm chỗ</strong> (ví dụ ngành cần nhiều buổi tối ở lab hoặc studio).
                        </p>
                      </div>
                      <div class="news-health-phase" role="listitem">
                        <span class="news-health-phase__label">Shortlist</span>
                        <p class="news-health-phase__title">Cùng một bảng với mục <a href="#tieu-chi">tiêu chí</a></p>
                        <p>
                          Gắn cột “địa điểm &amp; sinh hoạt ước lượng” vào bảng so sánh 2–3 trường — cùng hàng với ngành, ngôn ngữ, học bổng. Cách này tránh việc chốt trường rồi mới phát hiện <strong>ngân sách thực tế không khớp</strong>.
                        </p>
                      </div>
                    </div>

                    <h3 class="news-health-section__subheading">Lập chi tiết học phí, nhà ở và khoản cố định</h3>
                    <ul class="news-health-section__principles">
                      <li><strong>Bám brochure &amp; thông báo KTX:</strong> ưu tiên số liệu từ trường, hợp đồng mẫu hoặc group sinh viên tại khu đó — hơn là một con số trung bình lan truyền mạng không rõ quận.</li>
                      <li><strong>Nhân sinh hoạt với 10–12 tháng:</strong> chênh lệch thành phố thường thể hiện rõ ở nhóm nhà và ăn; một năm học có thể lệch <strong>hàng chục triệu</strong> (quy đổi) chỉ vì địa điểm.</li>
                      <li><strong>Khung đầy đủ các khoản:</strong> học phí, phí nhập học, bảo hiểm, chỗ ở, đi lại, học liệu — cách chia nhóm và checklist có trong <a href="chi-phi-du-hoc-han-quoc-2026.php">Chi phí du học Hàn Quốc 2026</a> (mục tổng quan, khoản chi và khung ngân sách).</li>
                      <li><strong>Dự phòng 10–20%:</strong> cho tỷ giá, chuyển tiền, chuyển phòng giữa kỳ hoặc chi phát sinh — tránh “chốt vừa khít” khiến kỳ đầu căng thẳng.</li>
                    </ul>

                    <p class="news-health-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Giá thuê và sinh hoạt <strong>thay đổi theo mùa, tuyến đường và hợp đồng</strong>; bài không thay cho báo giá cụ thể. Khi bạn đã có 2–3 trường trong shortlist, SUNNY có thể hỗ trợ <strong>điền giả định hợp lý</strong> và đối chiếu với hồ sơ đang chuẩn bị.
                    </p>
                  </div>
                </section>

                <section id="nganh-tuyen" class="news-detail-section news-lang-section" aria-labelledby="hl-nganh">
                  <div class="news-lang-section__shell">
                    <header class="news-lang-section__head">
                      <span class="news-lang-section__step-num" aria-hidden="true">04</span>
                      <div class="news-lang-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-nganh">Ngành học, kỳ tuyển sinh &amp; học bổng 2026</h2>
                        <p class="news-section-opener__kicker">
                          Mỗi trường công bố <strong>calendar tuyển sinh</strong> (early / regular và đôi khi thêm đợt riêng), <strong>học phí</strong> và <strong>học bổng nhập học</strong> theo bộ quy tắc riêng. Khi rút gọn danh sách, hãy so sánh <strong>cùng một kỳ nộp</strong> — tránh trường A đã đóng early trong khi trường B vẫn mở regular, khiến bảng so sánh của bạn “lệch thời gian”.
                        </p>
                      </div>
                    </header>

                    <p class="news-lang-section__intro">
                      <strong>Ngành</strong> quyết định bạn đọc brochure nào: cùng một trường có thể có <strong>nhiều kỳ</strong> hoặc <strong>yêu cầu bổ sung</strong> khác nhau giữa khoa kỹ thuật, kinh doanh hay nghệ thuật. Hãy ghim <strong>tên chương trình chính xác</strong> (tiếng Anh trên trang tuyển sinh) và đối chiếu với <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học Hàn Quốc</a> để không nhầm “gần giống” với “đúng ngành”. Song song, lập một hàng cho <strong>học phí theo kỳ thu</strong>, <strong>suất giảm học phí / sinh hoạt</strong> (nếu có) và <strong>điều kiện duy trì</strong> — nhiều suất chỉ còn giá trị nếu bạn giữ GPA hoặc tín chỉ tối thiểu sau kỳ đầu.
                    </p>

                    <div class="news-lang-section__dual">
                      <article class="news-lang-panel news-lang-panel--ko" aria-labelledby="hl-nt-lich">
                        <h3 class="news-lang-panel__title" id="hl-nt-lich">
                          <span class="news-lang-panel__ico" aria-hidden="true"><i class="bi bi-calendar-range"></i></span>
                          Kỳ tuyển &amp; lịch nộp — đồng bộ mới so sánh được
                        </h3>
                        <p>
                          Trên thực tế, “mùa xuân / mùa thu” chỉ là khung tham khảo: từng trường mở <strong>early admission</strong>, <strong>regular</strong>, đôi khi <strong>đợt bổ sung</strong> hoặc <strong>rolling</strong> chỉ dành cho ứng viên quốc tế. Deadline <strong>nộp online</strong>, <strong>nộp giấy gốc</strong> và <strong>phỏng vấn</strong> có thể cách nhau vài tuần — cần ghi rõ từng mốc trong bảng của bạn.
                        </p>
                        <ul class="news-lang-panel__list">
                          <li>So sánh các trường trong shortlist theo <strong>cùng một đợt ứng tuyển</strong> (ví dụ cùng regular xuân), không trộn early trường này với regular trường kia nếu bạn chưa chắc kịp hồ sơ cho cả hai.</li>
                          <li>Kiểm tra <strong>thời điểm công bố kết quả</strong> và <strong>hạn xác nhận nhập học</strong> — ảnh hưởng tới việc có nộp tiếp trường khác hay không.</li>
                          <li>Nếu cần <strong>dịch công chứng, portfolio, bài luận</strong>, lùi deadline nội bộ của bạn sớm hơn hạn trường ít nhất 2–3 tuần để tránh chồng lịch thi hoặc chậm chuyển phát.</li>
                        </ul>
                      </article>
                      <article class="news-lang-panel news-lang-panel--en" aria-labelledby="hl-nt-hoc-phi-hb">
                        <h3 class="news-lang-panel__title" id="hl-nt-hoc-phi-hb">
                          <span class="news-lang-panel__ico" aria-hidden="true"><i class="bi bi-currency-exchange"></i></span>
                          Học phí &amp; học bổng nhập học theo từng trường
                        </h3>
                        <p>
                          Học phí niêm yết thường kèm <strong>phí nhập học</strong>, <strong>bảo hiểm</strong> hoặc <strong>gói KTX</strong> tùy thông báo. Học bổng nhập học có thể là <strong>miễn giảm % học phí</strong>, <strong>trợ cấp một lần</strong>, hoặc gói <strong>KTX — sinh hoạt</strong> — mỗi loại ảnh hưởng khác nhau tới dòng tiền năm đầu. Luôn đọc <strong>điều khoản</strong>: có suất yêu cầu duy trì điểm, không được nghỉ quá X tín chỉ, hoặc chỉ áp dụng học kỳ đầu.
                        </p>
                        <ul class="news-lang-panel__list">
                          <li>Ưu tiên số liệu từ <strong>PDF tuyển sinh quốc tế</strong> hoặc FAQ trường; tránh dựa vào bài tổng hợp cũ trên mạng.</li>
                          <li>Khi đã có mức học bổng dự kiến, cập nhật lại cột <strong>tổng ngân sách</strong> ở mục <a href="#vi-tri-chi-phi">vị trí &amp; chi phí</a> và <a href="chi-phi-du-hoc-han-quoc-2026.php">Chi phí 2026</a>.</li>
                          <li>Nếu ngành cần <strong>lab, license, thiết bị</strong>, tách riêng khỏi học phí để không “ảo tưởng” mức suất đang nhận.</li>
                        </ul>
                      </article>
                    </div>

                    <h3 class="news-lang-section__subheading">Học bổng quy mô lớn &amp; ảnh hưởng tới chọn trường</h3>
                    <div class="news-lang-section__path" role="list">
                      <div class="news-lang-path-step" role="listitem">
                        <span class="news-lang-path-step__label">GKS</span>
                        <p class="news-lang-path-step__title"><a href="hoc-bong-gks-chinh-phu-han-quoc.php">Global Korea Scholarship (GKS)</a> &amp; danh mục đối tác</p>
                        <p>
                          Nhiều ứng viên hướng <strong>GKS</strong> phải chọn trường / ngành trong <strong>khung được công bố</strong> theo từng đợt. Tiêu chí có thể gắn với <strong>quốc tịch, bậc học, điểm học lực, chứng chỉ</strong> và <strong>thư giới thiệu</strong> — nên đọc song song với hướng dẫn chính thức và cập nhật trên <a href="hoc-bong-du-hoc-han-quoc-2026.php">Học bổng du học Hàn Quốc 2026</a>.
                        </p>
                      </div>
                      <div class="news-lang-path-step" role="listitem">
                        <span class="news-lang-path-step__label">SUNNY</span>
                        <p class="news-lang-path-step__title"><a href="hoc-bong.php">Danh mục học bổng SUNNY</a> &amp; khớp với shortlist</p>
                        <p>
                          Trang học bổng SUNNY giúp bạn <strong>lọc suất theo bậc — ngành — đối tác</strong>. Khi chọn trường, hãy kiểm tra suất nào <strong>đi cùng campus</strong> bạn đang nhắm và suất nào yêu cầu <strong>nộp hồ sơ riêng</strong> trước hoặc sau đơn trường.
                        </p>
                      </div>
                      <div class="news-lang-path-step" role="listitem">
                        <span class="news-lang-path-step__label">Chốt</span>
                        <p class="news-lang-path-step__title">Một dòng “học bổng — ràng buộc” trong bảng so sánh</p>
                        <p>
                          Ghi rõ: <strong>giá trị suất</strong>, <strong>thời hạn</strong>, <strong>điều kiện duy trì</strong>, và liệu suất có bắt buộc <strong>ở KTX</strong> hay hạn chế làm thêm không. Cách này tránh chọn trường vì “có học bổng” nhưng sau đó phát hiện <strong>không đủ điều kiện</strong> hoặc suất không bù được chi ngành bạn học.
                        </p>
                      </div>
                    </div>

                    <p class="news-lang-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Lịch tuyển, học phí và học bổng <strong>thay đổi theo kỳ</strong>. Bài viết chỉ giúp bạn <strong>cấu trúc cách so sánh</strong>; quyết định cuối cần dựa trên thông báo chính thức từng trường và tư vấn SUNNY tại thời điểm bạn nộp.
                    </p>
                  </div>
                </section>

                <section id="quy-trinh" class="news-detail-section news-visa-section" aria-labelledby="hl-quy-trinh">
                  <div class="news-visa-section__shell">
                    <header class="news-visa-section__head">
                      <span class="news-visa-section__step-num" aria-hidden="true">05</span>
                      <div class="news-visa-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-quy-trinh">Quy trình chọn trường: rút gọn 2–3 phương án &amp; sai lầm cần tránh</h2>
                        <p class="news-section-opener__kicker">
                          Mục tiêu không phải “biết càng nhiều trường càng tốt”, mà là <strong>đi từ danh sách rộng → lọc nhanh → so sánh công bằng → chốt ít trường để hồ sơ đủ sâu</strong>. Bốn bước dưới đây khớp với các mục <a href="#tieu-chi">tiêu chí</a>, <a href="#vi-tri-chi-phi">vị trí &amp; chi phí</a> và <a href="#nganh-tuyen">kỳ tuyển — học bổng</a> trong cùng bài.
                        </p>
                      </div>
                    </header>

                    <p class="news-visa-section__intro">
                      Hãy tưởng tượng bạn đang vận hành một <strong>bảng theo dõi</strong> (Excel, Notion hoặc giấy): mỗi trường là một hàng, mỗi tiêu chí là một cột — <strong>ngành</strong>, <strong>ngưỡng ngôn ngữ</strong>, <strong>kỳ nộp</strong>, <strong>tổng ngân sách ước lượng</strong>, <strong>học bổng</strong>, <strong>KTX / hỗ trợ QT</strong>. Khi một trường có quá nhiều ô đỏ (không kịp chứng chỉ, lệch kỳ, vượt ngân sách), hãy <strong>loại sớm</strong> thay vì giữ trong đầu cho đến sát deadline. Việc chốt <strong>2–3 trường</strong> giúp bạn viết luận và chuẩn bị portfolio <strong>đúng từng chương trình</strong>, tránh bản “chung chung” dễ bị loại ở vòng sơ tuyển.
                    </p>

                    <h3 class="news-visa-section__subheading">Bốn bước rút gọn shortlist (5–7 trường → 2–3 trường)</h3>
                    <div class="news-visa-section__flow" role="list">
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Bước 1 — Liệt kê 5–7 trường sơ bộ theo ngành &amp; ngân sách</p>
                        <p>
                          Xuất phát từ <strong>ngành</strong> và <strong>khoảng chi gia đình chấp nhận được</strong> (đã ước lượng theo <a href="chi-phi-du-hoc-han-quoc-2026.php">Chi phí 2026</a> nếu có thể). Ghi nhanh tên trường, campus, chương trình tiếng Anh trên web tuyển sinh — có thể tham khảo <a href="truong-dai-hoc.php">danh sách đại học</a> và <a href="cac-nganh-du-hoc-han-quoc.php">các ngành</a> trên SUNNY để mở phạm vi, <strong>chưa cần</strong> đọc sâu từng dòng brochure.
                        </p>
                      </div>
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Bước 2 — Loại trừ trường vượt ngưỡng ngôn ngữ hoặc không có kỳ phù hợp</p>
                        <p>
                          So khớp <strong>TOPIK / IELTS / TOEFL</strong> và thời điểm bạn <strong>kịp thi lại</strong> (nếu cần) với yêu cầu từng đợt. Loại luôn trường chỉ mở kỳ không khớp lịch thi hoặc kế hoạch <a href="he-tieng-d4.php">D-4</a> / <a href="he-dai-hoc-d2.php">D-2</a> của bạn. Nếu lộ trình visa và chứng minh tài chính chưa khớp, đối chiếu sớm <a href="dieu-kien-du-hoc-han-quoc-2026.php">Điều kiện du học Hàn Quốc 2026</a> để tránh “trúng tuyển trên giấy” nhưng bị kẹt ở bước sau.
                        </p>
                      </div>
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Bước 3 — So sánh song song: học phí, học bổng, KTX, môi trường quốc tế</p>
                        <p>
                          Với các trường còn lại, mở <strong>cùng một bảng</strong>: học phí theo kỳ, suất học bổng nhập học (nếu có), <strong>ưu đãi / hạn KTX</strong>, và các dòng đã làm ở mục <a href="#vi-tri-chi-phi">vị trí &amp; sinh hoạt</a>. <strong>Tỷ lệ sinh viên quốc tế</strong> hoặc mức độ “quốc tế hóa” chỉ nên là <strong>tham khảo</strong> — quan trọng hơn là bạn có được <strong>hỗ trợ nhập học, buddy, orientation</strong> rõ ràng hay không (xem lại <a href="#tieu-chi">tiêu chí hỗ trợ QT</a>).
                        </p>
                      </div>
                      <div class="news-visa-flow-step" role="listitem">
                        <p class="news-visa-flow-step__title">Bước 4 — Chốt 2–3 trường; đầu tư essay &amp; portfolio theo từng nơi</p>
                        <p>
                          Dừng mở rộng danh sách và chuyển sang <strong>chất lượng hồ sơ</strong>: bài luận nên phản ánh đúng <strong>chương trình — giảng viên — hướng nghề</strong> bạn chọn ở từng trường; portfolio (nếu có) tuân <strong>format &amp; giới hạn dung lượng</strong> họ yêu cầu. Một lỗi hay gặp là dùng <strong>cùng một bản luận</strong> chỉ đổi tên trường — hội đồng thường nhận ra và đánh giá thấp mức độ phù hợp.
                        </p>
                      </div>
                    </div>

                    <h3 class="news-visa-section__subheading">Sai lầm thường gặp — nên tránh khi chọn trường 2026</h3>
                    <ul class="news-visa-section__tips">
                      <li><strong>Chỉ nhìn tên trường nổi tiếng</strong> mà bỏ qua độ mạnh <strong>ngành — giảng viên — thực tập</strong>: một trường tốt chung chung có thể <strong>yếu đúng chuyên ngành</strong> bạn cần; luôn đọc curriculum và output tốt nghiệp.</li>
                      <li><strong>Không đọc kỹ điều kiện &amp; visa</strong> trước khi chốt: yêu cầu chứng chỉ, lộ trình <strong>D-4 → D-2</strong>, chứng minh tài chính và thời hạn lưu trú phải <strong>cùng một câu chuyện</strong> với trường bạn nộp — xem <a href="dieu-kien-du-hoc-han-quoc-2026.php">Điều kiện du học Hàn Quốc 2026</a>.</li>
                      <li><strong>Ôm quá nhiều trường</strong> dẫn tới hồ sơ ẩu, deadline chồng chéo, sai sót khi copy tài liệu giữa các đơn: nên giới hạn <strong>2–3 trường ưu tiên</strong> và giữ thêm tối đa 1 phương án dự phòng nếu lịch cho phép.</li>
                      <li><strong>Bỏ qua “deal-breaker” cá nhân:</strong> ví dụ không chịu được thời tiết / chi phí / áp lực đi lại, hoặc ngành bắt buộc tiếng Hàn cao trong khi bạn chưa có lộ trình ôn — nên loại sớm thay vì hy vọng “sang rồi tính”.</li>
                    </ul>

                    <p class="news-visa-section__note uni-detail-note text-sm mb-0">
                      <strong>Gợi ý:</strong> Nếu bạn đang kẹt giữa hai trường tương đương, hãy so <strong>một tiêu chí quyết định</strong> (ví dụ thực tập bắt buộc, ngôn ngữ giảng dạy, hoặc tổng chi năm đầu) thay vì thêm trường thứ ba chỉ để “có thêm lựa chọn”. SUNNY có thể giúp bạn <strong>ưu tiên hóa bảng so sánh</strong> và rà lại timeline trước khi đóng đơn.
                    </p>
                  </div>
                </section>

                <section id="faq-chon-truong" class="news-detail-section news-faq-section" aria-labelledby="hl-faq">
                  <div class="news-faq-section__shell">
                    <header class="news-faq-section__head">
                      <span class="news-faq-section__step-num" aria-hidden="true">06</span>
                      <div class="news-faq-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-faq">Câu hỏi thường gặp khi chọn trường du học Hàn Quốc 2026</h2>
                        <p class="news-section-opener__kicker">
                          Bốn câu hỏi “chốt hạ” khi bạn đang <strong>rút gọn danh sách</strong> — đáp án đầy đủ hơn nằm ở <a href="#tong-quan">tổng quan</a>, <a href="#tieu-chi">tiêu chí</a>, <a href="#vi-tri-chi-phi">vị trí &amp; chi phí</a>, <a href="#nganh-tuyen">kỳ tuyển &amp; học bổng</a> và <a href="#quy-trinh">quy trình shortlist</a>.
                        </p>
                      </div>
                    </header>
                    <p class="news-faq-section__lead">
                      Không có câu trả lời “đúng cho mọi người”: <strong>ngành</strong>, <strong>ngân sách</strong>, <strong>chứng chỉ</strong> và <strong>lộ trình visa</strong> của bạn quyết định trường nào thực sự khả thi. Hãy dùng FAQ để <strong>kiểm tra nhanh</strong>, rồi quay lại từng mục trong bài để điền bảng so sánh của riêng bạn.
                    </p>
                    <ul class="news-faq-list">
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q1</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-ct-q-seoul">Nên chọn trường ở Seoul hay tỉnh năm 2026?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              <strong>Tùy ngành và ngân sách.</strong> Seoul và vùng đô thị lớn thường <strong>thuận kết nối</strong> — nhiều sự kiện ngành, career fair, thực tập tại trung tâm; đổi lại <strong>nhà và sinh hoạt</strong> dễ tốn hơn (xem mục <a href="#vi-tri-chi-phi">vị trí &amp; chi phí</a> và <a href="chi-phi-du-hoc-han-quoc-2026.php">Chi phí du học Hàn Quốc 2026</a>). Tỉnh hoặc campus xa trung tâm có thể <strong>giảm áp lực chi phí</strong>, nhưng cần tính thêm <strong>thời gian — chi phí đi lại</strong> nếu ngành bạn vẫn phải lên Seoul thường xuyên (phỏng vấn, thực tập, networking).
                            </p>
                            <p>
                              Cách chọn an toàn: so <strong>tổng chi năm đầu</strong> và <strong>cơ hội thực tập / tuyển dụng trong ngành</strong> của bạn, không chỉ theo <strong>tên thành phố</strong> hay thứ hạng chung chung — khớp với <a href="#tieu-chi">tiêu chí chọn trường</a> trong bài.
                            </p>
                            <ul>
                              <li>Nếu ngân sách căng: cân nhắc <strong>thành phố vệ tinh / tuyến metro tốt</strong> thay vì bỏ hẳn mục tiêu nghề nghiệp ở đô thị lớn.</li>
                              <li>Nếu ngành cần <strong>studio, lab, thiết bị</strong>: ưu tiên chất lượng chương trình hơn “ở đúng Seoul”.</li>
                            </ul>
                          </div>
                        </div>
                      </li>
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q2</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-ct-q-visa">Chọn trường du học Hàn Quốc 2026 có cần biết trước visa D-2 hay D-4?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              <strong>Có.</strong> Lộ trình <strong><a href="he-tieng-d4.php">D-4 (tiếng)</a></strong> rồi chuyển <strong><a href="he-dai-hoc-d2.php">D-2 (đại học / sau ĐH)</a></strong> ảnh hưởng trực tiếp tới việc bạn ưu tiên trường có <strong>KLI / viện ngôn ngữ</strong> mạnh hay trường nhận <strong>nộp thẳng cử nhân</strong> khi đã đủ chứng chỉ. Khác visa cũng có thể khác <strong>thời điểm nhập học, chứng minh tài chính và cách khai địa chỉ</strong> trong hồ sơ — nên đọc sớm <a href="dieu-kien-du-hoc-han-quoc-2026.php">Điều kiện du học Hàn Quốc 2026</a> để tránh chọn trường xong mới phát hiện lộ trình không khớp.
                            </p>
                            <p>
                              Xác định sớm giúp bạn <strong>khớp kỳ tuyển</strong> (mục <a href="#nganh-tuyen">kỳ tuyển &amp; học bổng</a>) và chuẩn bị <strong>timeline ôn thi / dịch công chứng</strong> gọn hơn — trùng với tinh thần <a href="#tong-quan">bước bắt đầu</a> trong bài.
                            </p>
                          </div>
                        </div>
                      </li>
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q3</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-ct-q-nhieu-truong">Có thể nộp nhiều trường cùng lúc không?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              <strong>Thường được</strong> nếu lịch tuyển sinh, phí xét tuyển và năng lực chuẩn bị hồ sơ của bạn cho phép. Tuy nhiên, mỗi trường có thể yêu cầu <strong>form, luận, portfolio, thư giới thiệu</strong> hơi khác nhau — nộp quá nhiều nơi cùng lúc dễ dẫn tới <strong>sai sót khi copy — dịch</strong>, hoặc <strong>deadline chồng chéo</strong> khiến chất lượng hồ sơ giảm.
                            </p>
                            <p>
                              SUNNY khuyên <strong>tập trung 2–3 trường ưu tiên</strong> (và tối đa thêm một phương án dự phòng nếu bạn quản lý thời gian tốt), đúng tinh thần <a href="#quy-trinh">quy trình rút gọn</a> trong bài. Nếu bạn vẫn muốn nộp rộng, hãy có <strong>bảng deadline nội bộ</strong> sớm hơn hạn trường ít nhất vài tuần.
                            </p>
                          </div>
                        </div>
                      </li>
                      <li class="news-faq-item">
                        <span class="news-faq-item__num" aria-hidden="true">Q4</span>
                        <div class="news-faq-item__body">
                          <h3 class="news-faq-item__question" id="faq-ct-q-nganh">Làm sao biết trường có phù hợp ngành nghề?</h3>
                          <div class="news-faq-item__answer">
                            <p>
                              Bắt đầu từ <strong>chương trình đào tạo</strong> (curriculum) trên trang tuyển sinh quốc tế: môn cốt lõi, lựa chọn tín chỉ, đồ án, <strong>thực tập bắt buộc hay không</strong>, ngôn ngữ giảng dạy. Xem thêm <strong>đội ngũ giảng viên</strong>, <strong>lab — studio</strong>, và <strong>hợp tác doanh nghiệp</strong> (MoU, dự án, cựu sinh viên). So sánh <strong>cùng tên ngành</strong> giữa hai trường vẫn có thể khác nội dung — tham khảo nhóm ngành trong <a href="cac-nganh-du-hoc-han-quoc.php">các ngành du học Hàn Quốc</a>.
                            </p>
                            <p>
                              Nếu có điều kiện, trao đổi <strong>cựu du học sinh</strong> cùng ngành để hiểu “thực tế học — thực tập — việc làm”. Kết hợp <a href="#dang-ky">tư vấn SUNNY</a> để đối chiếu với <strong>năng lực &amp; điểm mạnh hồ sơ</strong> của bạn (điểm, portfolio, chứng chỉ), tránh chọn trường vượt quá <strong>ngưỡng thực tế</strong> hoặc ngược lại — chưa tận dụng hết tiềm năng khi ứng tuyển.
                            </p>
                          </div>
                        </div>
                      </li>
                    </ul>
                    <p class="news-faq-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> FAQ chỉ <strong>định hướng</strong>. Điều kiện tuyển, học phí và học bổng thay đổi theo từng kỳ — luôn xác nhận trên website chính thức của trường và với tư vấn SUNNY trước khi quyết định cuối.
                    </p>
                  </div>
                </section>

                <section id="ket-luan" class="news-detail-section news-conclusion-section" aria-labelledby="hl-ket-luan">
                  <div class="news-conclusion-section__shell">
                    <header class="news-conclusion-section__head">
                      <span class="news-conclusion-section__step-num" aria-hidden="true">07</span>
                      <div class="news-conclusion-section__head-text">
                        <h2 class="news-section-opener__title" id="hl-ket-luan">Kết luận: chọn trường du học Hàn Quốc 2026 cùng SUNNY</h2>
                        <p class="news-section-opener__kicker">
                          Chốt trường không phải “chọn logo đẹp nhất”, mà là chọn nơi <strong>ngành — ngân sách — ngôn ngữ — hỗ trợ quốc tế</strong> cùng hướng với bạn trong 3–4 năm tới — rồi chứng minh điều đó bằng một <strong>bộ hồ sơ nhất quán</strong>.
                        </p>
                      </div>
                    </header>

                    <p class="news-conclusion-section__lead">
                      <strong>Chọn trường du học Hàn Quốc 2026</strong> là bước then chốt sau khi bạn đã hình dung được <strong>bậc học</strong> và <strong>ngành</strong>. Thứ hạng tổng thể chỉ là một phần tham chiếu; quan trọng hơn là <strong>chương trình có giúp bạn đạt mục tiêu nghề nghiệp</strong>, <strong>tổng chi thực tế</strong> gia đình gánh được, và <strong>bạn có đủ chứng chỉ — thời gian</strong> để vào đúng kỳ tuyển. Khi bốn trụ này khớp, việc viết luận và chuẩn bị portfolio cũng tự nhiên hơn vì bạn đang kể một câu chuyện thật, không phải ghép tên trường vào mẫu có sẵn.
                    </p>
                    <p class="news-conclusion-section__body">
                      Trước khi đóng đơn, hãy lướt lại bảng so sánh của bạn: <strong>cùng kỳ nộp</strong>, <strong>cùng logic ngân sách</strong> (đã cộng nhà ở và sinh hoạt như mục <a href="#vi-tri-chi-phi">vị trí &amp; chi phí</a>), và <strong>cùng lộ trình visa</strong> với <a href="dieu-kien-du-hoc-han-quoc-2026.php">Điều kiện du học Hàn Quốc 2026</a>. Nếu một trường chỉ “đẹp trên giấy” nhưng lệch một trong các dòng này, đó là dấu hiệu nên điều chỉnh shortlist thay vì cố nộp cho đủ. Khi đã chốt 2–3 phương án, đầu tư thời gian cho <strong>essay riêng từng chương trình</strong> và checklist giấy tờ sẽ mang lại lợi ích lớn hơn việc mở thêm năm trường nữa.
                    </p>

                    <h3 class="news-conclusion-section__subheading">Đối chiếu nhanh các mục trong bài</h3>
                    <div class="news-conclusion-section__grid">
                      <a class="news-conclusion-card" href="#tong-quan">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-compass"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Bắt đầu từ đâu</span>
                          <span class="news-conclusion-card__hint">Lộ trình, kỳ tuyển, checklist tinh thần trước khi săn trường.</span>
                          <span class="news-conclusion-card__go">Mục 01 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#tieu-chi">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-check2-square"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Tiêu chí chọn trường</span>
                          <span class="news-conclusion-card__hint">Ngành, ngôn ngữ, uy tín ngành, hỗ trợ sinh viên quốc tế.</span>
                          <span class="news-conclusion-card__go">Mục 02 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#vi-tri-chi-phi">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Vị trí &amp; chi phí</span>
                          <span class="news-conclusion-card__hint">Seoul / vệ tinh / tỉnh — tổng ngân sách năm, không chỉ học phí.</span>
                          <span class="news-conclusion-card__go">Mục 03 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#nganh-tuyen">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Kỳ tuyển &amp; học bổng</span>
                          <span class="news-conclusion-card__hint">Early / regular, học phí, suất nhập học, GKS &amp; danh mục SUNNY.</span>
                          <span class="news-conclusion-card__go">Mục 04 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#quy-trinh">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-signpost-split"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Quy trình rút gọn</span>
                          <span class="news-conclusion-card__hint">5–7 trường → 2–3 trường; sai lầm cần tránh.</span>
                          <span class="news-conclusion-card__go">Mục 05 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="#faq-chon-truong">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-patch-question"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">FAQ chọn trường</span>
                          <span class="news-conclusion-card__hint">Seoul vs tỉnh, D-2/D-4, nộp nhiều trường, phù hợp ngành.</span>
                          <span class="news-conclusion-card__go">Mục 06 <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                      <a class="news-conclusion-card" href="chi-phi-du-hoc-han-quoc-2026.php">
                        <span class="news-conclusion-card__ico" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
                        <span class="news-conclusion-card__text">
                          <span class="news-conclusion-card__title">Chi phí du học 2026</span>
                          <span class="news-conclusion-card__hint">Khung khoản chi &amp; ngân sách khi đã có danh sách trường.</span>
                          <span class="news-conclusion-card__go">Bài liên quan <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </span>
                      </a>
                    </div>

                    <div class="news-conclusion-section__cta">
                      <p>
                        Đăng ký <a href="#dang-ky">tư vấn miễn phí</a> — SUNNY đồng hành <strong>so khớp 2–3 phương án trường</strong>, lịch nộp, checklist hồ sơ và đối chiếu nhanh với <a href="hoc-bong-du-hoc-han-quoc-2026.php">học bổng 2026</a>, <a href="cac-nganh-du-hoc-han-quoc.php">nhóm ngành</a> cùng lộ trình <a href="he-tieng-d4.php">D-4</a> / <a href="he-dai-hoc-d2.php">D-2</a> phù hợp mục tiêu của bạn.
                      </p>
                      <a href="#dang-ky" class="btn btn-sunny text-white px-4 mt-3 inline-flex items-center justify-center gap-2">
                        <i class="bi bi-chat-dots" aria-hidden="true"></i>
                        Đăng ký tư vấn miễn phí
                      </a>
                    </div>

                    <p class="news-conclusion-section__note uni-detail-note text-sm mb-0">
                      <strong>Lưu ý:</strong> Học phí, học bổng và chỉ tiêu tuyển sinh <strong>thay đổi theo từng kỳ</strong>. Luôn xác nhận trên website chính thức của trường và với tư vấn SUNNY trước khi quyết định cuối; bài viết chỉ mang tính <strong>định hướng</strong>.
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
                  Tư vấn chọn trường
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
