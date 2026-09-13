@props(['contextTitle' => null])

@php
    $isDashboard = request()->routeIs('client.dashboard');
    $isServices = request()->routeIs('client.services.*', 'client.applications.create', 'npwp.*');
    $isGeneralSupportChat = (bool) request()->attributes->get('is_general_support_chat', false);
    $isApplications = request()->routeIs('client.applications.index', 'client.applications.show', 'client.payments.*', 'client.activity.*') || (request()->routeIs('client.chat.*') && ! $isGeneralSupportChat);
    $isHelp = request()->routeIs('qna') || $isGeneralSupportChat;
@endphp

<header class="pb-header">
    <div class="pb-header__inner">
        <a class="pb-brand" href="{{ route('client.dashboard') }}" aria-label="Bantu Daftarin, buka Beranda">
            <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin">
        </a>

        @if($contextTitle)
            <span class="pb-header__context">{{ $contextTitle }}</span>
        @endif

        <nav class="pb-desktop-nav" aria-label="Navigasi utama klien">
            <a href="{{ route('client.dashboard') }}" @class(['is-active' => $isDashboard]) @if($isDashboard) aria-current="page" @endif><x-ui-icon name="home" :size="17" /><span>Beranda</span></a>
            <a href="{{ route('client.services.index') }}" @class(['is-active' => $isServices]) @if($isServices) aria-current="page" @endif><x-ui-icon name="services" :size="17" /><span>Layanan</span></a>
            <a href="{{ route('client.applications.index') }}" @class(['is-active' => $isApplications]) @if($isApplications) aria-current="page" @endif><x-ui-icon name="application" :size="17" /><span>Pengajuan</span></a>
            <a href="{{ route('qna') }}" @class(['is-active' => $isHelp]) @if($isHelp) aria-current="page" @endif><x-ui-icon name="help" :size="17" /><span>Bantuan</span><livewire:global-chat-notifier /></a>
        </nav>

        <details class="pb-account" data-account-menu>
            <summary aria-expanded="false" aria-label="Buka menu akun">
                <span class="pb-account__initial" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="pb-account__label">Akun</span>
                <span class="pb-account__caret" aria-hidden="true"></span>
            </summary>
            <div class="pb-account__menu">
                <div class="pb-account__identity">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->email }}</span>
                </div>
                <a href="{{ route('qna') }}">Bantuan</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Keluar</button>
                </form>
            </div>
        </details>
    </div>
</header>
