@props([
    'url' => null,
    'name' => '',
    'initials' => null,
    'alt' => null,
])

@php
    $name = trim((string) $name);
    $initials = $initials !== null && trim((string) $initials) !== ''
        ? trim((string) $initials)
        : \App\Models\User::initialsFrom($name);
    $url = is_string($url) ? trim($url) : '';
    $url = $url !== '' ? $url : null;
    $alt = $alt ?? $name;
@endphp

<span {{ $attributes->class(['user-avatar']) }} data-user-avatar>
    <span class="user-avatar-letters" aria-hidden="true">{{ $initials }}</span>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" data-user-avatar-img>
    @endif
</span>
