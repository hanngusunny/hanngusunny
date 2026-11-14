@extends('public.layout')

@php
  $title = $post->title;
  $cat = $category ?? $post->category;
  $catName = trim((string) ($cat->name ?? ''));
  $catUrl = ($cat && $cat->slug && $cat->is_active) ? url('/'.$cat->slug) : url('/su-kien');
  $isUpcoming = $post->published_at && $post->published_at->gte(now()->startOfDay());
  $dateAttr = $post->published_at ? $post->published_at->format('Y-m-d') : '';
  $dateLabel = $post->published_at ? $post->published_at->format('d/m/Y') : '';
  $weekdays = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
  $weekdayVi = $post->published_at ? ($weekdays[(int) $post->published_at->format('w')] ?? '') : '';
  $clockDisplay = '';
  if ($post->published_at && $post->published_at->format('H:i:s') !== '00:00:00') {
    $clockDisplay = $post->published_at->format('H:i') . ' (giờ Việt Nam)';
  }
  $leadPlain = trim((string) ($post->meta_description ?: strip_tags((string) $post->excerpt)));
  $views = number_format((int) $post->view_count, 0, ',', '.');
  $tocItems = $tocItems ?? [];
  $imgUrl = $post->featuredImageUrl();
  $uid = 'sk'.$post->id;
  $tenSuKien = $title . ($dateLabel !== '' ? ' — '.$dateLabel : '');
  $ctaAnchor = $isUpcoming ? 'chi-tiet-sap-dien-ra' : ($imgUrl !== '' ? 'hinh-anh-su-kien' : 'noi-dung-tong-ket');
  $ctaText = $isUpcoming ? 'Xem nội dung & đăng ký' : ($imgUrl !== '' ? 'Xem hình ảnh sự kiện' : 'Xem nội dung');
  $fallbackImg = 'https://images.unsplash.com/photo-1544531586-fde5298cdd40?w=640&q=80';
  $consultSource = request()->getRequestUri();
@endphp

