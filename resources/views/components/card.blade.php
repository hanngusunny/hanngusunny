@props([
    'title' => null,
    'variant' => 'default',
])

<x-sunny.card
    :title="$title"
    :variant="$variant"
    {{ $attributes }}
>
    @isset($header)
        <x-slot:header>{{ $header }}</x-slot:header>
    @endisset

    @isset($action)
        <x-slot:action>{{ $action }}</x-slot:action>
    @endisset

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-sunny.card>
