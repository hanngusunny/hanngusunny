@props([
    'title' => null,
    'variant' => 'default', // default | interactive | soft | elevated
])

@php
    $variantClass = match($variant) {
        'interactive' => 'sn-card--interactive',
        'soft' => 'sn-card--soft',
        'elevated' => 'sn-card--elevated',
        default => '',
    };
    $cardClass = trim("sn-card {$variantClass}");
@endphp

<div {{ $attributes->merge(['class' => $cardClass]) }}>
    @if ($title || isset($header) || isset($action))
        <div class="sn-card-header">
            @if (isset($header))
                {{ $header }}
            @elseif ($title)
                <h3 class="sn-card-title">{{ $title }}</h3>
            @endif

            @if (isset($action))
                <div class="sn-card-action">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    <div class="sn-card-body">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="sn-card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
