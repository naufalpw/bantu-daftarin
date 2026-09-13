<section class="bd-admin-list-surface bd-admin-support-surface mt-8 bd-admin-reactive-region" aria-labelledby="support-list-title">
    <div class="bd-admin-list-toolbar bd-admin-support-toolbar">
        <div><h2 id="support-list-title">Percakapan klien</h2><p>Pesan yang belum dibaca tampil lebih dulu.</p></div>
        <form class="bd-admin-search" method="get" action="{{ route('admin.support.index') }}" wire:submit.prevent="applySearch">
            <label class="sr-only" for="support-search">Cari percakapan</label>
            <input id="support-search" type="search" name="q" value="{{ $search }}" wire:model.live.debounce.350ms="search" placeholder="Cari percakapan">
            <input type="hidden" name="filter" value="{{ $filter }}">
        </form>
    </div>
    <nav class="bd-admin-filter-bar" aria-label="Filter dukungan">
        @foreach($filters as $key => $label)
            <a href="{{ route('admin.support.index', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}" wire:click.prevent="setFilter('{{ $key }}')" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @error('archive')<p class="bd-admin-alert bd-admin-alert--danger" role="alert">{{ $message }}</p>@enderror
    <div class="bd-admin-reactive-results" wire:loading.class="bd-admin-reactive-results--loading" wire:loading.attr="aria-busy" wire:target="setFilter,search,applySearch,archive,unarchive,setPage,gotoPage,previousPage,nextPage">
        <div class="bd-admin-reactive-loading" wire:loading.delay.flex style="display: none" wire:target="setFilter,search,applySearch,archive,unarchive,setPage,gotoPage,previousPage,nextPage" role="status" aria-live="polite">
            <span class="bd-admin-reactive-loading__spinner" aria-hidden="true"></span>
            <span>Memuat...</span>
        </div>
        <div class="bd-admin-reactive-content">
    @if($threads->isNotEmpty())
        <div class="bd-admin-support-list">
            @foreach($threads as $thread)
                @php($isUnread = $thread->unread_client_messages_count > 0)
                <div class="bd-admin-support-item">
                    <a href="{{ route('admin.chat.show', $thread->public_id) }}" data-support-thread @class(['bd-admin-support-row', 'is-unread' => $isUnread]) aria-label="Percakapan dengan {{ $thread->client?->name ?? 'klien' }}">
                        <span class="bd-admin-service-mark" aria-hidden="true">{{ strtoupper(mb_substr($thread->client?->name ?? 'K', 0, 1)) }}</span>
                        <span class="bd-admin-support-row__body">
                            <span class="bd-admin-support-row__heading"><strong>{{ $thread->client?->name ?? 'Klien' }}</strong><x-admin.status-badge :label="$thread->isGeneralSupport() ? 'Bantuan Umum' : 'Pengajuan'" :tone="$thread->isGeneralSupport() ? 'neutral' : 'info'" />@if($filter === 'archived')<em>Diarsipkan</em>@endif</span>
                            <small>{{ $thread->isGeneralSupport() ? 'Percakapan umum.' : (($thread->application?->service?->name ?? 'Pengajuan').' · ID …'.strtoupper(substr($thread->application?->public_id ?? '', -6))) }}</small>
                            <span>{{ $thread->latestMessage?->displayBody() ?? 'Belum ada pesan.' }}</span>
                        </span>
                        <span class="bd-admin-support-row__meta"><time>{{ ($thread->last_message_at ?? $thread->updated_at)->translatedFormat('d M, H:i') }}</time>@if($isUnread)<b aria-label="{{ $thread->unread_client_messages_count }} pesan belum dibaca">{{ $thread->unread_client_messages_count }}</b>@endif</span>
                    </a>
                    @if($filter === 'archived' || ! $isUnread)
                        <details class="bd-admin-support-item__archive" x-data @keydown.escape.window="$el.open = false">
                            <summary aria-label="Tindakan percakapan"><span aria-hidden="true">&hellip;</span></summary>
                            <form method="post" action="{{ $filter === 'archived' ? route('admin.support.unarchive', $thread->public_id) : route('admin.support.archive', $thread->public_id) }}" wire:submit.prevent="{{ $filter === 'archived' ? 'unarchive' : 'archive' }}('{{ $thread->public_id }}')">
                                @csrf
                                <button type="submit">{{ $filter === 'archived' ? 'Keluarkan dari arsip' : 'Arsipkan' }}</button>
                            </form>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="bd-admin-pagination">{{ $threads->links() }}</div>
    @else
        <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Percakapan tidak ditemukan' : ($filter === 'unread' ? 'Tidak ada pesan belum dibaca' : ($filter === 'archived' ? 'Arsip percakapan kosong' : 'Belum ada percakapan')) }}</h3><p>{{ $search !== '' ? 'Coba gunakan nama klien, layanan, atau ID pengajuan lain.' : ($filter === 'archived' ? 'Percakapan yang Anda arsipkan akan muncul di sini.' : 'Percakapan baru akan muncul saat klien menghubungi dukungan.') }}</p></div>
    @endif
        </div>
    </div>
</section>
