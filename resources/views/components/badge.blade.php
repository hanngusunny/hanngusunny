@props([
    'variant' => 'primary',
    'pill' => false,
    'icon' => null,
])

<x-sunny.badge
    :variant="$variant"
    :pill="$pill"
    :icon="$icon"
    {{ $attributes }}
>
    {{ $slot }}
</x-sunny.badge>
