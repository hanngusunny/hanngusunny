@extends('admin.layouts.app')

@section('content')
  @if (session('ok'))
    <p class="alert alert-success">{{ session('ok') }}</p>
  @endif
  <h1 class="admin-page-title">Cập nhật CSDL</h1>
  <p>Chạy <code>php artisan migrate --force</code> (idempotent, không DROP bảng). Super admin only.</p>
  <ul>
    @foreach ($files as $f)
      <li><code>{{ $f }}</code></li>
    @endforeach
  </ul>
  <form method="post" action="{{ route('admin.sql.run') }}" onsubmit="return confirm('Chạy migrate?');">
    @csrf
    <button class="btn btn-primary" type="submit">Chạy migrate</button>
  </form>
@endsection
