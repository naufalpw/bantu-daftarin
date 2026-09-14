@php($resendCooldownSeconds = max(0, (int) ($resendCooldownSeconds ?? 0)))
@extends(($isAdmin ?? false) ? 'layouts.admin-auth' : 'layouts.guest')
@section('title', ($isAdmin ?? false) ? 'Verifikasi Admin' : 'Verifikasi Login')
@section('content')
@if($isAdmin ?? false)
<div class="bd-admin-auth-content">
    <p class="bd-admin-kicker">VERIFIKASI ADMIN</p>
    <h1 id="admin-auth-title">Masukkan kode OTP</h1>
    <p class="bd-admin-auth-content__intro">Masukkan 6 digit OTP yang dikirim ke email terdaftar. Satu OTP hanya dapat dipakai sekali.</p>
    <form method="post" action="{{ route('auth.otp.verify') }}" class="bd-admin-auth-form">
        @csrf
        <label class="bd-admin-field bd-admin-field--otp">
            <span>Kode OTP</span>
            <input class="bd-otp-input" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}" @error('code') aria-describedby="otp-code-error" @enderror>
            @error('code')<span id="otp-code-error" class="bd-admin-field__error">{{ $message }}</span>@enderror
        </label>
        <button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide" type="submit">Verifikasi kode</button>
    </form>
    <div class="bd-admin-auth-resend" data-otp-resend data-otp-resend-remaining="{{ $resendCooldownSeconds }}">
        <p data-otp-resend-waiting aria-live="polite" @if($resendCooldownSeconds === 0) hidden @endif>
            Kirim ulang OTP tersedia dalam <span data-otp-countdown>{{ $resendCooldownSeconds }}</span> detik.
        </p>
        <p data-otp-resend-ready aria-live="polite" @if($resendCooldownSeconds > 0) hidden @endif>
            Belum menerima OTP? Anda dapat mengirim ulang sekarang.
        </p>
        <form method="post" action="{{ route('auth.otp.resend') }}">
            @csrf
            <button type="submit" data-otp-resend-button @disabled($resendCooldownSeconds > 0) aria-disabled="{{ $resendCooldownSeconds > 0 ? 'true' : 'false' }}">Kirim ulang OTP</button>
        </form>
    </div>
</div>
@else
<div @class(['pb-flow-content' => !($isAdmin ?? false)])>
<p @class(['pb-kicker' => !($isAdmin ?? false), 'hidden' => ($isAdmin ?? false)])>Verifikasi login</p>
<h1 class="text-2xl font-bold">Masukkan kode OTP</h1>
<p class="mt-2 text-sm text-slate-600">Masukkan 6 digit OTP yang dikirim ke email terdaftar. Satu OTP hanya dapat dipakai sekali.</p>
<form method="post" action="{{ route('auth.otp.verify') }}" class="mt-6 space-y-4 pb-auth-form">
    @csrf
    <label class="block text-sm font-medium">Kode OTP<input class="bd-otp-input mt-1" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}" @error('code') aria-describedby="otp-code-error" @enderror>@error('code')<span id="otp-code-error" class="pb-field__error">{{ $message }}</span>@enderror</label>
    <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white pb-button pb-button--primary pb-button--wide">Verifikasi kode</button>
</form>
<div class="mt-4 text-center text-sm text-slate-600" data-otp-resend data-otp-resend-remaining="{{ $resendCooldownSeconds }}">
    <p data-otp-resend-waiting aria-live="polite" @if($resendCooldownSeconds === 0) hidden @endif>
        Kirim ulang OTP tersedia dalam <span data-otp-countdown>{{ $resendCooldownSeconds }}</span> detik.
    </p>
    <p data-otp-resend-ready aria-live="polite" @if($resendCooldownSeconds > 0) hidden @endif>
        Belum menerima OTP? Anda dapat mengirim ulang sekarang.
    </p>
    <form method="post" action="{{ route('auth.otp.resend') }}" class="mt-2">
        @csrf
        <button type="submit" data-otp-resend-button @disabled($resendCooldownSeconds > 0) class="text-indigo-700 hover:underline disabled:cursor-not-allowed disabled:text-slate-400" aria-disabled="{{ $resendCooldownSeconds > 0 ? 'true' : 'false' }}">Kirim ulang OTP</button>
    </form>
</div>
</div>
@endif
@endsection
