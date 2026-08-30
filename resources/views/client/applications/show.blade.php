@extends('layouts.app')

@section('content')
@include('components.application-progress', ['status' => $application->status])

<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('client.dashboard') }}" class="text-sm text-indigo-700">Kembali ke dashboard</a>
        <h1 class="mt-3 text-3xl font-semibold">{{ $application->service->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $application->public_id }}</p>
    </div>
    <span class="rounded-full bg-indigo-50 px-4 py-2 text-sm text-indigo-700">{{ $application->status->label() }}</span>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-[1fr_360px]">
    <main class="space-y-6">
        @if(in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS'], true))
            <livewire:application-details-form :application="$application" />
        @endif

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Checklist dokumen</h2>
                    <p class="mt-1 text-sm text-slate-500">File disimpan secara private dan hanya dapat diakses setelah pemeriksaan izin.</p>
                </div>
            </div>
            <div class="mt-5 space-y-4">
                @foreach($application->requirements as $requirement)
                    @php($activeDocument = $requirement->documents->where('active', true)->first())
                    <div class="rounded-lg border p-4">
                        <div class="flex flex-wrap justify-between gap-2">
                            <div>
                                <p class="font-medium">{{ $requirement->name }} @if($requirement->is_required)<span class="text-rose-600">*</span>@endif</p>
                                <p class="text-xs text-slate-500">{{ strtoupper(implode(', ', $requirement->allowed_extensions)) }} · maksimal {{ number_format($requirement->max_size_bytes / 1048576, 0) }} MB</p>
                            </div>
                            <span class="text-sm {{ $requirement->status === 'ACCEPTED' ? 'text-emerald-700' : ($requirement->status === 'REVISION_REQUIRED' ? 'text-rose-700' : 'text-slate-600') }}">{{ $requirement->statusLabel() }}</span>
                        </div>

                        @if($activeDocument)
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded bg-slate-50 p-3 text-sm">
                                <span>Versi {{ $activeDocument->version_number }} · {{ $activeDocument->review_status->label() }}</span>
                                <a class="text-indigo-700 hover:underline" target="_blank" rel="noopener" href="{{ route('client.documents.view', $activeDocument->public_id) }}">Lihat</a>
                                <a class="text-indigo-700 hover:underline" href="{{ route('client.documents.download', $activeDocument->public_id) }}">Unduh</a>
                                @if(in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS'], true) || ($application->status->value === 'REVISION_REQUIRED' && $requirement->status === 'REVISION_REQUIRED'))
                                    <form method="post" action="{{ route('client.documents.destroy', $activeDocument->public_id) }}">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-700" onclick="return confirm('Hapus versi aktif ini?')">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if(in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS', 'DOCUMENTS_READY_FOR_PAYMENT', 'AWAITING_PAYMENT'], true) || ($application->status->value === 'REVISION_REQUIRED' && $requirement->status === 'REVISION_REQUIRED'))
                            <form method="post" enctype="multipart/form-data" action="{{ route('client.documents.store', [$application->public_id, $requirement->public_id]) }}" class="mt-3 flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf" class="text-sm">
                                <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white">{{ $activeDocument ? 'Unggah versi baru' : 'Unggah' }}</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        @if($application->resultDocuments->isNotEmpty())
            <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold">Hasil layanan</h2>
                <div class="mt-4 space-y-3">
                    @foreach($application->resultDocuments->filter(fn ($result) => $result->verification_status->value === 'VERIFIED') as $result)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 p-3 text-sm">
                            <span>{{ str_replace('_', ' ', $result->type->value) }}</span>
                            <a class="text-indigo-700 hover:underline" target="_blank" rel="noopener" href="{{ route('client.results.view', $result->public_id) }}">Lihat</a>
                            <a class="text-indigo-700 hover:underline" href="{{ route('client.results.download', $result->public_id) }}">Unduh hasil</a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <aside class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Langkah berikutnya</h2>
            <div class="mt-4 space-y-3 text-sm">
                @if($application->status->value === 'DRAFT')
                    <form method="post" action="{{ route('client.applications.submit', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Lanjut ke dokumen</button></form>
                @elseif($application->status->value === 'AWAITING_DOCUMENTS')
                    <p class="rounded-lg bg-amber-50 p-3 text-amber-800">Lengkapi seluruh dokumen wajib terlebih dahulu. Setelah itu tombol pembayaran akan tersedia.</p>
                @elseif(in_array($application->status->value, ['DOCUMENTS_READY_FOR_PAYMENT', 'AWAITING_PAYMENT'], true))
                    <form method="post" action="{{ route('client.applications.payment', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Bayar {{ $application->currency }} {{ number_format((float) $application->price_amount_snapshot, 0, ',', '.') }}</button></form>
                @elseif($application->status->value === 'PAYMENT_CONFIRMED')
                    <form method="post" action="{{ route('client.applications.documents.submit', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Kirim dokumen untuk diperiksa</button></form>
                @elseif($application->status->value === 'REVISION_REQUIRED')
                    <form method="post" action="{{ route('client.applications.revision.submit', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Kirim perbaikan</button></form>
                @endif
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Riwayat status</h2>
            <ol class="mt-4 space-y-3 text-sm">
                @foreach($application->statusHistories as $history)
                    <li class="border-l-2 border-indigo-200 pl-3"><p class="font-medium">{{ $history->to_status->label() }}</p><p class="text-xs text-slate-500">{{ $history->created_at->translatedFormat('d M Y H:i') }}</p></li>
                @endforeach
            </ol>
        </section>

        @if($application->chatThread)
            <a href="{{ route('client.chat.show', $application->chatThread->public_id) }}" class="block rounded-xl bg-indigo-700 p-5 text-white"><p class="font-semibold">Chat dengan admin</p><p class="mt-1 text-sm text-indigo-100">Tanyakan hal terkait aplikasi ini.</p></a>
        @endif
    </aside>
</div>
@endsection
