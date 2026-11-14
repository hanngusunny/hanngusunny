@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">Slider</h1>
  <form method="post" action="{{ route('admin.slider.categories.store') }}">@csrf
    <input class="form-control" name="name" placeholder="Tên nhóm" required>
    <button class="btn btn-primary mt-2" type="submit">Thêm nhóm</button>
  </form>
  <table class="table mt-3">
    @foreach ($rows as $r)
      <tr>
        <td>
          <form method="post" action="{{ route('admin.slider.categories.update', $r) }}" class="flex gap-2">@csrf
            <input class="form-control" name="name" value="{{ $r->name }}" required>
            <button class="btn btn-sm btn-primary" type="submit">Lưu</button>
          </form>
        </td>
        <td><a href="{{ route('admin.slider.slides', $r) }}">Slide</a></td>
        <td><form method="post" action="{{ route('admin.slider.categories.destroy', $r) }}">@csrf<button class="btn btn-link" type="submit">Xóa</button></form></td>
      </tr>
    @endforeach
  </table>
@endsection
