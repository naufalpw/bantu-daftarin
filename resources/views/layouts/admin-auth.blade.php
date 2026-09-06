<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title')) ?: 'Akses Admin' }} | Bantu Daftarin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bd-admin-auth-body">
<main id="main-content" class="bd-admin-auth-shell">
    <section class="bd-admin-auth-card" aria-labelledby="admin-auth-title">
        <a class="bd-admin-auth-brand" href="{{ route('home') }}">
            <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin">
        </a>

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
    </section>
</main>
</body>
</html>
