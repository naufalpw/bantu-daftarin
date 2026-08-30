@extends('layouts.marketing')

@section('body_class', 'bd-phase3-body bd-activity-body')

@section('content')
    <x-site-header />

    @php($latestPayment = $payments->first())
    @php($paymentStatus = $latestPayment?->status)
    @php($paymentStatusLabel = match ($paymentStatus?->value) {
        'PENDING' => 'Menunggu pembayaran',
        'PAID' => 'Berhasil',
        'FAILED' => 'Gagal',
        'EXPIRED' => 'Kedaluwarsa',
        'CANCELLED' => 'Dibatalkan',
        'REFUND_REQUESTED' => 'Menunggu pengembalian',
        'REFUNDING' => 'Sedang dikembalikan',
        'REFUNDED' => 'Dikembalikan',
        default => 'Belum ada pembayaran',
    })
    @php($paymentStatusClass = match ($paymentStatus?->value) {
        'PAID' => 'bd-activity-status--success',
        'FAILED', 'EXPIRED', 'CANCELLED' => 'bd-activity-status--failure',
        default => 'bd-activity-status--pending',
    })

    <main class="bd-phase3-main bd-activity-detail-page" data-node-id="261:12462">
        <a class="bd-phase3-back" href="{{ route('client.activity.index') }}">
            <img src="{{ asset('images/figma/phase3/qna/back.svg') }}" alt="">
            <span>Kembali ke aktivitas</span>
        </a>

        <section class="bd-activity-detail-grid" aria-label="Detail aktivitas">
            <article class="bd-activity-detail-card">
                <div class="bd-activity-detail-heading">
                    <span class="bd-activity-detail-icon" aria-hidden="true">Rp</span>
                    <div>
                        <p>Detail Pembayaran</p>
                        <h1>{{ $application->service?->name ?? 'Layanan aplikasi' }}</h1>
                    </div>
                </div>
                <dl class="bd-activity-detail-list">
                    <div>
                        <dt>Referensi pembayaran</dt>
                        <dd>{{ $latestPayment?->reference_id ?? 'Belum tersedia' }}</dd>
                    </div>
                    <div>
                        <dt>Metode pembayaran</dt>
                        <dd>{{ $latestPayment?->payment_method?->label() ?? 'Belum dipilih' }}</dd>
                    </div>
                    <div>
                        <dt>Status transaksi</dt>
                        <dd><span class="bd-activity-status {{ $paymentStatusClass }}">{{ $paymentStatusLabel }}</span></dd>
                    </div>
                    <div class="bd-activity-detail-list__total">
                        <dt>Total pembayaran</dt>
                        <dd>{{ $latestPayment ? $latestPayment->currency . ' ' . number_format((float) $latestPayment->amount, 0, ',', '.') : 'Belum tersedia' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="bd-activity-detail-card bd-activity-process-card">
                <div class="bd-activity-detail-heading">
                    <span class="bd-activity-detail-icon bd-activity-detail-icon--process" aria-hidden="true">✓</span>
                    <div>
                        <p>Status Pengerjaan</p>
                        <h1>{{ $application->status->label() }}</h1>
                    </div>
                </div>
                <ol class="bd-activity-timeline">
                    @forelse($histories->sortBy('created_at') as $history)
                        <li>
                            <span class="bd-activity-timeline__dot" aria-hidden="true"></span>
                            <div>
                                <strong>{{ $history->to_status?->label() ?? $history->to_status }}</strong>
                                <span>{{ $history->created_at?->format('d M Y, H:i') }}</span>
                            </div>
                        </li>
                    @empty
                        <li>
                            <span class="bd-activity-timeline__dot" aria-hidden="true"></span>
                            <div>
                                <strong>{{ $application->status->label() }}</strong>
                                <span>{{ $application->created_at?->format('d M Y, H:i') }}</span>
                            </div>
                        </li>
                    @endforelse
                </ol>
            </article>
        </section>
    </main>
@endsection
