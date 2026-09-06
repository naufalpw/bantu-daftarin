@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard.svg', 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Pengajuan', 'route' => 'admin.applications.index', 'icon' => 'applications.svg', 'active' => request()->routeIs('admin.applications.*', 'admin.results.*')],
        ['label' => 'Dokumen', 'route' => 'admin.documents.index', 'icon' => 'document.svg', 'active' => request()->routeIs('admin.documents.*')],
        ['label' => 'Dukungan', 'route' => 'admin.support.index', 'icon' => 'support.svg', 'active' => request()->routeIs('admin.support.*', 'admin.chat.*')],
        ['label' => 'Aktivitas', 'route' => 'admin.activity.index', 'icon' => 'activity.svg', 'active' => request()->routeIs('admin.activity.*')],
        ['label' => 'Pengguna', 'route' => 'admin.users.index', 'icon' => 'users.svg', 'active' => request()->routeIs('admin.users.*')],
    ];
@endphp

<aside id="admin-navigation" class="bd-admin-sidebar" data-admin-drawer aria-label="Navigasi administrasi">
    <div class="bd-admin-sidebar__brand-row">
        <a class="bd-admin-sidebar__brand" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin">
            <span>Admin</span>
        </a>
        <button class="bd-admin-sidebar__close" type="button" data-admin-drawer-close aria-label="Tutup menu administrasi">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <nav class="bd-admin-sidebar__nav" aria-label="Menu utama">
        <ul>
            @foreach($navigation as $item)
                <li>
                    <a href="{{ route($item['route']) }}" @class(['bd-admin-nav-link', 'is-active' => $item['active']]) @if($item['active']) aria-current="page" @endif>
                        <img class="bd-admin-nav-link__icon" src="{{ asset('images/figma/admin/sidebar/'.$item['icon']) }}" alt="" aria-hidden="true">
                        <span>{{ $item['label'] }}@if($item['route'] === 'admin.support.index') <livewire:global-chat-notifier /> @endif</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="bd-admin-sidebar__footer">
        <div class="bd-admin-sidebar__identity">
            <span class="bd-admin-avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span><strong>{{ auth()->user()->name }}</strong><small>Super Admin</small></span>
        </div>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="bd-admin-logout" type="submit">Logout</button>
        </form>
    </div>
</aside>
