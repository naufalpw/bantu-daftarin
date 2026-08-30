@extends('layouts.guest')
@section('content')
<h1 class="text-2xl font-semibold">Buat akun client</h1>
<form method="post" action="{{ route('register.store') }}" class="mt-6 space-y-4">
    @csrf
    <label class="block text-sm font-medium">Nama<input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-slate-300"></label>
    <label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border-slate-300"></label>
    <label class="block text-sm font-medium">Password<input name="password" type="password" required minlength="12" class="mt-1 w-full rounded-lg border-slate-300"></label>
    <label class="block text-sm font-medium">Ulangi password<input name="password_confirmation" type="password" required class="mt-1 w-full rounded-lg border-slate-300"></label>
    <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white">Daftar</button>
</form>
<p class="mt-5 text-sm">Sudah punya akun? <a class="text-indigo-700 hover:underline" href="{{ route('login') }}">Masuk</a></p>
@endsection
