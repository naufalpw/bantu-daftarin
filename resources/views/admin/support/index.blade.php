@extends('layouts.admin')
@section('title', 'Dukungan')
@section('admin_context', 'Dukungan')
@section('content')
    <div class="bd-admin-support-page">
        <x-admin.page-header kicker="DUKUNGAN" title="Kotak masuk dukungan" description="Kelola percakapan Bantuan Umum dan Dukungan Pengajuan." />

        <section class="bd-admin-list-surface bd-admin-support-surface mt-8" aria-labelledby="support-list-title">
            <div class="bd-admin-list-toolbar bd-admin-support-toolbar">
                <div>
                    <h2 id="support-list-title">Percakapan klien</h2>
                    <p>Pesan belum dibaca diprioritaskan pada daftar ini.</p>
                </div>
                <form class="bd-admin-search" method="get" action="{{ route('admin.support.index') }}">
                    <label class="sr-only" for="support-search">Cari percakapan</label>
                    <input id="support-search" type="search" name="q" value="{{ $search }}" placeholder="Cari percakapan">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <button class="bd-admin-button bd-admin-button--secondary" type="submit">Cari</button>
                </form>
            </div>

            <nav class="bd-admin-filter-bar" aria-label="Filter dukungan">
                @foreach(['all' => 'Semua', 'unread' => 'Belum dibaca', 'application' => 'Pengajuan', 'general' => 'Bantuan Umum'] as $key => $label)
                    <a href="{{ route('admin.support.index', array_filter(['filter' => $key, 'q' => $search ?: null])) }}" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>

            @if($threads->isNotEmpty())
                <div class="bd-admin-support-list">
                    @foreach($threads as $thread)
                        @php($isUnread = $thread->unread_client_messages_count > 0)
                        <a href="{{ route('admin.chat.show', $thread->public_id) }}" @class(['bd-admin-support-row', 'is-unread' => $isUnread]) aria-label="Buka percakapan {{ $thread->client?->name ?? 'klien' }}">
                            <span class="bd-admin-service-mark" aria-hidden="true">{{ strtoupper(mb_substr($thread->client?->name ?? 'K', 0, 1)) }}</span>
                            <span class="bd-admin-support-row__body">
                                <span class="bd-admin-support-row__heading"><strong>{{ $thread->client?->name ?? 'Klien' }}</strong><x-admin.status-badge :label="$thread->isGeneralSupport() ? 'Bantuan Umum' : 'Pengajuan'" :tone="$thread->isGeneralSupport() ? 'neutral' : 'info'" /></span>
                                <small>{{ $thread->isGeneralSupport() ? 'Percakapan tanpa konteks pengajuan.' : (($thread->application?->service?->name ?? 'Pengajuan').' · ID …'.strtoupper(substr($thread->application?->public_id ?? '', -6))) }}</small>
                                <span>{{ $thread->latestMessage?->body ?? 'Belum ada pesan.' }}</span>
                            </span>
                            <span class="bd-admin-support-row__meta"><time>{{ ($thread->last_message_at ?? $thread->updated_at)->translatedFormat('d M, H:i') }}</time>@if($isUnread)<b aria-label="{{ $thread->unread_client_messages_count }} pesan belum dibaca">{{ $thread->unread_client_messages_count }}</b>@endif<span class="bd-admin-support-row__open">Buka <span aria-hidden="true">&rarr;</span></span></span>
                        </a>
                    @endforeach
                </div>
                <div class="bd-admin-pagination">{{ $threads->links() }}</div>
            @else
                <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Percakapan tidak ditemukan' : ($filter === 'unread' ? 'Tidak ada pesan belum dibaca' : 'Belum ada percakapan') }}</h3><p>{{ $search !== '' ? 'Coba gunakan nama klien, layanan, atau ID pengajuan lain.' : 'Percakapan baru akan muncul saat klien menghubungi dukungan.' }}</p></div>
            @endif
        </section>
    </div>
@endsection
