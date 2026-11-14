@extends('public.layout')

@php
  $cat = $category ?? $page;
  $catTitle = $cat->name ?? 'Sự kiện';
  $all = collect($posts ?? [])->values();
  $startToday = now()->startOfDay();
  $upcoming = $all->filter(function ($p) use ($startToday) {
    return $p->published_at && $p->published_at->gte($startToday);
  })->sortBy(fn ($p) => $p->published_at?->timestamp ?? 0)->values();
  $past = $all->filter(function ($p) use ($startToday) {
    return ! $p->published_at || $p->published_at->lt($startToday);
  })->values();
  $lead = trim((string) ($cat->meta_description ?? ''));
  $fallbackImg = 'https://images.unsplash.com/photo-1544531586-fde5298cdd40?w=640&q=80';
@endphp

@section('content')
<div class="tpl tpl--category tpl--events" data-scope="events" data-page="category">
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
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">{{ $catTitle }}</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <p class="about-intro-hero__kicker">
              <span class="about-intro-hero__kicker-icon" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
              Sự kiện du học Hàn Quốc · Workshop &amp; gặp gỡ
            </p>
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">{{ $catTitle }} <span class="text-primary-sunny">SUNNY</span></h1>
            <p class="about-intro-hero__lead">
              @if ($lead !== '')
                {{ $lead }}
              @else
                Workshop, ngày hội tuyển sinh và talkshow
                <span class="about-intro-hero__dot" aria-hidden="true"></span>
                gặp <strong class="about-intro-hero__strong">cố vấn, đại diện trường và cựu du học sinh</strong>.
                <span class="about-intro-hero__dot" aria-hidden="true"></span>
                Đăng ký sớm để giữ chỗ tại <strong class="about-intro-hero__strong">các sự kiện sắp diễn ra</strong>.
              @endif
            </p>
            <div class="about-intro-hero__chips" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-easel2" aria-hidden="true"></i> Workshop &amp; talkshow</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-people-fill" aria-hidden="true"></i> Ngày hội tuyển sinh</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> Cố vấn &amp; cựu DHS</span>
            </div>
            <a href="#sap-dien-ra" class="about-intro-hero__cta">
              <span>Xem sự kiện sắp diễn ra</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Tại sự kiện SUNNY</p>
              <p class="about-intro-hero__panel-title">Gặp đúng người, đúng thông tin cho lộ trình của bạn</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-building"></i></span>
                  <span><strong>Đại diện trường</strong> — giải đáp tuyển sinh, ngành học và môi trường thực tế tại Hàn.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-chat-quote"></i></span>
                  <span><strong>Talkshow &amp; chia sẻ</strong> — kinh nghiệm từ cựu du học sinh và đội ngũ cố vấn SUNNY.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                  <span><strong>Đăng ký có chỗ</strong> — ưu tiên suất tham dự; SUNNY xác nhận qua điện thoại hoặc email.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="py-5 bg-white news-list-section" id="danh-sach-su-kien" aria-labelledby="heading-danh-sach-sk">
      <div class="container">
        @if (($descriptionHtml ?? '') !== '')
          <div class="page-content bun-richtext-content mb-4 pb-4 border-b">{!! $descriptionHtml !!}</div>
        @endif
        <h2 id="heading-danh-sach-sk" class="sr-only">Danh sách sự kiện</h2>
        <div class="event-section event-section--upcoming pt-0 pb-5" id="sap-dien-ra" aria-labelledby="heading-upcoming">
          <header class="event-section__head">
            <div class="event-section__eyebrow">
              <span class="event-section__badge event-section__badge--live">
                <i class="bi bi-broadcast" aria-hidden="true"></i>
                Sắp diễn ra
              </span>
            </div>
            <div class="title-line mb-3"></div>
            <div class="flex flex-wrap items-end gap-3 lg:gap-4">
              <div class="w-full lg:w-8/12">
                <h2 id="heading-upcoming" class="section-title event-section__title mb-2">
                  Sự kiện <span class="text-primary-sunny">sắp</span> diễn ra
                </h2>
                <p class="event-section__lead mb-0">Đăng ký trực tuyến — SUNNY xác nhận qua điện thoại hoặc email.</p>
              </div>
              <div class="w-full lg:w-4/12 lg:text-right">
                <a href="#da-dien-ra" class="event-section__link-skip">Sự kiện đã diễn ra <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
              </div>
            </div>
          </header>
          <div class="flex flex-wrap gap-4 lg:gap-5 pt-2 lg:pt-3 post-category-news-grid">
            @if ($upcoming->isEmpty())
              <div class="w-full text-center py-4 text-bs-secondary">Chưa có sự kiện sắp diễn ra trong chuyên mục này.</div>
            @else
              @foreach ($upcoming as $idx => $p)
                @include('public.partials.event-card', ['post' => $p, 'badge' => 'upcoming', 'idx' => $idx, 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
              @endforeach
            @endif
          </div>
        </div>
      </div>

      <div class="event-section event-section--past py-5" id="da-dien-ra" aria-labelledby="heading-past">
        <div class="container">
          <header class="event-section__head">
            <div class="event-section__eyebrow">
              <span class="event-section__badge event-section__badge--archive">
                <i class="bi bi-archive" aria-hidden="true"></i>
                Lưu trữ
              </span>
            </div>
            <div class="title-line mb-3"></div>
            <div class="flex flex-wrap items-end gap-3 lg:gap-4">
              <div class="w-full lg:w-8/12">
                <h2 id="heading-past" class="section-title event-section__title mb-2">
                  Sự kiện <span class="text-primary-sunny">đã</span> diễn ra
                </h2>
                <p class="event-section__lead mb-0">Tổng kết nhanh — xem lại hình ảnh và con số từ các hoạt động trước đây.</p>
              </div>
              <div class="w-full lg:w-4/12 lg:text-right">
                <a href="#sap-dien-ra" class="event-section__link-skip">Sự kiện sắp tới <i class="bi bi-arrow-up" aria-hidden="true"></i></a>
              </div>
            </div>
          </header>
          <div class="flex flex-wrap gap-4 lg:gap-5 mt-4 lg:mt-3 post-category-news-grid">
            @if ($past->isEmpty())
              <div class="w-full text-center py-4 text-bs-secondary">Chưa có sự kiện đã diễn ra trong chuyên mục này.</div>
            @else
              @foreach ($past as $idx => $p)
                @include('public.partials.event-card', ['post' => $p, 'badge' => 'past', 'idx' => $idx, 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
              @endforeach
            @endif
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
@endsection
