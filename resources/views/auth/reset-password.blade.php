@extends('layouts.guest')
@section('content')
<div class="pb-flow-content">
    <p class="pb-kicker">Pemulihan akun</p>
    <h1>Buat kata sandi baru</h1>
    <p>Gunakan kata sandi minimal 12 karakter yang tidak digunakan pada layanan lain.</p>
    <form method="post" action="{{ route('password.update') }}" class="pb-auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="pb-field"><span>Email</span><input name="email" type="email" value="{{ $email }}" required autocomplete="email"></label>
        <label class="pb-field"><span>Kata sandi baru</span><input name="password" type="password" minlength="12" required autocomplete="new-password"></label>
        <label class="pb-field"><span>Ulangi kata sandi</span><input name="password_confirmation" type="password" required autocomplete="new-password"></label>
        <button class="pb-button pb-button--primary pb-button--wide" type="submit">Simpan kata sandi</button>
    </form>
</div>
@endsection
