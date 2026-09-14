@props(['name', 'size' => 18])

<svg {{ $attributes->class('bd-ui-icon') }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('home')
        @case('dashboard')
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
        @case('documents')
            <path d="M7 3.5h7l4 4v12.8H7a2 2 0 0 1-2-2V5.5a2 2 0 0 1 2-2Z" />
            <path d="M14 3.5v4h4M8.8 12h6.4M8.8 15.5h4.8" />
            <path d="m14.5 18 1.2 1.2 2.5-2.8" />
            @break
        @case('help')
            <path d="M20.5 11.5a8.5 8.5 0 1 1-3.1-6.6" />
            <path d="M9.7 9a2.5 2.5 0 1 1 3.9 2.1c-1 .7-1.6 1.2-1.6 2.4M12 17.3h.01" />
            @break
        @case('support')
            <path d="M5.2 17.8 3.8 21l3.5-1.2c1.3.7 2.9 1.1 4.7 1.1 5 0 8.8-3.5 8.8-8.2S17 4.5 12 4.5s-8.8 3.5-8.8 8.2c0 1.9.7 3.7 2 5.1Z" />
            <path d="M8.5 12.6h7M8.5 9.5h4.8" />
            @break
        @case('review')
            <path d="M8 4.5h8M9 3v3M15 3v3M6 5.5h12a1.5 1.5 0 0 1 1.5 1.5v13H4.5V7A1.5 1.5 0 0 1 6 5.5Z" />
            <path d="m8.5 13 2.1 2 4.8-5" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 20c.6-4 3.1-6 7-6s6.4 2 7 6" />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19c.5-3.6 2.4-5.4 5.5-5.4s5 1.8 5.5 5.4" />
            <path d="M15 5.4a3 3 0 0 1 0 5.8M16.1 13.8c2.5.5 3.9 2.2 4.4 5.2" />
            @break
        @case('activity')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.5V12l3 2" />
            @break
        @case('search')
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path d="m15.5 15.5 4 4" />
            @break
        @case('open')
            <path d="M13 5h6v6M11 13l8-8" />
            <path d="M18 13v5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18V7a1.5 1.5 0 0 1 1.5-1.5H11" />
            @break
        @case('download')
            <path d="M12 4v10M8.5 10.5 12 14l3.5-3.5" />
            <path d="M5 18.5h14" />
            @break
        @case('copy')
            <rect x="8" y="8" width="11" height="11" rx="2" />
            <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" />
            @break
        @case('payment')
            <rect x="3.5" y="6" width="17" height="13" rx="2" />
            <path d="M3.5 10h17M7.5 15h3" />
            @break
        @case('camera')
            <path d="M4 8.5h3l1.4-2h7.2l1.4 2h3v10H4Z" />
            <circle cx="12" cy="13.5" r="3.2" />
            @break
        @case('arrow-right')
            <path d="M5 12h14M14 7l5 5-5 5" />
            @break
        @case('arrow-left')
            <path d="M19 12H5M10 7l-5 5 5 5" />
            @break
        @case('archive')
            <path d="M4 7.5h16v12H4Z" />
            <path d="M3 4.5h18v3H3ZM9 11.5h6" />
            @break
        @case('archive-restore')
            <path d="M4 7.5h16v12H4Z" />
            <path d="M3 4.5h18v3H3ZM9 11.5h6" />
            <path d="m8 16-2-2 2-2M6 14h5" />
            @break
    @endswitch
</svg>
