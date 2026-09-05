@extends('layouts.client')

@section('context_title', 'Pengajuan')
@section('body_class', 'pb-applications-body')

@section('content')
<div class="pb-page pb-applications-index">
    <header class="pb-page-heading pb-applications-index__heading">
        <p class="pb-kicker">Pengajuan</p>
        <h1>Semua pengajuan Anda</h1>
        <p>Lihat yang perlu ditindaklanjuti, sedang diproses, atau sudah selesai.</p>
    </header>

    @if($counts->sum() === 0)
        <section class="pb-empty" aria-labelledby="empty-applications-title">
            <h2 id="empty-applications-title">Belum ada pengajuan</h2>
            <p>Mulai dengan memilih layanan yang sesuai kebutuhan Anda.</p>
            <a class="pb-button pb-button--primary" href="{{ route('client.services.index') }}">Lihat layanan</a>
        </section>
    @else
        @php($filters = ['all' => 'Semua', 'action' => 'Perlu tindakan', 'processing' => 'Diproses', 'completed' => 'Selesai'])
        <nav class="pb-application-filters" aria-label="Filter pengajuan">
            @foreach($filters as $key => $label)
                <a href="{{ $key === 'all' ? route('client.applications.index') : route('client.applications.index', ['status' => $key]) }}" class="{{ $filter === $key ? 'is-active' : '' }}" @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <section class="pb-applications-results" aria-labelledby="applications-results-title">
            <div class="pb-section-heading">
                <div>
                    <p class="pb-kicker">{{ $filters[$filter] }}</p>
                    <h2 id="applications-results-title">{{ $filter === 'all' ? 'Daftar pengajuan' : $filters[$filter] }}</h2>
                </div>
            </div>
            @forelse($applications as $application)
                <x-application-list-item :application="$application" />
            @empty
                <div class="pb-inline-empty pb-inline-empty--card">
                    <strong>Tidak ada pengajuan {{ $filter === 'processing' ? 'yang sedang diproses' : strtolower($filters[$filter]) }}.</strong>
                    <span>Pengajuan pada tahap ini akan muncul di sini.</span>
                </div>
            @endforelse
        </section>
    @endif
</div>
@endsection
