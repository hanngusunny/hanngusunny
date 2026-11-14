@extends('public.layout')

@php
  $cat = $category ?? $page;
  $catTitle = trim((string) ($cat->name ?? '')) ?: 'Chuyên mục';
  $childList = collect($children ?? []);
  $coverUrl = method_exists($cat, 'coverImageUrl') ? $cat->coverImageUrl() : '';
  $hasBody = trim((string) ($descriptionHtml ?? '')) !== '';
  $isHub = ($cat->slug ?? '') === 'cac-nganh-du-hoc-han-quoc';
  $lead = trim((string) ($cat->meta_description ?? ''));
  $fallbackImg = 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=640&q=80';
  $hubItems = $isHub ? $childList->values()->filter(fn ($c) => trim((string) $c->slug) !== '') : collect();
  $hubHero = $hubItems->first();
  $hubCompact = $hubItems->slice(1, 2);
  $hubGrid = $hubItems->slice(3);
  $hubSidebar = $hubItems->take(5);
  $majorCard = static function ($child) use ($fallbackImg): array {
    $slug = trim((string) $child->slug);
    $meta = trim((string) ($child->meta_description ?? ''));
    $excerpt = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($meta !== '' ? $meta : (string) $child->description)) ?? ''), 220, '…');
    $img = method_exists($child, 'coverImageUrl') ? $child->coverImageUrl() : '';
    $ts = $child->updated_at ?? $child->created_at ?? null;

    return [
      'title' => trim((string) $child->name) ?: $slug,
      'url' => url('/'.$slug),
      'img' => $img !== '' ? $img : $fallbackImg,
      'excerpt' => $excerpt,
      'publishedAt' => $ts ? \Illuminate\Support\Carbon::parse($ts) : null,
      'viewCount' => (int) ($child->view_count ?? 0),
    ];
  };
  $tocItems = $tocItems ?? [];
  $leadImage = $leadImage ?? null;
  $imgUrl = is_array($leadImage) && ! empty($leadImage['src']) ? $leadImage['src'] : $coverUrl;
  $imgAlt = is_array($leadImage) && ! empty($leadImage['alt']) ? $leadImage['alt'] : $catTitle;
  $bodyPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($descriptionHtml ?? ''))) ?? '');
  $readMins = max(1, (int) ceil(mb_strlen($bodyPlain, 'UTF-8') / 1200));
  $updatedAt = $cat->updated_at ?? $cat->created_at ?? null;
  $dateAttr = $updatedAt ? \Illuminate\Support\Carbon::parse($updatedAt)->format('Y-m-d') : '';
  $dateLabel = $updatedAt ? \Illuminate\Support\Carbon::parse($updatedAt)->format('d/m/Y') : '';
  $shareUrl = rawurlencode(url()->current());
  $relatedMajors = collect($relatedMajors ?? []);
  $prevMajor = $prevMajor ?? null;
  $nextMajor = $nextMajor ?? null;
  $navMod = ($prevMajor && $nextMajor) ? '' : ($nextMajor && ! $prevMajor ? ' post-nav--next-only' : ($prevMajor && ! $nextMajor ? ' post-nav--prev-only' : ''));
  $majorPosts = collect();
  if (isset($posts)) {
    $majorPosts = (is_object($posts) && method_exists($posts, 'items')) ? collect($posts->items()) : collect($posts);
    $majorPosts = $majorPosts->take(5);
  }
  $hubUrl = url('/cac-nganh-du-hoc-han-quoc');
@endphp

