@extends('layouts.admin')

@section('title', 'Dashboard')
@section('admin_context', 'Dashboard operasional')

@section('content')
<div class="bd-admin-dashboard">
    <x-admin.page-header kicker="DASHBOARD" title="Ruang kerja admin" description="Lihat pengajuan yang perlu ditindaklanjuti dan aktivitas terbaru.">
        <a class="bd-admin-button bd-admin-button--primary" href="{{ route('admin.applications.index') }}">Lihat semua pengajuan</a>
    </x-admin.page-header>

    @php
        $attentionItems = [
            ['key' => 'review', 'label' => 'Pengajuan perlu ditinjau', 'action' => 'Buka pengajuan', 'href' => route('admin.applications.index', ['filter' => 'review'])],
            ['key' => 'revision', 'label' => 'Revisi masuk', 'action' => 'Tinjau dokumen', 'href' => route('admin.documents.index', ['filter' => 'revision'])],
            ['key' => 'result', 'label' => 'Hasil perlu diverifikasi', 'action' => 'Tinjau hasil', 'href' => route('admin.applications.index', ['filter' => 'result'])],
            ['key' => 'support', 'label' => 'Pesan belum dibaca', 'action' => 'Buka dukungan', 'href' => route('admin.support.index', ['filter' => 'unread'])],
        ];
    @endphp

    <section class="bd-admin-surface bd-admin-attention-panel" aria-labelledby="attention-title">
        <div class="bd-admin-surface__header">
            <div><h2 id="attention-title">Perlu ditindaklanjuti</h2></div>
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

    <div id="dashboard-priority-position" class="bd-phone-operation"></div>
    <div class="bd-admin-operational-grid">
        <div id="dashboard-recent-position" class="bd-phone-operation"></div>
        <livewire:admin.activity-chart :period="$period" :periods="$periods" :activity="$activity" />

        <section class="bd-admin-surface bd-admin-recent-activity" data-phone-move-to="#dashboard-recent-position" aria-labelledby="recent-activity-title">
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
                </div>
            @endif
        </section>
    </div>

    <section class="bd-admin-surface bd-admin-priority-queue" data-phone-move-to="#dashboard-priority-position" aria-labelledby="priority-queue-title">
        <div class="bd-admin-surface__header">
            <div>
                <h2 id="priority-queue-title">Antrian prioritas</h2>
                <p>Pengajuan yang memerlukan tindakan admin.</p>
            </div>
            <a href="{{ route('admin.applications.index', ['filter' => 'review']) }}">Lihat semua pengajuan</a>
        </div>

        @if($priorityQueue->isNotEmpty())
            <div class="bd-admin-table-wrap">
                <table class="bd-admin-table bd-admin-priority-queue__table bd-phone-records bd-phone-records--priority" role="table">
                    <thead>
                        <tr><th scope="col">Pengajuan</th><th scope="col">Klien</th><th scope="col">Status / kebutuhan</th><th scope="col">Diperbarui</th><th scope="col"><span class="sr-only">Tindakan</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach($priorityQueue as $application)
                            @php($nextAction = \App\Support\AdminApplicationPresenter::nextAction($application->status))
                            <tr role="row">
                                <td role="cell" data-label="Pengajuan"><strong>{{ $application->service->name }}</strong><small>ID ...{{ strtoupper(substr($application->public_id, -6)) }}</small></td>
                                <td role="cell" data-label="Klien">{{ $application->user->name }}</td>
                                <td role="cell" data-label="Status / kebutuhan"><x-admin.status-badge :status="$application->status" /><small>{{ $nextAction['description'] }}</small></td>
                                <td role="cell" data-label="Diperbarui">{{ $application->updated_at->translatedFormat('d M, H:i') }}</td>
                                <td role="cell"><a class="bd-admin-table-link" href="{{ route('admin.applications.show', $application->public_id) }}">Tinjau</a></td>
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
