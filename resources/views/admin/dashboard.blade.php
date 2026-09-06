@extends('layouts.admin')

@section('title', 'Dashboard')
@section('admin_context', 'Dashboard operasional')

@section('content')
<div class="bd-admin-dashboard">
    <x-admin.page-header kicker="DASHBOARD" title="Ruang kerja admin" description="Pantau pekerjaan penting dan perkembangan layanan.">
        <a class="bd-admin-button bd-admin-button--primary" href="{{ route('admin.applications.index') }}">Lihat semua pengajuan</a>
    </x-admin.page-header>

    @php
        $attentionItems = [
            ['key' => 'review', 'label' => 'Pengajuan perlu ditinjau', 'action' => 'Buka pengajuan', 'href' => route('admin.applications.index', ['filter' => 'review'])],
            ['key' => 'revision', 'label' => 'Revisi masuk', 'action' => 'Tinjau dokumen', 'href' => route('admin.documents.index', ['filter' => 'revision'])],
            ['key' => 'result', 'label' => 'Hasil perlu diverifikasi', 'action' => 'Tinjau hasil', 'href' => route('admin.applications.index', ['filter' => 'result'])],
            ['key' => 'support', 'label' => 'Pesan belum dibaca', 'action' => 'Buka dukungan', 'href' => route('admin.support.index', ['filter' => 'unread'])],
        ];
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

    <section class="bd-admin-surface bd-admin-attention-panel" aria-labelledby="attention-title">
        <div class="bd-admin-surface__header">
            <div>
                <p class="bd-admin-kicker">PERLU PERHATIAN</p>
                <h2 id="attention-title">Pekerjaan yang memerlukan tindakan</h2>
            </div>
        </div>
        <div class="bd-admin-attention-panel__items">
            @foreach($attentionItems as $item)
                <article class="bd-admin-attention-item" data-admin-attention="{{ $item['key'] }}">
                    <strong>{{ $attention[$item['key']] }}</strong>
                    <div>
                        <h3>{{ $item['label'] }}</h3>
                        <a href="{{ $item['href'] }}">{{ $item['action'] }} <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div class="bd-admin-operational-grid">
        <section class="bd-admin-surface bd-admin-activity-chart" aria-labelledby="operational-activity-title">
            <div class="bd-admin-activity-chart__header">
                <div>
                    <p class="bd-admin-kicker">AKTIVITAS OPERASIONAL</p>
                    <h2 id="operational-activity-title">Aktivitas layanan</h2>
                    <p>Jumlah aktivitas penting pada pengajuan selama periode yang dipilih.</p>
                </div>
                <form class="bd-admin-period-form" method="get" action="{{ route('admin.dashboard') }}">
                    <label for="dashboard-period">Periode aktivitas</label>
                    <select id="dashboard-period" name="period">
                        @foreach($periods as $value => $label)
                            <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Terapkan</button>
                </form>
            </div>

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
                    <p>Aktivitas workflow akan muncul setelah terdapat proses pengajuan.</p>
                </div>
            @endif
        </section>

        <section class="bd-admin-surface bd-admin-recent-activity" aria-labelledby="recent-activity-title">
            <div class="bd-admin-surface__header">
                <div>
                    <p class="bd-admin-kicker">AKTIVITAS TERBARU</p>
                    <h2 id="recent-activity-title">Pembaruan terakhir</h2>
                </div>
                <a href="{{ route('admin.activity.index') }}">Lihat semua</a>
            </div>
            @if($activity['recent']->isNotEmpty())
                <ol class="bd-admin-recent-activity__list">
                    @foreach($activity['recent'] as $item)
                        <li>
                            <a href="{{ route('admin.applications.show', $item['application']->public_id) }}#history-title">
                                <strong>{{ $item['label'] }}</strong>
                                <span>{{ $item['application']->service->name }} <span aria-hidden="true">&middot;</span> ID ...{{ strtoupper(substr($item['application']->public_id, -6)) }}</span>
                                <time datetime="{{ $item['at']->toIso8601String() }}">{{ $item['at']->translatedFormat('d M, H:i') }}</time>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="bd-admin-empty-state bd-admin-empty-state--compact">
                    <h3>Belum ada aktivitas terbaru</h3>
                    <p>Pembaruan workflow akan muncul di sini.</p>
                </div>
            @endif
        </section>
    </div>

    <section class="bd-admin-surface bd-admin-priority-queue" aria-labelledby="priority-queue-title">
        <div class="bd-admin-surface__header">
            <div>
                <p class="bd-admin-kicker">ANTRIAN PRIORITAS</p>
                <h2 id="priority-queue-title">Pengajuan yang perlu dibuka berikutnya</h2>
                <p>Hanya pengajuan yang memerlukan tindakan admin ditampilkan di sini.</p>
            </div>
            <a href="{{ route('admin.applications.index', ['filter' => 'review']) }}">Lihat semua pengajuan</a>
        </div>

        @if($priorityQueue->isNotEmpty())
            <div class="bd-admin-table-wrap">
                <table class="bd-admin-table bd-admin-priority-queue__table">
                    <thead>
                        <tr><th>Pengajuan</th><th>Klien</th><th>Status / kebutuhan</th><th>Diperbarui</th><th><span class="sr-only">Tindakan</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach($priorityQueue as $application)
                            @php($nextAction = \App\Support\AdminApplicationPresenter::nextAction($application->status))
                            <tr>
                                <td><strong>{{ $application->service->name }}</strong><small>ID ...{{ strtoupper(substr($application->public_id, -6)) }}</small></td>
                                <td>{{ $application->user->name }}</td>
                                <td><x-admin.status-badge :status="$application->status" /><small>{{ $nextAction['description'] }}</small></td>
                                <td>{{ $application->updated_at->translatedFormat('d M, H:i') }}</td>
                                <td><a class="bd-admin-table-link" href="{{ route('admin.applications.show', $application->public_id) }}">Tinjau</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bd-admin-empty-state">
                <h3>Semua pekerjaan prioritas sudah ditangani</h3>
                <p>Tidak ada pengajuan yang memerlukan tindakan admin saat ini.</p>
            </div>
        @endif
    </section>
</div>
@endsection
