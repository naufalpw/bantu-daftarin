@props(['label', 'value', 'description', 'tone' => 'blue', 'name'])

<article class="bd-admin-metric bd-admin-metric--{{ $tone }}" data-admin-metric="{{ $name }}">
    <p>{{ $label }}</p>
    <strong>{{ $value }}</strong>
    <span>{{ $description }}</span>
</article>
