@props([
    'name',
    'id' => null,
    'label' => null,
    'checked' => false,
    'value' => 1,
    'disabled' => false,
])

@php
    $id = $id ?? $name;
    $isChecked = old($name, $checked);
@endphp

<label for="{{ $id }}" class="sn-toggle-label">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        class="sn-toggle-input"
        @checked($isChecked)
        @disabled($disabled)
        {{ $attributes }}
    />
    <span class="sn-toggle-switch"></span>
    @if ($label || trim($slot) !== '')
        <span class="sn-toggle-text">{{ $label ?: $slot }}</span>
    @endif
</label>
