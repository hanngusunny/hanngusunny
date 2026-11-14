@extends('admin.layouts.app')

@section('content')
  @if (session('ok'))
    <p class="alert alert-success">{{ session('ok') }}</p>
  @endif
  <h1 class="admin-page-title">Nhận diện website</h1>
  <p><a href="{{ route('admin.customize.topbar') }}">Topbar</a></p>
  <form method="post" action="{{ route('admin.customize.identity.save') }}">
    @csrf
    <label class="form-label">Logo URL</label>
    <input class="form-control" name="logo_url" value="{{ old('logo_url', $settings['logo_url'] ?? '') }}">
    <label class="form-label">Logo thanh công cụ</label>
    <input class="form-control" name="toolbar_logo_url" value="{{ old('toolbar_logo_url', $settings['toolbar_logo_url'] ?? '') }}">
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
@endsection
