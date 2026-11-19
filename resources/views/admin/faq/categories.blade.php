@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">FAQ</h1>
  <form method="post" action="{{ route('admin.faq.categories.store') }}" class="mb-4">@csrf
    <x-sunny.input name="name" placeholder="Tên nhóm" :required="true" />
    <x-sunny.button type="submit" class="mt-2">Thêm nhóm</x-sunny.button>
  </form>
  <table class="table">
    @foreach ($rows as $r)
      <tr>
        <td>
          <form method="post" action="{{ route('admin.faq.categories.update', $r) }}" class="flex gap-2">@csrf
            <input class="sn-input" name="name" value="{{ $r->name }}" required>
            <x-sunny.button size="sm" type="submit">Lưu</x-sunny.button>
          </form>
        </td>
        <td><a href="{{ route('admin.faq.items', $r) }}">Câu hỏi</a></td>
        <td>
          <form method="post" action="{{ route('admin.faq.categories.destroy', $r) }}" onsubmit="return confirm('Xóa?')">@csrf<x-sunny.button variant="text" type="submit">Xóa</x-sunny.button></form>
        </td>
      </tr>
    @endforeach
  </table>
@endsection
