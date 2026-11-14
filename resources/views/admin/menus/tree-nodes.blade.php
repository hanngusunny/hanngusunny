@foreach ($nodes as $node)
  <li data-id="{{ $node['id'] }}" class="border p-2 mb-1">
    <span class="nav-sort-handle" style="cursor:grab">☰</span>
    {{ $node['label'] }}
    <ul class="list-none p-0 ms-4 mt-1">
      @include('admin.menus.tree-nodes', ['nodes' => $node['children'] ?? []])
    </ul>
  </li>
@endforeach