@section('content')
<div class="tpl tpl--category tpl--post tpl--major tpl--major-chi-tiet-chuyen-muc{{ $isHub ? '' : ' post-article-detail-tpl2' }}" data-scope="major" data-page="{{ $isHub ? 'category' : 'article' }}" data-template="chi-tiet-chuyen-muc"@if (! $isHub && ! empty($tocItems)) data-bun-news-toc="1"@endif>
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
            @if (! $isHub)
              <li class="page-about-breadcrumb__step">
                <a href="{{ url('/cac-nganh-du-hoc-han-quoc') }}" class="page-about-breadcrumb__link">Các ngành du học Hàn Quốc</a>
              </li>
            @endif
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">{{ $catTitle }}</span>
            </li>
          </ol>
        </nav>
        @if ($isHub)
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">{{ $catTitle }} <span class="text-primary-sunny">SUNNY</span></h1>
            @if ($lead !== '')
              <p class="about-intro-hero__lead">{{ $lead }}</p>
            @else
              <p class="about-intro-hero__lead">
                Nhóm ngành, điều kiện đầu vào và gợi ý lộ trình
                <span class="about-intro-hero__dot" aria-hidden="true"></span>
                giúp bạn chọn đúng <strong class="about-intro-hero__strong">chương trình du học Hàn Quốc</strong> phù hợp.
              </p>
            @endif
            <div class="about-intro-hero__chips" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-diagram-3-fill" aria-hidden="true"></i> Nhóm ngành &amp; nghề</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i> Điều kiện &amp; TOPIK</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-briefcase-fill" aria-hidden="true"></i> Thực tập &amp; việc làm</span>
            </div>
            @if ($hubItems->isNotEmpty())
              <a href="#heading-danh-sach-tin" class="about-intro-hero__cta">
                <span>Xem danh sách ngành</span>
                <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
              </a>
            @endif
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Đọc tại SUNNY</p>
              <p class="about-intro-hero__panel-title">Nội dung chọn lọc theo đúng nhịp của bạn</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-layers"></i></span>
                  <span><strong>Phân nhóm ngành</strong> — kỹ thuật, kinh doanh, nghệ thuật… giúp bạn định vị nhanh trước khi chọn trường.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-list-check"></i></span>
                  <span><strong>Checklist thực tế</strong> — portfolio, chứng chỉ, GPA và các bước thường gặp trong hồ sơ tuyển sinh.</span>
                </li>
                <li>
                  <span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-compass"></i></span>
                  <span><strong>Lộ trình</strong> — gợi ý bước tiếp theo sau khi đã chốt ngành và kỳ nhập học mục tiêu.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        @else
        <div class="flex flex-col justify-center">
          <div class="title-line mb-3"></div>
          <h1 class="section-title about-intro-hero__title mb-2 mb-md-3">{{ $catTitle }}</h1>
          <p class="text-sm text-bs-secondary mb-3 lg:mb-3 flex flex-wrap items-center gap-3">
            @if ($dateAttr !== '')
              <span class="inline-flex items-center gap-1">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <time datetime="{{ $dateAttr }}">{{ $dateLabel }}</time>
              </span>
            @endif
            @if ($hasBody)
              <span class="inline-flex items-center gap-1">
                <i class="bi bi-book" aria-hidden="true"></i>
                Khoảng {{ $readMins }} phút đọc
              </span>
            @endif
          </p>
          <p class="about-intro-hero__lead max-w-full">
            @if ($lead !== '')
              {{ $lead }}
            @else
              Tổng quan ngành, điều kiện đầu vào và gợi ý lộ trình
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              giúp bạn chọn đúng <strong class="about-intro-hero__strong">chương trình &amp; trường</strong> phù hợp tại Hàn Quốc.
            @endif
          </p>
          <a href="#noi-dung-bai-viet" class="about-intro-hero__cta">
            <span>Xem chi tiết nội dung</span>
            <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
          </a>
        </div>
        @endif
      </div>
    </header>

    @if ($isHub)
      <section class="news-section post-cat-news-tpl2 py-5 bg-white" id="heading-danh-sach-tin" aria-labelledby="news-list-heading">
        <div class="container">
          <div class="flex flex-wrap gap-4 items-start">
            <div class="w-full lg:w-8/12">
              <h2 id="news-list-heading" class="sr-only">Danh sách ngành</h2>
              @if (($descriptionHtml ?? '') !== '')
                <div class="page-content bun-richtext-content mb-4 pb-4 border-b">{!! $descriptionHtml !!}</div>
              @endif
              @if ($hubItems->isEmpty())
                <div class="text-center py-5">
                  <p class="text-bs-secondary mb-3 lg:mb-4">Chưa có ngành được xuất bản trong chuyên mục này.</p>
                  <a href="{{ url('/tin-tuc') }}" class="btn btn-outline-primary rounded-full px-4">Xem tin tức</a>
                </div>
              @else
                @if ($hubHero)
                  <div class="{{ $hubCompact->isEmpty() ? 'lead-grid lead-grid--single mb-4' : 'lead-grid mb-4' }}">
                    @include('public.partials.news-card', $majorCard($hubHero) + ['variant' => 'large', 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
                    @if ($hubCompact->isNotEmpty())
                      <div class="lead-stack">
                        @foreach ($hubCompact as $child)
                          @include('public.partials.news-card', $majorCard($child) + ['variant' => 'compact', 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
                        @endforeach
                      </div>
                    @endif
                  </div>
                @endif
                @if ($hubGrid->isNotEmpty())
                  <div class="article-grid">
                    @foreach ($hubGrid as $child)
                      @include('public.partials.news-card', $majorCard($child) + ['variant' => 'grid', 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
                    @endforeach
                  </div>
                @endif
              @endif
            </div>
            <aside class="w-full lg:w-4/12 sidebar" aria-label="Ngành nổi bật">
              @if ($hubSidebar->isNotEmpty())
                <section class="side-panel">
                  <h2><i class="bi bi-star" aria-hidden="true"></i> Ngành nổi bật</h2>
                  @foreach ($hubSidebar as $sp)
                    @php $card = $majorCard($sp); @endphp
                    <a class="featured-link" href="{{ $card['url'] }}">
                      <img src="{{ $card['img'] }}" alt="{{ $card['title'] }}" width="148" height="148" loading="lazy" decoding="async">
                      <span>
                        @if ($card['publishedAt'])
                          <time datetime="{{ $card['publishedAt']->format('Y-m-d') }}">{{ $card['publishedAt']->format('d/m/Y') }}</time>
                        @endif
                        <strong>{{ $card['title'] }}</strong>
                      </span>
                    </a>
                  @endforeach
                </section>
              @endif
              @if ($hubItems->isNotEmpty())
                <section class="side-panel tags-panel">
                  <h2><i class="bi bi-hash" aria-hidden="true"></i> Nhóm ngành</h2>
                  <div class="tags-list">
                    @foreach ($hubItems as $tg)
                      <a class="tag-pill" href="{{ url('/'.trim((string) $tg->slug)) }}">#{{ $tg->name }}</a>
                    @endforeach
                  </div>
                </section>
              @endif
              <section class="side-panel newsletter-panel">
                <h2><i class="bi bi-envelope-paper" aria-hidden="true"></i> Đăng ký nhận bản tin</h2>
                <p>Nhận ngay những tin tức, học bổng và sự kiện mới nhất từ SUNNY.</p>
                <form class="newsletter-form" action="#" method="post" onsubmit="return false;">
                  <label class="sr-only" for="sidebar-major-email">Email</label>
                  <input id="sidebar-major-email" class="tw-field" type="email" name="email" autocomplete="email" placeholder="Nhập email của bạn">
                  <button class="btn btn-sunny w-full" type="button">Đăng ký ngay</button>
                </form>
                <small>Chúng tôi cam kết bảo mật thông tin của bạn.</small>
                <div class="mail-illustration" aria-hidden="true"><i class="bi bi-envelope-heart"></i></div>
              </section>
            </aside>
          </div>
        </div>
      </section>
    @else
      <section class="article-detail-section post-article-detail-tpl2__main py-4 lg:py-5">
        <div class="container">
          <div class="flex flex-wrap gap-4 items-start">
            <div class="w-full lg:w-8/12">
              @if (! empty($tocItems))
                <div class="lg:hidden news-detail-toc--mobile mb-4" id="muc-luc-mobile">
                  <div class="accordion" id="accordionMucLuc">
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingMucLuc">
                        <button class="accordion-button collapsed" type="button" data-tw-toggle="collapse" data-tw-target="#collapseMucLuc" aria-expanded="false" aria-controls="collapseMucLuc">
                          <i class="bi bi-list-nested me-2 text-bs-primary" aria-hidden="true"></i>
                          Mục lục nhanh
                        </button>
                      </h2>
                      <div id="collapseMucLuc" class="accordion-collapse collapse" aria-labelledby="headingMucLuc" data-tw-parent="#accordionMucLuc">
                        <div class="accordion-body">
                          <nav class="news-detail-toc" aria-label="Mục lục bài viết (màn hình nhỏ)">
                            <ul class="news-detail-toc__list">
                              @foreach ($tocItems as $ti)
                                <li><a href="#{{ $ti['id'] }}">{{ $ti['text'] }}</a></li>
                              @endforeach
                            </ul>
                          </nav>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endif

              <article class="article-shell">
                @if ($imgUrl !== '')
                  <figure class="article-main-image-wrap">
                    <img class="article-main-image" src="{{ $imgUrl }}" alt="{{ $imgAlt }}" width="1200" height="675" fetchpriority="high" loading="eager" decoding="async">
                  </figure>
                @endif
                <div class="article-content">
                  <div id="noi-dung-bai-viet" class="news-article text-black bun-richtext-content">
                    @if ($hasBody)
                      {!! $descriptionHtml !!}
                    @else
                      <p>Nội dung chi tiết ngành đang được cập nhật. Liên hệ SUNNY để được tư vấn lộ trình phù hợp.</p>
                    @endif
                  </div>
                  <div class="article-share">
                    <div class="share-icons">
                      <span>Chia sẻ bài viết:</span>
                      <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chia sẻ Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                      <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chia sẻ LinkedIn"><i class="bi bi-linkedin" aria-hidden="true"></i></a>
                      <a href="https://zalo.me/share?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chia sẻ Zalo"><i class="bi bi-chat-dots" aria-hidden="true"></i></a>
                      <a href="{{ url()->current() }}" aria-label="Liên kết bài viết"><i class="bi bi-link-45deg" aria-hidden="true"></i></a>
                    </div>
                  </div>

                  @if ($prevMajor || $nextMajor)
                    <nav class="post-nav post-article-detail-tpl2__post-nav{{ $navMod }}" aria-label="Ngành trước và tiếp theo">
                      @if ($prevMajor)
                        <a href="{{ url('/'.trim((string) $prevMajor->slug)) }}" class="post-nav-item">
                          <img src="{{ $prevMajor->coverImageUrl() ?: $fallbackImg }}" alt="{{ $prevMajor->name }}" width="74" height="60" loading="lazy" decoding="async">
                          <span>
                            <em><i class="bi bi-chevron-left" aria-hidden="true"></i> Ngành trước</em>
                            <strong>{{ $prevMajor->name }}</strong>
                          </span>
                        </a>
                      @endif
                      @if ($nextMajor)
                        <a href="{{ url('/'.trim((string) $nextMajor->slug)) }}" class="post-nav-item text-right">
                          <span>
                            <em>Ngành tiếp theo <i class="bi bi-chevron-right" aria-hidden="true"></i></em>
                            <strong>{{ $nextMajor->name }}</strong>
                          </span>
                          <img src="{{ $nextMajor->coverImageUrl() ?: $fallbackImg }}" alt="{{ $nextMajor->name }}" width="74" height="60" loading="lazy" decoding="async">
                        </a>
                      @endif
                    </nav>
                  @endif

                  <div class="flex flex-col sm:flex-row gap-3 justify-between items-stretch sm:items-center mt-4 pt-4 border-t post-article-detail-tpl2__foot-cta">
                    <a href="{{ $hubUrl }}" class="btn btn-outline-dark font-semibold inline-flex items-center justify-center gap-1">
                      <i class="bi bi-arrow-left" aria-hidden="true"></i>
                      Về trang các ngành du học Hàn Quốc
                    </a>
                    <a href="{{ route('home') }}#dang-ky" class="btn btn-sunny text-white px-4 inline-flex items-center justify-center gap-1">
                      <i class="bi bi-chat-dots" aria-hidden="true"></i>
                      Tư vấn hồ sơ
                    </a>
                  </div>
                </div>
              </article>
            </div>

            <aside class="w-full lg:w-4/12 sidebar detail-sidebar" aria-label="Mục lục và ngành liên quan">
              @if (! empty($tocItems))
                <section class="side-panel post-article-detail-tpl2__toc-panel hidden lg:block">
                  <h2><i class="bi bi-bookmarks-fill" aria-hidden="true"></i> Trên trang này</h2>
                  <nav class="news-detail-toc news-detail-toc--in-sidebar" aria-label="Mục lục bài viết">
                    <ul class="news-detail-toc__list" role="list">
                      @foreach ($tocItems as $ti)
                        <li><a href="#{{ $ti['id'] }}">{{ $ti['text'] }}</a></li>
                      @endforeach
                    </ul>
                    <div class="news-detail-toc__foot border-t-0 pt-3 mt-2">
                      <a href="{{ $hubUrl }}" class="news-detail-toc__back"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Tất cả ngành</a>
                      <a href="{{ route('home') }}#dang-ky" class="news-detail-toc__cta"><i class="bi bi-pencil-square" aria-hidden="true"></i> Đăng ký tư vấn</a>
                    </div>
                  </nav>
                </section>
              @else
                <section class="side-panel post-article-detail-tpl2__toc-panel hidden lg:block">
                  <h2><i class="bi bi-link-45deg" aria-hidden="true"></i> Liên kết nhanh</h2>
                  <nav class="news-detail-toc news-detail-toc--in-sidebar" aria-label="Liên kết nhanh">
                    <div class="news-detail-toc__foot border-t-0 pt-0 mt-0">
                      <a href="{{ $hubUrl }}" class="news-detail-toc__back"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Tất cả ngành</a>
                      <a href="{{ route('home') }}#dang-ky" class="news-detail-toc__cta"><i class="bi bi-pencil-square" aria-hidden="true"></i> Đăng ký tư vấn</a>
                    </div>
                  </nav>
                </section>
              @endif

              <section class="side-panel search-panel">
                <h2><i class="bi bi-search" aria-hidden="true"></i> Tìm kiếm</h2>
                <form class="search-form" action="{{ route('search') }}" method="get">
                  <label class="sr-only" for="majorDetailSearchInput">Từ khóa</label>
                  <input id="majorDetailSearchInput" class="tw-field" type="search" name="q" value="" placeholder="Nhập từ khóa cần tìm..." autocomplete="off">
                  <button class="btn btn-sunny" type="submit" aria-label="Tìm kiếm"><i class="bi bi-search" aria-hidden="true"></i></button>
                </form>
              </section>

              @if ($relatedMajors->isNotEmpty())
                <section class="side-panel">
                  <h2><i class="bi bi-patch-plus" aria-hidden="true"></i> Ngành liên quan</h2>
                  @foreach ($relatedMajors as $rm)
                    @php
                      $rmSlug = trim((string) $rm->slug);
                      $rmTitle = trim((string) $rm->name) ?: $rmSlug;
                      $rmImg = $rm->coverImageUrl() ?: $fallbackImg;
                      $rmTs = $rm->updated_at ?? $rm->created_at ?? null;
                    @endphp
                    @if ($rmSlug !== '')
                      <a class="featured-link detail-related-link" href="{{ url('/'.$rmSlug) }}">
                        <img src="{{ $rmImg }}" alt="{{ $rmTitle }}" width="180" height="124" loading="lazy" decoding="async">
                        <span>
                          <strong>{{ $rmTitle }}</strong>
                          @if ($rmTs)
                            <time datetime="{{ \Illuminate\Support\Carbon::parse($rmTs)->format('Y-m-d') }}"><i class="bi bi-calendar4-week" aria-hidden="true"></i> {{ \Illuminate\Support\Carbon::parse($rmTs)->format('d/m/Y') }}</time>
                          @endif
                        </span>
                      </a>
                    @endif
                  @endforeach
                  <a class="panel-more" href="{{ $hubUrl }}">Xem tất cả ngành <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </section>
              @endif

              @if ($childList->isNotEmpty())
                <section class="side-panel categories-panel">
                  <h2><i class="bi bi-layout-text-window" aria-hidden="true"></i> Chuyên mục con</h2>
                  @foreach ($childList as $crow)
                    @php
                      $cname = trim((string) $crow->name);
                      $cslug = trim((string) $crow->slug);
                      if ($cname === '' || $cslug === '') { continue; }
                    @endphp
                    <a class="category-line" href="{{ url('/'.$cslug) }}">
                      <span>{{ $cname }}</span>
                    </a>
                  @endforeach
                </section>
              @endif

              @if ($majorPosts->isNotEmpty())
                <section class="side-panel">
                  <h2><i class="bi bi-journal-text" aria-hidden="true"></i> Bài viết trong ngành</h2>
                  @foreach ($majorPosts as $mp)
                    <a class="featured-link detail-related-link" href="{{ $mp->publicUrl() }}">
                      <img src="{{ $mp->featuredImageUrl() ?: $fallbackImg }}" alt="{{ $mp->title }}" width="180" height="124" loading="lazy" decoding="async">
                      <span>
                        <strong>{{ $mp->title }}</strong>
                        @if ($mp->published_at)
                          <time datetime="{{ $mp->published_at->format('Y-m-d') }}"><i class="bi bi-calendar4-week" aria-hidden="true"></i> {{ $mp->published_at->format('d/m/Y') }}</time>
                        @endif
                      </span>
                    </a>
                  @endforeach
                </section>
              @endif

              <section class="side-panel newsletter-panel detail-newsletter">
                <h2><i class="bi bi-envelope" aria-hidden="true"></i> Nhận tin cập nhật</h2>
                <p>Đăng ký để nhận thông tin mới nhất về học bổng, lịch khai giảng và ưu đãi.</p>
                <form class="newsletter-form" action="#" method="post" onsubmit="return false;">
                  <label class="sr-only" for="article-detail-email-major-{{ $cat->id }}">Email</label>
                  <input id="article-detail-email-major-{{ $cat->id }}" class="tw-field" type="email" name="email" autocomplete="email" placeholder="Nhập email của bạn...">
                  <button class="btn btn-sunny w-full" type="button">Đăng ký ngay</button>
                </form>
                <small><i class="bi bi-shield-check" aria-hidden="true"></i> Cam kết bảo mật thông tin của bạn.</small>
                <div class="mail-illustration" aria-hidden="true"><i class="bi bi-envelope-heart"></i></div>
              </section>
            </aside>
          </div>
        </div>
      </section>
    @endif
  </div>
</div>
@endsection
