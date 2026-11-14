@php
  $fallbackImg = 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="400" viewBox="0 0 640 400"><rect fill="#eef1f6" width="640" height="400"/><path fill="#cbd5e1" d="M260 284l76-104 96 152h160V132H448l-68 84-108-148H260v216z"/><circle cx="204" cy="156" r="48" fill="#cbd5e1"/></svg>');
@endphp
  <!-- Tin tức -->
  <section id="tin-tuc" class="section-tin-tuc py-5">
    <div class="section-tin-tuc__decor" aria-hidden="true">SUNNY</div>
    <div class="container relative">
      <div class="flex flex-wrap items-end justify-between gap-3 mb-4 lg:mb-5">
        <div class="grow min-w-0 basis-0">
          <div class="title-line mb-3"></div>
          <h2 class="section-title mb-2" id="tin-tuc-heading">Tin tức</h2>
          <p class="section-tin-tuc__lead mb-0">Chính sách, học bổng và mẹo hồ sơ — cập nhật thường xuyên.</p>
        </div>
        <div class="w-auto shrink-0">
          <div class="tin-tuc-toolbar flex items-center gap-2">
            <button type="button" class="tin-tuc-toolbar__btn tin-tuc-toolbar__btn--arrow tin-tuc-toolbar__btn--prev" aria-label="Tin trước">
              <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="tin-tuc-toolbar__btn tin-tuc-toolbar__btn--arrow tin-tuc-toolbar__btn--next" aria-label="Tin sau">
              <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
            <a href="{{ $listUrl }}" class="tin-tuc-toolbar__btn tin-tuc-toolbar__btn--all" aria-label="Xem tất cả tin">
              <i class="bi bi-plus-lg" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="swiper tin-tuc-swiper">
        <div class="swiper-wrapper">
          @foreach ($posts as $post)
            @php
              $title = trim((string) $post->title);
              $url = $post->publicUrl();
              $imgSrc = $post->featuredImageUrl() ?: $fallbackImg;
              $iso = $post->published_at ? $post->published_at->toIso8601String() : '';
              $lbl = $post->published_at ? $post->published_at->format('d/m/Y') : '';
              $excerpt = trim((string) ($post->excerpt ?: $post->meta_description ?: ''));
              if ($excerpt === '') {
                $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->body)) ?? '');
                if (\Illuminate\Support\Str::length($excerpt) > 240) {
                  $excerpt = \Illuminate\Support\Str::substr($excerpt, 0, 240).'…';
                }
              }
              $viewsF = number_format((int) $post->view_count, 0, ',', '.');
            @endphp
          <div class="swiper-slide">
            <article class="news-card news-card--compact tin-tuc-home-slider__card relative h-full flex flex-col">
              <div class="news-card__media tin-tuc-home-slider__media">
                <img src="{{ $imgSrc }}" alt="{{ $title }}" width="640" height="400" loading="lazy" decoding="async">
              </div>
              <div class="news-card__body tin-tuc-home-slider__body flex flex-col grow">
                <div class="news-card__meta tin-tuc-home-slider__meta mb-2 flex items-center flex-wrap gap-2">
                  @if ($lbl !== '')
                    <time class="news-card__date tin-tuc-home-slider__meta-date"@if ($iso !== '') datetime="{{ $iso }}"@endif>{{ $lbl }}</time>
                  @endif
                  <span class="news-card__views tin-tuc-home-slider__meta-views inline-flex items-center gap-1" aria-label="{{ $viewsF }} lượt xem">
                    <i class="bi bi-eye" aria-hidden="true"></i>{{ $viewsF }}
                  </span>
                </div>
                <h3 class="news-card__title tin-tuc-home-slider__title">
                  <a href="{{ $url }}" class="tin-tuc-home-slider__title-link">{{ $title }}</a>
                </h3>
                <p class="tin-tuc-home-slider__excerpt mb-2 grow">{{ $excerpt !== '' ? $excerpt : '—' }}</p>
                <a href="{{ $url }}" class="tin-tuc-home-slider__cta mt-auto font-semibold inline-flex items-center no-underline">Xem chi tiết <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
              </div>
            </article>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </section>
