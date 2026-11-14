@php
  $consultSource = request()->getRequestUri();
  if (\Illuminate\Support\Str::length($consultSource) > 500) {
    $consultSource = \Illuminate\Support\Str::substr($consultSource, 0, 500);
  }
@endphp
<section id="dang-ky" class="register-section register-section--footer-cta py-5" aria-labelledby="dang-ky-heading">
  <div class="container">
    <div class="register-wrap">
      <div class="register-cta-grid">
        <div class="register-cta-grid__photo flex items-stretch">
          <div class="register-expert w-full h-full">
            <div class="register-expert__photo">
              <img src="{{ asset('assets/img/form.jpg') }}" alt="Chuyên gia tư vấn SUNNY" width="480" height="480" loading="lazy" decoding="async">
            </div>
            <div class="register-expert__banner">
              <p class="register-expert__banner-title">Chuyên gia SUNNY</p>
              <p class="register-expert__banner-text">6 năm kinh nghiệm tư vấn du học Hàn.</p>
            </div>
          </div>
        </div>
        <div class="register-cta-grid__form flex items-stretch">
          <div class="form-register-panel grow flex flex-col">
            <form method="post" action="{{ route('consult.submit') }}" class="form-register flex-grow-1 flex flex-col" accept-charset="UTF-8" id="footer-consult-form">
              @csrf
              <input type="hidden" name="consult_form_origin" value="footer">
              <input type="hidden" name="consult_source" value="{{ $consultSource }}" maxlength="500">
              <div class="hidden" aria-hidden="true">
                <label for="footer-consult-hp">Không điền ô này</label>
                <input type="text" name="consult_hp" id="footer-consult-hp" value="" tabindex="-1" autocomplete="off">
              </div>
              <h2 id="dang-ky-heading" class="form-register__title">Đăng ký tư vấn miễn phí ngay</h2>
              <div class="form-register__fields flex flex-col gap-3">
                <div>
                  <label class="sr-only" for="footer-chu-de">Chủ đề tư vấn</label>
                  <select class="tw-select form-register__control" id="footer-chu-de" name="chuDe" required>
                    <option value="" disabled selected>Chọn chủ đề bạn muốn nhận tư vấn *</option>
                    @foreach (\App\Models\ConsultLead::TOPICS as $k => $lab)
                      <option value="{{ $k }}">{{ $lab }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label class="sr-only" for="footer-ho-ten">Họ và tên</label>
                  <input type="text" class="tw-field form-register__control" id="footer-ho-ten" name="hoTen" required autocomplete="name" placeholder="Họ và Tên *">
                </div>
                <div>
                  <label class="sr-only" for="footer-sdt">Số điện thoại</label>
                  <input type="tel" class="tw-field form-register__control" id="footer-sdt" name="sdt" required autocomplete="tel" placeholder="Số điện thoại *">
                </div>
                <div>
                  <label class="sr-only" for="footer-tinh-thanh">Tỉnh / Thành phố</label>
                  <input type="text" class="tw-field form-register__control" id="footer-tinh-thanh" name="tinhThanh" required autocomplete="address-level1" placeholder="Tỉnh/Thành phố nơi bạn đang sống *">
                </div>
                <div>
                  <label class="sr-only" for="footer-cau-hoi">Câu hỏi</label>
                  <textarea class="tw-field form-register__control form-register__textarea" id="footer-cau-hoi" name="cauHoi" rows="5" required placeholder="Câu hỏi của bạn dành cho Sunny *"></textarea>
                </div>
              </div>
              <button type="submit" class="btn form-register__submit">Hoàn thành</button>
              <p class="form-register__note mb-0 sr-only">SUNNY liên hệ trong 24h làm việc — không spam, không chia sẻ thông tin cho bên thứ ba.</p>
            </form>
            <script>
              (function () {
                var f = document.getElementById('footer-consult-form');
                if (!f) return;
                f.addEventListener('submit', function () {
                  var h = f.querySelector('input[name="consult_source"]');
                  if (!h) return;
                  var v = (window.location.pathname || '/') + (window.location.search || '');
                  if (v.length > 500) v = v.slice(0, 500);
                  h.value = v;
                });
              })();
            </script>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
