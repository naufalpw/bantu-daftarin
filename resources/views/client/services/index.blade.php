@extends('layouts.client')

@section('body_class', 'bd-client-body bd-client-services-body')

@section('content')
    <div class="bd-client-page bd-services-page">
        <section class="bd-client-hero bd-services-hero">
            <div class="bd-services-hero__copy">
                <span class="bd-client-eyebrow">Layanan Bantu Daftarin</span>
                <h1>Pilih kebutuhan Anda</h1>
                <p>Bantu Daftarin membantu proses administrasi NPWP dengan alur yang mudah dipantau.</p>
            </div>
            <div class="bd-services-hero__badge" aria-hidden="true">
                <img src="{{ asset('images/figma/home/tax-icon.svg') }}" alt="">
            </div>
        </section>

        <section class="bd-client-section" aria-labelledby="available-services-title">
            <div class="bd-client-section__heading">
                <div>
                    <span class="bd-client-eyebrow">Mulai dari sini</span>
                    <h2 id="available-services-title">Layanan yang tersedia</h2>
                </div>
            </div>

            <div class="bd-services-grid">
                @foreach($services as $service)
                    @php($bookable = $service->isBookable())
                    <article class="bd-client-card bd-service-card {{ $service->status->value === 'COMING_SOON' ? 'bd-service-card--coming-soon' : '' }}">
                        <div class="bd-service-card__topline">
                            <span class="bd-service-card__icon" aria-hidden="true">
                                <img src="{{ asset($service->code === 'NPWP_BUSINESS' ? 'images/figma/home/process-icon.svg' : 'images/figma/home/personal-icon.svg') }}" alt="">
                            </span>
                            @if($service->status->value === 'COMING_SOON')
                                <span class="bd-client-status bd-client-status--warning">Segera hadir</span>
                            @else
                                <span class="bd-service-card__available">Tersedia</span>
                            @endif
                        </div>

                        <h3>{{ $service->name }}</h3>
                        <p>{{ $service->description }}</p>

                        @if($service->price_amount !== null)
                            <div class="bd-service-card__price">
                                <span>Mulai dari</span>
                                <strong>{{ $service->currency }} {{ number_format((float) $service->price_amount, 0, ',', '.') }}</strong>
                            </div>
                        @endif

                        <div class="bd-service-card__action">
                            @if($bookable)
                                <a class="bd-client-button bd-client-button--primary" href="{{ $service->code === 'NPWP_PERSONAL' ? route('npwp.personal') : ($service->code === 'NPWP_BUSINESS' ? route('npwp.business.types') : route('client.applications.create', $service->public_id)) }}">Mulai aplikasi</a>
                            @else
                                <span class="bd-client-button bd-client-button--disabled">Belum tersedia</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
@endsection
