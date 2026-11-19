@props([
    'variant' => 'primary', // primary | secondary | success | danger | warning | gray | purple
    'pill' => false,
    'icon' => null,
])

@php
    $variantClass = match($variant) {
        'secondary' => 'sn-badge--secondary',
        'success' => 'sn-badge--success',
        'danger' => 'sn-badge--danger',
        'warning' => 'sn-badge--warning',
        'gray', 'neutral' => 'sn-badge--gray',
        'purple' => 'sn-badge--purple',
        default => 'sn-badge--primary',
    };

    $classes = trim("sn-badge {$variantClass}" . ($pill ? ' sn-badge--pill' : ''));
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
