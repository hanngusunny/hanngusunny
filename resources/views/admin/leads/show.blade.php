@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $lead->full_name }}</h1>
  <p>{{ $lead->phone }} — {{ $lead->province }}</p>
  <p>{{ $lead->topic_label }}</p>
  <div>{!! nl2br(e($lead->message)) !!}</div>
@endsection
