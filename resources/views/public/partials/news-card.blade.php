@php
  $title = $title ?? (isset($post) ? (string) $post->title : '');
  $url = $url ?? (isset($post) && method_exists($post, 'publicUrl') ? $post->publicUrl() : '#');
  $img = $img ?? (isset($post) && method_exists($post, 'featuredImageUrl') ? $post->featuredImageUrl() : '');
  if ($img === '') {
    $img = $fallbackImg ?? '';
  }
  $views = number_format((int) ($viewCount ?? (isset($post) ? ($post->view_count ?? 0) : 0)), 0, ',', '.');
  $excerpt = $excerpt ?? '';
  if ($excerpt === '' && isset($post)) {
    $excerpt = trim(strip_tags((string) ($post->excerpt ?: $post->meta_description ?: '')));
    if ($excerpt === '') {
      $excerpt = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($post->body ?? $post->description ?? ''))) ?? ''), 220);
    }
  }
  $publishedAt = $publishedAt ?? (isset($post) ? $post->published_at : null);
  $variant = $variant ?? 'grid';
@endphp
@if ($variant === 'large')
<article class="news-card news-card-large">
  <a class="thumb thumb-large" href="{{ $url }}"><img src="{{ $img }}" alt="{{ $title }}" width="800" height="480" loading="lazy" decoding="async"></a>
  <div class="news-body">
    <div class="meta-row">
      @if ($publishedAt)
        <span class="meta-row__item"><i class="bi bi-calendar3" aria-hidden="true"></i><time datetime="{{ $publishedAt->format('Y-m-d') }}">{{ $publishedAt->format('d/m/Y') }}</time></span>
      @endif
      <span class="meta-row__item"><i class="bi bi-eye" aria-hidden="true"></i><span>{{ $views }} lượt xem</span></span>
    </div>
    <h2><a href="{{ $url }}">{{ $title }}</a></h2>
    @if ($excerpt !== '')<p>{{ $excerpt }}</p>@endif
    <a class="read-more" href="{{ $url }}">Xem chi tiết <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </div>
</article>
@elseif ($variant === 'compact')
<article class="news-card compact-card">
  <a class="thumb" href="{{ $url }}"><img src="{{ $img }}" alt="{{ $title }}" width="640" height="400" loading="lazy" decoding="async"></a>
  <div class="news-body">
    <div class="meta-row">
      @if ($publishedAt)
        <span class="meta-row__item"><i class="bi bi-calendar3" aria-hidden="true"></i><time datetime="{{ $publishedAt->format('Y-m-d') }}">{{ $publishedAt->format('d/m/Y') }}</time></span>
      @endif
      <span class="meta-row__item"><i class="bi bi-eye" aria-hidden="true"></i><span>{{ $views }} lượt xem</span></span>
    </div>
    <h3><a href="{{ $url }}">{{ $title }}</a></h3>
    <a class="read-more" href="{{ $url }}">Xem chi tiết <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </div>
</article>
@else
<article class="news-card">
  <a class="thumb" href="{{ $url }}"><img src="{{ $img }}" alt="{{ $title }}" width="640" height="400" loading="lazy" decoding="async"></a>
  <div class="news-body">
    <div class="meta-row">
      @if ($publishedAt)
        <span class="meta-row__item"><i class="bi bi-calendar3" aria-hidden="true"></i><time datetime="{{ $publishedAt->format('Y-m-d') }}">{{ $publishedAt->format('d/m/Y') }}</time></span>
      @endif
      <span class="meta-row__item"><i class="bi bi-eye" aria-hidden="true"></i><span>{{ $views }} lượt xem</span></span>
    </div>
    <h3><a href="{{ $url }}">{{ $title }}</a></h3>
    <a class="read-more" href="{{ $url }}">Xem chi tiết <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </div>
</article>
@endif
