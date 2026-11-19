@props([
    'variant' => 'primary', // primary | secondary | white | danger | success | text
    'size' => 'm',          // xl | lg | m | sm | xs
    'type' => 'button',     // button | submit | reset
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'disabled' => false,
])

@php
    $variantClass = match($variant) {
        'secondary' => 'sn-btn--secondary',
        'white', 'tertiary' => 'sn-btn--white',
        'danger' => 'sn-btn--danger',
        'success' => 'sn-btn--success',
        'text' => 'sn-btn--text',
        default => 'sn-btn--primary',
    };

    $sizeClass = match($size) {
        'xl' => 'sn-btn--xl',
        'lg' => 'sn-btn--lg',
        'sm' => 'sn-btn--sm',
        'xs' => 'sn-btn--xs',
        default => '',
    };

    $classes = trim("sn-btn {$variantClass} {$sizeClass}" . ($iconOnly ? ' sn-btn--icon-only' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if($disabled) aria-disabled="true" tabindex="-1" style="pointer-events: none; opacity: 0.6;" @endif>
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        @if (trim($slot) !== '')
            <span>{{ $slot }}</span>
        @endif
        @if ($iconRight)
            <i class="{{ $iconRight }}" aria-hidden="true"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        @if (trim($slot) !== '')
            <span>{{ $slot }}</span>
        @endif
        @if ($iconRight)
            <i class="{{ $iconRight }}" aria-hidden="true"></i>
        @endif
    </button>
@endif