@section('content')
<div class="tpl tpl--article tpl--event-detail" data-scope="events" data-page="article"@if (!empty($tocItems)) data-bun-news-toc="1"@endif>
  <div id="noi-dung-chinh">
    <header class="about-intro-hero page-about-hero bg-soft-orange">
      <div class="about-intro-hero__deco" aria-hidden="true"></div>
      <div class="container py-4 lg:py-5 relative">
        <nav class="page-about-breadcrumb page-about-breadcrumb--pill mb-3 lg:mb-4" aria-label="Breadcrumb">
          <ol class="page-about-breadcrumb__list">
            <li class="page-about-breadcrumb__step">
              <a href="{{ route('home') }}" class="page-about-breadcrumb__link">
                <i class="bi bi-house-door-fill page-about-breadcrumb__home-ico" aria-hidden="true"></i>
                Trang chủ
              </a>
            </li>
            <li class="page-about-breadcrumb__step">
              <a href="{{ url('/su-kien') }}" class="page-about-breadcrumb__link">Sự kiện</a>
            </li>
            @if ($catName !== '')
              <li class="page-about-breadcrumb__step">
                <a href="{{ $catUrl }}" class="page-about-breadcrumb__link">{{ $catName }}</a>
              </li>
            @endif
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">Chi tiết</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <p class="about-intro-hero__kicker">
              <span class="about-intro-hero__kicker-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
              {{ $isUpcoming ? 'Chi tiết chương trình · Đăng ký tham dự' : 'Sự kiện đã diễn ra' }}
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">{{ $title }} <span class="text-primary-sunny">SUNNY</span></h1>
            <p class="about-intro-hero__lead">
              @if ($leadPlain !== '')
                {{ $leadPlain }}
              @elseif ($isUpcoming)
                Thông tin chương trình và <strong class="about-intro-hero__strong">form đăng ký tham dự</strong> bên dưới.
              @else
                Hình ảnh và <strong class="about-intro-hero__strong">nội dung chương trình</strong> được lưu trên trang này.
              @endif
            </p>
            @if ($isUpcoming)
              <div class="about-intro-hero__chips" role="list">
                <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-broadcast" aria-hidden="true"></i> Sắp diễn ra &amp; đăng ký</span>
                <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-calendar-event" aria-hidden="true"></i> Thời gian trong nội dung</span>
                <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-pencil-square" aria-hidden="true"></i> Form bên phải (desktop)</span>
              </div>
            @else
              <div class="about-intro-hero__chips" role="list">
                <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-archive" aria-hidden="true"></i> Đã diễn ra</span>
                @if ($weekdayVi !== '' && $dateLabel !== '')
                  <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-calendar3" aria-hidden="true"></i><time datetime="{{ $dateAttr }}">{{ $weekdayVi }}, {{ $dateLabel }}</time></span>
                @endif
                @if ($clockDisplay !== '')
                  <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-clock" aria-hidden="true"></i>{{ $clockDisplay }}</span>
                @endif
                <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-eye" aria-hidden="true"></i> {{ $views }} lượt xem</span>
              </div>
            @endif
            <a href="#{{ $ctaAnchor }}" class="about-intro-hero__cta">
              <span>{{ $ctaText }}</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              @if ($isUpcoming)
                <p class="about-intro-hero__panel-kicker">Tham dự an toàn &amp; minh bạch</p>
                <p class="about-intro-hero__panel-title">Đăng ký qua form — SUNNY xác nhận</p>
                <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                  <li>
                    <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-calendar-plus"></i></span>
                    <span><strong>Nội dung chính</strong> — đọc mô tả và thời gian; điền form bên cạnh bài trên màn hình lớn.</span>
                  </li>
                  <li>
                    <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-envelope-check"></i></span>
                    <span><strong>Xác nhận qua email / điện thoại</strong> — link Zoom/Tài liệu gửi theo từng chương trình.</span>
                  </li>
                  <li>
                    <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-link-45deg"></i></span>
                    <span><strong>Neo trang</strong> — chia sẻ trực tiếp <code class="text-sm">#chi-tiet-sap-dien-ra</code> để người đọc vào đúng phần đăng ký.</span>
                  </li>
                </ul>
              @else
                <p class="about-intro-hero__panel-kicker">Thông tin chương trình</p>
                <p class="about-intro-hero__panel-title">Sự kiện đã kết thúc</p>
                <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                  @if ($weekdayVi !== '' && $dateLabel !== '')
                    <li>
                      <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                      <span><strong>Thời gian</strong> — <time datetime="{{ $dateAttr }}">{{ $weekdayVi }}, {{ $dateLabel }}</time>@if ($clockDisplay !== '') · {{ $clockDisplay }}@endif</span>
                    </li>
                  @endif
                  <li>
                    <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-images"></i></span>
                    <span><strong>Hình ảnh</strong> — {{ $imgUrl !== '' ? 'Xem ảnh đại diện và nội dung bên dưới.' : 'Chi tiết chương trình trong nội dung bên dưới.' }}</span>
                  </li>
                </ul>
              @endif
            </div>
          </div>
        </div>
      </div>
    </header>

    @if ($isUpcoming)
      <div id="chi-tiet-sap-dien-ra">
        <section class="event-detail-upcoming py-5 bg-white border-b border-opacity-10" aria-labelledby="heading-event-upcoming">
          <div class="container">
            <header class="event-detail-upcoming__intro mb-4 lg:mb-5">
              <div class="title-line mb-3"></div>
              <h2 id="heading-event-upcoming" class="section-title mb-0">{{ $title }}</h2>
              <p class="text-bs-secondary text-sm mb-0 mt-2 flex flex-wrap items-center gap-3">
                @if ($dateAttr !== '')
                  <span class="inline-flex items-center gap-1"><i class="bi bi-calendar3" aria-hidden="true"></i><time datetime="{{ $dateAttr }}">{{ $dateLabel }}</time></span>
                @endif
                <span class="inline-flex items-center gap-1"><i class="bi bi-eye" aria-hidden="true"></i>{{ $views }} lượt xem</span>
              </p>
            </header>
            <div class="flex flex-wrap event-detail-upcoming__row gap-4 xl:gap-5 lg:items-stretch">
              <div class="w-full lg:w-7/12">
                <div class="event-detail-meta-card">
                  <ul class="list-none p-0 event-detail-meta mb-0">
                    @if ($weekdayVi !== '' && $dateLabel !== '')
                      <li><i class="bi bi-calendar-event text-primary-sunny me-2" aria-hidden="true"></i><time datetime="{{ $dateAttr }}">{{ $weekdayVi }}, {{ $dateLabel }}</time></li>
                    @endif
                    @if ($clockDisplay !== '')
                      <li><i class="bi bi-clock text-primary-sunny me-2" aria-hidden="true"></i>{{ $clockDisplay }}</li>
                    @endif
                    @if ($weekdayVi === '' && $clockDisplay === '')
                      <li><i class="bi bi-info-circle text-primary-sunny me-2" aria-hidden="true"></i>Chi tiết chương trình trong nội dung bên dưới.</li>
                    @endif
                  </ul>
                </div>
                @if (!empty($tocItems))
                  <nav class="news-detail-toc mb-3 lg:hidden" aria-label="Mục lục">
                    <p class="text-sm font-bold mb-2">Mục lục</p>
                    <ul class="news-detail-toc__list">
                      @foreach ($tocItems as $ti)
                        <li><a href="#{{ $ti['id'] }}">{{ $ti['text'] }}</a></li>
                      @endforeach
                    </ul>
                  </nav>
                @endif
                <div class="news-article text-black bun-richtext-content mb-0">{!! $bodyHtml !!}</div>
              </div>
              <div class="w-full lg:w-5/12 flex flex-col">
                <div class="event-detail-sticky-form w-full">
                  <section id="dang-ky-su-kien" class="register-section register-section--inline border-0 py-0 bg-transparent w-full mx-auto mx-lg-0" aria-labelledby="heading-dang-ky-sk">
                    <h2 id="heading-dang-ky-sk" class="sr-only">Đăng ký tham dự sự kiện</h2>
                    <div class="register-wrap event-signup-wrap event-signup-wrap--aside w-full">
                      <form class="form-register flex flex-col" action="{{ route('consult.submit') }}" method="post">
                        @csrf
                        <input type="hidden" name="consult_form_origin" value="event">
                        <input type="hidden" name="consult_source" value="{{ $consultSource }}" maxlength="500">
                        <input type="hidden" name="chuDe" value="khac">
                        <input type="hidden" name="cauHoi" value="Đăng ký tham dự sự kiện: {{ $tenSuKien }}">
                        <div class="hidden" aria-hidden="true">
                          <label for="{{ $uid }}-hp">Không điền ô này</label>
                          <input type="text" name="consult_hp" id="{{ $uid }}-hp" value="" tabindex="-1" autocomplete="off">
                        </div>
                        <h2 class="form-register__title">Đăng ký tham dự</h2>
                        <p class="text-center text-sm text-black mb-3 lg:mb-4">Vui lòng điền đúng số điện thoại và email để SUNNY liên hệ xác nhận.</p>
                        <div class="form-register__fields flex flex-col gap-3">
                          <div>
                            <label class="sr-only" for="{{ $uid }}HoTen">Họ và tên</label>
                            <input type="text" class="tw-field form-register__control" id="{{ $uid }}HoTen" name="hoTen" required autocomplete="name" placeholder="Họ và tên *">
                          </div>
                          <div>
                            <label class="sr-only" for="{{ $uid }}Sdt">Số điện thoại</label>
                            <input type="tel" class="tw-field form-register__control" id="{{ $uid }}Sdt" name="sdt" required autocomplete="tel" placeholder="Số điện thoại *">
                          </div>
                          <div>
                            <label class="sr-only" for="{{ $uid }}KhuVuc">Khu vực</label>
                            <select class="tw-select form-register__control" id="{{ $uid }}KhuVuc" name="tinhThanh" required>
                              <option value="" disabled selected>Bạn đang ở khu vực *</option>
                              <option value="Miền Bắc">Miền Bắc</option>
                              <option value="Miền Trung">Miền Trung</option>
                              <option value="Miền Nam">Miền Nam</option>
                              <option value="Ngoài Việt Nam">Ngoài Việt Nam</option>
                            </select>
                          </div>
                        </div>
                        <button type="submit" class="btn form-register__submit self-stretch">Gửi đăng ký</button>
                        <p class="form-register__note text-sm mt-3 mb-0 text-bs-secondary">SUNNY sẽ xác nhận qua điện thoại hoặc email.</p>
                      </form>
                    </div>
                  </section>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    @else
      <div id="chi-tiet-da-dien-ra">
        @if ($imgUrl !== '')
          <section class="event-recap-gallery-section py-4 lg:py-5 bg-white border-b" id="hinh-anh-su-kien" aria-labelledby="heading-event-gallery">
            <div class="container">
              <figure class="mb-0 rounded-4 overflow-hidden">
                <img src="{{ $imgUrl }}" alt="{{ $title }}" width="1280" height="800" loading="eager" decoding="async">
              </figure>
            </div>
          </section>
        @endif
        <section class="py-5 bg-white" id="noi-dung-tong-ket" aria-label="Nội dung sự kiện">
          <div class="container">
            @if (!empty($tocItems))
              <nav class="news-detail-toc mb-4" aria-label="Mục lục">
                <p class="text-sm font-bold mb-2">Mục lục</p>
                <ul class="news-detail-toc__list">
                  @foreach ($tocItems as $ti)
                    <li><a href="#{{ $ti['id'] }}">{{ $ti['text'] }}</a></li>
                  @endforeach
                </ul>
              </nav>
            @endif
            <div class="news-article text-black bun-richtext-content">{!! $bodyHtml !!}</div>
            <div class="uni-detail-note text-sm mt-5 mb-0">
              <strong>Lưu ý:</strong> Sự kiện tiếp theo xem tại <a href="{{ url('/su-kien') }}#sap-dien-ra" class="font-semibold text-inherit">Sự kiện sắp diễn ra</a>.
            </div>
          </div>
        </section>
      </div>
    @endif

    @if (!empty($relatedPosts) && $relatedPosts->isNotEmpty())
      <section class="event-section event-section--past py-5 border-t" id="su-kien-lien-quan" aria-labelledby="heading-sk-lien-quan">
        <div class="container">
          <header class="event-section__head">
            <div class="event-section__eyebrow">
              <span class="event-section__badge event-section__badge--archive">
                <i class="bi bi-archive" aria-hidden="true"></i>
                Liên quan
              </span>
            </div>
            <div class="title-line mb-3"></div>
            <div class="flex flex-wrap items-end gap-3 lg:gap-4">
              <div class="w-full lg:w-8/12">
                <h2 id="heading-sk-lien-quan" class="section-title event-section__title mb-2">
                  Sự kiện <span class="text-primary-sunny">liên quan</span>
                </h2>
                <p class="event-section__lead mb-0">Cùng chuyên mục hoặc chủ đề tương tự — xem thêm hoạt động SUNNY.</p>
              </div>
              <div class="w-full lg:w-4/12 lg:text-right">
                <a href="{{ url('/su-kien') }}#da-dien-ra" class="event-section__link-skip">Tất cả sự kiện <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
              </div>
            </div>
          </header>
          <div class="flex flex-wrap gap-4 lg:gap-5 mt-4 lg:mt-3 post-category-news-grid">
            @foreach ($relatedPosts as $idx => $rp)
              @include('public.partials.event-card', [
                'post' => $rp,
                'badge' => ($rp->published_at && $rp->published_at->gte(now()->startOfDay())) ? 'upcoming' : 'past',
                'idx' => $idx,
                'fallbackImg' => $fallbackImg,
                'catTitle' => $catName !== '' ? $catName : 'Sự kiện',
              ])
            @endforeach
          </div>
        </div>
      </section>
    @endif
  </div>
</div>
@endsection
