@extends('layouts.guest')
@section('content')
<div class="pb-flow-content">
    <p class="pb-kicker">Pemulihan akun</p>
    <h1>Atur ulang kata sandi</h1>
    <p>Masukkan email akun Anda. Jika cocok, kami akan mengirim tautan reset.</p>
    <form method="post" action="{{ route('password.email') }}" class="pb-auth-form">
        @csrf
        <label class="pb-field"><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <button class="pb-button pb-button--primary pb-button--wide" type="submit">Kirim tautan reset</button>
    </form>
    <a class="pb-back-link" href="{{ route('login') }}"><span aria-hidden="true">←</span> Kembali ke login</a>
</div>
@endsection
