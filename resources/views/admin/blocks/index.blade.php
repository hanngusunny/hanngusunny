@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <div class="flex justify-between"><h1 class="admin-page-title">Blocks</h1><a class="btn btn-primary" href="{{ route('admin.blocks.create') }}">Thêm</a></div>
  <table class="table">
    @foreach ($rows as $r)
      <tr>
        <td>{{ $r->name }}</td><td>{{ $r->slug }}</td>
        <td><a href="{{ route('admin.blocks.edit', $r) }}">Sửa</a>
          <form method="post" action="{{ route('admin.blocks.destroy', $r) }}" class="inline">@csrf<button class="btn btn-link" type="submit">Xóa</button></form></td>
      </tr>
    @endforeach
  </table>
@endsection
