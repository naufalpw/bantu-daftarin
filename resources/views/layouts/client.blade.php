<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#031a5a">
    @include('components.favicon')
    <title>{{ $title ?? 'Bantu Daftarin' }}</title>
    @vite(['resources/css/app.css', 'resources/css/phase-b.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="phase-b pb-client-body @yield('body_class')">
    <a class="pb-skip-link" href="#main-content">Lewati ke konten utama</a>
    <x-client-header :context-title="trim($__env->yieldContent('context_title'))" />

    <div class="pb-feedback" aria-live="polite">
        @if(session('status'))
            <div class="pb-alert pb-alert--success" role="status">{{ session('status') }}</div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="pb-alert pb-alert--danger" role="alert">
                <strong>Periksa kembali informasi berikut:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <main id="main-content" class="pb-main" tabindex="-1">
        @yield('content')
    </main>

    <x-mobile-bottom-nav />
    <x-presence-heartbeat />
    @livewireScripts
    @stack('scripts')
</body>
</html>
