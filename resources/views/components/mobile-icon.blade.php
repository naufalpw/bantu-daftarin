@props(['name', 'size' => 20])

<svg {{ $attributes->class('bd-mobile-icon') }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
    @switch($name)
        @case('home')
            <path d="M3.5 10.6 12 3.8l8.5 6.8v8.1a1.8 1.8 0 0 1-1.8 1.8H5.3a1.8 1.8 0 0 1-1.8-1.8v-8.1Z" />
            <path d="M9 20.5v-6.2h6v6.2" />
            @break
        @case('services')
            <rect x="3.5" y="4" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="4" width="7" height="7" rx="1.5" />
            <rect x="3.5" y="14" width="7" height="6" rx="1.5" />
            <rect x="13.5" y="14" width="7" height="6" rx="1.5" />
            @break
        @case('application')
            <path d="M7 3.5h7l4 4v13H7a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" />
            <path d="M14 3.5v4h4M9 12h6M9 16h6" />
            @break
        @case('help')
            <path d="M20.5 11.5a8.5 8.5 0 1 1-3.1-6.6" />
            <path d="M9.7 9a2.5 2.5 0 1 1 3.9 2.1c-1 .7-1.6 1.2-1.6 2.4M12 17.3h.01" />
            @break
        @case('review')
            <path d="M8 4.5h8M9 3v3M15 3v3M6 5.5h12a1.5 1.5 0 0 1 1.5 1.5v13H4.5V7A1.5 1.5 0 0 1 6 5.5Z" />
            <path d="m8.5 13 2.1 2 4.8-5" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 20c.6-4 3.1-6 7-6s6.4 2 7 6" />
            @break
        @case('activity')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.5V12l3 2" />
            @break
    @endswitch
</svg>
