@props([
    'action',
    'value',
    'active' => false,
])

<button
    type="button"
    wire:key="admin-filter-{{ $action }}-{{ $value }}"
    wire:click="{{ $action }}('{{ $value }}')"
    @class(['bd-admin-filter-control', 'is-active' => $active])
    aria-pressed="{{ $active ? 'true' : 'false' }}"
>
    {{ $slot }}
</button>
