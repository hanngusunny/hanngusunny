@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">{{ $menu->name }}</h1>
  <p>Kéo thả (Sortable) để lồng menu. Thả xong sẽ tự lưu cây.</p>
  <form method="post" action="{{ route('admin.menus.items.store', $menu) }}">@csrf
    <input class="form-control" name="label" placeholder="Nhãn" required>
    <input class="form-control mt-1" name="url" placeholder="URL (custom)">
    <select class="form-control mt-1" name="item_type">
      <option value="custom">custom</option>
      <option value="page">page</option>
      <option value="post">post</option>
      <option value="category">category</option>
    </select>
    <select class="form-control mt-1" name="parent_id">
      <option value="0">— Gốc —</option>
      @foreach ($items as $p)
        <option value="{{ $p->id }}">{{ $p->label }}</option>
      @endforeach
    </select>
    <button class="btn btn-primary mt-2" type="submit">Thêm mục</button>
  </form>
  <ul id="nav-sortable-root" class="list-none p-0 mt-4" data-save-url="{{ route('admin.menus.tree', $menu) }}">
    @include('admin.menus.tree-nodes', ['nodes' => $tree])
  </ul>
  @foreach ($items as $item)
    <form method="post" action="{{ route('admin.menus.items.update', $item) }}" class="mt-3 border p-2">@csrf
      <input class="form-control" name="label" value="{{ $item->label }}">
      <input class="form-control mt-1" name="url" value="{{ $item->url }}">
      <select class="form-control mt-1" name="parent_id">
        <option value="0">— Gốc —</option>
        @foreach ($items as $p)
          @if ($p->id !== $item->id)
            <option value="{{ $p->id }}" @selected((int) $item->parent_id === (int) $p->id)>{{ $p->label }}</option>
          @endif
        @endforeach
      </select>
      <input type="hidden" name="is_active" value="0">
      <label class="form-check-label"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Hiện</label>
      <button class="btn btn-sm btn-primary" type="submit">Lưu nhãn</button>
    </form>
    <form method="post" action="{{ route('admin.menus.items.destroy', $item) }}">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
  @endforeach
@endsection
@section('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <script src="{{ asset('js/admin-nav-tree.js') }}"></script>
@endsection
