@extends('layouts.client')

@section('context_title', 'Beranda')
@section('body_class', 'pb-dashboard-body')

@section('content')
<div class="pb-page pb-dashboard">
    <header class="pb-page-heading pb-dashboard__heading">
        <p class="pb-kicker">Beranda</p>
        <h1>Selamat datang, {{ auth()->user()->name }}</h1>
        <p>Lanjutkan hal yang perlu Anda tindaklanjuti atau lihat perkembangan pengajuan Anda.</p>
    </header>

    @if($applications->isEmpty())
        <section class="pb-empty pb-empty--dashboard" aria-labelledby="empty-dashboard-title">
            <div>
                <p class="pb-kicker">Mulai pengajuan</p>
                <h2 id="empty-dashboard-title">Belum ada pengajuan</h2>
                <p>Pilih layanan untuk memulai pengajuan pertama Anda.</p>
                <a class="pb-button pb-button--primary" href="{{ route('client.services.index') }}">Lihat layanan</a>
            </div>
        </section>
    @else
        @if($priorityApplication)
            <section class="pb-priority pb-priority--next" aria-labelledby="priority-title">
                <div class="pb-priority__copy">
                    <div class="pb-priority__topline">
                        <p class="pb-kicker pb-kicker--inverse">Langkah berikutnya</p>
                        <span class="pb-status pb-status--{{ $priorityPresentation['tone'] }}">{{ $priorityPresentation['label'] }}</span>
                    </div>
                    <h2 id="priority-title">{{ $priorityApplication->service->name }}</h2>
                    <p>{{ $priorityPresentation['next_action'] }}</p>
                    <div class="pb-priority__meta">
                        <span>ID …{{ strtoupper(substr($priorityApplication->public_id, -4)) }}</span>
                        <time datetime="{{ $priorityApplication->updated_at?->toAtomString() }}">Diperbarui {{ $priorityApplication->updated_at?->translatedFormat('d M Y') }}</time>
                    </div>
                </div>
                <div class="pb-priority__action">
                    @if($priorityPresentation['cta_label'])
                        @if($priorityPresentation['cta_method'] === 'post')
                            <form method="post" action="{{ route('client.applications.documents.submit', $priorityApplication->public_id) }}">
                                @csrf
                                <button class="pb-button pb-button--light" type="submit">{{ $priorityPresentation['cta_label'] }}</button>
                            </form>
                        @else
                            <a class="pb-button pb-button--light" href="{{ $priorityPresentation['cta_url'] }}">{{ $priorityPresentation['cta_label'] }}</a>
                        @endif
                    @else
                        <a class="pb-button pb-button--light" href="{{ route('client.applications.show', $priorityApplication->public_id) }}">Lihat pengajuan</a>
                    @endif
                </div>
            </section>
        @endif

        <div class="pb-dashboard__columns">
            <section class="pb-section pb-dashboard-applications" aria-labelledby="dashboard-applications-title">
                <div class="pb-section-heading">
                    <div>
                        <p class="pb-kicker">Pengajuan Anda</p>
                        <h2 id="dashboard-applications-title">Pengajuan yang sedang berjalan</h2>
                    </div>
                    <a href="{{ route('client.applications.index') }}">Lihat semua</a>
                </div>
                <div class="pb-application-list pb-application-list--compact">
                    @foreach($dashboardApplications as $application)
                        <x-application-list-item :application="$application" compact />
                    @endforeach
                </div>
            </section>

            <section class="pb-latest" aria-labelledby="latest-update-title">
                <p class="pb-kicker">Pembaruan terbaru</p>
                <h2 id="latest-update-title">Aktivitas pengajuan</h2>
                @forelse($recentUpdates as $update)
                    @php($updatePresentation = \App\Support\ApplicationStatusPresenter::forStatus($update['history']->to_status))
                    <div class="pb-latest__item">
                        <time datetime="{{ $update['history']->created_at->toAtomString() }}">{{ $update['history']->created_at->translatedFormat('d M Y, H:i') }}</time>
                        <strong>{{ $updatePresentation['label'] }}</strong>
                        <p>{{ $update['application']->service->name }}</p>
                    </div>
                @empty
                    <p class="pb-inline-empty">Belum ada pembaruan pengajuan.</p>
                @endforelse
                <a href="{{ route('client.applications.index') }}">Lihat aktivitas</a>
            </section>
        </div>

        <section class="pb-service-shortcuts pb-service-shortcuts--dashboard" aria-labelledby="other-services-title">
            <div>
                <p class="pb-kicker">Butuh layanan lain?</p>
                <h2 id="other-services-title">Pilih layanan sesuai kebutuhan Anda</h2>
            </div>
            <div class="pb-service-shortcuts__list">
                @foreach($services as $service)
                    @if($service->status->value === 'COMING_SOON')
                        <span>{{ $service->name }} <small>Segera hadir</small></span>
                    @else
                        <a href="{{ route('client.services.index') }}">{{ $service->name }}</a>
                    @endif
                @endforeach
            </div>
            <div class="pb-service-shortcuts__actions">
                <a class="pb-button pb-button--primary" href="{{ route('client.services.index') }}">Lihat semua layanan</a>
            </div>
        </section>
    @endif
</div>
@endsection
