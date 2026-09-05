@php
    $isGeneralSupportChat = (bool) request()->attributes->get('is_general_support_chat', false);
    $items = [
        ['label' => 'Beranda', 'route' => 'client.dashboard', 'active' => request()->routeIs('client.dashboard')],
        ['label' => 'Layanan', 'route' => 'client.services.index', 'active' => request()->routeIs('client.services.*', 'client.applications.create', 'npwp.*')],
        ['label' => 'Pengajuan', 'route' => 'client.applications.index', 'active' => request()->routeIs('client.applications.index', 'client.applications.show', 'client.payments.*', 'client.activity.*') || (request()->routeIs('client.chat.*') && ! $isGeneralSupportChat)],
        ['label' => 'Bantuan', 'route' => 'qna', 'active' => request()->routeIs('qna') || $isGeneralSupportChat],
    ];
@endphp

<nav class="pb-bottom-nav" aria-label="Navigasi utama klien">
    @foreach($items as $item)
        <a href="{{ route($item['route']) }}" @class(['is-active' => $item['active']]) @if($item['active']) aria-current="page" @endif>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
