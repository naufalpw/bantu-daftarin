<section class="bd-admin-list-surface mt-8 bd-admin-reactive-region" aria-labelledby="application-list-title">
    <div class="bd-admin-list-toolbar">
        <div><h2 id="application-list-title">Semua pengajuan</h2><p>Pengajuan yang perlu ditindaklanjuti ditampilkan lebih dulu.</p></div>
        <form class="bd-admin-search" method="get" action="{{ route('admin.applications.index') }}" wire:submit.prevent="applySearch">
            <label class="sr-only" for="application-search">Cari pengajuan</label>
            <input id="application-search" name="q" type="search" value="{{ $search }}" wire:model.live.debounce.350ms="search" placeholder="Cari ID, klien, email, atau layanan">
            <input type="hidden" name="filter" value="{{ $filter }}">
        </form>
    </div>
    <nav class="bd-admin-filter-bar" aria-label="Filter pengajuan">
        @foreach($filters as $key => $label)
            <a href="{{ route('admin.applications.index', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}" wire:click.prevent="setFilter('{{ $key }}')" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="bd-admin-reactive-results" wire:loading.class="bd-admin-reactive-results--loading" wire:loading.attr="aria-busy" wire:target="setFilter,search,applySearch,setPage,gotoPage,previousPage,nextPage">
        <div class="bd-admin-reactive-loading" wire:loading.delay.flex style="display: none" wire:target="setFilter,search,applySearch,setPage,gotoPage,previousPage,nextPage" role="status" aria-live="polite">
            <span class="bd-admin-reactive-loading__spinner" aria-hidden="true"></span>
            <span>Memuat...</span>
        </div>
        <div class="bd-admin-reactive-content">
    @if($applications->isNotEmpty())
        <div class="bd-admin-table-wrap"><table class="bd-admin-table bd-phone-records bd-phone-records--applications" role="table"><thead><tr><th scope="col">ID pengajuan</th><th scope="col">Klien</th><th scope="col">Layanan</th><th scope="col">Status operasional</th><th scope="col">Diperbarui</th><th scope="col"><span class="sr-only">Tindakan</span></th></tr></thead><tbody>
            @foreach($applications as $application)
                @php($nextAction = \App\Support\AdminApplicationPresenter::nextAction($application->status))
                <tr role="row">
                    <td role="cell" data-label="ID pengajuan"><span class="bd-phone-record-icon"><x-mobile-icon name="application" :size="19" /></span><strong>…{{ strtoupper(substr($application->public_id, -6)) }}</strong><small>{{ $nextAction['label'] }}</small></td>
                    <td role="cell" data-label="Klien"><strong>{{ $application->user->name }}</strong><small>{{ $application->user->email }}</small></td>
                    <td role="cell" data-label="Layanan">{{ $application->service->name }}</td>
                    <td role="cell" data-label="Status operasional"><x-admin.status-badge :status="$application->status" /><small>{{ $nextAction['description'] }}</small></td>
                    <td role="cell" data-label="Diperbarui">{{ $application->updated_at->translatedFormat('d M Y, H:i') }}</td>
                    <td role="cell"><a class="bd-admin-table-link" href="{{ route('admin.applications.show', $application->public_id) }}">Tinjau</a></td>
                </tr>
            @endforeach
        </tbody></table></div>
        <div class="bd-admin-pagination">{{ $applications->links() }}</div>
    @else
        <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Pengajuan tidak ditemukan' : ($filter === 'all' ? 'Belum ada pengajuan' : 'Tidak ada pengajuan pada tahap ini') }}</h3><p>{{ $search !== '' ? 'Coba gunakan kata kunci ID, nama klien, email, atau layanan lain.' : 'Pengajuan yang sesuai dengan filter ini akan muncul di sini.' }}</p></div>
    @endif
        </div>
    </div>
</section>
