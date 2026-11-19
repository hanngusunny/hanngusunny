@props([
    'id',
    'title' => null,
])

<div id="{{ $id }}" class="sn-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" {{ $attributes }}>
    <div class="sn-modal-dialog">
        @if ($title || isset($header))
            <div class="sn-modal-header">
                @if (isset($header))
                    {{ $header }}
                @else
                    <h3 class="sn-modal-title" id="{{ $id }}-title">{{ $title }}</h3>
                @endif
                <button type="button" class="sn-btn sn-btn--text sn-btn--xs sn-btn--icon-only" onclick="document.getElementById('{{ $id }}').style.display='none'" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        <div class="sn-modal-body">
            {{ $slot }}
        </div>

        @if (isset($footer))
            <div class="sn-modal-footer">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
