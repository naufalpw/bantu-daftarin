@extends('layouts.admin')

@section('title', 'Detail Pengajuan')
@section('admin_context', 'Pengajuan')

@section('content')
    @php
        $nextAction = \App\Support\AdminApplicationPresenter::nextAction($application->status);
        $documentCount = $application->requirements->where('is_required', true)->count();
        $acceptedCount = $application->requirements->where('is_required', true)->where('status', 'ACCEPTED')->count();
        $latestPayment = $application->payments->sortByDesc('created_at')->first();
        $cancellationHistory = $application->statusHistories->first(fn ($history) => $history->to_status === \App\Enums\ApplicationStatus::CANCELLED);
        $latePayment = $application->status === \App\Enums\ApplicationStatus::CANCELLED && $latestPayment?->status === \App\Enums\PaymentStatus::PAID;
    @endphp

    <a class="bd-admin-back-link" href="{{ route('admin.applications.index') }}">Kembali ke daftar pengajuan</a>
    <header class="bd-admin-detail-header">
        <div><p class="bd-admin-kicker">PENGAJUAN</p><h1>{{ $application->service->name }}</h1><p>…{{ strtoupper(substr($application->public_id, -6)) }} · {{ $application->user->name }} · {{ $application->user->email }}</p></div>
        <div class="bd-admin-detail-header__status"><x-admin.status-badge :status="$application->status" /><p>{{ $nextAction['label'] }}</p><small>{{ $nextAction['description'] }}</small></div>
    </header>

    <div class="bd-admin-detail-grid mt-8">
        <div class="bd-admin-detail-content">
            <section class="bd-admin-surface bd-admin-detail-section--summary" aria-labelledby="summary-title">
                <div class="bd-admin-surface__header"><div><h2 id="summary-title">Ringkasan operasional</h2></div></div>
                <dl class="bd-admin-definition-grid"><div><dt>Klien</dt><dd>{{ $application->user->name }}</dd></div><div><dt>Diperbarui</dt><dd>{{ $application->updated_at->translatedFormat('d M Y, H:i') }}</dd></div><div><dt>Dokumen wajib</dt><dd>{{ $acceptedCount }} dari {{ $documentCount }} diterima</dd></div><div><dt>Langkah berikutnya</dt><dd>{{ $nextAction['label'] }}</dd></div></dl>
            </section>

            @if($application->status === \App\Enums\ApplicationStatus::CANCELLED)
                <section class="bd-admin-surface bd-admin-cancellation-summary" aria-labelledby="cancellation-summary-title">
                    <div class="bd-admin-surface__header"><div><h2 id="cancellation-summary-title">Pengajuan dibatalkan</h2><p>Pengajuan tetap tersimpan sebagai riwayat dan tidak dapat dilanjutkan.</p></div><x-admin.status-badge :status="$application->status" /></div>
                    <dl class="bd-admin-definition-grid">
                        <div><dt>Dibatalkan oleh</dt><dd>{{ $cancellationHistory?->actor_type === 'user' ? 'Klien' : 'Sistem' }}</dd></div>
                        <div><dt>Tanggal</dt><dd>{{ $cancellationHistory?->created_at?->translatedFormat('d F Y, H:i') ?? '-' }}</dd></div>
                        <div class="bd-admin-definition-grid__wide"><dt>Alasan</dt><dd>{{ $cancellationHistory?->reason ?: '-' }}</dd></div>
                    </dl>
                </section>
            @endif

            <section class="bd-admin-surface bd-admin-detail-section--payment" aria-labelledby="payment-title">
                <div class="bd-admin-surface__header"><div><h2 id="payment-title">Pembayaran</h2><p>Informasi pembayaran bersifat baca-saja dan berasal dari alur provider.</p></div></div>
                @if($latestPayment)
                    @php($paymentPresentation = \App\Support\PaymentStatusPresenter::for($latestPayment->status, $latestPayment->expires_at?->isPast() ?? false))
                    <dl class="bd-admin-definition-grid"><div><dt>Status</dt><dd><x-admin.status-badge :label="$paymentPresentation['label']" :tone="$paymentPresentation['tone']" /></dd></div><div><dt>Jumlah</dt><dd>{{ $latestPayment->currency }} {{ number_format((float) $latestPayment->amount, 0, ',', '.') }}</dd></div><div><dt>Metode</dt><dd>{{ $latestPayment->payment_method?->label() ?? '-' }}</dd></div><div><dt>Provider</dt><dd>{{ strtoupper($latestPayment->provider) }}</dd></div><div><dt>Dibuat</dt><dd>{{ $latestPayment->created_at->translatedFormat('d M Y, H:i') }}</dd></div><div><dt>Dibayar / berakhir</dt><dd>{{ $latestPayment->paid_at?->translatedFormat('d M Y, H:i') ?? $latestPayment->expires_at?->translatedFormat('d M Y, H:i') ?? '-' }}</dd></div></dl>
                    @if($latePayment)<p class="bd-admin-inline-note bd-admin-inline-note--danger"><strong>Pembayaran diterima setelah pengajuan dibatalkan.</strong><br>Pengajuan tetap dibatalkan dan memerlukan tindak lanjut manual. Tidak ada refund otomatis.</p>@endif
                @else
                    <div class="bd-admin-empty-state bd-admin-empty-state--compact"><h3>Belum ada pembayaran</h3><p>Pembayaran akan tercatat setelah klien memilih metode yang tersedia.</p></div>
                @endif
            </section>

            <section class="bd-admin-surface bd-admin-detail-section--data" aria-labelledby="data-title">
                <div class="bd-admin-surface__header"><div><h2 id="data-title">Data pengajuan</h2><p>Informasi pemohon yang digunakan dalam pengajuan. Nomor identitas ditampilkan dalam bentuk tersamarkan.</p></div></div>
                @if($application->personalDetails)
                    <dl class="bd-admin-definition-grid">
                        @php($nik = $application->personalDetails->nik)
                        @php($familyCardNumber = $application->personalDetails->family_card_number)
                        @php($maskedNik = filled($nik) ? str_repeat('*', max(0, mb_strlen($nik) - 4)).mb_substr($nik, -4) : '-')
                        @php($maskedFamilyCard = filled($familyCardNumber) ? str_repeat('*', max(0, mb_strlen($familyCardNumber) - 4)).mb_substr($familyCardNumber, -4) : '-')
                        <div><dt>Nama lengkap</dt><dd>{{ $application->personalDetails->name }}</dd></div><div><dt>Email</dt><dd>{{ $application->personalDetails->email ?: '-' }}</dd></div><div><dt>Jenis kelamin</dt><dd>{{ $application->personalDetails->gender ?: '-' }}</dd></div><div><dt>Status pernikahan</dt><dd>{{ $application->personalDetails->marital_status ?: '-' }}</dd></div><div><dt>Status dalam keluarga</dt><dd>{{ $application->personalDetails->family_status ?: '-' }}</dd></div><div><dt>Keperluan NPWP</dt><dd>{{ $application->personalDetails->purpose ?: '-' }}</dd></div><div><dt>NIK</dt><dd>{{ $maskedNik }}</dd></div><div><dt>Nomor KK</dt><dd>{{ $maskedFamilyCard }}</dd></div>
                    </dl>
                @else
                    <div class="bd-admin-data-groups">
                        <section aria-labelledby="business-information-title"><h3 id="business-information-title">Informasi badan</h3><dl class="bd-admin-definition-grid"><div><dt>Nama badan</dt><dd>{{ $application->businessDetails?->business_name ?: '-' }}</dd></div><div><dt>Jenis badan</dt><dd>{{ $application->businessDetails?->businessTypeLabel() ?: '-' }}</dd></div>@if(filled($application->businessDetails?->business_type_other))<div><dt>Jenis badan lainnya</dt><dd>{{ $application->businessDetails->business_type_other }}</dd></div>@endif<div><dt>Keperluan NPWP</dt><dd>{{ $application->businessDetails?->purpose ?: '-' }}</dd></div></dl></section>
                        <section aria-labelledby="representatives-title"><h3 id="representatives-title">Penanggung jawab</h3><dl class="bd-admin-definition-grid">@forelse($application->representatives as $representative)<div><dt>{{ $representative->is_primary ? 'Penanggung jawab utama' : 'Penanggung jawab tambahan' }}</dt><dd>{{ $representative->name }}<br><span class="bd-admin-field-secondary">{{ \Illuminate\Support\Str::headline(strtolower($representative->relationship->value)) }}@if($representative->email) &middot; {{ $representative->email }}@endif</span></dd></div>@empty<div><dt>Penanggung jawab</dt><dd>-</dd></div>@endforelse</dl></section>
                    </div>
                @endif
            </section>

            <section class="bd-admin-surface bd-admin-detail-section--documents" aria-labelledby="documents-title">
                <div class="bd-admin-surface__header"><div><h2 id="documents-title">Dokumen dan pemeriksaan</h2><p>Dokumen aktif yang dikirim pengguna. Keputusan hanya berlaku pada versi dokumen yang sedang aktif.</p></div><a href="{{ route('admin.documents.index', ['filter' => 'review']) }}">Buka antrian dokumen</a></div>
                <div class="bd-admin-document-list">
                    @foreach($application->requirements as $requirement)
                        @php($activeDocument = $requirement->documents->where('active', true)->first())
                        <article class="bd-admin-document" @if($activeDocument) id="document-{{ $activeDocument->public_id }}" @endif>
                            <header><div><h3>{{ $requirement->name }}</h3><p>{{ $requirement->is_required ? 'Wajib' : 'Opsional' }} · {{ $requirement->statusLabel() }}</p></div><x-admin.status-badge :label="$activeDocument?->review_status->label() ?? 'Belum diunggah'" :tone="$activeDocument ? match($activeDocument->review_status->value) {'PENDING' => 'attention', 'REVISION_REQUIRED', 'REJECTED' => 'danger', 'LOCKED', 'ACCEPTED' => 'success', default => 'neutral'} : 'neutral'" /></header>
                            @if($activeDocument)
                                <div class="bd-admin-document__preview">
                                    @if(str_starts_with($activeDocument->mime_type, 'image/'))
                                        <img src="{{ route('admin.documents.view', $activeDocument->public_id) }}" alt="Pratinjau {{ $requirement->name }} versi {{ $activeDocument->version_number }}">
                                    @else
                                        <span class="bd-admin-document__pdf">PDF</span>
                                        <strong>{{ $activeDocument->original_filename ?: $requirement->name.'.'.strtolower($activeDocument->extension) }}</strong>
                                        <small>{{ number_format($activeDocument->size_bytes / 1024 / 1024, 1) }} MB</small>
                                    @endif
                                </div>
                                <div class="bd-admin-document__meta"><span>Versi {{ $activeDocument->version_number }}</span><span>{{ strtoupper($activeDocument->extension) }} · {{ number_format($activeDocument->size_bytes / 1024, 0) }} KB</span><span>Diunggah {{ $activeDocument->uploaded_at->translatedFormat('d M Y, H:i') }}</span><span>{{ $activeDocument->scan_status->value === 'PASSED' ? 'Lolos pemeriksaan keamanan' : 'Status keamanan: '.$activeDocument->scan_status->value }}</span></div>
                                @if($activeDocument->rejection_reason)<p class="bd-admin-inline-note bd-admin-inline-note--danger"><strong>Alasan:</strong> {{ $activeDocument->rejection_reason }}@if($activeDocument->revision_instruction)<br><strong>Instruksi:</strong> {{ $activeDocument->revision_instruction }}@endif</p>@endif
                                <div class="bd-admin-document__actions"><a href="{{ route('admin.documents.view', $activeDocument->public_id) }}" target="_blank" rel="noopener">Lihat dokumen</a><a href="{{ route('admin.documents.download', $activeDocument->public_id) }}">Unduh</a></div>
                                @if($application->status->value === 'UNDER_REVIEW' && $activeDocument->review_status->value === 'PENDING')
                                    <details class="bd-admin-review-form"><summary>Catat keputusan</summary><form method="post" action="{{ route('admin.documents.review', $activeDocument->public_id) }}">@csrf<label for="action-{{ $activeDocument->id }}">Keputusan</label><select id="action-{{ $activeDocument->id }}" name="action"><option value="ACCEPT">Terima dokumen</option><option value="REQUEST_REVISION">Minta perbaikan</option><option value="REJECT">Tolak dokumen</option></select><label for="reason-{{ $activeDocument->id }}">Alasan bila ditolak atau perlu diperbaiki</label><textarea id="reason-{{ $activeDocument->id }}" name="reason" rows="2"></textarea><label for="instruction-{{ $activeDocument->id }}">Instruksi revisi</label><textarea id="instruction-{{ $activeDocument->id }}" name="instruction" rows="2"></textarea><button class="bd-admin-button bd-admin-button--primary" type="submit">Simpan keputusan</button></form></details>
                                @endif
                                @if($requirement->documents->count() > 1)<details class="bd-admin-version-history"><summary>{{ $requirement->documents->count() - 1 }} versi sebelumnya</summary><ul>@foreach($requirement->documents->where('active', false)->sortByDesc('version_number') as $previous)<li>Versi {{ $previous->version_number }} · {{ $previous->review_status->label() }} · {{ $previous->uploaded_at->translatedFormat('d M Y') }}</li>@endforeach</ul></details>@endif
                            @else
                                <div class="bd-admin-document__preview bd-admin-document__preview--empty"><strong>Belum diunggah</strong><span>Belum ada versi aktif yang dapat ditinjau.</span></div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="bd-admin-surface bd-admin-detail-section--result" aria-labelledby="result-title">
                <div class="bd-admin-surface__header"><div><h2 id="result-title">Hasil</h2><p>Hanya hasil terverifikasi yang dapat tersedia untuk klien.</p></div></div>
                @if($application->resultDocuments->isNotEmpty())<div class="bd-admin-document-list">@foreach($application->resultDocuments as $result)@php($resultTone = match($result->verification_status->value) {'VERIFIED' => 'success', 'REJECTED' => 'danger', default => 'attention'})<article class="bd-admin-document"><header><div><h3>{{ str_replace('_', ' ', strtolower($result->type->value)) }}</h3><p>Diunggah {{ $result->uploaded_at->translatedFormat('d M Y, H:i') }}</p></div><x-admin.status-badge :label="$result->verification_status->value === 'VERIFIED' ? 'Terverifikasi' : ($result->verification_status->value === 'REJECTED' ? 'Ditolak' : 'Menunggu verifikasi')" :tone="$resultTone" /></header><div class="bd-admin-document__actions"><a href="{{ route('admin.results.view', $result->public_id) }}" target="_blank" rel="noopener">Lihat hasil</a><a href="{{ route('admin.results.download', $result->public_id) }}">Unduh</a></div>@if($result->rejection_reason)<p class="bd-admin-inline-note bd-admin-inline-note--danger"><strong>Alasan:</strong> {{ $result->rejection_reason }}</p>@endif@if(in_array($result->verification_status->value, ['PENDING', 'REJECTED'], true) && $application->status->value === 'RESULT_REVIEW')<details class="bd-admin-review-form"><summary>Verifikasi hasil</summary><form method="post" action="{{ route('admin.results.verify', $result->public_id) }}">@csrf<label for="verified-{{ $result->id }}">Keputusan</label><select id="verified-{{ $result->id }}" name="verified"><option value="1">Verifikasi hasil</option><option value="0">Tolak hasil</option></select><label for="result-reason-{{ $result->id }}">Alasan bila ditolak</label><textarea id="result-reason-{{ $result->id }}" name="reason" rows="2"></textarea><button class="bd-admin-button bd-admin-button--primary" type="submit">Simpan verifikasi</button></form></details>@endif</article>@endforeach</div>@else<div class="bd-admin-empty-state bd-admin-empty-state--compact"><h3>Belum ada hasil</h3><p>Hasil akan dicatat di sini setelah diunggah melalui tahap proses yang valid.</p></div>@endif
            </section>

            <section class="bd-admin-surface" aria-labelledby="history-title"><div class="bd-admin-surface__header"><div><h2 id="history-title">Riwayat pengajuan</h2><p>Riwayat status dan estimasi</p></div></div><ol class="bd-admin-timeline">@forelse($application->statusHistories->sortByDesc('created_at') as $history)<li><strong>{{ $history->to_status->label() }}</strong><span>{{ $history->created_at->translatedFormat('d M Y, H:i') }}</span>@if($history->reason)<p>{{ $history->reason }}</p>@endif</li>@empty<li><strong>Belum ada riwayat status</strong></li>@endforelse @foreach($application->estimateHistories->sortByDesc('created_at') as $estimate)<li><strong>Estimasi diperbarui</strong><span>{{ $estimate->created_at->translatedFormat('d M Y, H:i') }}</span><p>{{ $estimate->reason }}</p></li>@endforeach</ol></section>
        </div>

        <aside class="bd-admin-detail-aside">
            <section class="bd-admin-surface"><div class="bd-admin-surface__header"><div><h2>Tindakan berikutnya</h2><p>{{ $nextAction['description'] }}</p></div></div>
                <div class="bd-admin-action-stack">
                    @if(in_array($application->status->value, ['DOCUMENTS_SUBMITTED', 'REVISION_SUBMITTED'], true))<form method="post" action="{{ route('admin.applications.review.start', $application->public_id) }}">@csrf<button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide">Mulai pemeriksaan</button></form>
                    @elseif($application->status->value === 'UNDER_REVIEW')<form method="post" action="{{ route('admin.applications.review.finalize', $application->public_id) }}">@csrf<label for="final-review-reason">Catatan pemeriksaan (opsional)</label><textarea id="final-review-reason" name="reason" rows="2"></textarea><button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide">Selesaikan peninjauan</button></form>
                    @elseif(in_array($application->status->value, ['DOCUMENTS_ACCEPTED', 'ESTIMATE_PENDING'], true))<form method="post" action="{{ route('admin.applications.estimate', $application->public_id) }}">@csrf<label for="estimate-at">Estimasi selesai</label><input id="estimate-at" type="datetime-local" name="estimated_completion_at" required><label for="estimate-reason">Alasan estimasi</label><textarea id="estimate-reason" name="reason" rows="2" required></textarea><button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide">Simpan estimasi</button></form>
                    @elseif($application->status->value === 'IN_PROGRESS')<form method="post" action="{{ route('admin.applications.external.waiting', $application->public_id) }}">@csrf<button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide">Tandai menunggu proses instansi</button></form>
                    @elseif($application->status->value === 'WAITING_EXTERNAL_PROCESS')<form method="post" enctype="multipart/form-data" action="{{ route('admin.applications.results.upload', $application->public_id) }}">@csrf<label for="result-file">File hasil</label><input id="result-file" type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf"><label for="result-type">Jenis hasil</label><select id="result-type" name="type"><option value="PRIMARY_RESULT">Hasil utama</option><option value="SUPPORTING_DOCUMENT">Dokumen pendukung</option><option value="RECEIPT">Kwitansi</option><option value="REPORT">Laporan</option><option value="OTHER">Lainnya</option></select><button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide">Unggah hasil</button></form>
                    @elseif($application->status->value === 'RESULT_UPLOADED')<form method="post" action="{{ route('admin.applications.results.review', $application->public_id) }}">@csrf<button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide">Tinjau hasil</button></form>
                    @elseif($application->status->value === 'RESULT_REVIEW')<form method="post" action="{{ route('admin.applications.complete', $application->public_id) }}">@csrf<button class="bd-admin-button bd-admin-button--primary bd-admin-button--wide">Tandai selesai</button></form>
                    @elseif($application->status->value === 'COMPLETED')<form method="post" action="{{ route('admin.applications.archive', $application->public_id) }}">@csrf<button class="bd-admin-button bd-admin-button--secondary bd-admin-button--wide" onclick="return confirm('Arsipkan pengajuan ini?')">Arsipkan pengajuan</button></form>@else<p class="bd-admin-muted-copy">Tidak ada tindakan admin yang tersedia pada tahap ini.</p>@endif
                </div>
            </section>
            @if($application->chatThread)<a class="bd-admin-support-link" href="{{ route('admin.chat.show', $application->chatThread->public_id) }}"><strong>Dukungan pengajuan</strong><span>Buka percakapan klien dalam konteks pengajuan ini.</span></a>@endif
        </aside>
    </div>
@endsection
