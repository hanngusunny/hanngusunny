<div class="cms-embed-slider" data-slider-category="{{ $category->id }}">
  @foreach ($category->slides as $slide)
    <div class="cms-embed-slider__slide mb-3">
      @if ($slide->image_desktop)
        <img src="{{ $slide->image_desktop }}" alt="" class="max-w-full h-auto" loading="lazy">
      @endif
      @if ($slide->content)
        <div>{!! $slide->content !!}</div>
      @endif
      @if ($slide->link_url)
        <a href="{{ $slide->link_url }}">Xem thêm</a>
      @endif
    </div>
  @endforeach
</div>
