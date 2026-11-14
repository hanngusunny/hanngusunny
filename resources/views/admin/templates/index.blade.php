@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">Giao diện nội dung</h1>
  <p>Metadata bảng <code>content_template</code>. Laravel <strong>không</strong> thực thi PHP CI4 trong cột content; frontend dùng Blade <code>public/*</code>.</p>
  <form method="get" class="mb-3">
    <select name="scope" class="form-control" onchange="this.form.submit()">
      @foreach (['post','page','events','school','major'] as $s)
        <option value="{{ $s }}" @selected($scope === $s)>{{ $s }}</option>
      @endforeach
    </select>
    <select name="page_type" class="form-control mt-1" onchange="this.form.submit()">
      @foreach (['category','article','tag'] as $t)
        <option value="{{ $t }}" @selected($pageType === $t)>{{ $t }}</option>
      @endforeach
    </select>
  </form>
  <form method="post" action="{{ route('admin.templates.store') }}">@csrf
    <input type="hidden" name="scope" value="{{ $scope }}">
    <input type="hidden" name="page_type" value="{{ $pageType }}">
    <input class="form-control" name="name" placeholder="Tên" required>
    <input class="form-control mt-1" name="slug" placeholder="slug" required>
    <button class="btn btn-primary mt-2" type="submit">Thêm</button>
  </form>
  <table class="table mt-3">
    <thead><tr><th>Slug</th><th>Tên</th><th>Active</th><th></th></tr></thead>
    <tbody>
      @foreach ($rows as $r)
        <tr>
          <td>{{ $r->slug }}</td>
          <td>{{ $r->name }}</td>
          <td>{{ $r->is_active ? 'yes' : '' }}</td>
          <td>
            <a href="{{ route('admin.templates.edit', $r) }}">Sửa</a>
            <form method="post" action="{{ route('admin.templates.activate', $r) }}" class="inline">@csrf<button type="submit">Đặt active</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endsection
