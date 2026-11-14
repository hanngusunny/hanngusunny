@extends('admin.layouts.app')

@section('content')
  @if (session('content_success'))
    <p class="alert alert-success">{{ session('content_success') }}</p>
  @endif
  <div class="flex justify-between mb-3">
    <h1 class="admin-page-title">{{ $cfg['label'] }}</h1>
    <a class="btn btn-primary" href="{{ route('admin.'.$module.'.posts.create') }}">Thêm</a>
  </div>
  <form method="get" class="flex gap-2 flex-wrap mb-3">
    <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="Tìm tiêu đề / slug">
    <select class="form-control" name="category_id">
      <option value="">Tất cả chuyên mục</option>
      @foreach ($categories ?? [] as $c)
        <option value="{{ $c->id }}" @selected((int) ($categoryId ?? 0) === (int) $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
    <button class="btn btn-outline-secondary" type="submit">Lọc</button>
  </form>
  <table class="table">
    <thead><tr><th>Tiêu đề</th><th>Slug</th><th>Trạng thái</th><th></th></tr></thead>
    <tbody>
      @forelse ($posts as $p)
        <tr>
          <td>{{ $p->title }}</td>
          <td>{{ $p->slug }}</td>
          <td>{{ $p->status }}</td>
          <td>
            <a href="{{ route('admin.'.$module.'.posts.edit', $p) }}">Sửa</a>
            <form class="inline" method="post" action="{{ route('admin.'.$module.'.posts.destroy', $p) }}" onsubmit="return confirm('Xóa?')">
              @csrf
              <button class="btn btn-link" type="submit">Xóa</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4">Chưa có mục nào.</td></tr>
      @endforelse
    </tbody>
  </table>
  {{ $posts->links() }}
@endsection
