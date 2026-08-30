@extends('layouts.guest')
@section('content')
<h1 class="text-2xl font-semibold">Reset password</h1>
<p class="mt-2 text-sm text-slate-600">Masukkan email akun Anda. Tautan reset akan dikirim melalui email.</p>
<form method="post" action="{{ route('password.email') }}" class="mt-6 space-y-4">@csrf<label class="block text-sm font-medium">Email<input name="email" type="email" required class="mt-1 w-full rounded-lg border-slate-300"></label><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white">Kirim tautan</button></form>
@endsection
