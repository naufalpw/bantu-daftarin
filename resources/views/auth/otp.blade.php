@extends('layouts.guest')
@php($resendCooldownSeconds = max(0, (int) ($resendCooldownSeconds ?? 0)))
@section('content')
<div @class(['pb-flow-content' => !($isAdmin ?? false)])>
<p @class(['pb-kicker' => !($isAdmin ?? false), 'hidden' => ($isAdmin ?? false)])>Verifikasi login</p>
<h1 class="text-2xl font-bold">Masukkan kode OTP</h1>
<p class="mt-2 text-sm text-slate-600">Masukkan 6 digit OTP yang dikirim ke email terdaftar. Satu OTP hanya dapat dipakai sekali.</p>
<form method="post" action="{{ route('auth.otp.verify') }}" class="mt-6 space-y-4 pb-auth-form">
    @csrf
    <label class="block text-sm font-medium">Kode OTP<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus class="mt-1 w-full rounded-lg border-slate-300 text-center text-2xl tracking-[0.4em]"></label>
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
<script>
    (() => {
        const container = document.querySelector('[data-otp-resend]');
        if (!container) return;

        const button = container.querySelector('[data-otp-resend-button]');
        const countdown = container.querySelector('[data-otp-countdown]');
        const waiting = container.querySelector('[data-otp-resend-waiting]');
        const ready = container.querySelector('[data-otp-resend-ready]');
        let remaining = Math.max(0, Number.parseInt(container.dataset.otpResendRemaining || '0', 10));

        const render = () => {
            const isWaiting = remaining > 0;
            countdown.textContent = String(remaining);
            waiting.hidden = !isWaiting;
            ready.hidden = isWaiting;
            button.disabled = isWaiting;
            button.setAttribute('aria-disabled', isWaiting ? 'true' : 'false');
        };

        render();
        if (remaining === 0) return;

        const timer = window.setInterval(() => {
            remaining -= 1;
            render();

            if (remaining === 0) {
                window.clearInterval(timer);
            }
        }, 1000);
    })();
</script>
@endsection
