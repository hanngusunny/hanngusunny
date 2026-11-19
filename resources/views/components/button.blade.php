@props([
    'variant' => 'primary',
    'size' => 'm',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'disabled' => false,
])

<x-sunny.button
    :variant="$variant"
    :size="$size"
    :type="$type"
    :href="$href"
    :icon="$icon"
    :iconRight="$iconRight"
    :iconOnly="$iconOnly"
    :disabled="$disabled"
    {{ $attributes }}
>
    {{ $slot }}
</x-sunny.button>
