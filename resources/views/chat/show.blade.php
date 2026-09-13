@php($isAdmin = auth()->user()->isAdmin())
@php($isGeneralSupport = $thread->isGeneralSupport())
@extends($isAdmin ? 'layouts.admin' : 'layouts.client')
@section('title', $isAdmin ? 'Percakapan Dukungan' : 'Bantuan Pengajuan')
@section('admin_context', 'Dukungan')
@section('context_title', $isGeneralSupport ? 'Bantuan Umum' : 'Bantuan pengajuan')
@section('body_class', $isAdmin ? '' : 'pb-chat-body')

@section('content')
    @if($isAdmin)
        <div class="bd-admin-chat-page">
            <a class="bd-admin-back-link" data-support-return href="{{ route('admin.support.index') }}">Kembali ke Dukungan</a>
            <header class="bd-admin-chat-context">
                <div>
                    <p class="bd-admin-kicker">{{ $isGeneralSupport ? 'BANTUAN UMUM' : 'DUKUNGAN PENGAJUAN' }}</p>
                    <h1>{{ $isGeneralSupport ? $thread->client->name : $thread->application->service->name }}</h1>
                    <p>{{ $isGeneralSupport ? 'Percakapan umum.' : 'ID …'.strtoupper(substr($thread->application->public_id, -6)).' · '.$thread->client->name }}</p>
                </div>
                @if(! $isGeneralSupport)
                    <a class="bd-admin-button bd-admin-button--secondary" href="{{ route('admin.applications.show', $thread->application->public_id) }}">Buka pengajuan</a>
                @endif
            </header>
            <div class="bd-admin-chat-surface mt-8"><livewire:chat-thread :thread-id="$thread->public_id" /></div>
        </div>
    @else
        <div class="pb-page pb-chat-page" data-node-id="208:20985">
            <a class="pb-back-link pb-chat-back" href="{{ $isGeneralSupport ? route('qna') : route('client.applications.show', $thread->application->public_id) }}">
                <x-ui-icon name="arrow-left" :size="18" class="pb-chat-back__icon" />
                <span class="pb-chat-back__compact">{{ $isGeneralSupport ? 'Pusat Bantuan' : 'Ruang pengajuan' }}</span>
                <span class="pb-chat-back__full">{{ $isGeneralSupport ? 'Kembali ke Pusat Bantuan' : 'Kembali ke ruang pengajuan' }}</span>
            </a>
            <header class="pb-page-heading pb-chat-page__intro">
                <p class="pb-kicker">{{ $isGeneralSupport ? 'Bantuan umum' : 'Bantuan pengajuan' }}</p>
                <h1>{{ $isGeneralSupport ? 'Percakapan dengan Tim Bantu Daftarin' : 'Tanya tentang '.$thread->application->service->name }}</h1>
                <p>{{ $isGeneralSupport ? 'Gunakan percakapan ini untuk pertanyaan umum.' : 'Percakapan ini terkait pengajuan dengan ID …'.strtoupper(substr($thread->application->public_id, -4)).'.' }}</p>
            </header>
            <livewire:chat-thread :thread-id="$thread->public_id" />
        </div>
    @endif
@endsection
