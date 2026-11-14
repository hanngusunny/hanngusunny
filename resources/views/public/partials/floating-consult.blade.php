@php
  $consultSource = request()->getRequestUri();
  if (\Illuminate\Support\Str::length($consultSource) > 500) {
    $consultSource = \Illuminate\Support\Str::substr($consultSource, 0, 500);
  }
@endphp
<div class="consult-float-stack" id="consult-float-stack" role="presentation">
  <div class="consult-float-social" id="consult-float-social" hidden>
    <ul class="consult-float-social__list" role="list">
      <li>
        <a class="consult-float-social__link consult-float-social__link--facebook" href="https://www.facebook.com/huhocsunny.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook Du học Hàn Quốc SUNNY" tabindex="0">
          <i class="bi bi-facebook" aria-hidden="true"></i>
        </a>
      </li>
      <li>
        <a class="consult-float-social__link consult-float-social__link--zalo" href="https://zalo.me/408031997547741433" target="_blank" rel="noopener noreferrer" aria-label="Zalo SUNNY (hotline)" tabindex="0">
          <span class="consult-float-social__zalo-z" aria-hidden="true">Z</span>
        </a>
      </li>
      <li>
        <a class="consult-float-social__link consult-float-social__link--tiktok" href="https://www.tiktok.com/@duhocsunny" target="_blank" rel="noopener noreferrer" aria-label="TikTok duhocsunny" tabindex="0">
          <i class="bi bi-tiktok" aria-hidden="true"></i>
        </a>
      </li>
      <li>
        <a class="consult-float-social__link consult-float-social__link--youtube" href="https://www.youtube.com/@duhocsunny" target="_blank" rel="noopener noreferrer" aria-label="Kênh YouTube Du học Hàn Quốc SUNNY" tabindex="0">
          <i class="bi bi-youtube" aria-hidden="true"></i>
        </a>
      </li>
    </ul>
  </div>
  <button type="button" class="consult-float-social-toggle" id="consult-float-social-toggle" aria-expanded="false" aria-controls="consult-float-social" title="Mạng xã hội SUNNY" aria-label="Mở liên kết mạng xã hội SUNNY">
    <span class="consult-float-social-toggle__icon-open" aria-hidden="true"><i class="bi bi-share-fill"></i></span>
    <span class="consult-float-social-toggle__icon-close hidden" aria-hidden="true">
      <svg class="consult-float-social-toggle__chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path class="consult-float-social-toggle__chev-stroke consult-float-social-toggle__chev-stroke--a" d="M7.2 8.9 12 13.35 16.8 8.9" />
        <path class="consult-float-social-toggle__chev-stroke consult-float-social-toggle__chev-stroke--b" d="M7.2 12.55 12 17 16.8 12.55" />
      </svg>
    </span>
  </button>
  <button type="button" class="register-fab" data-tw-toggle="modal" data-tw-target="#modalConsultSite" title="Đăng ký tư vấn miễn phí" aria-label="Mở biểu mẫu đăng ký tư vấn">
    <i class="bi bi-pencil-square register-fab__icon" aria-hidden="true"></i>
    <span class="register-fab__text hidden sm:inline">Tư vấn</span>
  </button>
