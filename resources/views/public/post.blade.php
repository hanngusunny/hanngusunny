@extends('public.layout')

@php
  $title = $post->title;
  $cat = $category ?? $post->category;
  $catName = trim((string) ($cat->name ?? ''));
  $catUrl = ($cat && $cat->slug && $cat->is_active) ? url('/'.$cat->slug) : url('/tin-tuc');
  $crumbCurrent = $catName !== '' ? $catName : 'Bài viết';
  $hubUrl = match ($post->scope) {
    'events' => url('/su-kien'),
    'major' => url('/cac-nganh-du-hoc-han-quoc'),
    'school' => url('/truong-dai-hoc'),
    default => url('/tin-tuc'),
  };
  $hubLabel = match ($post->scope) {
    'events' => 'Sự kiện',
    'major' => 'Các ngành du học Hàn Quốc',
    'school' => 'Trường đại học',
    default => 'Tin tức',
  };
  $dateAttr = $post->published_at ? $post->published_at->format('Y-m-d') : '';
  $dateLabel = $post->published_at ? $post->published_at->format('d/m/Y') : '';
  $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($bodyHtml ?? ''))) ?? '');
  $readMins = max(1, (int) ceil(mb_strlen($plain, 'UTF-8') / 1200));
  $leadPlain = trim((string) ($post->meta_description ?: strip_tags((string) $post->excerpt)));
  $imgUrl = $leadImage['src'] ?? $post->featuredImageUrl();
  $imgAlt = $leadImage['alt'] ?? $title;
  $shareUrl = rawurlencode(url()->current());
  $tocItems = $tocItems ?? [];
  $fallbackImg = 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=640&q=80';
  $navPrev = $prevPost;
  $navNext = $nextPost;
  $navMod = ($navPrev && $navNext) ? '' : ($navNext && ! $navPrev ? ' post-nav--next-only' : ($navPrev && ! $navNext ? ' post-nav--prev-only' : ''));
  $views = number_format((int) $post->view_count, 0, ',', '.');
@endphp

