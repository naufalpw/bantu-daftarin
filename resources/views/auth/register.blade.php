@extends('layouts.marketing')

@section('body_class', 'pb-auth-body pb-register-body')
@section('content')
<main id="main-content" class="pb-register-page">
    <a class="pb-register-back" href="{{ route('login') }}"><span aria-hidden="true">←</span> Kembali ke login</a>

    <section class="pb-register-intro" aria-labelledby="register-page-title">
        <span class="pb-register-intro__icon" aria-hidden="true"><img src="{{ asset('images/figma/register/user-add.svg') }}" alt=""></span>
        <h1 id="register-page-title">Buat Akun Baru</h1>
        <p>Daftar untuk membuat akun dan memulai pengajuan NPWP. Setelah mendaftar, verifikasi email Anda sebelum masuk.</p>
    </section>

    <section class="pb-register-card" aria-labelledby="register-title">
        <h2 id="register-title">Form Pendaftaran</h2>

        @if($errors->any())
            <div class="pb-alert pb-alert--danger" role="alert">Periksa kembali data yang ditandai di bawah.</div>
        @endif

        <form method="post" action="{{ route('register.store') }}" class="pb-register-form">
            @csrf
            <label class="pb-field">
                <span>Nama lengkap</span>
                <input name="name" type="text" value="{{ old('name') }}" required autocomplete="name" placeholder="Masukkan nama lengkap Anda" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @error('name') aria-describedby="name-error" @enderror>
                @error('name')<small id="name-error" class="pb-field__error">{{ $message }}</small>@enderror
            </label>

            <label class="pb-field">
                <span>Email</span>
                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Masukkan email Anda" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="email-error" @enderror>
                @error('email')<small id="email-error" class="pb-field__error">{{ $message }}</small>@enderror
            </label>

            <label class="pb-field">
                <span>Kata sandi</span>
                <input name="password" type="password" required autocomplete="new-password" placeholder="Masukkan kata sandi Anda" aria-describedby="password-help @error('password') password-error @enderror" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                <small id="password-help">Gunakan minimal 12 karakter.</small>
                @error('password')<small id="password-error" class="pb-field__error">{{ $message }}</small>@enderror
            </label>

            <label class="pb-field">
                <span>Konfirmasi kata sandi</span>
                <input name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Masukkan ulang kata sandi Anda" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" @error('password_confirmation') aria-describedby="password-confirmation-error" @enderror>
                @error('password_confirmation')<small id="password-confirmation-error" class="pb-field__error">{{ $message }}</small>@enderror
            </label>

            <button class="pb-button pb-button--primary pb-button--wide" type="submit">Daftar dan kirim verifikasi</button>
        </form>
    </section>

    <p class="pb-register-login">Sudah memiliki akun? <a href="{{ route('login') }}">Masuk</a></p>
</main>
@endsection
