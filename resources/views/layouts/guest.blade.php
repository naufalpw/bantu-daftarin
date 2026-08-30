<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Bantu Daftarin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto flex min-h-screen max-w-md items-center px-4 py-10">
    <section class="w-full rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <a href="{{ route('login') }}" class="text-xl font-bold text-indigo-700">Bantu Daftarin</a>
        <p class="mt-1 mb-6 text-sm text-slate-500">Layanan administrasi NPWP yang mudah dipantau.</p>
        @if(session('status')) <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif
        @if($errors->any()) <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        @yield('content')
    </section>
</main>
</body>
</html>
