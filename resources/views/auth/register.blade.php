@extends('layouts.marketing')

@section('body_class', 'bd-register-body')
@section('content')
<main class="bd-register-page" data-node-id="208:21025" data-name="Desktop">
    <a class="bd-register-back" href="{{ route('login') }}">
        <img src="{{ asset('images/figma/register/back.svg') }}" alt="">
        <span>Kembali</span>
    </a>

    <section class="bd-register-intro" aria-labelledby="register-title">
        <span class="bd-register-intro__icon">
            <img src="{{ asset('images/figma/register/user-add.svg') }}" alt="">
        </span>
        <h1 id="register-title">Buat AKun baru</h1>
        <p>Daftar untuk membuat akun dan mulai proses pendaftaran NPWP anda</p>
    </section>

    <section class="bd-register-card" aria-labelledby="register-form-title">
        @if(session('status') || $errors->any())
            <div class="bd-form-feedback">
                @if(session('status'))
                    <div class="bd-feedback-status">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="bd-feedback-errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
            </div>
        @endif
        <form method="post" action="{{ route('register.store') }}" class="bd-register-form">
            @csrf
            <h2 id="register-form-title" class="bd-register-form__intro">From Pendaftaran</h2>
            <x-form-input field-class="bd-register-field" name="name" label="Nama Lengkap" value="{{ old('name') }}" placeholder="Masukan nama lengkap anda" autocomplete="name" required />
            <x-form-input field-class="bd-register-field" name="email" label="Email" type="email" value="{{ old('email') }}" placeholder="Masukan Email anda" autocomplete="email" required />
            <x-form-input field-class="bd-register-field" name="password" label="Kata Sandi" type="password" placeholder="Masukan kata sandi anda" autocomplete="new-password" required />
            <x-form-input field-class="bd-register-field" name="password_confirmation" label="Konfirmasi Kata Sandi" type="password" placeholder="Masukan ulang kata sandi anda" autocomplete="new-password" required />
            <x-button type="submit" variant="primary" class="bd-register-submit">Daftar Sekarang</x-button>
        </form>
    </section>
</main>
@endsection
