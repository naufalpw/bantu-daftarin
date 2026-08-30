<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Bantu Daftarin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<header class="border-b bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <a href="{{ auth()->user()?->isAdmin() ? route('admin.dashboard') : route('client.dashboard') }}" class="font-bold text-indigo-700">Bantu Daftarin</a>
        @auth
            <nav class="flex items-center gap-4 text-sm">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.applications.index') }}" class="hover:text-indigo-700">Aplikasi</a>
                @else
                    <a href="{{ route('client.services.index') }}" class="hover:text-indigo-700">Layanan</a>
                @endif
                <span class="text-slate-500">{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-lg border px-3 py-1.5 hover:bg-slate-100">Keluar</button></form>
            </nav>
        @endauth
    </div>
</header>
<main class="mx-auto max-w-6xl px-4 py-8">
    @if(session('status')) <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div> @endif
    @if($errors->any()) <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-3 text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    @yield('content')
</main>
@livewireScripts
</body>
</html>
