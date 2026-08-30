@extends('layouts.guest')
@section('content')
<h1 class="text-2xl font-semibold">Masuk</h1>
<p class="mt-2 text-sm text-slate-600">Setelah password benar, kami akan mengirim OTP ke email Anda.</p>
<form method="post" action="{{ request()->is('admin/*') ? route('admin.login.store') : route('login.store') }}" class="mt-6 space-y-4">
    @csrf
    <label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-lg border-slate-300"></label>
    <label class="block text-sm font-medium">Password<input name="password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border-slate-300"></label>
    <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">Lanjutkan</button>
</form>
<div class="mt-5 flex justify-between text-sm"><a class="text-indigo-700 hover:underline" href="{{ route('password.request') }}">Lupa password?</a><a class="text-indigo-700 hover:underline" href="{{ route('register') }}">Daftar client</a></div>
@endsection
