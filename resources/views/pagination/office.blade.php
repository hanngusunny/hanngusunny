@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center gap-2 text-sm">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1.5 text-slate-400">Trước</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5">Trước</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-slate-400">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-on px-3 py-1.5">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-1.5">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5">Sau</a>
        @else
            <span class="px-3 py-1.5 text-slate-400">Sau</span>
        @endif

        <span class="ml-2 text-slate-500">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }}</span>
    </nav>
@endif
