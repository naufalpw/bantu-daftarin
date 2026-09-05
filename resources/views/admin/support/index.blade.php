@extends('layouts.app')

@section('content')
    <div class="flex items-end justify-between gap-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Bantuan</p>
            <h1 class="mt-2 text-3xl font-bold">Percakapan klien</h1>
            <p class="mt-2 text-sm text-slate-600">Bantuan umum dan chat pengajuan menggunakan alur pesan yang sama.</p>
        </div>
    </div>

    <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white" aria-label="Daftar percakapan bantuan">
        @forelse($threads as $thread)
            <a href="{{ route('admin.chat.show', $thread->public_id) }}" class="grid gap-4 border-b border-slate-200 p-5 last:border-b-0 hover:bg-slate-50 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $thread->isGeneralSupport() ? 'Bantuan Umum' : 'Pengajuan' }}</span><strong>{{ $thread->client?->name ?? 'Klien' }}</strong></div>
                    @if($thread->isGeneralSupport())
                        <p class="mt-2 text-sm text-slate-600">Pertanyaan umum tanpa konteks pengajuan.</p>
                    @else
                        <p class="mt-2 text-sm text-slate-600">{{ $thread->application?->service?->name ?? 'Layanan pengajuan' }} · ID …{{ strtoupper(substr($thread->application?->public_id ?? '', -4)) }}</p>
                    @endif
                    <p class="mt-2 truncate text-sm {{ $thread->latestMessage ? 'text-slate-700' : 'text-slate-500' }}">{{ $thread->latestMessage?->body ?? 'Belum ada pesan.' }}</p>
                </div>
                <span class="text-sm font-semibold text-indigo-700">Buka percakapan</span>
            </a>
        @empty
            <div class="p-8 text-center"><h2 class="font-semibold">Belum ada percakapan</h2><p class="mt-2 text-sm text-slate-600">Percakapan bantuan akan tampil di sini setelah dibuat.</p></div>
        @endforelse
    </section>
@endsection
