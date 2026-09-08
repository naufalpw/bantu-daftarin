@extends('layouts.marketing')

@section('body_class', 'bd-phase3-body bd-activity-body')

@section('content')
    <x-site-header />

    <main class="bd-phase3-main bd-activity-page" data-node-id="340:18877">
        <a class="bd-phase3-back" href="{{ route('client.dashboard') }}">
            <img src="{{ asset('images/figma/register/back.svg') }}" alt="">
            <span>Kembali</span>
        </a>

        <section class="bd-activity-panel" aria-label="Aktivitas aplikasi">
            <details class="bd-activity-accordion">
                <summary>
                    <span>Status Transaksi</span>
                    <span class="bd-activity-caret" aria-hidden="true"></span>
                </summary>
                <div class="bd-activity-accordion__content">
                    @forelse($transactions as $transaction)
                        @php($payment = $transaction['payment'])
                        @php($application = $transaction['application'])
                        @php($statusClass = in_array($payment->status?->value, ['PAID'], true) ? 'bd-activity-status--success' : (in_array($payment->status?->value, ['FAILED', 'EXPIRED', 'CANCELLED'], true) ? 'bd-activity-status--failure' : 'bd-activity-status--pending'))
                        <article class="bd-activity-row">
                            <div>
                                <strong>{{ $transaction['method_label'] }}</strong>
                                <span>{{ $payment->reference_id }}</span>
                            </div>
                            <div>
                                <strong>{{ $payment->amount !== null ? $payment->currency . ' ' . number_format((float) $payment->amount, 0, ',', '.') : 'Nominal belum tersedia' }}</strong>
                                <span>{{ $payment->created_at?->format('d M Y, H:i') }}</span>
                            </div>
                            <span class="bd-activity-status {{ $statusClass }}">{{ $transaction['status_label'] }}</span>
                            <a class="bd-activity-detail-link" href="{{ route('client.activity.show', $application->public_id) }}">Detail</a>
                        </article>
                    @empty
                        <p class="bd-activity-empty">Belum ada transaksi pembayaran.</p>
                    @endforelse
                </div>
            </details>

            <details class="bd-activity-accordion">
                <summary>
                    <span>Proses Pengerjaan</span>
                    <span class="bd-activity-caret" aria-hidden="true"></span>
                </summary>
                <div class="bd-activity-accordion__content">
                    @forelse($processes as $process)
                        @php($history = $process['history'])
                        @php($application = $process['application'])
                        <article class="bd-activity-row bd-activity-row--process">
                            <div>
                                <strong>{{ $process['label'] }}</strong>
                                <span>{{ $application->service?->name ?? 'Layanan pengajuan' }}</span>
                            </div>
                            <div>
                                <strong>{{ $history->created_at?->format('d M Y') }}</strong>
                                <span>{{ $history->created_at?->format('H:i') }}</span>
                            </div>
                            <span class="bd-activity-status bd-activity-status--process">Pembaruan</span>
                            <a class="bd-activity-detail-link" href="{{ route('client.activity.show', $application->public_id) }}">Detail</a>
                        </article>
                    @empty
                        <p class="bd-activity-empty">Belum ada riwayat proses pengerjaan.</p>
                    @endforelse
                </div>
            </details>

            <details class="bd-activity-accordion">
                <summary>
                    <span>Pesanan</span>
                    <span class="bd-activity-caret" aria-hidden="true"></span>
                </summary>
                <div class="bd-activity-accordion__content">
                    @forelse($orders as $order)
                        @php($application = $order['application'])
                        <article class="bd-activity-row bd-activity-row--order">
                            <div>
                                <strong>{{ $application->service?->name ?? 'Layanan pengajuan' }}</strong>
                                <span>{{ $application->public_id }}</span>
                            </div>
                            <div>
                                <strong>{{ $order['status_label'] }}</strong>
                                <span>{{ $application->created_at?->format('d M Y, H:i') }}</span>
                            </div>
                            <span class="bd-activity-status bd-activity-status--process">{{ $order['payment_status_label'] }}</span>
                            <a class="bd-activity-detail-link" href="{{ route('client.activity.show', $application->public_id) }}">Detail</a>
                        </article>
                    @empty
                        <p class="bd-activity-empty">Belum ada pesanan.</p>
                    @endforelse
                </div>
            </details>
        </section>
    </main>
@endsection
