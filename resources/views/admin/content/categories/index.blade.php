@extends('admin.layouts.app')

@section('content')
  @if (session('content_success'))
    <p class="alert alert-success">{{ session('content_success') }}</p>
  @endif
  <div class="flex justify-between mb-3">
    <h1 class="admin-page-title">{{ $cfg['label'] }} — chuyên mục</h1>
    <a class="btn btn-primary" href="{{ route('admin.'.$module.'.categories.create') }}">Thêm</a>
  </div>
  <table class="table">
    <thead><tr><th>Tên</th><th>Slug</th><th></th></tr></thead>
    <tbody>
      @forelse ($rows as $r)
        <tr>
          <td>{{ $r->name }}</td>
          <td>{{ $r->slug }}</td>
          <td>
            <a href="{{ route('admin.'.$module.'.categories.edit', $r) }}">Sửa</a>
            <form class="inline" method="post" action="{{ route('admin.'.$module.'.categories.up', $r) }}">@csrf<button class="btn btn-link" type="submit">↑</button></form>
            <form class="inline" method="post" action="{{ route('admin.'.$module.'.categories.down', $r) }}">@csrf<button class="btn btn-link" type="submit">↓</button></form>
            <form class="inline" method="post" action="{{ route('admin.'.$module.'.categories.destroy', $r) }}" onsubmit="return confirm('Xóa?')">
              @csrf
              <button class="btn btn-link" type="submit">Xóa</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="3">Trống</td></tr>
      @endforelse
    </tbody>
  </table>
@endsection
