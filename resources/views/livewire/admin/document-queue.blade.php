<section class="bd-admin-list-surface mt-8 bd-admin-reactive-region" aria-labelledby="document-queue-title">
    <div class="bd-admin-list-toolbar">
        <div><h2 id="document-queue-title">Dokumen untuk ditinjau</h2><p>Tinjau dokumen melalui detail pengajuan yang dapat Anda akses.</p></div>
        <form class="bd-admin-search" method="get" action="{{ route('admin.documents.index') }}" wire:submit.prevent="applySearch">
            <label class="sr-only" for="document-search">Cari pengajuan dokumen</label>
            <input id="document-search" name="q" type="search" value="{{ $search }}" wire:model.live.debounce.350ms="search" placeholder="Cari ID, klien, layanan, atau dokumen">
            <input type="hidden" name="filter" value="{{ $filter }}">
        </form>
    </div>
    <nav class="bd-admin-filter-bar" aria-label="Filter antrian dokumen">
        @foreach($filters as $key => $label)
            <a href="{{ route('admin.documents.index', array_filter(['filter' => $key === 'review' ? null : $key, 'q' => $search ?: null])) }}" wire:click.prevent="setFilter('{{ $key }}')" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="bd-admin-reactive-results" wire:loading.class="bd-admin-reactive-results--loading" wire:loading.attr="aria-busy" wire:target="setFilter,search,applySearch,setPage,gotoPage,previousPage,nextPage">
        <div class="bd-admin-reactive-loading" wire:loading.delay.flex style="display: none" wire:target="setFilter,search,applySearch,setPage,gotoPage,previousPage,nextPage" role="status" aria-live="polite">
            <span class="bd-admin-reactive-loading__spinner" aria-hidden="true"></span>
            <span>Memuat...</span>
        </div>
        <div class="bd-admin-reactive-content">
    @if($applications->isNotEmpty())
        <div class="bd-admin-table-wrap"><table class="bd-admin-table bd-admin-document-queue-table bd-phone-records bd-phone-records--documents" role="table"><thead><tr><th scope="col">Pengajuan</th><th scope="col">Dokumen</th><th scope="col">Status pemeriksaan</th><th scope="col">Diperbarui</th><th scope="col"><span class="sr-only">Tindakan</span></th></tr></thead><tbody>
            @foreach($applications as $application)
                @php($queueTone = $application->revision_required_count > 0 || $application->status->value === 'REVISION_REQUIRED' ? 'danger' : ($application->pending_review_count > 0 ? 'attention' : 'success'))
                    @php($queueLabel = $application->revision_required_count > 0 || $application->status->value === 'REVISION_REQUIRED' ? 'Perlu perbaikan' : ($application->pending_review_count > 0 ? $application->pending_review_count.' menunggu pemeriksaan' : 'Pemeriksaan selesai'))
                <tr role="row">
                    <td role="cell" data-label="Pengajuan"><span class="bd-phone-record-icon"><x-mobile-icon name="review" :size="19" /></span><strong>{{ $application->service->name }}</strong><small>{{ $application->user->name }} · ID …{{ strtoupper(substr($application->public_id, -6)) }}</small></td>
                    <td role="cell" data-label="Dokumen"><strong>{{ $application->available_documents_count }} dari {{ $application->document_requirements_count }} dokumen tersedia</strong><small>{{ $application->revision_required_count ? $application->revision_required_count.' dokumen perlu ditindaklanjuti' : 'Dokumen dikelompokkan per pengajuan.' }}</small></td>
                    <td role="cell" data-label="Status pemeriksaan"><x-admin.status-badge :label="$queueLabel" :tone="$queueTone" /><small>{{ $application->status->label() }}</small></td>
                    <td role="cell" data-label="Diperbarui">{{ $application->latest_document_uploaded_at?->translatedFormat('d M Y, H:i') ?? $application->updated_at->translatedFormat('d M Y, H:i') }}</td>
                    <td role="cell"><a class="bd-admin-table-link" href="{{ route('admin.applications.show', $application->public_id) }}#documents-title">Tinjau</a></td>
                </tr>
            @endforeach
        </tbody></table></div>
        <div class="bd-admin-pagination">{{ $applications->links() }}</div>
    @else
        <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Pengajuan tidak ditemukan' : 'Tidak ada pengajuan pada antrian ini' }}</h3><p>{{ $search !== '' ? 'Coba gunakan kata kunci lain.' : 'Pengajuan dengan dokumen yang relevan akan muncul di sini.' }}</p></div>
    @endif
        </div>
    </div>
</section>
