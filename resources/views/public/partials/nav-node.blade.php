@if (!empty($node['children']))
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle{{ ($pageSlug ?? '') === \Illuminate\Support\Str::slug($node['label']) ? ' active' : '' }}" href="{{ $node['url'] ?: '#' }}" data-tw-toggle="dropdown" @if (!empty($node['target_blank'])) target="_blank" rel="noopener" @endif>{{ $node['label'] }}</a>
    <ul class="dropdown-menu">
      @foreach ($node['children'] as $child)
        @if (!empty($child['children']))
          @include('public.partials.nav-node', ['node' => $child])
        @else
          <li><a class="dropdown-item" href="{{ $child['url'] ?: '#' }}" @if (!empty($child['target_blank'])) target="_blank" rel="noopener" @endif>{{ $child['label'] }}</a></li>
        @endif
      @endforeach
    </ul>
  </li>
@else
  <li class="nav-item">
    <a class="nav-link{{ ($pageSlug ?? '') !== '' && str_contains((string) ($node['url'] ?? ''), $pageSlug) ? ' active' : '' }}" href="{{ $node['url'] ?: '#' }}" @if (!empty($node['target_blank'])) target="_blank" rel="noopener" @endif>{{ $node['label'] }}</a>
  </li>
@endif
