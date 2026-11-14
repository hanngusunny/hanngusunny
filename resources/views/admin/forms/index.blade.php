@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <div class="flex justify-between"><h1 class="admin-page-title">Form</h1><a class="btn btn-primary" href="{{ route('admin.forms.create') }}">Tạo</a></div>
  <table class="table">
    @foreach ($rows as $r)
      <tr>
        <td>{{ $r->name }}</td>
        <td>{{ $r->slug }}</td>
        <td>
          <a href="{{ route('admin.forms.fields', $r) }}">Trường</a>
          <a href="{{ route('admin.forms.submissions', $r) }}">Gửi</a>
          <a href="{{ route('admin.forms.edit', $r) }}">Sửa</a>
          <form method="post" action="{{ route('admin.forms.destroy', $r) }}" class="inline">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
        </td>
      </tr>
    @endforeach
  </table>
@endsection
