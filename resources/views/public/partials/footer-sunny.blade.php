<footer id="lien-he" class="footer-sunny">
  <div class="container">
    <div class="footer-sunny__main">
      <div>
        <a class="footer-brand inline-block mb-3" href="{{ route('home') }}" aria-label="Trang chủ Sunny">
          <img src="{{ $publicLogoUrl }}" alt="Sunny Du học Hàn Quốc" class="footer-logo max-w-full h-auto" width="200" height="52" loading="lazy" decoding="async">
        </a>
        <p class="footer-sunny__tagline mb-3">
          SUNNY là trung tâm du học Hàn Quốc với nguyên tắc cốt lõi: <strong>minh bạch chi phí</strong> và thông tin trong mọi lộ trình — tư vấn đúng, nói thật, đồng hành có trách nhiệm.
        </p>
        <div class="flex flex-wrap gap-2 footer-sunny__social">
          <a href="https://huhocsunny.com/" class="footer-sunny__social-btn" target="_blank" rel="noopener noreferrer" aria-label="Website huhocsunny.com"><i class="bi bi-globe2" aria-hidden="true"></i></a>
          <a href="https://www.facebook.com/huhocsunny.com" class="footer-sunny__social-btn" target="_blank" rel="noopener noreferrer" aria-label="Fanpage Facebook Du học Hàn Quốc SUNNY"><i class="bi bi-facebook" aria-hidden="true"></i></a>
          <a href="https://www.tiktok.com/@duhocsunny" class="footer-sunny__social-btn" target="_blank" rel="noopener noreferrer" aria-label="TikTok @duhocsunny"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
        </div>
      </div>
      <div>
        <h6 class="footer-sunny__title">Khám phá</h6>
        <ul class="list-none p-0 footer-sunny__links">
          <li><a href="{{ url('/gioi-thieu') }}">Giới thiệu</a></li>
          <li><a href="{{ url('/khoanh-khac') }}">Khoảnh khắc đáng nhớ</a></li>
          <li><a href="{{ url('/dieu-kien-du-hoc-han-quoc-2026') }}">Điều kiện du học 2026</a></li>
          <li><a href="{{ url('/chon-truong-du-hoc-han-quoc-2026') }}">Chọn trường 2026</a></li>
          <li><a href="{{ url('/chi-phi-du-hoc-han-quoc-2026') }}">Chi phí 2026</a></li>
          <li><a href="{{ url('/hoc-bong-du-hoc-han-quoc-2026') }}">Học bổng 2026</a></li>
        </ul>
      </div>
      <div>
        <h6 class="footer-sunny__title">Menu du học</h6>
        <ul class="list-none p-0 footer-sunny__links">
          <li><a href="{{ route('home') }}#he-du-hoc">Hệ du học</a></li>
          <li><a href="{{ url('/du-hoc-han-quoc-he-tieng-d4-1') }}">Du học hệ tiếng (D4-1)</a></li>
          <li><a href="{{ url('/du-hoc-han-quoc-he-dai-hoc-d2-2') }}">Du học hệ đại học (D2-2)</a></li>
          <li><a href="{{ url('/du-hoc-han-quoc-he-thac-si-d2-3') }}">Du học hệ thạc sĩ (D2-3)</a></li>
          <li><a href="{{ url('/du-hoc-han-quoc-he-cao-dang-d2-1') }}">Du học hệ cao đẳng (D2-1)</a></li>
          <li><a href="{{ url('/truong-dai-hoc') }}">Trường đại học</a></li>
          <li><a href="{{ url('/cac-nganh-du-hoc-han-quoc') }}">Ngành học</a></li>
          <li><a href="{{ url('/dao-tao-tieng-han') }}">Đào tạo tiếng Hàn</a></li>
          <li><a href="{{ url('/tin-tuc') }}">Tin tức</a></li>
        </ul>
      </div>
      <div>
        <h6 class="footer-sunny__title">Liên hệ</h6>
        <ul class="list-none p-0 footer-sunny__contact mb-0">
          <li>
            <span class="footer-sunny__contact-label">Hotline</span>
            <a href="tel:0981698292" data-hotline-click>0981 698 292</a>
          </li>
          <li>
            <span class="footer-sunny__contact-label">Miền Bắc</span>
            <a href="tel:0355892252">0355.892.252</a> <span class="footer-sunny__hint">(Trần Thị Thu Minh)</span>
          </li>
          <li>
            <span class="footer-sunny__contact-label">Miền Trung</span>
            <a href="tel:0762885604">076.288.5604</a> <span class="footer-sunny__hint">(Ms. Bích Hồng)</span>
          </li>
          <li>
            <span class="footer-sunny__contact-label">Miền Nam</span>
            <a href="tel:0393725155">039.372.5155</a> <span class="footer-sunny__hint">(Ms. Hồ Yến)</span>
          </li>
          <li class="mt-2">
            <i class="bi bi-envelope text-primary-sunny me-1" aria-hidden="true"></i>
            <a href="mailto:info@duhocsunny.edu.vn">info@duhocsunny.edu.vn</a>
          </li>
        </ul>
      </div>
    </div>
    <div class="footer-sunny__offices">
      <h6 class="footer-sunny__title mb-3">Hệ thống văn phòng</h6>
      <div class="footer-sunny__offices-grid text-sm">
        <div>
          <div class="footer-sunny__office">
            <span class="footer-sunny__office-name">SUNNY Hà Nội</span>
            <p class="mb-0">Số nhà 3 ngách 376/19 đường Bưởi, P. Ngọc Hà, Hà Nội.</p>
          </div>
        </div>
        <div>
          <div class="footer-sunny__office">
            <span class="footer-sunny__office-name">SUNNY Đà Nẵng</span>
            <p class="mb-0">63 Hà Huy Giáp, phường Hoà Cường, Đà Nẵng.</p>
          </div>
        </div>
        <div>
          <div class="footer-sunny__office">
            <span class="footer-sunny__office-name">SUNNY Hồ Chí Minh</span>
            <p class="mb-0">40 đường số 8 KDC Cityland Park Hills, Phường Gò Vấp, TP. Hồ Chí Minh.</p>
          </div>
        </div>
        <div>
          <div class="footer-sunny__office">
            <span class="footer-sunny__office-name">SUNNY Hàn Quốc</span>
            <p class="mb-0">Room 611, 6F, Ssangnim Building, 286 Toegye-ro, Jung-gu, Seoul, Korea.</p>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom footer-sunny__bottom text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-2">
      <span>© {{ date('Y') }} <a href="https://huhocsunny.com/" class="text-white no-underline" target="_blank" rel="noopener noreferrer">Trung tâm Du học Hàn Quốc SUNNY</a> — huhocsunny.com</span>
      <span class="footer-sunny__legal"><a href="https://huhocsunny.com/" target="_blank" rel="noopener noreferrer">Chính sách bảo mật</a> · <a href="https://huhocsunny.com/" target="_blank" rel="noopener noreferrer">Điều khoản sử dụng</a></span>
    </div>
  </div>
</footer>
