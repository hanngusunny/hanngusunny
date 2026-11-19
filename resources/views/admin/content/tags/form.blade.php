@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $row ? 'Sửa tag' : 'Thêm tag' }}</h1>
  <form method="post" action="{{ $row ? route('admin.'.$module.'.tags.update', $row) : route('admin.'.$module.'.tags.store') }}">
    @csrf
    <x-sunny.input name="name" label="Tên" :value="$row->name ?? ''" :required="true" />
    <x-sunny.input name="slug" label="Slug" :value="$row->slug ?? ''" />
    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>
@endsection
