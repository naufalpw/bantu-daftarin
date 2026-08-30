@php
    $paymentMethodClass = \App\Enums\PaymentMethod::class;
    $paymentStatusClass = \App\Enums\PaymentStatus::class;
    $selectedMethod = $payment?->payment_method instanceof $paymentMethodClass
        ? $payment->payment_method
        : $paymentMethodClass::BCA;
    $amount = number_format((float) $application->price_amount_snapshot, 0, ',', '.');
    $paymentStatus = match (true) {
        $payment?->status === $paymentStatusClass::PENDING && $payment->expires_at?->isPast() => 'Pembayaran kedaluwarsa',
        $payment?->status === $paymentStatusClass::PENDING => 'Menunggu pembayaran',
        $payment?->status === $paymentStatusClass::PAID => 'Pembayaran berhasil',
        $payment?->status === $paymentStatusClass::FAILED => 'Pembayaran gagal',
        $payment?->status === $paymentStatusClass::EXPIRED => 'Pembayaran kedaluwarsa',
        default => $application->status->label(),
    };
@endphp

@extends('layouts.marketing')

@section('body_class', 'bd-payment-body')
@section('content')
<x-site-header variant="payment" />

@if(in_array($state, ['selection', 'failure', 'waiting'], true))
    <main class="bd-payment-page" data-node-id="208:20682" data-name="/bayar">
        <a class="bd-payment-back" href="{{ route('client.applications.show', $application->public_id) }}">
            <img src="{{ asset('images/figma/payment/back.svg') }}" alt="">
            <span>Kembali</span>
        </a>

        <section class="bd-payment-card" aria-labelledby="payment-title">
            @if(session('status') || $errors->any())
                <div class="bd-payment-feedback">
                    @if(session('status'))
                        <div class="bd-feedback-status">{{ session('status') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="bd-feedback-errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                </div>
            @endif

            <div class="bd-payment-order">
                <p>Nomor pengajuan</p>
                <h1 id="payment-title">#{{ $payment?->reference_id ?? $application->public_id }}</h1>
                <p>Jenis Layanan</p>
                <span class="bd-payment-service-tag">{{ $application->service->name }}</span>
            </div>

            <div class="bd-payment-service-card">
                <h2>{{ $application->service->name }}</h2>
                @if($application->service->description)
                    <p>{{ $application->service->description }}</p>
                @endif
                <strong>Rp {{ $amount }}</strong>
            </div>

            <div class="bd-payment-divider" aria-hidden="true"></div>

            @if($state === 'waiting' && $payment)
                <div class="bd-payment-waiting" data-payment-state="waiting">
                    <div>
                        <p class="bd-payment-section-label">Status pembayaran</p>
                        <h2>{{ $paymentStatus }}</h2>
                        <p class="bd-payment-muted">Selesaikan pembayaran menggunakan instruksi dari provider. Status berhasil hanya diperbarui setelah webhook tervalidasi.</p>
                    </div>

                    @if($payment->virtualAccountNumber())
                        <div class="bd-payment-instruction">
                            <span>Nomor Virtual Account</span>
                            <div class="bd-payment-copy-row">
                                <strong>{{ $payment->virtualAccountNumber() }}</strong>
                                <button type="button" class="bd-payment-copy" data-copy-value="{{ $payment->virtualAccountNumber() }}">Salin</button>
                            </div>
                        </div>
                    @elseif($payment->qrString())
                        <div class="bd-payment-instruction bd-payment-instruction--qr">
                            <span>QR pembayaran dari Xendit</span>
                            @if($qrCodeImage)
                                <img class="bd-payment-qr-code" src="{{ $qrCodeImage }}" alt="QR pembayaran QRIS">
                            @else
                                <p role="status">QR pembayaran belum dapat ditampilkan. Silakan muat ulang halaman.</p>
                            @endif
                            <p>Gunakan aplikasi pembayaran yang mendukung QRIS untuk memindai kode ini.</p>
                        </div>
                    @elseif($payment->checkout_url)
                        <div class="bd-payment-instruction">
                            <span>Halaman pembayaran</span>
                            <a class="bd-payment-provider-link" href="{{ $payment->checkout_url }}" target="_blank" rel="noopener noreferrer">Buka halaman pembayaran</a>
                        </div>
                    @endif

                    @if($payment->expires_at)
                        <p class="bd-payment-expiry">Berlaku sampai {{ $payment->expires_at->format('d M Y, H:i') }} WIB.</p>
                    @endif
                </div>
            @else
                @if($state === 'failure')
                    <div class="bd-payment-alert" role="alert">{{ $paymentStatus }}. Pilih metode lain atau coba buat payment request baru.</div>
                @endif

                <form method="post" action="{{ route('client.payments.store', $application->public_id) }}" class="bd-payment-form" data-payment-form>
                    @csrf
                    <p class="bd-payment-section-label">Metode</p>
                    <div class="bd-payment-methods">
                        @foreach($paymentMethods as $method)
                            @php($isPaypal = $method === $paymentMethodClass::PAYPAL)
                            <label class="bd-payment-method {{ $selectedMethod === $method ? 'is-selected' : '' }} {{ $isPaypal ? 'is-disabled' : '' }}">
                                <input type="radio" name="payment_method" value="{{ $method->value }}" @checked($selectedMethod === $method) @disabled($isPaypal) required>
                                <img class="bd-payment-method__logo bd-payment-method__logo--{{ strtolower($method->value) }}" src="{{ asset(match ($method) {
                                    $paymentMethodClass::BCA => 'images/figma/payment/xendit.png',
                                    $paymentMethodClass::BRI => 'images/figma/payment/bri.png',
                                    $paymentMethodClass::QRIS => 'images/figma/payment/qris.png',
                                    $paymentMethodClass::PAYPAL => 'images/figma/payment/bca.png',
                                }) }}" alt="{{ $method->label() }}">
                                <span class="bd-payment-method__name">{{ $method->label() }}</span>
                                <img class="bd-payment-method__radio" src="{{ asset('images/figma/payment/radio-'.strtolower($method->value).'.svg') }}" alt="">
                                @if($isPaypal)
                                    <small>Segera hadir</small>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div class="bd-payment-total">
                        <span>Total Pesanan</span>
                        <strong>Rp{{ $amount }}</strong>
                    </div>

                    <button type="submit" class="bd-payment-submit" data-payment-submit>Bayar</button>
                </form>
            @endif
        </section>
    </main>
