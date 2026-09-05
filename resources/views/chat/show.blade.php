@php($isAdmin = auth()->user()->isAdmin())
@php($isGeneralSupport = $thread->isGeneralSupport())
@extends($isAdmin ? 'layouts.app' : 'layouts.client')
@section('context_title', $isGeneralSupport ? 'Bantuan Umum' : 'Chat pengajuan')
@section('body_class', $isAdmin ? '' : 'pb-chat-body')

@section('content')
    @if($isAdmin)
        <div>
            <a href="{{ $isGeneralSupport ? route('admin.support.index') : route('admin.applications.show', $thread->application->public_id) }}" class="text-sm text-indigo-700">&larr; {{ $isGeneralSupport ? 'Kembali ke bantuan' : 'Kembali ke aplikasi' }}</a>
            <p class="mt-3 text-sm font-semibold uppercase tracking-wide text-indigo-700">{{ $isGeneralSupport ? 'Bantuan Umum' : 'Pengajuan' }}</p>
            <h1 class="mt-1 text-3xl font-bold">{{ $isGeneralSupport ? 'Percakapan dengan '.$thread->client->name : 'Chat aplikasi' }}</h1>
        </div>
        <div class="mt-8"><livewire:chat-thread :thread-id="$thread->public_id" /></div>
    @else
        <div class="pb-page pb-chat-page" data-node-id="208:20985">
            <a class="pb-back-link" href="{{ $isGeneralSupport ? route('qna') : route('client.applications.show', $thread->application->public_id) }}">
                <span aria-hidden="true">&larr;</span> {{ $isGeneralSupport ? 'Kembali ke Pusat Bantuan' : 'Kembali ke ruang pengajuan' }}
            </a>
            <header class="pb-page-heading">
                <p class="pb-kicker">{{ $isGeneralSupport ? 'Bantuan umum' : 'Chat pengajuan' }}</p>
                <h1>{{ $isGeneralSupport ? 'Percakapan dengan Admin' : 'Tanya tentang '.$thread->application->service->name }}</h1>
                <p>{{ $isGeneralSupport ? 'Gunakan percakapan ini untuk pertanyaan yang tidak terikat pada satu pengajuan.' : 'Percakapan ini hanya terkait pengajuan dengan ID …'.strtoupper(substr($thread->application->public_id, -4)).'.' }}</p>
            </header>
            <livewire:chat-thread :thread-id="$thread->public_id" />
        </div>
    @endif
@endsection
