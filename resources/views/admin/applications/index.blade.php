@extends('layouts.admin')

@section('title', 'Pengajuan')
@section('admin_context', 'Pengajuan')

@section('content')
    <x-admin.page-header kicker="PENGAJUAN" title="Daftar pengajuan" description="Tinjau pekerjaan yang memerlukan keputusan, pantau proses aktif, dan buka detail pengajuan secara aman." />

    <section class="bd-admin-list-surface mt-8" aria-labelledby="application-list-title">
        <div class="bd-admin-list-toolbar">
            <div><h2 id="application-list-title">Semua pengajuan</h2><p>Urutan memprioritaskan pekerjaan operasional yang perlu ditindaklanjuti.</p></div>
            <form class="bd-admin-search" method="get" action="{{ route('admin.applications.index') }}"><label class="sr-only" for="application-search">Cari pengajuan</label><input id="application-search" name="q" type="search" value="{{ $search }}" placeholder="Cari ID, klien, email, atau layanan"><input type="hidden" name="filter" value="{{ $filter }}"><button class="bd-admin-button bd-admin-button--secondary" type="submit">Cari</button></form>
        </div>
        <nav class="bd-admin-filter-bar" aria-label="Filter pengajuan">@foreach($filters as $key => $label)<a href="{{ route('admin.applications.index', array_filter(['filter' => $key, 'q' => $search ?: null])) }}" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
        @if($applications->isNotEmpty())
            <div class="bd-admin-table-wrap"><table class="bd-admin-table"><thead><tr><th>ID pengajuan</th><th>Klien</th><th>Layanan</th><th>Status operasional</th><th>Diperbarui</th><th><span class="sr-only">Tindakan</span></th></tr></thead><tbody>
                @foreach($applications as $application)
                    @php($nextAction = \App\Support\AdminApplicationPresenter::nextAction($application->status))
                    <tr><td><strong>…{{ strtoupper(substr($application->public_id, -6)) }}</strong><small>{{ $nextAction['label'] }}</small></td><td><strong>{{ $application->user->name }}</strong><small>{{ $application->user->email }}</small></td><td>{{ $application->service->name }}</td><td><x-admin.status-badge :status="$application->status" /><small>{{ $nextAction['description'] }}</small></td><td>{{ $application->updated_at->translatedFormat('d M Y, H:i') }}</td><td><a class="bd-admin-table-link" href="{{ route('admin.applications.show', $application->public_id) }}">Tinjau</a></td></tr>
                @endforeach
            </tbody></table></div>
            <div class="bd-admin-pagination">{{ $applications->links() }}</div>
        @else
            <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Pengajuan tidak ditemukan' : ($filter === 'all' ? 'Belum ada pengajuan' : 'Tidak ada pengajuan pada tahap ini') }}</h3><p>{{ $search !== '' ? 'Coba gunakan kata kunci ID, nama klien, email, atau layanan lain.' : 'Pengajuan yang sesuai dengan filter ini akan muncul di sini.' }}</p></div>
        @endif
    </section>
@endsection
