@props([
    'name',
    'id' => null,
    'label' => null,
    'value' => null,
    'rows' => 4,
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
    $textareaClass = 'sn-textarea' . ($hasError ? ' sn-textarea--error' : '');
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

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @required($required)
        @disabled($disabled)
        {{ $attributes->merge(['class' => $textareaClass]) }}
    >{{ old($name, $value ?? $slot) }}</textarea>

    @if ($hasError)
        <span class="sn-error-text">{{ $errorMessage }}</span>
    @elseif ($hint)
        <span class="sn-hint">{{ $hint }}</span>
    @endif
</div>
