@php
  $fallbackImg = 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&q=80';
  $badgeCycle = [
    ['cls' => 'hoat-dong-card__badge--sun', 'abbr' => 'SK', 'title' => 'Sự kiện'],
    ['cls' => 'hoat-dong-card__badge--violet', 'abbr' => 'SK', 'title' => 'Sự kiện'],
    ['cls' => 'hoat-dong-card__badge--teal', 'abbr' => 'SK', 'title' => 'Sự kiện'],
  ];
  $arc = $eventsUrl ?? url('/su-kien');
@endphp
  <!-- Hoạt động Sunny -->
  <section id="hoat-dong" class="section-hoat-dong py-5">
    <div class="container">
      <div class="section-hoat-dong__row">
        <div class="section-hoat-dong__aside flex flex-col">
          <div class="title-line mb-3"></div>
          <h2 class="section-title section-hoat-dong__title mb-2">Hoạt động của Sunny</h2>
          <p class="section-hoat-dong__lead mb-4">
            Tin sự kiện và hoạt động SUNNY — workshop, talkshow và tuyển sinh được cập nhật trong chuyên mục sự kiện.
          </p>
          <div class="flex flex-wrap gap-2 mb-2" role="navigation" aria-label="Liên quan hoạt động">
            <a href="{{ $arc }}" class="btn btn-outline-secondary btn-sm rounded-full px-3">Danh mục sự kiện</a>
          </div>
          <div class="section-hoat-dong__filters flex flex-wrap gap-2 mt-3" role="group" aria-label="Lọc theo loại hoạt động">
            <button type="button" class="hoat-dong-filter is-active" data-hoat-dong-filter="all">Tất cả</button>
            <button type="button" class="hoat-dong-filter" data-hoat-dong-filter="workshop">Workshop</button>
            <button type="button" class="hoat-dong-filter" data-hoat-dong-filter="talkshow">Talkshow</button>
            <button type="button" class="hoat-dong-filter" data-hoat-dong-filter="tuyen-sinh">Tuyển sinh</button>
          </div>
          <div class="section-hoat-dong__nav flex items-center gap-2 mt-4 mt-lg-auto lg:pt-3">
            <button type="button" class="hoat-dong-nav-btn hoat-dong-nav-btn--prev" aria-label="Slide trước">
              <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="hoat-dong-nav-btn hoat-dong-nav-btn--toggle" aria-label="Tạm dừng tự chạy" aria-pressed="false">
              <i class="bi bi-pause-fill hoat-dong-nav-btn__icon-pause" aria-hidden="true"></i>
              <i class="bi bi-play-fill hoat-dong-nav-btn__icon-play hidden" aria-hidden="true"></i>
            </button>
            <button type="button" class="hoat-dong-nav-btn hoat-dong-nav-btn--next" aria-label="Slide sau">
              <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        </div>
        <div class="section-hoat-dong__slider-col">
          <div class="swiper hoat-dong-swiper">
            <div class="swiper-wrapper">
              @forelse ($posts as $i => $post)
                @php
                  $badge = $badgeCycle[$i % count($badgeCycle)];
                  $title = trim((string) $post->title);
                  $url = $post->publicUrl();
                  $ts = $post->published_at ? $post->published_at->getTimestamp() : 0;
                  $day = $ts ? $post->published_at->format('d') : '--';
                  $ym = $ts ? $post->published_at->format('Y.m') : '';
                  $startIso = $ts ? $post->published_at->format('Y-m-d') : '';
                  $endRaw = trim((string) ($post->event_registration_deadline ?? ''));
                  $endIso = $endRaw !== '' && strtotime($endRaw) ? date('Y-m-d', strtotime($endRaw)) : $startIso;
                  $footLine = $startIso !== '' ? $startIso.' ~ '.($endIso !== '' ? $endIso : $startIso) : '';
                  $excerpt = trim((string) ($post->meta_description ?: ''));
                  if ($excerpt === '') {
                    $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->body)) ?? '');
                    if (\Illuminate\Support\Str::length($excerpt) > 160) {
                      $excerpt = \Illuminate\Support\Str::substr($excerpt, 0, 160).'…';
                    }
                  }
                  $imgUrl = $post->featuredImageUrl() ?: $fallbackImg;
                @endphp
                @if ($title !== '')
              <div class="swiper-slide" data-hoat-dong-cat="cms">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="{{ $imgUrl }}" alt="{{ $title }}" width="600" height="375" loading="lazy" decoding="async">
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge {{ $badge['cls'] }}" title="{{ $badge['title'] }}">{{ $badge['abbr'] }}</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">{{ $day }}</span>
                        <span class="hoat-dong-card__ym">{{ $ym }}</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="{{ $url }}" class="stretched-link no-underline text-inherit">{{ $title }}</a>
                    </h3>
                    <p class="hoat-dong-card__desc">{{ $excerpt !== '' ? $excerpt : ' ' }}</p>
                    @if ($footLine !== '')
                    <footer class="hoat-dong-card__foot">{{ $footLine }}</footer>
                    @endif
                  </div>
                </article>
              </div>
                @endif
              @empty
              <div class="swiper-slide" data-hoat-dong-cat="cms">
                <article class="hoat-dong-card relative h-full">
                  <div class="hoat-dong-card__media">
                    <img src="{{ $fallbackImg }}" alt="" width="600" height="375" loading="lazy" decoding="async">
                  </div>
                  <div class="hoat-dong-card__body">
                    <header class="hoat-dong-card__head">
                      <span class="hoat-dong-card__badge hoat-dong-card__badge--sun" title="Sự kiện">SK</span>
                      <div class="hoat-dong-card__dateblock">
                        <span class="hoat-dong-card__day">—</span>
                        <span class="hoat-dong-card__ym">—</span>
                      </div>
                    </header>
                    <h3 class="hoat-dong-card__title">
                      <a href="{{ $arc }}" class="stretched-link no-underline text-inherit">Chưa có sự kiện được xuất bản</a>
                    </h3>
                    <p class="hoat-dong-card__desc">Thêm và xuất bản ít nhất một bài trong chuyên mục «Sự kiện» (CMS) để hiển thị tại đây.</p>
                  </div>
                </article>
              </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
