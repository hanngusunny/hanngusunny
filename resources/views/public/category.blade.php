@extends('public.layout')

@section('content')
  @include('public.partials.breadcrumb')
  <h1>{{ $category->name }}</h1>
  <div>{!! $descriptionHtml ?? $category->description !!}</div>
  @if (($children ?? collect())->isNotEmpty())
    <ul class="category-children">
      @foreach ($children as $ch)
        <li><a href="{{ url('/'.$ch->slug) }}">{{ $ch->name }}</a></li>
      @endforeach
    </ul>
  @endif
  <ul>
    @foreach ($posts ?? [] as $p)
      <li><a href="{{ $p->publicUrl() }}">{{ $p->title }}</a></li>
    @endforeach
  </ul>
  @if (isset($posts) && method_exists($posts, 'links'))
    {{ $posts->links() }}
  @endif
@endsection
