<section class="bd-admin-list-surface mt-8 bd-admin-reactive-region" aria-labelledby="activity-list-title">
    <div class="bd-admin-list-toolbar"><div><h2 id="activity-list-title">Pembaruan terbaru</h2><p>Gunakan detail pengajuan untuk meninjau konteks dan tindakan yang tersedia.</p></div></div>
    <div class="bd-admin-filter-bar" role="group" aria-label="Filter aktivitas">
        @foreach($categories as $key => $label)
            <x-admin.filter-control action="setCategory" :value="$key" :active="$category === $key">{{ $label }}</x-admin.filter-control>
        @endforeach
    </div>
    <div class="bd-admin-reactive-results" wire:loading.class="bd-admin-reactive-results--loading" wire:loading.attr="aria-busy" wire:target="setCategory,setPage,gotoPage,previousPage,nextPage">
        <div class="bd-admin-reactive-loading" wire:loading.delay.flex style="display: none" wire:target="setCategory,setPage,gotoPage,previousPage,nextPage" role="status" aria-live="polite">
            <span class="bd-admin-reactive-loading__spinner" aria-hidden="true"></span>
            <span>Memuat...</span>
        </div>
        <div class="bd-admin-reactive-content">
    @if($activities->isNotEmpty())
        <div class="bd-admin-table-wrap"><table class="bd-admin-table bd-phone-records bd-phone-records--activity" role="table"><thead><tr><th scope="col">Peristiwa</th><th scope="col">Pengajuan</th><th scope="col">Klien</th><th scope="col">Kategori</th><th scope="col">Waktu</th><th scope="col"><span class="sr-only">Detail</span></th></tr></thead><tbody>@foreach($activities as $activity)<tr role="row"><td role="cell" data-label="Peristiwa"><span class="bd-phone-record-icon"><x-mobile-icon name="activity" :size="19" /></span><strong>{{ $activity['label'] }}</strong><small>{{ $activity['description'] }}</small></td><td role="cell" data-label="Pengajuan"><strong>…{{ strtoupper(substr($activity['application']->public_id, -6)) }}</strong><small>{{ $activity['application']->service->name }}</small></td><td role="cell" data-label="Klien">{{ $activity['client'] }}</td><td role="cell" data-label="Kategori"><x-admin.status-badge :label="$categories[$activity['category']]" tone="info" /></td><td role="cell" data-label="Waktu">{{ $activity['at']->translatedFormat('d M Y, H:i') }}</td><td role="cell"><a class="bd-admin-table-link" href="{{ route('admin.applications.show', $activity['application']->public_id) }}#history-title">Detail</a></td></tr>@endforeach</tbody></table></div>
        <div class="bd-admin-pagination">{{ $activities->links() }}</div>
    @else
        <div class="bd-admin-empty-state"><h3>Tidak ada aktivitas pada kategori ini</h3><p>Pembaruan operasional yang sesuai akan muncul di sini.</p></div>
    @endif
        </div>
    </div>
</section>
