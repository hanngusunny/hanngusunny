@php
  $title = $post->title;
  $url = $post->publicUrl();
  $img = $post->featuredImageUrl() ?: ($fallbackImg ?? 'https://images.unsplash.com/photo-1544531586-fde5298cdd40?w=640&q=80');
  $views = number_format((int) $post->view_count, 0, ',', '.');
  $badge = $badge ?? 'past';
  $muted = ((int) ($idx ?? 0) >= 2) ? ' news-card--muted' : '';
  $timeAttr = '';
  $timeLabel = '';
  if ($post->published_at) {
    $timeAttr = $post->published_at->format('Y-m-d');
    $timeLabel = $post->published_at->format('H:i:s') !== '00:00:00'
      ? $post->published_at->format('d/m/Y') . ' · ' . $post->published_at->format('H:i')
      : $post->published_at->format('d/m/Y');
  }
@endphp
<div class="w-full md:w-6/12 xl:w-4/12">
  <article class="news-card relative h-full{{ $muted }}">
    <div class="news-card__media">
      <span class="news-card__cat event-badge {{ $badge === 'upcoming' ? 'event-badge--upcoming' : 'event-badge--past' }}">
        {{ $badge === 'upcoming' ? 'Sắp diễn ra' : 'Đã diễn ra' }}
      </span>
      <img src="{{ $img }}" alt="{{ $title !== '' ? $title : ($catTitle ?? 'Sự kiện') }}" width="640" height="400" loading="lazy" decoding="async">
    </div>
    <div class="news-card__body">
      <div class="news-card__meta">
        @if ($timeAttr !== '')
          <time datetime="{{ $timeAttr }}">{{ $timeLabel }}</time>
        @endif
        <span class="news-card__views" aria-label="{{ $views }} lượt xem"><i class="bi bi-eye" aria-hidden="true"></i>{{ $views }}</span>
      </div>
      <h3 class="news-card__title">
        <a href="{{ $url }}" class="stretched-link no-underline">{{ $title }}</a>
      </h3>
      <span class="news-card__more">Đọc tiếp <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
    </div>
  </article>
</div>
