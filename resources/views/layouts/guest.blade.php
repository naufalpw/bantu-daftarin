<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon')
    <title>{{ $title ?? 'Bantu Daftarin' }}</title>
    @vite(['resources/css/app.css', 'resources/css/phase-b.css', 'resources/js/app.js'])
</head>
<body class="{{ request()->is('admin/*') || ($isAdmin ?? false) ? 'min-h-screen bg-slate-100 text-slate-900' : 'phase-b pb-flow-body' }}">
<main id="main-content" class="{{ request()->is('admin/*') || ($isAdmin ?? false) ? 'mx-auto flex min-h-screen max-w-md items-center px-4 py-10' : 'pb-flow-shell' }}">
    <section class="{{ request()->is('admin/*') || ($isAdmin ?? false) ? 'w-full rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200' : 'pb-flow-card' }}">
        @if(request()->is('admin/*') || ($isAdmin ?? false))
            <a href="{{ route('login') }}" class="text-xl font-bold text-indigo-700">Bantu Daftarin</a>
            <p class="mt-1 mb-6 text-sm text-slate-500">Layanan administrasi NPWP yang mudah dipantau.</p>
        @else
            <a href="{{ route('home') }}" class="pb-flow-brand"><img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin"></a>
            <p class="pb-flow-intro">Layanan bantuan administrasi NPWP yang dapat dipantau dari akun Anda.</p>
        @endif
        @if(session('status')) <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif
        @if($errors->any()) <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        @yield('content')
    </section>
</main>
</body>
</html>
