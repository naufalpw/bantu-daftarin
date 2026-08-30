@php($authLayout = request()->is('admin/*') ? 'layouts.guest' : 'layouts.marketing')
@extends($authLayout)

@if(request()->is('admin/*'))
@section('content')
<h1 class="text-2xl font-semibold">Masuk</h1>
<p class="mt-2 text-sm text-slate-600">Setelah password benar, kami akan mengirim OTP ke email Anda.</p>
<form method="post" action="{{ request()->is('admin/*') ? route('admin.login.store') : route('login.store') }}" class="mt-6 space-y-4">
    @csrf
    <label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-lg border-slate-300"></label>
    <label class="block text-sm font-medium">Password<input name="password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border-slate-300"></label>
    <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">Lanjutkan</button>
</form>
<div class="mt-5 flex justify-between text-sm"><a class="text-indigo-700 hover:underline" href="{{ route('password.request') }}">Lupa password?</a><a class="text-indigo-700 hover:underline" href="{{ route('register') }}">Daftar client</a></div>
@endsection
@else
@section('body_class', 'bd-auth-body')
@section('content')
<main class="bd-auth-page" data-node-id="208:10934" data-name="Desktop">
    <div class="bd-auth-stage">
        <section class="bd-auth-visual" aria-label="Bantu Daftarin">
            <img class="bd-auth-visual__logo" src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantudaftarin">
            <div class="bd-auth-visual__illustration" aria-hidden="true">
                <img src="{{ asset('images/figma/auth/login-illustration.png') }}" alt="">
            </div>
        </section>

        <section class="bd-auth-card" aria-labelledby="login-title">
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

            <div class="bd-auth-form">
                <h1 id="login-title" class="bd-auth-form__intro">Selamat Datang</h1>
                <form method="post" action="{{ route('login.store') }}" class="bd-auth-form__fields">
                    @csrf
                    <x-form-input name="email" label="ID Pengguna" type="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required />
                    <x-form-input name="password" label="Kata Sandi" type="password" placeholder="Masukan kata sandi anda" autocomplete="current-password" required />
                    <a class="bd-auth-form__forgot" href="{{ route('password.request') }}">Lupa Kata Sendi</a>
                    <x-button type="submit" variant="primary" class="bd-auth-form__submit">Masuk</x-button>
                </form>

                <div class="bd-auth-form__separator" aria-hidden="true">Atau</div>

                <a class="bd-auth-register" href="{{ route('daftar') }}">
                    <span class="bd-auth-register__icon">
                        <img src="{{ asset('images/figma/auth/login-user.svg') }}" alt="">
                    </span>
                    <span class="bd-auth-register__copy">
                        <strong>Pengguna Baru?</strong>
                        <span>Daftar Disini</span>
                    </span>
                </a>
            </div>
        </section>
    </div>
</main>
@endsection
@endif