@elseif($state === 'success')
    <main class="bd-payment-state-page" data-node-id="208:20836" data-name="/bayar-2">
        <section class="bd-payment-state-card bd-payment-state-card--success" aria-labelledby="payment-success-title">
            <div class="bd-payment-state-logo-wrap bd-payment-state-logo-wrap--success">
                <img class="bd-payment-state-logo" src="{{ asset('images/figma/payment/payment-logo.png') }}" alt="Bantudaftarin">
            </div>
            <div class="bd-payment-success-summary">
                <img class="bd-payment-success-icon" src="{{ asset('images/figma/payment/success-check.svg') }}" alt="">
                <p class="bd-payment-success-amount">Rp {{ $amount }}</p>
            </div>
            <h1 id="payment-success-title">Pembayaran berhasil</h1>
            <a class="bd-payment-confirm" href="{{ route('client.applications.show', $application->public_id) }}">Konfirmasi</a>
        </section>
    </main>
@elseif($state === 'submitted')
    <main class="bd-payment-state-page" data-node-id="208:20859" data-name="/bayar-3">
        <section class="bd-payment-state-card bd-payment-state-card--submitted" aria-labelledby="payment-submitted-title">
            <div class="bd-payment-state-logo-wrap bd-payment-state-logo-wrap--submitted">
                <img class="bd-payment-state-logo" src="{{ asset('images/figma/payment/payment-logo.png') }}" alt="Bantudaftarin">
            </div>
            <h1 id="payment-submitted-title">Permohonan berhasil di ajukan</h1>
            <a class="bd-payment-confirm" href="{{ route('client.applications.show', $application->public_id) }}">Konfirmasi</a>
        </section>
    </main>
@elseif($state === 'estimate')
    <main class="bd-payment-state-page bd-payment-state-page--with-back" data-node-id="208:20876" data-name="/bayar-4">
        <a class="bd-payment-back" href="{{ route('client.applications.show', $application->public_id) }}">
            <img src="{{ asset('images/figma/payment/back.svg') }}" alt="">
            <span>Kembali</span>
        </a>
        <section class="bd-payment-state-card bd-payment-state-card--estimate" aria-labelledby="payment-estimate-title">
            <div class="bd-payment-state-logo-wrap bd-payment-state-logo-wrap--estimate">
                <img class="bd-payment-state-logo" src="{{ asset('images/figma/payment/payment-logo.png') }}" alt="Bantudaftarin">
            </div>
            <h1 id="payment-estimate-title">@if($application->estimated_completion_at) Estimasi Selesai Tanggal {{ $application->estimated_completion_at->locale('id')->translatedFormat('j F Y') }} @else Estimasi selesai sedang disiapkan @endif</h1>
            <a class="bd-payment-confirm" href="{{ route('client.applications.show', $application->public_id) }}">Konfirmasi</a>
        </section>
    </main>
@else
    <main class="bd-payment-state-page bd-payment-state-page--with-back" data-node-id="208:20893" data-name="/bayar-5">
        <a class="bd-payment-back" href="{{ route('client.applications.show', $application->public_id) }}">
            <img src="{{ asset('images/figma/payment/back.svg') }}" alt="">
            <span>Kembali</span>
        </a>
        <section class="bd-payment-state-card bd-payment-state-card--result" aria-labelledby="payment-result-title">
            <div class="bd-payment-state-logo-wrap bd-payment-state-logo-wrap--result">
                <img class="bd-payment-state-logo" src="{{ asset('images/figma/payment/payment-logo.png') }}" alt="Bantudaftarin">
            </div>
            <img class="bd-payment-result-image" src="{{ asset('images/figma/payment/payment-email.png') }}" alt="">
            <h1 id="payment-result-title">Hasil layanan tersedia di akun Anda</h1>
            <a class="bd-payment-confirm" href="{{ route('client.applications.show', $application->public_id) }}">Konfirmasi</a>
        </section>
    </main>
@endif
@endsection
