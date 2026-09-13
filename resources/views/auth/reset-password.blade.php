@extends('layouts.guest')
@section('content')
<div class="pb-flow-content">
    <p class="pb-kicker">Pemulihan akun</p>
    <h1>Buat kata sandi baru</h1>
    <p>Gunakan kata sandi minimal 12 karakter yang tidak digunakan pada layanan lain.</p>
    <form method="post" action="{{ route('password.update') }}" class="pb-auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="pb-field"><span>Email</span><input name="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="auth-email-error" @enderror type="email" value="{{ $email }}" required autocomplete="email"></label>
        @error('email')<span id="auth-email-error" class="bd-phone-only pb-field__error">{{ $message }}</span>@enderror
        <label class="pb-field"><span>Kata sandi baru</span><input name="password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="auth-password-error" @enderror type="password" minlength="12" required autocomplete="new-password"></label>
        @error('password')<span id="auth-password-error" class="bd-phone-only pb-field__error">{{ $message }}</span>@enderror
        <label class="pb-field"><span>Ulangi kata sandi</span><input name="password_confirmation" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" @error('password_confirmation') aria-describedby="auth-password_confirmation-error" @enderror type="password" required autocomplete="new-password"></label>
        @error('password_confirmation')<span id="auth-password_confirmation-error" class="bd-phone-only pb-field__error">{{ $message }}</span>@enderror
        <button class="pb-button pb-button--primary pb-button--wide" type="submit">Simpan kata sandi</button>
    </form>
</div>
@endsection
