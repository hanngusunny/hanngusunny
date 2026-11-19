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

<x-sunny.input
    :name="$name"
    :id="$id"
    :label="$label"
    :type="$type"
    :value="$value"
    :placeholder="$placeholder"
    :required="$required"
    :disabled="$disabled"
    :icon="$icon"
    :iconRight="$iconRight"
    :hint="$hint"
    :error="$error"
    {{ $attributes }}
/>
