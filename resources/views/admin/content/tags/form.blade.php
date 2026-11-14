@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $row ? 'Sửa tag' : 'Thêm tag' }}</h1>
  <form method="post" action="{{ $row ? route('admin.'.$module.'.tags.update', $row) : route('admin.'.$module.'.tags.store') }}">
    @csrf
    <label class="form-label">Tên</label>
    <input class="form-control" name="name" value="{{ old('name', $row->name ?? '') }}" required>
    <label class="form-label">Slug</label>
    <input class="form-control" name="slug" value="{{ old('slug', $row->slug ?? '') }}">
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
