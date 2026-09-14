<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon')
    <title>{{ trim($__env->yieldContent('title')) ?: 'Panel Admin' }} | Bantu Daftarin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bd-admin-body">
<a class="bd-admin-skip-link" href="#main-content">Lewati ke konten utama</a>

<div class="bd-admin-shell" data-admin-shell>
    <x-admin.sidebar />
    <button class="bd-admin-backdrop" type="button" data-admin-drawer-close aria-label="Tutup menu administrasi" hidden></button>

    <div class="bd-admin-workspace">
        <x-admin.header />

        <main id="main-content" class="bd-admin-main" tabindex="-1">
            @if(session('status'))
                <div class="bd-admin-alert bd-admin-alert--success" role="status">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="bd-admin-alert bd-admin-alert--danger" role="alert">
                    <ul>
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<x-presence-heartbeat />
@livewireScripts
</body>
</html>
