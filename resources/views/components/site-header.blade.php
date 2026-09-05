@php
    $isHome = request()->routeIs('home');
    $homeAnchor = fn (string $anchor): string => $isHome ? '#'.$anchor : route('home').'#'.$anchor;
@endphp

<header class="pb-public-header">
    <div class="pb-public-header__inner">
        <a class="pb-brand" href="{{ $homeAnchor('beranda') }}" aria-label="Bantu Daftarin, buka Beranda">
            <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin">
        </a>

        <nav class="pb-public-nav" aria-label="Navigasi publik">
            <a href="{{ $homeAnchor('beranda') }}" @if($isHome) aria-current="page" class="is-active" @endif>Beranda</a>
            <a href="{{ $homeAnchor('layanan') }}">Layanan</a>
            <a href="{{ $homeAnchor('cara-kerja') }}">Cara Kerja</a>
            <a href="{{ $homeAnchor('qna') }}">FAQ</a>
        </nav>

        <div class="pb-public-header__actions">
            <a class="pb-button pb-button--text" href="{{ route('login') }}">Masuk</a>
            <a class="pb-button pb-button--primary" href="{{ route('register') }}">Daftar</a>
        </div>

        <button class="pb-public-menu-button" type="button" data-public-menu-button aria-expanded="false" aria-controls="public-menu">
            <span class="sr-only">Buka menu</span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </button>
    </div>

    <nav id="public-menu" class="pb-public-menu" data-public-menu aria-label="Menu publik" hidden>
        <div class="pb-public-menu__inner">
            <a href="{{ $homeAnchor('beranda') }}">Beranda</a>
            <a href="{{ $homeAnchor('layanan') }}">Layanan</a>
            <a href="{{ $homeAnchor('cara-kerja') }}">Cara Kerja</a>
            <a href="{{ $homeAnchor('qna') }}">FAQ</a>
            <a href="{{ route('login') }}">Masuk</a>
            <a class="pb-button pb-button--primary" href="{{ route('register') }}">Daftar</a>
        </div>
    </nav>
</header>
