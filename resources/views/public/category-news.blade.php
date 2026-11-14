@extends('public.layout')

@php
  $cat = $category ?? $page;
  $catTitle = $cat->name ?? 'Chuyên mục';
  $source = $posts ?? [];
  $all = (is_object($source) && method_exists($source, 'items'))
    ? collect($source->items())->values()
    : collect($source)->values();
  $heroLarge = $all->first();
  $heroCompact = $all->slice(1, 2);
  $gridPosts = $all->slice(3);
  $fallbackImg = 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=640&q=80';
  $lead = trim((string) ($cat->meta_description ?? ''));
  $pager = is_object($source) && method_exists($source, 'currentPage') ? $source : null;
@endphp

@section('content')
<div class="tpl tpl--category tpl--post" data-scope="post" data-page="category">
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
              <a href="{{ url('/tin-tuc') }}" class="page-about-breadcrumb__link">Tin tức</a>
            </li>
            <li class="page-about-breadcrumb__step page-about-breadcrumb__step--current" aria-current="page">
              <span class="page-about-breadcrumb__current">{{ $catTitle }}</span>
            </li>
          </ol>
        </nav>
        <div class="flex flex-wrap items-stretch gap-4 xl:gap-5">
          <div class="w-full lg:w-7/12 xl:w-7/12 flex flex-col justify-center">
            <div class="title-line mb-3"></div>
            <h1 class="section-title about-intro-hero__title mb-3">{{ $catTitle }} <span class="text-primary-sunny">SUNNY</span></h1>
            <p class="about-intro-hero__lead">
              @if ($lead !== '')
                {{ $lead }}
              @else
                Chính sách, học bổng, hồ sơ visa và kinh nghiệm sống
                <span class="about-intro-hero__dot" aria-hidden="true"></span>
                cập nhật thường xuyên cho <strong class="about-intro-hero__strong">hành trình du học Hàn Quốc</strong> của bạn.
              @endif
            </p>
            <div class="about-intro-hero__chips" role="list">
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Chính sách &amp; visa</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-award-fill" aria-hidden="true"></i> Học bổng</span>
              <span class="about-intro-hero__chip" role="listitem"><i class="bi bi-emoji-smile" aria-hidden="true"></i> Đời sống &amp; kinh nghiệm</span>
            </div>
            <a href="#heading-danh-sach-tin" class="about-intro-hero__cta">
              <span>Xem danh sách bài viết</span>
              <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
            </a>
          </div>
          <div class="w-full lg:w-5/12 xl:w-5/12 flex">
            <div class="about-intro-hero__panel w-full lg:self-center">
              <p class="about-intro-hero__panel-kicker">Đọc tại SUNNY</p>
              <p class="about-intro-hero__panel-title">Nội dung chọn lọc theo đúng nhịp của bạn</p>
              <ul class="about-intro-hero__panel-list list-none p-0 mb-0">
                <li><span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-passport"></i></span><span><strong>Hồ sơ &amp; visa</strong> — những thay đổi và checklist thực tế, giúp bạn nộp hồ sơ gọn và tự tin hơn.</span></li>
                <li><span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-gift"></i></span><span><strong>Chính sách &amp; học bổng</strong> — tóm tắt cơ hội và điều kiện thường gặp, tránh sót tin quan trọng.</span></li>
                <li><span class="about-intro-hero__panel-ico" aria-hidden="true"><i class="bi bi-heart-pulse"></i></span><span><strong>Kinh nghiệm sống</strong> — góc nhìn từ du học sinh và đội ngũ SUNNY về học tập, sinh hoạt tại Hàn.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="news-section post-cat-news-tpl2 py-5 bg-white" id="heading-danh-sach-tin" aria-labelledby="news-list-heading">
      <div class="container">
        <div class="flex flex-wrap gap-4 items-start">
          <div class="w-full lg:w-8/12">
            <h2 id="news-list-heading" class="sr-only">Danh sách bài viết</h2>
            @if (($descriptionHtml ?? '') !== '')
              <div class="page-content bun-richtext-content mb-4 pb-4 border-b">{!! $descriptionHtml !!}</div>
            @endif
            @if ($all->isEmpty())
              <div class="text-center py-5">
                <p class="text-bs-secondary mb-3 lg:mb-4">Chưa có bài viết xuất bản trong chuyên mục này.</p>
                <a href="{{ url('/tin-tuc') }}" class="btn btn-outline-primary rounded-full px-4">Xem tất cả tin tức</a>
              </div>
            @else
              @if ($heroLarge)
                <div class="{{ $heroCompact->isEmpty() ? 'lead-grid lead-grid--single mb-4' : 'lead-grid mb-4' }}">
                  @include('public.partials.news-card', ['post' => $heroLarge, 'variant' => 'large', 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
                  @if ($heroCompact->isNotEmpty())
                    <div class="lead-stack">
                      @foreach ($heroCompact as $p)
                        @include('public.partials.news-card', ['post' => $p, 'variant' => 'compact', 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
                      @endforeach
                    </div>
                  @endif
                </div>
              @endif
              @if ($gridPosts->isNotEmpty())
                <div class="article-grid">
                  @foreach ($gridPosts as $p)
                    @include('public.partials.news-card', ['post' => $p, 'variant' => 'grid', 'fallbackImg' => $fallbackImg, 'catTitle' => $catTitle])
                  @endforeach
                </div>
              @endif
              @if ($pager && $pager->lastPage() > 1)
                <p class="post-cat-news-tpl2__range text-sm text-bs-secondary text-center mb-3 lg:mb-0">Hiển thị {{ $pager->firstItem() }}–{{ $pager->lastItem() }} trong {{ $pager->total() }} bài</p>
                <nav class="news-pagination" aria-label="Phân trang tin tức">
                  @if ($pager->onFirstPage())
                    <span class="news-pagination__edge news-pagination__edge--disabled" aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                  @else
                    <a href="{{ $pager->previousPageUrl() }}" aria-label="Trang trước"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                  @endif
                  @foreach ($pager->getUrlRange(1, $pager->lastPage()) as $pageNum => $pageUrl)
                    <a href="{{ $pageUrl }}" class="{{ $pageNum === $pager->currentPage() ? 'active' : '' }}" @if($pageNum === $pager->currentPage()) aria-current="page" @endif>{{ $pageNum }}</a>
                  @endforeach
                  @if ($pager->hasMorePages())
                    <a href="{{ $pager->nextPageUrl() }}" aria-label="Trang sau"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                  @else
                    <span class="news-pagination__edge news-pagination__edge--disabled" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                  @endif
                </nav>
              @endif
            @endif
          </div>
          <aside class="w-full lg:w-4/12 sidebar" aria-label="Bài nổi bật và từ khóa">
            @if (!empty($sidebarFeatured) && $sidebarFeatured->isNotEmpty())
              <section class="side-panel">
                <h2><i class="bi bi-star" aria-hidden="true"></i> Bài viết nổi bật</h2>
                @foreach ($sidebarFeatured as $sp)
                  <a class="featured-link" href="{{ $sp->publicUrl() }}">
                    <img src="{{ $sp->featuredImageUrl() ?: $fallbackImg }}" alt="{{ $sp->title }}" width="148" height="148" loading="lazy" decoding="async">
                    <span>
                      @if ($sp->published_at)
                        <time datetime="{{ $sp->published_at->format('Y-m-d') }}">{{ $sp->published_at->format('d/m/Y') }}</time>
                      @endif
                      <strong>{{ $sp->title }}</strong>
                    </span>
                  </a>
                @endforeach
              </section>
            @endif
            @if (!empty($sidebarTags) && $sidebarTags->isNotEmpty())
              <section class="side-panel tags-panel">
                <h2><i class="bi bi-hash" aria-hidden="true"></i> Từ khóa phổ biến</h2>
                <div class="tags-list">
                  @foreach ($sidebarTags as $tg)
                    <span class="tag-pill">#{{ $tg->name }}</span>
                  @endforeach
                </div>
              </section>
            @endif
            <section class="side-panel newsletter-panel">
              <h2><i class="bi bi-envelope-paper" aria-hidden="true"></i> Đăng ký nhận bản tin</h2>
              <p>Nhận ngay những tin tức, học bổng và sự kiện mới nhất từ SUNNY.</p>
              <form class="newsletter-form" action="#" method="post" onsubmit="return false;">
                <label class="sr-only" for="sidebar-news-email">Email</label>
                <input id="sidebar-news-email" class="tw-field" type="email" name="email" autocomplete="email" placeholder="Nhập email của bạn">
                <button class="btn btn-sunny w-full" type="button">Đăng ký ngay</button>
              </form>
              <small>Chúng tôi cam kết bảo mật thông tin của bạn.</small>
              <div class="mail-illustration" aria-hidden="true"><i class="bi bi-envelope-heart"></i></div>
            </section>
          </aside>
        </div>
      </div>
    </section>
  </div>
</div>
@endsection
