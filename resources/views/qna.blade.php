@php($isClient = auth()->check() && auth()->user()?->isClient())
@extends($isClient ? 'layouts.client' : 'layouts.marketing')
@section('title', 'Pusat Bantuan - Bantu Daftarin')
@section('context_title', 'Bantuan')
@section('body_class', $isClient ? 'pb-help-body' : 'pb-public-body pb-help-body')

@section('content')
@if(!$isClient)<x-site-header />@endif

<div @if(!$isClient) id="main-content" @endif class="pb-page pb-help-center" data-help-center>
    <header class="pb-page-heading pb-help-center__heading">
        <p class="pb-kicker">Bantuan</p>
        <h1>Pusat Bantuan</h1>
        <p>Cari jawaban umum atau bantuan untuk pengajuan Anda.</p>
    </header>

    <div class="pb-help-search">
        <label class="pb-sr-only" for="help-search">Cari pertanyaan</label>
        <span class="pb-help-search__icon" aria-hidden="true"></span>
        <input id="help-search" type="search" autocomplete="off" placeholder="Cari pertanyaan, mis. dokumen, pembayaran, revisi…" data-help-search>
    </div>

    <div class="pb-help-filters" role="group" aria-label="Filter kategori pertanyaan">
        @foreach($categories as $category)
            <button type="button" data-help-category="{{ $category['id'] }}" aria-pressed="{{ $category['id'] === 'all' ? 'true' : 'false' }}">{{ $category['label'] }}</button>
        @endforeach
    </div>

    <div class="pb-help-layout @if(!$isClient) pb-help-layout--public @endif">
        <section class="pb-help-faq" aria-labelledby="help-faq-title">
            <div class="pb-help-faq__heading">
                <div>
                    <p class="pb-kicker">Pengetahuan umum</p>
                    <h2 id="help-faq-title">Pertanyaan umum</h2>
                </div>
                <p data-help-result-status aria-live="polite">{{ count($questions) }} jawaban tersedia</p>
            </div>

            <div class="pb-help-faq__list" data-help-list>
                @foreach($questions as $index => $item)
                    @php($searchable = implode(' ', [$item['question'], $item['answer'], \App\Support\HelpFaq::categoryLabel($item['category']), ...$item['keywords']]))
                    <details class="pb-help-faq__item" data-help-item data-category="{{ $item['category'] }}" data-search="{{ $searchable }}" @if($index === 0) open @endif>
                        <summary aria-controls="help-answer-{{ $item['id'] }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                            <span>{{ $item['question'] }}</span>
                            <span class="pb-help-faq__chevron" aria-hidden="true"></span>
                        </summary>
                        <div id="help-answer-{{ $item['id'] }}" class="pb-help-faq__answer"><p>{{ $item['answer'] }}</p></div>
                    </details>
                @endforeach
            </div>

            <div class="pb-help-empty" data-help-empty hidden>
                <h3>Pertanyaan tidak ditemukan</h3>
                <p>Tidak ada jawaban untuk “<span data-help-empty-query></span>”. Coba kata kunci lain atau hubungi admin jika masih perlu bantuan.</p>
                <div class="pb-help-empty__actions">
                    <button class="pb-button pb-button--secondary" type="button" data-help-clear>Hapus pencarian</button>
                    @if($isClient)
                        <form method="post" action="{{ route('client.help.chat.store') }}">@csrf<button class="pb-button pb-button--primary" type="submit">Hubungi Admin</button></form>
                    @else
                        <a class="pb-button pb-button--primary" href="{{ route('login') }}">Masuk untuk bantuan</a>
                    @endif
                </div>
            </div>
        </section>

        @if($isClient)
            <aside class="pb-help-sidebar" aria-label="Jalur bantuan">
                <section class="pb-help-support-card pb-help-support-card--applications" aria-labelledby="application-help-title">
                    <p class="pb-kicker">Bantuan pengajuan</p>
                    <h2 id="application-help-title">Pilih pengajuan</h2>
                    <p class="pb-help-support-card__intro">Tim kami dapat melihat konteksnya.</p>
                    <div class="pb-help-applications">
                        @forelse($helpApplications as $item)
                            @php($application = $item['application'])
                            <article class="pb-help-application">
                                <div class="pb-help-application__heading">
                                    <div><h3>{{ $application->service->name }}</h3><span>ID …{{ strtoupper(substr($application->public_id, -4)) }}</span></div>
                                    <span class="pb-status pb-status--{{ $item['status']['tone'] }}">{{ $item['status']['label'] }}</span>
                                </div>
                                <a class="pb-button pb-button--secondary pb-button--wide" href="{{ route('client.chat.show', $application->chatThread->public_id) }}">Tanya tentang pengajuan ini</a>
                            </article>
                        @empty
                            <div class="pb-help-no-application">
                                <strong>Belum ada pengajuan</strong>
                                <p>Chat terkait pengajuan tersedia setelah Anda membuat pengajuan.</p>
                                <a class="pb-button pb-button--secondary pb-button--wide" href="{{ route('client.services.index') }}">Lihat layanan</a>
                            </div>
                        @endforelse
                    </div>
                    @if($hasMoreApplications)<a class="pb-help-all-applications" href="{{ route('client.applications.index') }}">Lihat semua pengajuan</a>@endif
                </section>

                <section class="pb-help-support-card pb-help-support-card--conversations" aria-labelledby="conversation-list-title">
                    <p class="pb-kicker">Percakapan</p>
                    <h2 id="conversation-list-title">{{ $conversationFilter === 'archived' ? 'Arsip percakapan' : 'Percakapan tersimpan' }}</h2>
                    <nav class="pb-help-conversation-filters" aria-label="Filter percakapan">
                        <a href="{{ route('qna', ['conversation_filter' => 'active']) }}" @if($conversationFilter === 'active') aria-current="page" @endif>Percakapan</a>
                        <a href="{{ route('qna', ['conversation_filter' => 'archived']) }}" @if($conversationFilter === 'archived') aria-current="page" @endif>Arsip</a>
                    </nav>
                    <div class="pb-help-conversations">
                        @forelse($helpThreads as $thread)
                            <a href="{{ route('client.chat.show', $thread->public_id) }}" class="pb-help-conversation">
                                <strong>{{ $thread->isGeneralSupport() ? 'Bantuan Umum' : ($thread->application?->service?->name ?? 'Pengajuan') }}</strong>
                                <span>{{ $thread->latestMessage?->displayBody() ?? 'Belum ada pesan.' }}</span>
                                <small>{{ ($thread->last_message_at ?? $thread->updated_at)->translatedFormat('d M, H:i') }}</small>
                            </a>
                        @empty
                            <p class="pb-help-support-card__intro">{{ $conversationFilter === 'archived' ? 'Belum ada percakapan di arsip.' : 'Belum ada percakapan tersimpan.' }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="pb-help-support-card pb-help-support-card--general" aria-labelledby="general-help-title">
                    <p class="pb-kicker">Bantuan umum</p>
                    <h2 id="general-help-title">Masih belum menemukan jawaban?</h2>
                    <p>Tanyakan langsung kepada admin untuk masalah yang tidak terikat pada satu pengajuan.</p>
                    <form method="post" action="{{ route('client.help.chat.store') }}">@csrf<button class="pb-button pb-button--primary pb-button--wide" type="submit">Hubungi Admin</button></form>
                </section>
            </aside>
        @else
            <aside class="pb-help-sidebar" aria-label="Bantuan akun">
                <section class="pb-help-support-card pb-help-support-card--general">
                    <p class="pb-kicker">Bantuan akun</p>
                    <h2>Perlu bantuan lebih lanjut?</h2>
                    <p>Masuk untuk membuka bantuan sesuai pengajuan atau menghubungi admin.</p>
                    <a class="pb-button pb-button--primary pb-button--wide" href="{{ route('login') }}">Masuk ke akun</a>
                </section>
            </aside>
        @endif
    </div>
</div>
@endsection
