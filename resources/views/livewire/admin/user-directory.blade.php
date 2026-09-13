<section class="bd-admin-list-surface mt-8 bd-admin-reactive-region" aria-labelledby="user-list-title">
    <div class="bd-admin-list-toolbar"><div><h2 id="user-list-title">Pelanggan</h2><p>Direktori ini bersifat baca-saja.</p></div><form class="bd-admin-search" method="get" action="{{ route('admin.users.index') }}" wire:submit.prevent="applySearch"><label class="sr-only" for="user-search">Cari pengguna</label><input id="user-search" name="q" type="search" value="{{ $search }}" wire:model.live.debounce.350ms="search" placeholder="Cari nama atau email"><input type="hidden" name="filter" value="{{ $filter }}"></form></div>
    <nav class="bd-admin-filter-bar" aria-label="Filter pengguna">
        @foreach($filters as $key => $label)
            <a href="{{ route('admin.users.index', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}" wire:click.prevent="setFilter('{{ $key }}')" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="bd-admin-reactive-results" wire:loading.class="bd-admin-reactive-results--loading" wire:loading.attr="aria-busy" wire:target="setFilter,search,applySearch,setPage,gotoPage,previousPage,nextPage">
        <div class="bd-admin-reactive-loading" wire:loading.delay.flex style="display: none" wire:target="setFilter,search,applySearch,setPage,gotoPage,previousPage,nextPage" role="status" aria-live="polite">
            <span class="bd-admin-reactive-loading__spinner" aria-hidden="true"></span>
            <span>Memuat...</span>
        </div>
        <div class="bd-admin-reactive-content">
    @if($users->isNotEmpty())
        <div class="bd-admin-table-wrap"><table class="bd-admin-table bd-phone-records bd-phone-records--users" role="table"><thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Status akun</th><th scope="col">Pengajuan</th><th scope="col">Bergabung</th><th scope="col"><span class="sr-only">Detail</span></th></tr></thead><tbody>@foreach($users as $user)<tr role="row"><td role="cell" data-label="Nama"><span class="bd-phone-record-icon"><x-mobile-icon name="user" :size="19" /></span><strong>{{ $user->name }}</strong></td><td role="cell" data-label="Email">{{ $user->email }}</td><td role="cell" data-label="Status akun"><x-admin.status-badge :label="$user->is_active ? 'Aktif' : 'Tidak aktif'" :tone="$user->is_active ? 'success' : 'neutral'" /></td><td role="cell" data-label="Pengajuan">{{ $user->applications_count }}</td><td role="cell" data-label="Bergabung">{{ $user->created_at->translatedFormat('d M Y') }}</td><td role="cell"><a class="bd-admin-table-link" href="{{ route('admin.users.show', $user->public_id) }}">Detail</a></td></tr>@endforeach</tbody></table></div><div class="bd-admin-pagination">{{ $users->links() }}</div>
    @else
        <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Pengguna tidak ditemukan' : 'Belum ada pelanggan' }}</h3><p>{{ $search !== '' ? 'Coba gunakan nama atau alamat email lain.' : 'Pelanggan terdaftar akan muncul di sini.' }}</p></div>
    @endif
        </div>
    </div>
</section>
