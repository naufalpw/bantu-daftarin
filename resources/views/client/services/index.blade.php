@extends('layouts.client')

@section('context_title', 'Layanan')
@section('body_class', 'pb-services-body')

@section('content')
<div class="pb-page">
    <header class="pb-page-heading pb-services-heading">
        <p class="pb-kicker">Layanan</p>
        <h1>Pilih layanan sesuai kebutuhan Anda</h1>
        <p>Bandingkan layanan, persyaratan utama, dan lanjutkan pengajuan.</p>
    </header>

    <section class="pb-service-catalog" aria-label="Layanan pengajuan NPWP">
        <div class="pb-service-catalog__active-grid">
        @foreach($services as $service)
            @php($bookable = $service->isBookable())
            @php($activeApplication = $service->applications->first())
            @continue(!$bookable)
            @php($statusPresentation = $activeApplication ? \App\Support\ApplicationStatusPresenter::for($activeApplication) : null)
            <article class="pb-service-catalog-card">
                <div class="pb-service-catalog-card__icon" aria-hidden="true">
                    <img src="{{ asset(match ($service->code) {
                        'NPWP_BUSINESS' => 'images/figma/home/business-icon.svg',
                        'NPWP_PERSONAL' => 'images/figma/home/personal-icon.svg',
                        default => 'images/figma/home/tax-icon.svg',
                    }) }}" alt="">
                </div>
                <div class="pb-service-catalog-card__heading">
                    <h2>{{ $service->name }}</h2>
                    <p>{{ $service->description }}</p>
                </div>

                <div class="pb-service-catalog-card__price">
                    <span>Biaya layanan</span>
                    <strong>{{ $service->currency }} {{ number_format((float) $service->price_amount, 0, ',', '.') }}</strong>
                </div>

                <div class="pb-service-catalog-card__requirements">
                    <h3>Persyaratan utama</h3>
                    <ul>
                        @foreach($service->requirements->where('is_required', true)->take(3) as $requirement)
                            <li>{{ $requirement->name }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="pb-service-catalog-card__footer">
                    @if($activeApplication)
                        <div class="pb-service-catalog-card__active-state">
                            <span>Pengajuan aktif</span>
                            <strong>{{ $statusPresentation['label'] }}</strong>
                            <p>Terakhir diperbarui {{ $activeApplication->updated_at->format('d M Y') }}</p>
                        </div>
                        <a class="pb-button pb-button--primary pb-button--wide" href="{{ route('client.applications.show', $activeApplication->public_id) }}">Lanjutkan pengajuan</a>
                    @else
                        <a class="pb-button pb-button--primary pb-button--wide" href="{{ route('client.applications.create', $service->public_id) }}">Lihat persyaratan &amp; mulai</a>
                    @endif
                </div>
            </article>
        @endforeach
        </div>

        @foreach($services as $service)
            @continue($service->isBookable())
            <article class="pb-service-coming-soon">
                <div class="pb-service-coming-soon__icon" aria-hidden="true">
                    <img src="{{ asset('images/figma/home/tax-icon.png') }}" alt="">
                </div>
                <div class="pb-service-coming-soon__body">
                    <h2>{{ $service->name }}</h2>
                    <p>Layanan pelaporan pajak sedang dipersiapkan.</p>
                </div>
                <span class="pb-status pb-status--neutral">Segera hadir</span>
            </article>
        @endforeach
    </section>

</div>
@endsection
