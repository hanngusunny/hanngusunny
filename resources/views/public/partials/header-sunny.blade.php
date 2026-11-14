@php
  $pf = fn (string $slug) => url('/'.$slug);
  $pageSlug = (string) ($pageSlug ?? '');
  $veSunny = in_array($pageSlug, ['gioi-thieu', 'khoanh-khac'], true);
  $chuongTrinh = in_array($pageSlug, ['du-hoc-han-quoc-tong-the', 'du-hoc-han-quoc-he-tieng-d4-1', 'du-hoc-han-quoc-he-dai-hoc-d2-2', 'du-hoc-han-quoc-he-thac-si-d2-3', 'du-hoc-han-quoc-he-cao-dang-d2-1'], true);
  $daoTao = $pageSlug === 'dao-tao-tieng-han';
  $truongNganh = in_array($pageSlug, ['truong-dai-hoc', 'truong-kookmin', 'cac-nganh-du-hoc-han-quoc', 'nganh-cong-nghe-thong-tin-han-quoc'], true)
    || (str_starts_with($pageSlug, 'truong-dai-hoc-') && str_ends_with($pageSlug, '-han-quoc'));
  $tinMuc = in_array($pageSlug, ['tin-tuc', 'tin-visa-d2-ho-so-chuan-bi'], true) || str_starts_with($pageSlug, 'tin-');
  $suKien = in_array($pageSlug, ['su-kien', 'su-kien-chi-tiet'], true);
  if ($pageSlug === 'tim-kiem') {
    $tinMuc = false;
  }
  $navClass = 'navbar-sunny'.(!empty($navIsHome) ? ' navbar-sunny--no-bg navbar-sunny--nav-light fixed-top' : ' sticky-top');
