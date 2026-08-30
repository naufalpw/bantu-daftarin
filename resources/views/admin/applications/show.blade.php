@extends('layouts.app')

@section('content')
@if($application->status->value === 'COMPLETED')
    <form method="post" action="{{ route('admin.applications.archive', $application->public_id) }}" class="mb-4">
        @csrf
        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm" onclick="return confirm('Arsipkan aplikasi ini?')">Arsipkan aplikasi</button>
    </form>
@endif

@if(in_array($application->status->value, ['RESULT_UPLOADED', 'RESULT_REVIEW'], true))
    <form method="post" enctype="multipart/form-data" action="{{ route('admin.applications.results.upload', $application->public_id) }}" class="mb-4 flex flex-wrap items-center gap-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        @csrf
        <input type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf" class="text-sm">
        <select name="type" class="rounded border-slate-300 text-sm">
            <option value="PRIMARY_RESULT">Hasil utama</option>
            <option value="SUPPORTING_DOCUMENT">Dokumen pendukung</option>
            <option value="RECEIPT">Kwitansi</option>
            <option value="REPORT">Laporan</option>
            <option value="OTHER">Lainnya</option>
        </select>
        <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white">Tambah hasil</button>
    </form>
@endif

<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.applications.index') }}" class="text-sm text-indigo-700">Kembali ke semua aplikasi</a>
        <h1 class="mt-3 text-3xl font-semibold">{{ $application->service->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $application->user->email }} · {{ $application->public_id }}</p>
    </div>
    <span class="rounded-full bg-indigo-50 px-4 py-2 text-sm text-indigo-700">{{ $application->status->label() }}</span>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Data aplikasi</h2>
            <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                @if($application->personalDetails)
                    <div><dt class="text-slate-500">Nama</dt><dd>{{ $application->personalDetails->name }}</dd></div>
                    <div><dt class="text-slate-500">Email</dt><dd>{{ $application->personalDetails->email }}</dd></div>
                    <div><dt class="text-slate-500">Jenis kelamin</dt><dd>{{ $application->personalDetails->gender ?: '-' }}</dd></div>
                @else
                    <div><dt class="text-slate-500">Badan usaha</dt><dd>{{ $application->businessDetails?->business_name }}</dd></div>
                    <div><dt class="text-slate-500">Jenis</dt><dd>{{ $application->businessDetails?->business_type ?: '-' }}</dd></div>
                    @foreach($application->representatives as $rep)
                        <div><dt class="text-slate-500">Penanggung jawab</dt><dd>{{ $rep->name }} ({{ $rep->relationship->value }})</dd></div>
                    @endforeach
                @endif
            </dl>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Review dokumen</h2>
            <div class="mt-5 space-y-4">
                @foreach($application->requirements as $requirement)
                    <div class="rounded-lg border p-4">
                        <div class="flex justify-between gap-3"><div><p class="font-medium">{{ $requirement->name }}</p><p class="text-xs text-slate-500">{{ $requirement->statusLabel() }}</p></div></div>
                        @foreach($requirement->documents->where('active', true) as $document)
                            <div class="mt-3 rounded bg-slate-50 p-3 text-sm">
                                <div class="flex flex-wrap justify-between gap-2">
                                    <span>v{{ $document->version_number }} · {{ $document->review_status->label() }}</span>
                                    <div class="flex flex-wrap gap-3">
                                        <a target="_blank" rel="noopener" href="{{ route('admin.documents.view', $document->public_id) }}" class="text-indigo-700">Lihat</a>
                                        <a href="{{ route('admin.documents.download', $document->public_id) }}" class="text-indigo-700">Unduh</a>
                                        @if($application->status->value === 'UNDER_REVIEW' && $document->review_status->value === 'PENDING')
                                            <form method="post" action="{{ route('admin.documents.review', $document->public_id) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <select name="action" class="rounded border-slate-300 text-xs"><option value="ACCEPT">Terima</option><option value="REQUEST_REVISION">Minta perbaikan</option><option value="REJECT">Tolak</option></select>
                                                <input name="reason" placeholder="Alasan bila perlu" class="rounded border-slate-300 text-xs">
                                                <input name="instruction" placeholder="Instruksi revisi" class="rounded border-slate-300 text-xs">
                                                <button class="rounded bg-slate-900 px-2 py-1 text-xs text-white">Simpan</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        @if($application->resultDocuments->isNotEmpty())
            <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold">Dokumen hasil</h2>
                <div class="mt-4 space-y-3">
                    @foreach($application->resultDocuments as $result)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 text-sm">
                            <span>{{ str_replace('_', ' ', $result->type->value) }} · {{ $result->verification_status->value }}</span>
                            <div class="flex flex-wrap gap-3"><a target="_blank" rel="noopener" href="{{ route('admin.results.view', $result->public_id) }}" class="text-indigo-700">Lihat</a><a href="{{ route('admin.results.download', $result->public_id) }}" class="text-indigo-700">Unduh</a></div>
                            @if(in_array($result->verification_status->value, ['PENDING', 'REJECTED'], true) && $application->status->value === 'RESULT_REVIEW')
                                <form method="post" action="{{ route('admin.results.verify', $result->public_id) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <select name="verified" class="rounded border-slate-300 text-xs"><option value="1">Verifikasi</option><option value="0">Tolak hasil</option></select>
                                    <input name="reason" placeholder="Alasan bila ditolak" class="rounded border-slate-300 text-xs">
                                    <button class="rounded bg-emerald-600 px-2 py-1 text-xs text-white">Simpan</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <aside class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Tindakan</h2>
            <div class="mt-4 space-y-3 text-sm">
                @if(in_array($application->status->value, ['DOCUMENTS_SUBMITTED', 'REVISION_SUBMITTED'], true))
                    <form method="post" action="{{ route('admin.applications.review.start', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Mulai pemeriksaan</button></form>
                @elseif($application->status->value === 'UNDER_REVIEW')
                    <form method="post" action="{{ route('admin.applications.review.finalize', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Selesaikan review</button></form>
                @elseif(in_array($application->status->value, ['DOCUMENTS_ACCEPTED', 'ESTIMATE_PENDING'], true))
                    <form method="post" action="{{ route('admin.applications.estimate', $application->public_id) }}" class="space-y-2">@csrf<input type="datetime-local" name="estimated_completion_at" required class="w-full rounded border-slate-300"><input name="reason" placeholder="Alasan estimasi" required class="w-full rounded border-slate-300"><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Simpan estimasi</button></form>
                @elseif($application->status->value === 'IN_PROGRESS')
                    <form method="post" action="{{ route('admin.applications.external.waiting', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Tandai menunggu proses eksternal</button></form>
                @elseif($application->status->value === 'WAITING_EXTERNAL_PROCESS')
                    <form method="post" enctype="multipart/form-data" action="{{ route('admin.applications.results.upload', $application->public_id) }}" class="space-y-2">@csrf<input type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm"><select name="type" class="w-full rounded border-slate-300"><option value="PRIMARY_RESULT">Hasil utama</option><option value="SUPPORTING_DOCUMENT">Dokumen pendukung</option><option value="RECEIPT">Kwitansi</option><option value="REPORT">Laporan</option><option value="OTHER">Lainnya</option></select><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Upload hasil</button></form>
                @elseif($application->status->value === 'RESULT_UPLOADED')
                    <form method="post" action="{{ route('admin.applications.results.review', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Mulai review hasil</button></form>
                @elseif($application->status->value === 'RESULT_REVIEW')
                    <form method="post" action="{{ route('admin.applications.complete', $application->public_id) }}">@csrf<button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">Tandai selesai</button></form>
                @endif
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Riwayat status</h2>
            <ol class="mt-4 space-y-3 text-sm">
                @foreach($application->statusHistories as $history)
                    <li class="border-l-2 border-indigo-200 pl-3"><p>{{ $history->to_status->label() }}</p><p class="text-xs text-slate-500">{{ $history->created_at->translatedFormat('d M Y H:i') }}</p></li>
                @endforeach
            </ol>
        </section>

        @if($application->chatThread)
            <a href="{{ route('client.chat.show', $application->chatThread->public_id) }}" class="block rounded-xl bg-indigo-700 p-5 text-white"><p class="font-semibold">Chat client</p></a>
        @endif
    </aside>
</div>
@endsection
