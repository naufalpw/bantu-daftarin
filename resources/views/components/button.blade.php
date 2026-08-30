@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
    'disabled' => false,
])

@php($buttonClasses = 'bd-button bd-button--'.$variant)

@if($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $buttonClasses]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $buttonClasses]) }}>{{ $slot }}</button>
@endif
