@extends('public.layout')

@section('head')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin="">
  <link href="{{ asset('assets/css/school-uni-map.css') }}" rel="stylesheet">
@endsection

@section('content')
  <div class="tpl tpl--category tpl--school school-uni-map-page" id="school-uni-map-app">
    <h1 class="sr-only">{{ $category->name }}</h1>
    <section id="danh-sach-truong" class="uni-list-section py-0">
      @if (($descriptionHtml ?? '') !== '')
        <div class="container py-4 border-b bg-white">
          <div class="page-content bun-richtext-content">{!! $descriptionHtml !!}</div>
        </div>
      @endif
      @if (!empty($mapItems))
        <script type="application/json" id="school-uni-map-json">{!! json_encode($mapItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <div class="container-fluid p-0">
          <div class="flex flex-wrap gap-0 row-main">
            <aside class="w-full lg:w-3/12 border-end col-sidebar flex flex-col">
              <div class="sidebar-head p-3 shrink-0">
                <h3 class="sidebar-title h5 mb-1">Danh sách trường</h3>
                <p class="sidebar-lead text-sm mb-2">Chọn trường để định vị trên bản đồ hoặc mở hồ sơ.</p>
                <div class="flex flex-wrap gap-2 items-end">
                  <div class="w-6/12">
                    <label for="uni-search" class="tw-label text-sm mb-1">Tìm theo tên</label>
                    <input type="search" id="uni-search" class="tw-field form-control-sm" placeholder="Tên, mô tả…" autocomplete="off">
                  </div>
                  <div class="w-6/12">
                    <label for="region-filter" class="tw-label text-sm mb-1">Khu vực</label>
                    <select id="region-filter" class="tw-select form-select-sm">
                      <option value="all">Tất cả khu vực</option>
                      <option value="seoul">Seoul · 수도권</option>
                      <option value="chungcheong">Chungcheong · 충청</option>
                      <option value="gyeongbuk">Gyeongsang Bắc · 경북</option>
                      <option value="gyeongnam">Gyeongsang Nam · 경남</option>
                    </select>
                  </div>
                </div>
              </div>
              <p id="uni-no-results" class="text-sm text-center py-2 mb-0 hidden">Không tìm thấy trường phù hợp.</p>
              <div id="uni-list" class="list-group list-group-flush grow"></div>
            </aside>
            <div class="w-full lg:w-9/12 p-0 col-map">
              <div class="map-wrap">
                <div id="map" role="application" aria-label="Bản đồ trường đại học"></div>
              </div>
            </div>
          </div>
        </div>
      @else
        <div class="container py-5">
          <p class="text-center">Chưa có chuyên mục con hoặc trường xuất bản trong chuyên mục này.</p>
        </div>
      @endif
    </section>
  </div>
@endsection

@section('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js" crossorigin=""></script>
  <script src="https://unpkg.com/@maplibre/maplibre-gl-leaflet@0.1.3/leaflet-maplibre-gl.js" crossorigin=""></script>
  <script src="{{ asset('assets/js/school-uni-map-page.js') }}"></script>
@endsection
