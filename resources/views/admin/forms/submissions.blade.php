@extends('admin.layouts.app')
@section('content')
  <h1 class="admin-page-title">Gửi — {{ $form->name }}</h1>
  <p><a class="btn btn-outline-secondary" href="{{ route('admin.forms.submissions.export', $form) }}">Xuất CSV</a></p>
  @foreach ($rows as $s)
    <pre class="mb-3">{{ json_encode($s->payload(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
  @endforeach
  {{ $rows->links() }}
@endsection
