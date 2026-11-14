@extends('public.layout')
@section('content')
  <h1>{{ $title }}</h1>
  <p>{{ $body }}</p>
  <p><a href="{{ route('home') }}">{{ __('consult.notice_btn_home') }}</a></p>
@endsection
