@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'fieldClass' => '',
    'inputClass' => '',
])

<label class="bd-field {{ $fieldClass }}">
    <span class="bd-field__label">{{ $label }}</span>
    <input
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'bd-field__input '.$inputClass]) }}
    >
</label>
