<div class="cms-embed-faq" data-faq-category="{{ $category->id }}">
  <h3 class="h5">{{ $category->name }}</h3>
  @foreach ($category->items as $item)
    <details class="mb-2">
      <summary>{{ $item->question }}</summary>
      <div>{!! $item->answer !!}</div>
    </details>
  @endforeach
</div>
