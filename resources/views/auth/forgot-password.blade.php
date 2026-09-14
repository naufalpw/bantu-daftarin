@extends('layouts.guest')
@section('content')
<div class="pb-flow-content">
    <p class="pb-kicker">Pemulihan akun</p>
    <h1>Atur ulang kata sandi</h1>
    <p>Masukkan email akun Anda. Jika cocok, kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
    <form method="post" action="{{ route('password.email') }}" class="pb-auth-form">
        @csrf
        <label class="pb-field"><span>Email</span><input name="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="auth-email-error" @enderror type="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<span id="auth-email-error" class="pb-field__error">{{ $message }}</span>@enderror</label>
        <button class="pb-button pb-button--primary pb-button--wide" type="submit">Kirim tautan pengaturan ulang</button>
    </form>
    <a class="pb-back-link" href="{{ route('login') }}"><span aria-hidden="true">←</span> Kembali ke login</a>
</div>
@endsection
