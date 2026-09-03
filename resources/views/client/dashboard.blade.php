@extends('layouts.client')

@section('body_class', 'bd-client-body bd-client-dashboard-body')

@section('content')
    <div class="bd-client-page bd-dashboard-page">
        <section class="bd-client-hero bd-dashboard-hero">
            <div class="bd-dashboard-hero__copy">
                <span class="bd-client-eyebrow">Ruang client</span>
                <h1>Aplikasi Anda</h1>
                <p>Pantau data, dokumen, pembayaran, dan hasil dalam satu tempat.</p>
            </div>
            <div class="bd-dashboard-hero__actions">
                <a class="bd-client-button bd-client-button--primary" href="{{ route('client.services.index') }}">Buat aplikasi</a>
                <a class="bd-client-button bd-client-button--secondary" href="{{ route('client.activity.index') }}">Lihat aktivitas</a>
            </div>
        </section>

        <section class="bd-client-section" id="client-applications" aria-labelledby="client-applications-title">
            <div class="bd-client-section__heading">
                <div>
                    <span class="bd-client-eyebrow">Ringkasan layanan</span>
                    <h2 id="client-applications-title">Aplikasi yang sedang berjalan</h2>
                </div>
                <span class="bd-client-section__count">{{ $applications->total() }} aplikasi</span>
            </div>

            <div class="bd-dashboard-grid">
                @forelse($applications as $application)
                    @php($statusTone = match ($application->status->value) {
                        'COMPLETED', 'ARCHIVED' => 'success',
                        'REVISION_REQUIRED', 'FAILED' => 'danger',
                        'AWAITING_PAYMENT', 'DOCUMENTS_READY_FOR_PAYMENT' => 'warning',
                        default => 'primary',
                    })
                    <article class="bd-client-card bd-dashboard-application-card">
                        <header class="bd-dashboard-application-card__header">
                            <div>
                                <span class="bd-client-card__label">Layanan</span>
                                <h3>{{ $application->service->name }}</h3>
                            </div>
                            <span class="bd-client-status bd-client-status--{{ $statusTone }}">{{ $application->status->label() }}</span>
                        </header>

                        <div class="bd-dashboard-application-card__reference">
                            <span>Nomor aplikasi</span>
                            <code>{{ $application->public_id }}</code>
                        </div>

                        <div class="bd-dashboard-application-card__progress">
                            @include('components.application-progress', ['status' => $application->status, 'compact' => true])
                        </div>

                        <footer class="bd-dashboard-application-card__footer">
                            <span>Dibuat {{ $application->created_at->translatedFormat('d M Y H:i') }}</span>
                            <div class="bd-dashboard-application-card__actions">
                                <a class="bd-client-link" href="{{ route('client.applications.show', $application->public_id) }}">Buka aplikasi</a>
                                @if($application->chatThread)
                                    <a class="bd-client-link bd-client-link--muted" href="{{ route('client.chat.show', $application->chatThread->public_id) }}">Chat admin</a>
                                @endif
                            </div>
                        </footer>
                    </article>
                @empty
                    <div class="bd-client-empty bd-dashboard-empty">
                        <span class="bd-client-empty__icon" aria-hidden="true">+</span>
                        <h3>Belum ada aplikasi</h3>
                        <p>Pilih layanan untuk memulai proses pendaftaran Anda.</p>
                        <a class="bd-client-button bd-client-button--primary" href="{{ route('client.services.index') }}">Pilih layanan</a>
                    </div>
                @endforelse
            </div>

            @if($applications->hasPages())
                <div class="bd-client-pagination">{{ $applications->links() }}</div>
            @endif
        </section>
    </div>
@endsection