@endphp
<nav class="{{ $navClass }}">
  <div class="container navbar-sunny-inner flex flex-wrap lg:flex-nowrap items-center">
    <a class="navbar-brand py-0 me-3 shrink-0" href="{{ route('home') }}" aria-label="Trang chủ">
      <img src="{{ $publicLogoUrl }}" alt="{{ $site['site_name'] ?? 'Sunny Du học Hàn Quốc' }}" class="navbar-logo max-w-full" width="170" height="44" fetchpriority="high" loading="eager" decoding="async">
    </a>
    <button class="navbar-toggler ms-auto lg:hidden" type="button" data-tw-toggle="collapse" data-tw-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-sunny-collapse hidden w-full lg:flex lg:w-auto lg:grow lg:items-center" id="mainNav">
      <div class="flex flex-col lg:flex-row lg:flex-nowrap lg:items-center w-full lg:grow pt-3 lg:pt-0">
        <div class="flex flex-col lg:flex-row lg:flex-nowrap lg:items-center lg:justify-center lg:grow min-w-0">
          <ul class="navbar-nav flex flex-col lg:flex-row lg:flex-nowrap lg:items-center lg:gap-1 lg:mx-auto text-center lg:text-left">
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle{{ $veSunny ? ' active' : '' }}" href="#" data-tw-toggle="dropdown" aria-expanded="false"@if ($veSunny) aria-current="true"@endif>Về SUNNY</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item{{ $pageSlug === 'gioi-thieu' ? ' active' : '' }}" href="{{ $pf('gioi-thieu') }}"@if ($pageSlug === 'gioi-thieu') aria-current="page"@endif>Giới thiệu</a></li>
                <li><a class="dropdown-item{{ $pageSlug === 'khoanh-khac' ? ' active' : '' }}" href="{{ $pf('khoanh-khac') }}"@if ($pageSlug === 'khoanh-khac') aria-current="page"@endif>Khoảnh khắc</a></li>
              </ul>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle{{ $chuongTrinh ? ' active' : '' }}" href="{{ $pf('du-hoc-han-quoc-tong-the') }}" data-tw-toggle="dropdown"@if ($chuongTrinh) aria-current="true"@endif>Chương trình Du học</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item{{ $pageSlug === 'du-hoc-han-quoc-he-tieng-d4-1' ? ' active' : '' }}" href="{{ $pf('du-hoc-han-quoc-he-tieng-d4-1') }}">Du học hệ tiếng (D4-1)</a></li>
                <li><a class="dropdown-item{{ $pageSlug === 'du-hoc-han-quoc-he-dai-hoc-d2-2' ? ' active' : '' }}" href="{{ $pf('du-hoc-han-quoc-he-dai-hoc-d2-2') }}">Du học hệ đại học (D2-2)</a></li>
                <li><a class="dropdown-item{{ $pageSlug === 'du-hoc-han-quoc-he-thac-si-d2-3' ? ' active' : '' }}" href="{{ $pf('du-hoc-han-quoc-he-thac-si-d2-3') }}">Du học hệ thạc sĩ (D2-3)</a></li>
                <li><a class="dropdown-item{{ $pageSlug === 'du-hoc-han-quoc-he-cao-dang-d2-1' ? ' active' : '' }}" href="{{ $pf('du-hoc-han-quoc-he-cao-dang-d2-1') }}">Du học hệ cao đẳng (D2-1)</a></li>
              </ul>
            </li>
            <li class="nav-item"><a class="nav-link{{ $daoTao ? ' active' : '' }}" href="{{ $pf('dao-tao-tieng-han') }}"@if ($daoTao) aria-current="page"@endif>Đào tạo tiếng Hàn</a></li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle{{ $truongNganh ? ' active' : '' }}" href="{{ $pf('truong-dai-hoc') }}" data-tw-toggle="dropdown"@if ($truongNganh) aria-current="true"@endif>Trường &amp; Ngành</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ $pf('truong-dai-hoc') }}">Trường đại học Hàn Quốc</a></li>
                <li><a class="dropdown-item" href="{{ $pf('cac-nganh-du-hoc-han-quoc') }}">Các ngành du học</a></li>
              </ul>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle{{ ($tinMuc || $suKien) ? ' active' : '' }}" href="#" data-tw-toggle="dropdown"@if ($tinMuc || $suKien) aria-current="true"@endif>Tin tức</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item{{ $pageSlug === 'tin-tuc' ? ' active' : '' }}" href="{{ $pf('tin-tuc') }}"@if ($pageSlug === 'tin-tuc') aria-current="page"@endif>Tin tức</a></li>
                <li><a class="dropdown-item{{ $suKien ? ' active' : '' }}" href="{{ $pf('su-kien') }}"@if ($suKien) aria-current="page"@endif>Sự kiện</a></li>
              </ul>
            </li>
            <li class="nav-item"><a class="nav-link{{ $pageSlug === 'lien-he' ? ' active' : '' }}" href="{{ $pf('lien-he') }}"@if ($pageSlug === 'lien-he') aria-current="page"@endif>Liên hệ</a></li>
          </ul>
        </div>
        <div class="flex items-center justify-center lg:justify-end shrink-0 gap-2 lg:ps-3 pb-3 lg:pb-0 mt-2 lg:mt-0">
          <button type="button" class="navbar-sunny__search inline-flex items-center justify-center" id="navSearchToggle" aria-label="Mở ô tìm kiếm" aria-expanded="false" aria-controls="navSearchPanel">
            <i class="bi bi-search" aria-hidden="true"></i>
          </button>
          <a class="btn btn-sunny btn-sunny-pulse text-white" href="{{ route('home') }}#dang-ky">Đăng ký tư vấn</a>
        </div>
      </div>
    </div>
  </div>
</nav>
<div id="navSearchPanel" class="navbar-search-panel" role="search" aria-label="Tìm kiếm" hidden>
  <form class="navbar-search-panel__form" action="{{ route('search') }}" method="get" role="search">
    <div class="container navbar-sunny-inner py-3 md:py-3">
      <p class="navbar-search-panel__kicker">Tìm bài viết, chủ đề &amp; hướng dẫn</p>
      <div class="navbar-search-panel__row">
        <label class="sr-only" for="navSearchInput">Từ khóa tìm kiếm</label>
        <input type="search" class="tw-field navbar-search-panel__input" name="q" id="navSearchInput" placeholder="Nhập từ khóa tìm kiếm…" autocomplete="off">
        <button type="submit" class="navbar-search-panel__submit flex items-center justify-center gap-1" aria-label="Tìm kiếm">
          <i class="bi bi-search" aria-hidden="true"></i>
          <span class="navbar-search-panel__submit-text hidden sm:inline">Tìm</span>
        </button>
      </div>
    </div>
  </form>
</div>
