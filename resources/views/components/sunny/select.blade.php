@props([
    'name',
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'error' => null,
])

@php
    $id = $id ?? $name;
    $hasSessionErrors = isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag;
    $errorMessage = $error ?: ($hasSessionErrors && $errors->has($name) ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);
    $currentVal = old($name, $selected);
    $selectClass = 'sn-select' . ($hasError ? ' sn-select--error' : '');
@endphp

<div class="sn-form-group">
    @if ($label)
        <label for="{{ $id }}" class="sn-label">
            {{ $label }}
            @if ($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @required($required)
        @disabled($disabled)
        {{ $attributes->merge(['class' => $selectClass]) }}
    >
        @if ($placeholder)
            <option value="" @selected($currentVal === '' || $currentVal === null)>{{ $placeholder }}</option>
        @endif

        @if (!empty($options))
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string)$currentVal === (string)$optValue)>
                    {{ $optLabel }}
                </option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if ($hasError)
        <span class="sn-error-text">{{ $errorMessage }}</span>
    @elseif ($hint)
        <span class="sn-hint">{{ $hint }}</span>
    @endif
</div>
