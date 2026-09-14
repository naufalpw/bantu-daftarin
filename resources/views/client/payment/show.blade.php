@extends('layouts.client')

@section('context_title', 'Pembayaran')
@section('body_class', 'pb-payment-body')

@section('content')
@php
    $paymentMethodClass = \App\Enums\PaymentMethod::class;
    $selectedMethod = $payment?->payment_method instanceof $paymentMethodClass ? $payment->payment_method : $paymentMethodClass::BCA;
    $amount = number_format((float) $application->price_amount_snapshot, 0, ',', '.');
    $presentedPaymentStatus = $payment?->status ?? ($state === 'success' ? \App\Enums\PaymentStatus::PAID : null);
    $paymentPresentation = $state === 'cancelled'
        ? ['label' => 'Pengajuan dibatalkan', 'tone' => 'danger']
        : \App\Support\PaymentStatusPresenter::for($presentedPaymentStatus, $payment?->status?->value === 'PENDING' && $payment?->expires_at?->isPast());
@endphp

<div class="pb-page pb-payment-page">
    <a class="pb-back-link" href="{{ route('client.applications.show', $application->public_id) }}#pembayaran"><span aria-hidden="true">←</span> Kembali ke ruang pengajuan</a>

    <header class="pb-page-heading">
        <p class="pb-kicker">Pembayaran</p>
        <h1>{{ $application->service->name }}</h1>
        <p>Pilih metode, ikuti instruksi transaksi, lalu tunggu konfirmasi pembayaran dari sistem.</p>
    </header>

    <div class="pb-payment-layout">
        <aside class="pb-order-summary" @if($state === 'waiting') data-phone-move-to="#payment-reference-position" @endif aria-labelledby="order-summary-title">
            <h2 id="order-summary-title">Ringkasan pembayaran</h2>
            <dl>
                <div><dt>Layanan</dt><dd>{{ $application->service->name }}</dd></div>
                <div><dt>ID pengajuan</dt><dd>…{{ strtoupper(substr($application->public_id, -4)) }}</dd></div>
                <div class="pb-order-summary__total"><dt>Total</dt><dd>{{ $application->currency }} {{ $amount }}</dd></div>
                @if($payment)
                    <div><dt>Referensi</dt><dd>{{ $payment->reference_id }}</dd></div>
                @endif
            </dl>
            <p>Status pembayaran hanya berubah setelah notifikasi provider tervalidasi. Kembali dari halaman pembayaran tidak otomatis berarti transaksi berhasil.</p>
        </aside>

        <section class="pb-payment-action" aria-labelledby="payment-action-title">
            <div class="pb-payment-action__heading">
                <div><p class="pb-kicker">Status transaksi</p><h2 id="payment-action-title">{{ $paymentPresentation['label'] }}</h2></div>
                <span class="pb-status pb-status--{{ $paymentPresentation['tone'] }}">{{ $paymentPresentation['label'] }}</span>
            </div>

            @if($state === 'waiting')<p class="bd-phone-only pb-payment-total"><span>Total</span><strong>{{ $application->currency }} {{ $amount }}</strong></p>@endif
            @if($state === 'cancelled')
                <div class="pb-payment-cancelled" data-payment-state="cancelled">
                    <strong>Pengajuan telah dibatalkan</strong>
                    <p>Pembayaran tidak dapat dilanjutkan untuk pengajuan ini.</p>
                    <a class="pb-button pb-button--secondary" href="{{ route('client.applications.show', $application->public_id) }}">Kembali ke Pengajuan</a>
                </div>
            @elseif($state === 'waiting' && $payment)
                <div class="pb-payment-instructions" data-payment-state="waiting">
                    <p>Selesaikan pembayaran menggunakan instruksi berikut. Halaman ini dapat dimuat ulang untuk melihat status terbaru.</p>
                    @if($payment->virtualAccountNumber())
                        <div class="pb-payment-code">
                            <span>Nomor Virtual Account {{ $payment->payment_method?->label() }}</span>
                            <strong>{{ $payment->virtualAccountNumber() }}</strong>
                            <button type="button" data-copy-value="{{ $payment->virtualAccountNumber() }}"><x-ui-icon class="bd-desktop-action-icon" name="copy" :size="15" />Salin nomor</button>
                        </div>
                    @elseif($payment->qrString())
                        <div class="pb-payment-qr">
                            <span>QRIS</span>
                            @if($qrCodeImage)
                                <img class="pb-payment-qr-code" src="{{ $qrCodeImage }}" alt="Kode QR pembayaran QRIS">
                            @else
                                <p role="status">QR pembayaran belum dapat ditampilkan. Muat ulang halaman untuk mencoba kembali.</p>
                            @endif
                            <p>Pindai melalui aplikasi pembayaran yang mendukung QRIS.</p>
                        </div>
                    @elseif($payment->checkout_url)
                        <a class="pb-button pb-button--primary" href="{{ $payment->checkout_url }}" target="_blank" rel="noopener noreferrer">Buka halaman pembayaran</a>
                    @endif
                    @if($payment->expires_at)
                        <p class="pb-payment-expiry">Instruksi berlaku sampai {{ $payment->expires_at->translatedFormat('d M Y, H:i') }} WIB.</p>
                    @endif
                    <div class="pb-payment-refresh">
                        <a class="pb-button pb-button--secondary" href="{{ route('client.payments.show', $application->public_id) }}">Periksa status pembayaran</a>
                    </div>
                </div>
            @elseif($state === 'success')
                <div class="pb-payment-success">
                    <img src="{{ asset('images/figma/payment/success-check.svg') }}" alt="" aria-hidden="true">
                    <div><strong>Pembayaran sudah diterima</strong><p>Rincian pembayaran tetap tersedia di ruang pengajuan. Tahap berikutnya mengikuti status pengajuan, bukan halaman pembayaran ini.</p></div>
                    <a class="pb-button pb-button--primary" href="{{ route('client.applications.show', $application->public_id) }}#ringkasan">Kembali ke ruang pengajuan</a>
                </div>
            @elseif($state === 'refund')
                <div class="pb-payment-success">
                    <div><strong>{{ $paymentPresentation['label'] }}</strong><p>Status pengembalian dana berasal dari proses pembayaran yang tercatat. Rincian pengajuan tetap tersedia di ruang pengajuan.</p></div>
                    <a class="pb-button pb-button--primary" href="{{ route('client.applications.show', $application->public_id) }}#pembayaran">Kembali ke ruang pengajuan</a>
                </div>
            @else
                @if($state === 'failure')
                    <div class="pb-alert pb-alert--danger" role="alert">
                        <strong>{{ $paymentPresentation['label'] }}</strong>
                        <span>Anda dapat membuat instruksi pembayaran baru selama pengajuan masih mengizinkan pembayaran.</span>
                    </div>
                @endif

                <form method="post" action="{{ route('client.payments.store', $application->public_id) }}" class="pb-payment-form" data-payment-form>
                    @csrf
                    <fieldset>
                        <legend>Pilih metode pembayaran</legend>
                        <div class="pb-payment-methods">
                            @foreach($paymentMethods as $method)
                                @php($isPaypal = $method === $paymentMethodClass::PAYPAL)
                                <label class="pb-payment-method {{ $isPaypal ? 'is-disabled' : '' }}">
                                    <input type="radio" name="payment_method" value="{{ $method->value }}" @checked(!$isPaypal && $selectedMethod === $method) @disabled($isPaypal) required>
                                    @if(!$isPaypal)
                                        <img src="{{ asset(match ($method) { $paymentMethodClass::BCA => 'images/figma/payment/bca.png', $paymentMethodClass::BRI => 'images/figma/payment/bri.png', default => 'images/figma/payment/qris.png' }) }}" alt="">
                                    @endif
                                    <span><strong>{{ $method->label() }}</strong>@if($isPaypal)<small>Segera hadir</small>@else<small>{{ $method === $paymentMethodClass::QRIS ? 'Pindai kode QR' : 'Virtual Account' }}</small>@endif</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <button class="pb-button pb-button--primary pb-button--wide" type="submit" data-payment-submit>Buat instruksi pembayaran</button>
                </form>
            @endif
        </section>
        <div id="payment-reference-position" class="pb-payment-reference-slot"></div>
    </div>
</div>
@endsection