@section('content')
<div class="tpl tpl--article post-article-detail-tpl2" data-scope="{{ $post->scope }}" data-page="article"@if (!empty($tocItems)) data-bun-news-toc="1"@endif>
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
              <a href="{{ $hubUrl }}" class="page-about-breadcrumb__link">{{ $hubLabel }}</a>
            </li>
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">{{ $crumbCurrent }}</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-col justify-center">
          <div class="title-line mb-3"></div>
          <h1 class="section-title about-intro-hero__title mb-2 mb-md-3">{{ $title }}</h1>
          <p class="text-sm text-bs-secondary mb-3 lg:mb-3 flex flex-wrap items-center gap-3">
            @if ($dateAttr !== '')
              <span class="inline-flex items-center gap-1">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <time datetime="{{ $dateAttr }}">{{ $dateLabel }}</time>
              </span>
            @endif
            <span class="inline-flex items-center gap-1">
              <i class="bi bi-book" aria-hidden="true"></i>
              Khoảng {{ $readMins }} phút đọc
            </span>
            <span class="inline-flex items-center gap-1">
              <i class="bi bi-eye" aria-hidden="true"></i>
              {{ $views }} lượt xem
            </span>
          </p>
          <p class="about-intro-hero__lead max-w-full">
            @if ($leadPlain !== '')
              {{ $leadPlain }}
            @else
              Visa D-2 dành cho du học sinh theo học tại các cơ sở giáo dục tại Hàn Quốc.
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              <strong class="about-intro-hero__strong">Quy trình và bộ hồ sơ</strong> có thể được điều chỉnh theo từng thời điểm — bài viết dưới đây giúp bạn sắp xếp hồ sơ, tránh sai sót thường gặp.
              <span class="about-intro-hero__dot" aria-hidden="true"></span>
              Luôn đối chiếu lần cuối với hướng dẫn mới nhất của <strong class="about-intro-hero__strong">Đại sứ quán</strong> và tình huống hồ sơ của chính bạn.
            @endif
          </p>
          <a href="#noi-dung-bai-viet" class="about-intro-hero__cta">
            <span>Xem chi tiết nội dung</span>
            <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </header>

    <section class="article-detail-section post-article-detail-tpl2__main py-4 lg:py-5">
      <div class="container">
        <div class="flex flex-wrap gap-4 items-start">
          <div class="w-full lg:w-8/12">
            @if (!empty($tocItems))
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
                  {!! $bodyHtml !!}
                </div>
                <div class="article-share">
                  <div class="share-icons">
                    <span>Chia sẻ bài viết:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chia sẻ Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chia sẻ LinkedIn"><i class="bi bi-linkedin" aria-hidden="true"></i></a>
                    <a href="https://zalo.me/share?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chia sẻ Zalo"><i class="bi bi-chat-dots" aria-hidden="true"></i></a>
                    <a href="{{ url()->current() }}" aria-label="Liên kết bài viết"><i class="bi bi-link-45deg" aria-hidden="true"></i></a>
                  </div>
                  @if (!empty($tags) && $tags->isNotEmpty())
                    <div class="share-tags">
                      <span>Từ khóa:</span>
                      @foreach ($tags as $ptag)
                        @if (trim((string) $ptag->name) !== '')
                          <span class="share-tag-pill">#{{ $ptag->name }}</span>
                        @endif
                      @endforeach
                    </div>
                  @endif
                </div>

                @if ($navPrev || $navNext)
                  <nav class="post-nav post-article-detail-tpl2__post-nav{{ $navMod }}" aria-label="Bài viết trước và tiếp theo">
                    @if ($navPrev)
                      <a href="{{ $navPrev->publicUrl() }}" class="post-nav-item">
                        <img src="{{ $navPrev->featuredImageUrl() ?: $fallbackImg }}" alt="{{ $navPrev->title }}" width="74" height="60" loading="lazy" decoding="async">
                        <span>
                          <em><i class="bi bi-chevron-left" aria-hidden="true"></i> Bài viết trước</em>
                          <strong>{{ $navPrev->title }}</strong>
                        </span>
                      </a>
                    @endif
                    @if ($navNext)
                      <a href="{{ $navNext->publicUrl() }}" class="post-nav-item text-right">
                        <span>
                          <em>Bài viết tiếp theo <i class="bi bi-chevron-right" aria-hidden="true"></i></em>
                          <strong>{{ $navNext->title }}</strong>
                        </span>
                        <img src="{{ $navNext->featuredImageUrl() ?: $fallbackImg }}" alt="{{ $navNext->title }}" width="74" height="60" loading="lazy" decoding="async">
                      </a>
                    @endif
                  </nav>
                @endif

                <div class="flex flex-col sm:flex-row gap-3 justify-between items-stretch sm:items-center mt-4 pt-4 border-t post-article-detail-tpl2__foot-cta">
                  <a href="{{ $hubUrl }}" class="btn btn-outline-dark font-semibold inline-flex items-center justify-center gap-1">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Về trang {{ mb_strtolower($hubLabel) }}
                  </a>
                  <x-sunny.button href="{{ route('home') }}#dang-ky" icon="bi bi-chat-dots">Tư vấn hồ sơ</x-sunny.button>
                </div>

                @if (!empty($commentsEnabled) || (!empty($comments) && $comments->isNotEmpty()))
                  <section class="mt-6 pt-4 border-t" id="binh-luan">
                    <h2 class="h5 mb-3">Bình luận</h2>
                    @if (session('comment_ok'))
                      <p class="text-success">{{ session('comment_ok') }}</p>
                    @endif
                    @foreach ($comments ?? [] as $c)
                      <p class="mb-3"><strong>{{ $c->author_name }}</strong><br>{{ $c->body }}</p>
                    @endforeach
                    @if (!empty($commentsEnabled))
                      <form method="post" action="{{ $commentAction ?? url('/post/'.$post->slug.'/comment') }}">
                        @csrf
                        <x-sunny.input name="author_name" placeholder="Tên" :required="true" />
                        <x-sunny.input name="author_email" placeholder="Email" class="mt-2" />
                        <textarea class="sn-textarea mt-2" name="body" placeholder="Nội dung bình luận..." required></textarea>
                        <x-sunny.button class="mt-3" type="submit">Gửi bình luận</x-sunny.button>
                      </form>
                    @endif
                  </section>
                @endif
              </div>
            </article>
          </div>

          <aside class="w-full lg:w-4/12 sidebar detail-sidebar" aria-label="Mục lục và tin liên quan">
            @if (!empty($tocItems))
              <section class="side-panel post-article-detail-tpl2__toc-panel hidden lg:block">
                <h2><i class="bi bi-bookmarks-fill" aria-hidden="true"></i> Trên trang này</h2>
                <nav class="news-detail-toc news-detail-toc--in-sidebar" aria-label="Mục lục bài viết">
                  <ul class="news-detail-toc__list" role="list">
                    @foreach ($tocItems as $ti)
                      <li><a href="#{{ $ti['id'] }}">{{ $ti['text'] }}</a></li>
                    @endforeach
                  </ul>
                  <div class="news-detail-toc__foot border-t-0 pt-3 mt-2">
                    <a href="{{ $catUrl }}" class="news-detail-toc__back"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Tất cả bài</a>
                    <a href="{{ route('home') }}#dang-ky" class="news-detail-toc__cta"><i class="bi bi-pencil-square" aria-hidden="true"></i> Đăng ký tư vấn</a>
                  </div>
                </nav>
              </section>
            @else
              <section class="side-panel post-article-detail-tpl2__toc-panel hidden lg:block">
                <h2><i class="bi bi-link-45deg" aria-hidden="true"></i> Liên kết nhanh</h2>
                <nav class="news-detail-toc news-detail-toc--in-sidebar" aria-label="Liên kết nhanh">
                  <div class="news-detail-toc__foot border-t-0 pt-0 mt-0">
                    <a href="{{ $catUrl }}" class="news-detail-toc__back"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Tất cả bài</a>
                    <a href="{{ route('home') }}#dang-ky" class="news-detail-toc__cta"><i class="bi bi-pencil-square" aria-hidden="true"></i> Đăng ký tư vấn</a>
                  </div>
                </nav>
              </section>
            @endif

            <section class="side-panel search-panel">
              <h2><i class="bi bi-search" aria-hidden="true"></i> Tìm kiếm</h2>
              <form class="search-form" action="{{ route('search') }}" method="get">
                <label class="sr-only" for="articleSearchInput">Từ khóa</label>
                <input id="articleSearchInput" class="tw-field" type="search" name="q" value="" placeholder="Nhập từ khóa cần tìm..." autocomplete="off">
                <button class="btn btn-sunny" type="submit" aria-label="Tìm kiếm"><i class="bi bi-search" aria-hidden="true"></i></button>
              </form>
            </section>

            @if (!empty($relatedPosts) && $relatedPosts->isNotEmpty())
              <section class="side-panel">
                <h2><i class="bi bi-patch-plus" aria-hidden="true"></i> Bài viết liên quan</h2>
                @foreach ($relatedPosts as $rp)
                  <a class="featured-link detail-related-link" href="{{ $rp->publicUrl() }}">
                    <img src="{{ $rp->featuredImageUrl() ?: $fallbackImg }}" alt="{{ $rp->title }}" width="180" height="124" loading="lazy" decoding="async">
                    <span>
                      <strong>{{ $rp->title }}</strong>
                      @if ($rp->published_at)
                        <time datetime="{{ $rp->published_at->format('Y-m-d') }}"><i class="bi bi-calendar4-week" aria-hidden="true"></i> {{ $rp->published_at->format('d/m/Y') }}</time>
                      @endif
                    </span>
                  </a>
                @endforeach
                <a class="panel-more" href="{{ $catUrl }}">Xem tất cả bài viết <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
              </section>
            @endif

            @if (!empty($sidebarCategories) && $sidebarCategories->isNotEmpty())
              <section class="side-panel categories-panel">
                <h2><i class="bi bi-layout-text-window" aria-hidden="true"></i> Danh mục tin tức</h2>
                @foreach ($sidebarCategories as $crow)
                  @php
                    $cname = trim((string) $crow->name);
                    $cslug = trim((string) $crow->slug);
                    if ($cname === '' || $cslug === '') { continue; }
                  @endphp
                  <a class="category-line{{ (int) $crow->id === (int) ($cat->id ?? 0) ? ' active' : '' }}" href="{{ url('/'.$cslug) }}">
                    <span>{{ $cname }}</span><b>{{ (int) ($crow->post_count ?? 0) }}</b>
                  </a>
                @endforeach
                <a class="panel-more" href="{{ url('/tin-tuc') }}">Xem tất cả danh mục <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
              </section>
            @endif

            @if (!empty($sidebarTags) && $sidebarTags->isNotEmpty())
              <section class="side-panel tags-panel">
                <h2><i class="bi bi-hash" aria-hidden="true"></i> Từ khóa phổ biến</h2>
                <div class="tags-list compact-tags">
                  @foreach ($sidebarTags as $tg)
                    @if (trim((string) $tg->name) !== '')
                      <span class="tag-pill tag-pill--muted">{{ $tg->name }}</span>
                    @endif
                  @endforeach
                </div>
              </section>
            @endif

            <section class="side-panel newsletter-panel detail-newsletter">
              <h2><i class="bi bi-envelope" aria-hidden="true"></i> Nhận tin cập nhật</h2>
              <p>Đăng ký để nhận thông tin mới nhất về học bổng, lịch khai giảng và ưu đãi.</p>
              <form class="newsletter-form" action="#" method="post" onsubmit="return false;">
                <label class="sr-only" for="article-detail-email-{{ $post->id }}">Email</label>
                <input id="article-detail-email-{{ $post->id }}" class="tw-field" type="email" name="email" autocomplete="email" placeholder="Nhập email của bạn...">
                <button class="btn btn-sunny w-full" type="button">Đăng ký ngay</button>
              </form>
              <small><i class="bi bi-shield-check" aria-hidden="true"></i> Cam kết bảo mật thông tin của bạn.</small>
              <div class="mail-illustration" aria-hidden="true"><i class="bi bi-envelope-heart"></i></div>
            </section>
          </aside>
        </div>
      </div>
    </section>
  </div>
</div>
@endsection
