@props(['application', 'compact' => false])

@php
    $presentation = \App\Support\ApplicationStatusPresenter::for($application);
    $isCompleted = \App\Support\ApplicationStatusPresenter::category($application->status) === \App\Support\ApplicationStatusPresenter::CATEGORY_COMPLETED;
    $isCancelled = $application->status === \App\Enums\ApplicationStatus::CANCELLED;
    $href = $compact ? route('client.applications.show', $application->public_id) : ($presentation['cta_url'] ?? route('client.applications.show', $application->public_id));
    $ctaLabel = $compact ? ($isCompleted && $presentation['cta_label'] ? $presentation['cta_label'] : 'Lihat') : ($presentation['cta_label'] ?? 'Lihat pengajuan');
@endphp

<article class="pb-application-card {{ $compact ? 'pb-application-card--compact' : '' }}">
    <div class="pb-application-card__header">
        <div class="pb-application-card__context">
            <span class="pb-service-mark" aria-hidden="true">{{ $application->service?->code === 'NPWP_BUSINESS' ? 'BU' : 'NP' }}</span>
            <div>
                <h3>{{ $application->service?->name ?? 'Layanan NPWP' }}</h3>
                <span>ID …{{ strtoupper(substr($application->public_id, -4)) }}</span>
            </div>
        </div>
        <span class="pb-status pb-status--{{ $presentation['tone'] }}">{{ $presentation['label'] }}</span>
    </div>

    @unless($compact)
        <p class="pb-application-card__description">{{ $presentation['description'] }}</p>
    @endunless

    <div class="pb-application-card__footer">
        <time datetime="{{ $application->updated_at?->toAtomString() }}">{{ $isCancelled ? 'Dibatalkan' : 'Diperbarui' }} {{ $application->updated_at?->translatedFormat('d M Y') }}</time>
        @if(!$compact && $presentation['cta_method'] === 'post' && $presentation['cta_url'])
            <form method="POST" action="{{ $presentation['cta_url'] }}">
                @csrf
                <button class="pb-button pb-button--secondary" type="submit">{{ $ctaLabel }}</button>
            </form>
        @else
            <a class="pb-button pb-button--secondary" href="{{ $href }}">{{ $ctaLabel }}</a>
        @endif
    </div>
</article>
