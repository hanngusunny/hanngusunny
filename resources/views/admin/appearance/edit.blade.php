@extends('admin.layouts.app')

@section('content')
  @if (session('ok'))
    <p class="alert alert-success">{{ session('ok') }}</p>
  @endif
  <h1 class="admin-page-title">Giao diện frontend (Blade)</h1>
  <form method="get" class="mb-3">
    <select name="file" class="sn-select" onchange="this.form.submit()">
      @foreach ($files as $k => $path)
        <option value="{{ $k }}" @selected($fileKey === $k)>{{ $k }} — {{ $path }}</option>
      @endforeach
    </select>
  </form>
  @if ($adminIsSuper ?? false)
    <form method="post" action="{{ route('admin.appearance.update') }}">
      @csrf
      <input type="hidden" name="file" value="{{ $fileKey }}">
      <textarea class="sn-textarea" name="content" rows="24" style="font-family:monospace">{{ $content }}</textarea>
      <x-sunny.button type="submit" class="mt-3">Lưu file</x-sunny.button>
    </form>
  @else
    <p>Chỉ super admin được sửa file.</p>
    <pre style="white-space:pre-wrap">{{ $content }}</pre>
  @endif
@endsection
