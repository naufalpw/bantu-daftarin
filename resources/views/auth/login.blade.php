@php($authLayout = request()->is('admin/*') ? 'layouts.admin-auth' : 'layouts.marketing')
@extends($authLayout)

@if(request()->is('admin/*'))
@section('title', 'Masuk Admin')
@section('content')
<div class="bd-admin-auth-content">
    <p class="bd-admin-kicker">AKSES ADMIN</p>
    <h1 id="admin-auth-title">Masuk sebagai Super Admin</h1>
    <p class="bd-admin-auth-content__intro">Gunakan akun admin yang aktif. Setelah kata sandi benar, kode OTP dikirim ke email terdaftar.</p>

    <form method="post" action="{{ route('admin.login.store') }}" class="bd-admin-auth-form">
        @csrf
        <label class="bd-admin-field">
            <span>Email</span>
            <input name="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="auth-email-error" @enderror type="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
        @error('email')<span id="auth-email-error" class="bd-admin-field__error">{{ $message }}</span>@enderror
        </label>
        <label class="bd-admin-field">
            <span>Kata sandi</span>
            <input name="password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="auth-password-error" @enderror type="password" required autocomplete="current-password">
        @error('password')<span id="auth-password-error" class="bd-admin-field__error">{{ $message }}</span>@enderror
        </label>
        <button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide" type="submit">Masuk dan kirim OTP</button>
    </form>

    <p class="bd-admin-auth-note">Akses ini khusus untuk pengelola Bantu Daftarin.</p>
</div>
@endsection
@else
@section('body_class', 'pb-auth-body pb-login-body')
@section('content')
<main id="main-content" class="pb-login-page">
    <section class="pb-login-visual" aria-label="Bantu Daftarin">
        <a class="pb-login-brand" href="{{ route('home') }}">
            <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin">
        </a>
        <img class="pb-login-visual__art" src="{{ asset('images/figma/auth/login-illustration.svg') }}" alt="Ilustrasi pengajuan NPWP" fetchpriority="high" decoding="async">
    </section>

    <section class="pb-login-form-side" aria-labelledby="login-title">
        <a class="pb-login-back" href="{{ route('home') }}">Kembali ke beranda</a>
        <div class="pb-login-card">
            <h1 id="login-title">Selamat Datang</h1>

            @if(session('status'))<div class="pb-alert pb-alert--success" role="status">{{ session('status') }}</div>@endif
            @if($errors->auth->any())<div class="pb-alert pb-alert--danger" role="alert" data-auth-feedback><ul>@foreach($errors->auth->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if(session('verification_email'))
                <div class="pb-verification-state">
                    <strong>Verifikasi email Anda</strong>
                    <p>Kami mengirim link verifikasi ke <b>{{ session('verification_email') }}</b>. Buka link tersebut sebelum login.</p>
                    <form method="post" action="{{ route('verification.send') }}">@csrf<input type="hidden" name="email" value="{{ session('verification_email') }}"><button class="pb-button pb-button--secondary" type="submit">Kirim ulang link verifikasi</button></form>
                </div>
            @endif

            <form method="post" action="{{ route('login.store') }}" class="pb-login-form">
                @csrf
                <label class="pb-field"><span>Email</span><input name="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="auth-email-error" @enderror type="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<span id="auth-email-error" class="pb-field__error">{{ $message }}</span>@enderror</label>
                <label class="pb-field"><span>Kata sandi</span><input name="password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="auth-password-error" @enderror type="password" required autocomplete="current-password">@error('password')<span id="auth-password-error" class="pb-field__error">{{ $message }}</span>@enderror</label>
                <a class="pb-login-form__forgot" href="{{ route('password.request') }}">Lupa kata sandi?</a>
                <button class="pb-button pb-button--primary pb-button--wide" type="submit">Masuk</button>
            </form>

            <div class="pb-login-divider" aria-hidden="true"><span></span><b>Atau</b><span></span></div>
            <a class="pb-login-register-card" href="{{ route('register') }}">
                <span class="pb-login-register-card__icon" aria-hidden="true"><img src="{{ asset('images/figma/auth/login-user.svg') }}" alt=""></span>
                <span><strong>Pengguna baru?</strong><small>Daftar di sini</small></span>
            </a>
        </div>
    </section>
</main>
@endsection
@endif
