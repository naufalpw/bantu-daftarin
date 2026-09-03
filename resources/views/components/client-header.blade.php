@props(['chatUrl' => null])

@php
    $resolvedChatUrl = $chatUrl;

    if (! $resolvedChatUrl && isset($thread) && $thread?->public_id) {
        $resolvedChatUrl = route('client.chat.show', $thread->public_id);
    }

    if (! $resolvedChatUrl && isset($application) && $application?->chatThread?->public_id) {
        $resolvedChatUrl = route('client.chat.show', $application->chatThread->public_id);
    }

    $resolvedChatUrl ??= route('client.dashboard').'#client-applications';
@endphp

<header class="bd-client-header" data-node-id="208:10635" data-name="Authenticated client header">
    <a class="bd-client-header__brand" href="{{ route('client.dashboard') }}" aria-label="Bantu Daftarin - dashboard">
        <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantudaftarin">
    </a>

    <nav class="bd-client-header__nav" aria-label="Navigasi client">
        <a href="{{ route('client.services.index') }}">Layanan</a>
        <a href="{{ route('client.activity.index') }}">Aktivitas</a>
        <a href="{{ route('qna') }}">QnA</a>
    </nav>

    <div class="bd-client-header__actions">
        <a class="bd-client-header__chat" href="{{ $resolvedChatUrl }}">
            <img src="{{ asset('images/figma/home/header-subtract.svg') }}" alt="">
            <span>Live Chat</span>
        </a>

        <div class="bd-client-header__profile">
            <span class="bd-client-header__avatar" aria-hidden="true">
                <img src="{{ asset('images/figma/home/header-user.svg') }}" alt="">
            </span>
            <span class="bd-client-header__name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="bd-client-header__logout">Keluar</button>
            </form>
        </div>
    </div>
</header>
