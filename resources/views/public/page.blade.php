@extends('public.layout')

@section('content')
  @if (!empty($isHome) && $page)
    {!! $descriptionHtml ?? $page->description !!}
  @elseif ($page)
    <div class="tpl tpl--category tpl--page" data-scope="page" data-page="category">
      <div class="container pb-4">
        <article class="page-content bun-richtext-content">
          {!! $descriptionHtml ?? $page->description !!}
        </article>
      </div>
    </div>
  @else
    <div class="container py-5">
      <h1>{{ $site['site_name'] ?? 'Website' }}</h1>
      <p>{{ $site['site_tagline'] ?? '' }}</p>
    </div>
  @endif
@endsection
