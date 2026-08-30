@extends('layouts.app')
@section('content')
<div><a href="{{ auth()->user()->isAdmin() ? route('admin.applications.show', $thread->application->public_id) : route('client.applications.show', $thread->application->public_id) }}" class="text-sm text-indigo-700">← Kembali ke aplikasi</a><h1 class="mt-3 text-3xl font-semibold">Chat aplikasi</h1></div>
<div class="mt-8"><livewire:chat-thread :thread-id="$thread->public_id" /></div>
@endsection
