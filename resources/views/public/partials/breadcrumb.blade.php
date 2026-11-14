@php
  $crumbs = $breadcrumbs ?? [];
@endphp
@if ($crumbs !== [])
  <nav class="page-about-breadcrumb page-about-breadcrumb--pill mb-3 lg:mb-4" aria-label="Breadcrumb">
    <ol class="page-about-breadcrumb__list">
      <li class="page-about-breadcrumb__step">
        <a href="{{ route('home') }}" class="page-about-breadcrumb__link">
          <i class="bi bi-house-door-fill page-about-breadcrumb__home-ico" aria-hidden="true"></i>
          Trang chủ
        </a>
      </li>
      @foreach ($crumbs as $crumb)
        <li class="page-about-breadcrumb__step{{ $loop->last ? ' page-about-breadcrumb__step--current' : '' }}" @if($loop->last) aria-current="page" @endif>
          @if (! $loop->last && !empty($crumb['url']))
            <a href="{{ $crumb['url'] }}" class="page-about-breadcrumb__link">{{ $crumb['label'] }}</a>
          @else
            <span class="page-about-breadcrumb__current">{{ $crumb['label'] }}</span>
          @endif
        </li>
      @endforeach
    </ol>
  </nav>
@endif
