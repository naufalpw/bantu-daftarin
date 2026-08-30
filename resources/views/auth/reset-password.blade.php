@extends('layouts.guest')
@section('content')
<h1 class="text-2xl font-semibold">Password baru</h1>
<form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">@csrf<input type="hidden" name="token" value="{{ $token }}"><label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ $email }}" required class="mt-1 w-full rounded-lg border-slate-300"></label><label class="block text-sm font-medium">Password baru<input name="password" type="password" minlength="12" required class="mt-1 w-full rounded-lg border-slate-300"></label><label class="block text-sm font-medium">Ulangi password<input name="password_confirmation" type="password" required class="mt-1 w-full rounded-lg border-slate-300"></label><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white">Simpan password</button></form>
@endsection
