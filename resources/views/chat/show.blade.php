@if(auth()->user()->isAdmin())
    @extends('layouts.app')
@else
    @extends('layouts.marketing')
    @section('body_class', 'bd-phase3-body bd-chat-body')
@endif

@section('content')
    @if(auth()->user()->isAdmin())
        <div><a href="{{ route('admin.applications.show', $thread->application->public_id) }}" class="text-sm text-indigo-700">&larr; Kembali ke aplikasi</a><h1 class="mt-3 text-3xl font-semibold">Chat aplikasi</h1></div>
        <div class="mt-8"><livewire:chat-thread :thread-id="$thread->public_id" /></div>
    @else
        <x-site-header variant="payment" :chat-url="route('client.chat.show', $thread->public_id)" />
        <main class="bd-phase3-main bd-chat-page" data-node-id="208:20985">
            <a class="bd-phase3-back" href="{{ route('client.applications.show', $thread->application->public_id) }}">
                <img src="{{ asset('images/figma/phase3/chat/back.svg') }}" alt="">
                <span>Kembali</span>
            </a>
            <livewire:chat-thread :thread-id="$thread->public_id" />
        </main>
    @endif
@endsection
