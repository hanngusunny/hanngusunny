@extends('public.layout')
@section('content')
  <div class="container py-5 text-center">
    <h1>{{ $siteName }}</h1>
    <p>{{ $siteTagline }}</p>
    <p>Coming soon — {{ $untilIso }}</p>
  </div>
@endsection
