@extends('admin.layouts.app')

@section('content')
  @if (session('ok'))
    <p class="alert alert-success">{{ session('ok') }}</p>
  @endif
  <h1 class="admin-page-title">Nhận diện website</h1>
  <p><a href="{{ route('admin.customize.topbar') }}">Topbar</a></p>
  <form method="post" action="{{ route('admin.customize.identity.save') }}">
    @csrf
    <x-sunny.input name="logo_url" label="Logo URL" :value="$settings['logo_url'] ?? ''" />
    <x-sunny.input name="toolbar_logo_url" label="Logo thanh công cụ" :value="$settings['toolbar_logo_url'] ?? ''" />
    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>
@endsection