</div>
<div class="modal fade modal-register" id="modalConsultSite" tabindex="-1" aria-labelledby="modalConsultSiteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-register__dialog">
    <div class="modal-content modal-register__content">
      <button type="button" class="modal-register__close" data-tw-dismiss="modal" aria-label="Đóng biểu mẫu đăng ký tư vấn">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
      </button>
      <div class="modal-register__grid">
        <aside class="modal-register__aside" aria-hidden="false">
          <div class="modal-register__aside-media">
            <img src="{{ asset('assets/img/form.jpg') }}" alt="" width="480" height="640" loading="lazy" decoding="async">
          </div>
          <div class="modal-register__aside-body">
            <p class="modal-register__aside-kicker">SUNNY Du học Hàn Quốc</p>
            <p class="modal-register__aside-title">Tư vấn 1:1 cùng chuyên gia</p>
            <ul class="modal-register__benefits">
              <li><i class="bi bi-check2-circle" aria-hidden="true"></i>Lộ trình D-4 / D-2 rõ ràng</li>
              <li><i class="bi bi-check2-circle" aria-hidden="true"></i>Phản hồi trong 24 giờ làm việc</li>
              <li><i class="bi bi-check2-circle" aria-hidden="true"></i>Không phí tư vấn, không spam</li>
            </ul>
            @if (!empty($publicHotlineTel) && !empty($publicHotline))
              <a class="modal-register__hotline" href="{{ $publicHotlineTel }}" data-hotline-click>
                <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                <span>{{ $publicHotline }}</span>
              </a>
            @endif
          </div>
        </aside>
        <div class="modal-register__main">
          <header class="modal-register__intro">
            <p class="modal-register__eyebrow">Miễn phí · Bảo mật thông tin</p>
            <h2 class="modal-register__title" id="modalConsultSiteLabel">Đăng ký tư vấn miễn phí</h2>
            <p class="modal-register__lead">Điền form, SUNNY gọi lại để tư vấn trường, visa và học bổng phù hợp với bạn.</p>
          </header>
          <form method="post" action="{{ route('consult.submit') }}" class="form-register form-register--modal" accept-charset="UTF-8" id="site-consult-modal-form">
            @csrf
            <input type="hidden" name="consult_form_origin" value="modal">
            <input type="hidden" name="consult_source" value="{{ $consultSource }}" maxlength="500">
            <div class="hidden" aria-hidden="true">
              <label for="site-consult-hp">Không điền ô này</label>
              <input type="text" name="consult_hp" id="site-consult-hp" value="" tabindex="-1" autocomplete="off">
            </div>
            <div class="modal-register__fields">
              <div class="modal-register__field modal-register__field--full">
                <label class="modal-register__label" for="site-consult-chu-de">Chủ đề tư vấn</label>
                <div class="modal-register__control-wrap">
                  <i class="bi bi-journal-bookmark modal-register__icon" aria-hidden="true"></i>
                  <select class="tw-select form-register__control modal-register__control" id="site-consult-chu-de" name="chuDe" required>
                    <option value="" disabled selected>Chọn chủ đề bạn muốn nhận tư vấn *</option>
                    @foreach (\App\Models\ConsultLead::TOPICS as $k => $lab)
                      <option value="{{ $k }}">{{ $lab }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="modal-register__field">
                <label class="modal-register__label" for="site-consult-ho-ten">Họ và tên</label>
                <div class="modal-register__control-wrap">
                  <i class="bi bi-person modal-register__icon" aria-hidden="true"></i>
                  <input type="text" class="tw-field form-register__control modal-register__control" id="site-consult-ho-ten" name="hoTen" required autocomplete="name" placeholder="Nguyễn Văn A">
                </div>
              </div>
              <div class="modal-register__field">
                <label class="modal-register__label" for="site-consult-sdt">Số điện thoại</label>
                <div class="modal-register__control-wrap">
                  <i class="bi bi-phone modal-register__icon" aria-hidden="true"></i>
                  <input type="tel" class="tw-field form-register__control modal-register__control" id="site-consult-sdt" name="sdt" required autocomplete="tel" placeholder="09xx xxx xxx">
                </div>
              </div>
              <div class="modal-register__field modal-register__field--full">
                <label class="modal-register__label" for="site-consult-tinh-thanh">Tỉnh / Thành phố</label>
                <div class="modal-register__control-wrap">
                  <i class="bi bi-geo-alt modal-register__icon" aria-hidden="true"></i>
                  <input type="text" class="tw-field form-register__control modal-register__control" id="site-consult-tinh-thanh" name="tinhThanh" required autocomplete="address-level1" placeholder="Tỉnh/Thành phố đang sống">
                </div>
              </div>
              <div class="modal-register__field modal-register__field--full">
                <label class="modal-register__label" for="site-consult-cau-hoi">Câu hỏi cho SUNNY</label>
                <textarea class="tw-field form-register__control form-register__textarea modal-register__textarea" id="site-consult-cau-hoi" name="cauHoi" rows="4" required placeholder="Bạn muốn hỏi về trường, visa, học bổng hay lộ trình nào?"></textarea>
              </div>
            </div>
            <button type="submit" class="btn form-register__submit modal-register__submit">
              Gửi đăng ký tư vấn
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
            <p class="modal-register__note">SUNNY liên hệ trong 24h làm việc. Thông tin chỉ dùng để tư vấn, không chia sẻ cho bên thứ ba.</p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var f = document.getElementById('site-consult-modal-form');
  if (!f) return;
  function syncSource() {
    var h = f.querySelector('input[name="consult_source"]');
    if (!h) return;
    var v = (window.location.pathname || '/') + (window.location.search || '');
    if (v.length > 500) v = v.slice(0, 500);
    h.value = v;
  }
  f.addEventListener('submit', syncSource);
  var modal = document.getElementById('modalConsultSite');
  if (modal) {
    modal.addEventListener('show.tw.modal', syncSource);
    modal.addEventListener('show.bs.modal', syncSource);
  }
})();
(function () {
  var stack = document.getElementById('consult-float-stack');
  var panel = document.getElementById('consult-float-social');
  var toggle = document.getElementById('consult-float-social-toggle');
  if (!stack || !panel || !toggle) return;
  var iconOpen = toggle.querySelector('.consult-float-social-toggle__icon-open');
  var iconClose = toggle.querySelector('.consult-float-social-toggle__icon-close');
  var KEY_DESK = 'consultFloatSocialOpen';
  var KEY_MOBILE = 'consultFloatSocialOpenMobile';
  var mqMobile = window.matchMedia('(max-width: 767.98px)');
  function setLinksTabindex(open) {
    panel.querySelectorAll('a.consult-float-social__link').forEach(function (el) {
      el.setAttribute('tabindex', open ? '0' : '-1');
    });
  }
  function icons(open) {
    if (iconOpen && iconClose) {
      iconOpen.classList.toggle('hidden', open);
      iconClose.classList.toggle('hidden', !open);
    }
  }
  function applyState(open) {
    stack.classList.toggle('consult-float-stack--social-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Đóng liên kết mạng xã hội SUNNY' : 'Mở liên kết mạng xã hội SUNNY');
    panel.hidden = !open;
    setLinksTabindex(open);
    icons(open);
    try { sessionStorage.setItem(mqMobile.matches ? KEY_MOBILE : KEY_DESK, open ? '1' : '0'); } catch (e) {}
  }
  function resolveInitialOpen() {
    try {
      return mqMobile.matches ? sessionStorage.getItem(KEY_MOBILE) === '1' : sessionStorage.getItem(KEY_DESK) !== '0';
    } catch (e) { return !mqMobile.matches; }
  }
  applyState(resolveInitialOpen());
  if (typeof mqMobile.addEventListener === 'function') mqMobile.addEventListener('change', function () { applyState(resolveInitialOpen()); });
  toggle.addEventListener('click', function () {
    applyState(!stack.classList.contains('consult-float-stack--social-open'));
  });
})();
</script>
