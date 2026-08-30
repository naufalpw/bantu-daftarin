@props(['variant' => 'default', 'chatUrl' => null])

<header class="bd-site-header {{ $variant === 'payment' ? 'bd-site-header--payment' : '' }}" data-node-id="208:10635" data-name="Component 14">
    <a href="{{ route('home') }}" aria-label="Bantu Daftarin">
        <img class="bd-site-header__logo" src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantudaftarin">
    </a>

    <nav class="bd-site-header__nav" aria-label="Navigasi utama">
        <a class="bd-site-header__nav-link" href="#cara-mudah">
            <span>Cara Buat NPWP</span>
            <img src="{{ asset('images/figma/home/caret.svg') }}" alt="">
        </a>
        <a class="bd-site-header__nav-link" href="{{ route('qna') }}" aria-label="QnA">
            <span>QnA</span>
            <img src="{{ asset('images/figma/home/caret.svg') }}" alt="">
        </a>
        <a class="bd-site-header__nav-link" href="#testimoni">
            <span>Testimoni</span>
            <img src="{{ asset('images/figma/home/caret.svg') }}" alt="">
        </a>
    </nav>

    <div class="bd-site-header__actions">
        <a class="bd-site-header__chat" href="{{ $chatUrl ?? route('login') }}">
            <img src="{{ asset('images/figma/home/header-subtract.svg') }}" alt="">
            <span>Live Chat</span>
        </a>
        @if($variant === 'payment')
            <a class="bd-site-header__login" href="{{ auth()->check() ? route('client.dashboard') : route('login') }}">Login</a>
        @else
            <a class="bd-site-header__cart" href="{{ route('login') }}" aria-label="Masuk untuk melihat pesanan">
                <img src="{{ asset('images/figma/home/live-chat.svg') }}" alt="">
            </a>
            <a class="bd-site-header__user" href="{{ route('login') }}" aria-label="Masuk">
                <img src="{{ asset('images/figma/home/header-user.svg') }}" alt="">
            </a>
        @endif
    </div>
</header>
