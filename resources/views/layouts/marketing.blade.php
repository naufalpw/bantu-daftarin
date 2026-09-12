<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon')
    <title>@yield('title', 'Bantu Daftarin')</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @vite(['resources/css/app.css', 'resources/css/phase-b.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="phase-b @yield('body_class', 'pb-public-body')">
    <a class="pb-skip-link" href="#main-content">Lewati ke konten utama</a>
    @yield('content')
    @livewireScripts
</body>
</html>
