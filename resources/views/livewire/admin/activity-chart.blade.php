@php
    $chartPoints = $activity['points'];
    $chartCount = $chartPoints->count();
    $chartMaximum = max(1, (int) $chartPoints->max('count'));
    $chartScaleMaximum = max(4, (int) (ceil($chartMaximum / 4) * 4));
    $chartLeft = 42;
    $chartRight = 626;
    $chartTop = 18;
    $chartBottom = 202;
    $chartWidth = $chartRight - $chartLeft;
    $chartHeight = $chartBottom - $chartTop;
    $chartCoordinates = $chartPoints->values()->map(function (array $point, int $index) use ($chartCount, $chartLeft, $chartWidth, $chartTop, $chartHeight, $chartScaleMaximum): array {
        $x = $chartLeft + ($chartCount > 1 ? ($chartWidth * $index / ($chartCount - 1)) : ($chartWidth / 2));
        $y = $chartTop + $chartHeight * (1 - ($point['count'] / $chartScaleMaximum));

        return array_merge($point, ['x' => round($x, 2), 'y' => round($y, 2)]);
    });
    $chartLine = $chartCoordinates->map(fn (array $point) => $point['x'].','.$point['y'])->implode(' ');
    $chartArea = $chartCoordinates->isNotEmpty()
        ? $chartLeft.','.$chartBottom.' '.$chartLine.' '.$chartRight.','.$chartBottom
        : '';
    $chartLabelEvery = max(1, (int) ceil(max(1, $chartCount - 1) / 5));
@endphp

<section class="bd-admin-surface bd-admin-activity-chart bd-admin-reactive-region" aria-labelledby="operational-activity-title">
    <div class="bd-admin-activity-chart__header">
        <div>
            <p class="bd-admin-kicker">AKTIVITAS OPERASIONAL</p>
            <h2 id="operational-activity-title">Aktivitas layanan</h2>
            <p>Jumlah aktivitas penting pada pengajuan selama periode yang dipilih.</p>
        </div>
        <form class="bd-admin-period-form" method="get" action="{{ route('admin.dashboard') }}" wire:submit.prevent>
            <label for="dashboard-period">Periode aktivitas</label>
            <select id="dashboard-period" name="period" wire:model.live="period">
                @foreach($periods as $value => $label)
                    <option value="{{ $value }}" @selected($period === (int) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bd-admin-reactive-results bd-admin-reactive-results--chart" wire:loading.class="bd-admin-reactive-results--loading" wire:loading.attr="aria-busy" wire:target="period">
        <div class="bd-admin-reactive-loading" wire:loading.delay.flex style="display: none" wire:target="period" role="status" aria-live="polite">
            <span class="bd-admin-reactive-loading__spinner" aria-hidden="true"></span>
            <span>Memuat...</span>
        </div>
        <div class="bd-admin-reactive-content">
    @if($activity['total'] > 0)
        <figure class="bd-admin-chart-figure" aria-labelledby="operational-activity-title" aria-describedby="operational-activity-summary">
            <svg viewBox="0 0 660 242" role="img" aria-label="{{ $activity['total'] }} aktivitas operasional selama {{ $periods[$period] }}">
                @for($step = 0; $step <= 4; $step++)
                    @php($y = $chartTop + ($chartHeight * $step / 4))
                    @php($axisValue = (int) round($chartScaleMaximum * (1 - $step / 4)))
                    <line class="bd-admin-chart-gridline" x1="{{ $chartLeft }}" x2="{{ $chartRight }}" y1="{{ $y }}" y2="{{ $y }}" />
                    <text class="bd-admin-chart-y-label" x="{{ $chartLeft - 9 }}" y="{{ $y + 4 }}">{{ $axisValue }}</text>
                @endfor
                <polygon class="bd-admin-chart-area" points="{{ $chartArea }}" />
                <polyline class="bd-admin-chart-line" points="{{ $chartLine }}" />
                @foreach($chartCoordinates as $index => $point)
                    <g class="bd-admin-chart-point" tabindex="0" aria-label="{{ $point['label'] }}: {{ $point['count'] }} aktivitas">
                        <title>{{ $point['label'] }}: {{ $point['count'] }} aktivitas</title>
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" />
                    </g>
                    @if($index === 0 || $index === $chartCount - 1 || $index % $chartLabelEvery === 0)
                        <text class="bd-admin-chart-x-label" x="{{ $point['x'] }}" y="{{ $chartBottom + 25 }}">{{ $point['date']->translatedFormat('j M') }}</text>
                    @endif
                @endforeach
            </svg>
            <figcaption id="operational-activity-summary">{{ $activity['total'] }} aktivitas tercatat dalam {{ $periods[$period] }}.</figcaption>
        </figure>
    @else
        <div class="bd-admin-chart-empty-state">
            <h3>Belum ada aktivitas pada periode ini</h3>
            <p>Data aktivitas akan muncul saat pengajuan mulai diproses.</p>
        </div>
    @endif
        </div>
    </div>
</section>
