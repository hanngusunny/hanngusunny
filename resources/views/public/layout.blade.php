<!DOCTYPE html>
<html lang="vi">
<head>
  @include('public.partials.gtm-head')
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? ($site['meta_title_default'] ?? $site['site_name'] ?? config('app.name')) }}</title>
  <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">
  <link rel="alternate" type="application/rss+xml" title="RSS" href="{{ url('/rss.xml') }}">
  @if (!empty($jsonLd) && is_array($jsonLd))
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
  @endif
  <meta name="description" content="{{ $metaDescription ?? ($site['meta_description_default'] ?? '') }}">
  @if (!empty($ogTitle) || !empty($title))
    <meta property="og:title" content="{{ $ogTitle ?? $title }}">
  @endif
  @if (!empty($metaDescription))
    <meta property="og:description" content="{{ $metaDescription }}">
  @endif
  @if (!empty($ogImage))
    <meta property="og:image" content="{{ \App\Support\PublicUrl::to($ogImage) }}">
  @endif
  @if (!empty($publicFaviconUrl))
    <link rel="icon" href="{{ $publicFaviconUrl }}">
  @endif
  @if (!empty($site['google_analytics_id']))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $site['google_analytics_id'] }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ $site['google_analytics_id'] }}');
    </script>
  @endif
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:wght@100..900&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="{{ asset('assets/css/tailwind.css') }}" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
  @yield('head')
  <script>document.documentElement.classList.add("js");</script>
</head>
<body class="bg-light flex flex-col min-h-screen{{ !empty($navIsHome) ? ' sunny-front-home' : '' }}">
  @include('public.partials.gtm-body')
  @if (($site['frontend_topbar_enabled'] ?? '1') === '1')
    <div class="frontend-topbar" style="{{ !empty($site['frontend_topbar_bg_color']) ? 'background:'.$site['frontend_topbar_bg_color'].';' : '' }}min-height:{{ (int) ($site['frontend_topbar_height_px'] ?? 56) }}px">
      <div class="container py-2 flex justify-between items-center gap-3">
        <span>{{ $site['frontend_topbar_tagline'] ?: ($site['site_tagline'] ?? '') }}</span>
        @if ($publicHotlineTel)
          <a href="{{ $publicHotlineTel }}" data-hotline-click>{{ $publicHotline }}</a>
        @endif
      </div>
    </div>
  @endif
  @include('public.partials.header-sunny')
  <main class="site-main" id="main-content" role="main">
    @yield('content')
  </main>
  @include('public.partials.footer-consult-form')
  @include('public.partials.footer-sunny')
  @if (!empty($navIsHome))
    @include('public.partials.home-testimonial-modals')
  @endif
  @include('public.partials.floating-consult')
  @include('public.partials.hotline-call-ring')
  <script src="{{ asset('assets/js/tw-ui.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  @if (!empty($navIsHome))
    <script src="{{ asset('assets/js/hero-video-swiper-init.js') }}"></script>
    <script src="{{ asset('assets/js/uni-showcase-swiper-init.js') }}"></script>
    <script src="{{ asset('assets/js/home-page-swipers.js') }}"></script>
    <script src="{{ asset('assets/js/home-page-widgets.js') }}"></script>
  @endif
  @if (!empty($navIsHome) || !empty($loadNewsCardFit))
    <script src="{{ asset('assets/js/news-card-media-fit.js') }}" defer></script>
  @endif
  @if (!empty($loadNewsToc))
    <script src="{{ asset('assets/js/news-article-toc.js') }}" defer></script>
  @endif
  <script src="{{ asset('assets/js/lazy-img.js') }}" defer></script>
  <script src="{{ asset('assets/js/register-fab-scroll.js') }}" defer></script>
  <script src="{{ asset('assets/js/nav-search-panel.js') }}"></script>
  @yield('scripts')
  <script>
    document.querySelectorAll('[data-hotline-click]').forEach(function (el) {
      el.addEventListener('click', function () {
        fetch(@json(route('hotline.click')), {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
          body: 'source_page=' + encodeURIComponent(location.pathname + location.search)
        });
      });
    });
  </script>
</body>
</html>

