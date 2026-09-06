@extends('layouts.admin')

@section('title', 'Dokumen')
@section('admin_context', 'Dokumen')

@section('content')
    <x-admin.page-header kicker="DOKUMEN" title="Dokumen yang memerlukan perhatian" description="Setiap item merangkum dokumen aktif dalam satu pengajuan agar konteks review tetap jelas." />

    <section class="bd-admin-list-surface mt-8" aria-labelledby="document-queue-title">
        <div class="bd-admin-list-toolbar">
            <div><h2 id="document-queue-title">Antrian review pengajuan</h2><p>Tinjau dokumen dari detail pengajuan yang sudah terotorisasi.</p></div>
            <form class="bd-admin-search" method="get" action="{{ route('admin.documents.index') }}"><label class="sr-only" for="document-search">Cari pengajuan dokumen</label><input id="document-search" name="q" type="search" value="{{ $search }}" placeholder="Cari ID, klien, layanan, atau dokumen"><input type="hidden" name="filter" value="{{ $filter }}"><button class="bd-admin-button bd-admin-button--secondary" type="submit">Cari</button></form>
        </div>
        @php($filters = ['all' => 'Semua', 'review' => 'Menunggu review', 'revision' => 'Revisi masuk', 'needs_fix' => 'Perlu diperbaiki', 'reviewed' => 'Selesai direview'])
        <nav class="bd-admin-filter-bar" aria-label="Filter antrian dokumen">@foreach($filters as $key => $label)<a href="{{ route('admin.documents.index', array_filter(['filter' => $key, 'q' => $search ?: null])) }}" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>

        @if($applications->isNotEmpty())
            <div class="bd-admin-table-wrap"><table class="bd-admin-table bd-admin-document-queue-table"><thead><tr><th>Pengajuan</th><th>Dokumen</th><th>Status review</th><th>Diperbarui</th><th><span class="sr-only">Tindakan</span></th></tr></thead><tbody>
                @foreach($applications as $application)
                    @php($queueTone = $application->revision_required_count > 0 || $application->status->value === 'REVISION_REQUIRED' ? 'danger' : ($application->pending_review_count > 0 ? 'attention' : 'success'))
                    @php($queueLabel = $application->revision_required_count > 0 || $application->status->value === 'REVISION_REQUIRED' ? 'Perlu perbaikan' : ($application->pending_review_count > 0 ? $application->pending_review_count.' menunggu pemeriksaan' : 'Selesai direview'))
                    <tr>
                        <td><strong>{{ $application->service->name }}</strong><small>{{ $application->user->name }} · ID …{{ strtoupper(substr($application->public_id, -6)) }}</small></td>
                        <td><strong>{{ $application->available_documents_count }} dari {{ $application->document_requirements_count }} dokumen tersedia</strong><small>{{ $application->revision_required_count ? $application->revision_required_count.' dokumen perlu ditindaklanjuti' : 'Dokumen aktif diringkas dalam satu pengajuan.' }}</small></td>
                        <td><x-admin.status-badge :label="$queueLabel" :tone="$queueTone" /><small>{{ $application->status->label() }}</small></td>
                        <td>{{ $application->latest_document_uploaded_at?->translatedFormat('d M Y, H:i') ?? $application->updated_at->translatedFormat('d M Y, H:i') }}</td>
                        <td><a class="bd-admin-table-link" href="{{ route('admin.applications.show', $application->public_id) }}#documents-title">Tinjau</a></td>
                    </tr>
                @endforeach
            </tbody></table></div>
            <div class="bd-admin-pagination">{{ $applications->links() }}</div>
        @else
            <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Pengajuan tidak ditemukan' : 'Tidak ada pengajuan pada antrian ini' }}</h3><p>{{ $search !== '' ? 'Coba gunakan kata kunci lain.' : 'Pengajuan dengan dokumen yang relevan akan muncul di sini.' }}</p></div>
        @endif
    </section>
@endsection
