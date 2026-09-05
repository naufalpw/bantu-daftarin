@php($authLayout = request()->is('admin/*') ? 'layouts.guest' : 'layouts.marketing')
@extends($authLayout)

@if(request()->is('admin/*'))
@section('content')
<h1 class="text-2xl font-bold">Masuk</h1>
<p class="mt-2 text-sm text-slate-600">Setelah password benar, kami akan mengirim OTP ke email Anda.</p>
<form method="post" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">@csrf<label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-lg border-slate-300"></label><label class="block text-sm font-medium">Password<input name="password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border-slate-300"></label><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">Lanjutkan</button></form>
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
            @if($errors->any())<div class="pb-alert pb-alert--danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if(session('verification_email'))
                <div class="pb-verification-state">
                    <strong>Verifikasi email Anda</strong>
                    <p>Kami mengirim link verifikasi ke <b>{{ session('verification_email') }}</b>. Buka link tersebut sebelum login.</p>
                    <form method="post" action="{{ route('verification.send') }}">@csrf<input type="hidden" name="email" value="{{ session('verification_email') }}"><button class="pb-button pb-button--secondary" type="submit">Kirim ulang link verifikasi</button></form>
                </div>
            @endif

            <form method="post" action="{{ route('login.store') }}" class="pb-login-form">
                @csrf
                <label class="pb-field"><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
                <label class="pb-field"><span>Kata sandi</span><input name="password" type="password" required autocomplete="current-password"></label>
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
