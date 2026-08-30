@extends('layouts.guest')
@section('content')
<h1 class="text-2xl font-semibold">Verifikasi login</h1>
<p class="mt-2 text-sm text-slate-600">Masukkan 6 digit OTP yang dikirim ke email terdaftar. Satu OTP hanya dapat dipakai sekali.</p>
<form method="post" action="{{ route('auth.otp.verify') }}" class="mt-6 space-y-4">
    @csrf
    <label class="block text-sm font-medium">Kode OTP<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus class="mt-1 w-full rounded-lg border-slate-300 text-center text-2xl tracking-[0.4em]"></label>
    <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white">Verifikasi</button>
</form>
<form method="post" action="{{ route('auth.otp.resend') }}" class="mt-4 text-center">@csrf<button class="text-sm text-indigo-700 hover:underline">Kirim ulang OTP</button></form>
@endsection
