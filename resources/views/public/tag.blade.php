@extends('public.layout')

@php
  $fallbackImg = 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=640&q=80';
  $pager = isset($posts) && method_exists($posts, 'currentPage') ? $posts : null;
@endphp

@section('content')
<div class="tpl tpl--category tpl--post" data-scope="post" data-page="tag">
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
              <span class="page-about-breadcrumb__current">#{{ $tag->name }}</span>
            </li>
          </ol>
        </nav>
        <div class="title-line mb-3"></div>
        <h1 class="section-title about-intro-hero__title mb-0">Từ khóa <span class="text-primary-sunny">#{{ $tag->name }}</span></h1>
      </div>
    </header>
    <section class="news-section post-cat-news-tpl2 py-5 bg-white">
      <div class="container">
        @if ($posts->isEmpty())
          <p class="text-center py-5 text-bs-secondary">Chưa có bài viết gắn từ khóa này.</p>
        @else
          <div class="article-grid">
            @foreach ($posts as $p)
              @include('public.partials.news-card', ['post' => $p, 'variant' => 'grid', 'fallbackImg' => $fallbackImg, 'catTitle' => $tag->name])
            @endforeach
          </div>
          @if ($pager && $pager->lastPage() > 1)
            <nav class="news-pagination mt-4" aria-label="Phân trang">
              @foreach ($pager->getUrlRange(1, $pager->lastPage()) as $pageNum => $pageUrl)
                <a href="{{ $pageUrl }}" class="{{ $pageNum === $pager->currentPage() ? 'active' : '' }}">{{ $pageNum }}</a>
              @endforeach
            </nav>
          @endif
        @endif
      </div>
    </section>
  </div>
</div>
@endsection
