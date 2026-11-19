@props([
    'name',
    'id' => null,
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'icon' => null,
    'iconRight' => null,
    'hint' => null,
    'error' => null,
])

@php
    $id = $id ?? $name;
    // Check if error is provided explicitly or present in Laravel's $errors MessageBag
    $hasSessionErrors = isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag;
    $errorMessage = $error ?: ($hasSessionErrors && $errors->has($name) ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);

    $wrapperClass = 'sn-input-wrapper'
        . ($icon ? ' sn-input-wrapper--has-icon-left' : '')
        . ($iconRight ? ' sn-input-wrapper--has-icon-right' : '');
    
    $inputClass = 'sn-input' . ($hasError ? ' sn-input--error' : '');
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

    <div class="{{ $wrapperClass }}">
        @if ($icon)
            <span class="sn-input-icon-left">
                <i class="{{ $icon }}" aria-hidden="true"></i>
            </span>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @required($required)
            @disabled($disabled)
            {{ $attributes->merge(['class' => $inputClass]) }}
        />

        @if ($iconRight)
            <span class="sn-input-icon-right">
                <i class="{{ $iconRight }}" aria-hidden="true"></i>
            </span>
        @endif
    </div>

    @if ($hasError)
        <span class="sn-error-text">{{ $errorMessage }}</span>
    @elseif ($hint)
        <span class="sn-hint">{{ $hint }}</span>
    @endif
</div>
