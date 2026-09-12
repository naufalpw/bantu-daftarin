@extends('layouts.client')

@section('context_title', 'Ruang pengajuan')
@section('body_class', 'pb-workspace-body')

@section('content')
@php
    $canEditDetails = in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS'], true);
    $canUploadGenerally = in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS', 'DOCUMENTS_READY_FOR_PAYMENT', 'AWAITING_PAYMENT'], true);
    $requiredRequirements = $application->requirements->where('active', true)->where('is_required', true);
    $completeRequired = $requiredRequirements->filter(fn ($requirement) => $requirement->documents->contains(fn ($document) => $document->active && $document->scan_status->value === 'PASSED' && $document->deletion_scheduled_at === null && $document->deleted_at === null));
    $latestPayment = $application->payments->sortByDesc('id')->first();
    $paymentPresentation = \App\Support\PaymentStatusPresenter::for($latestPayment?->status, $latestPayment?->status?->value === 'PENDING' && $latestPayment?->expires_at?->isPast());
    $verifiedResults = $application->resultDocuments->filter(fn ($result) => $result->deleted_at === null && $result->verification_status->value === 'VERIFIED');
    $shortId = strtoupper(substr($application->public_id, -4));
    $isPersonalWorkspace = $application->service->code === 'NPWP_PERSONAL';
    $isBusinessWorkspace = $application->service->code === 'NPWP_BUSINESS';
    $showPaymentSection = in_array($application->status->value, ['DOCUMENTS_READY_FOR_PAYMENT', 'AWAITING_PAYMENT', 'PAYMENT_CONFIRMED'], true);
    $showProcessSection = in_array($application->status->value, ['DOCUMENTS_SUBMITTED', 'UNDER_REVIEW', 'REVISION_SUBMITTED', 'DOCUMENTS_ACCEPTED', 'ESTIMATE_PENDING', 'IN_PROGRESS', 'WAITING_EXTERNAL_PROCESS', 'RESULT_UPLOADED', 'RESULT_REVIEW', 'COMPLETED', 'ARCHIVED'], true) || $timeline->isNotEmpty();
    $showResultSection = in_array($application->status->value, ['RESULT_UPLOADED', 'RESULT_REVIEW', 'COMPLETED', 'ARCHIVED'], true);
    $isCancelled = $application->status === \App\Enums\ApplicationStatus::CANCELLED;
    $hasConfirmedPayment = $latestPayment && ($latestPayment->paid_at || in_array($latestPayment->status->value, ['PAID', 'REFUND_REQUESTED', 'REFUNDING', 'REFUNDED'], true));
    $showPaymentSection = $showPaymentSection || ($isCancelled && $latestPayment);
@endphp

<div class="pb-page pb-workspace{{ $isPersonalWorkspace ? ' pb-workspace--personal' : '' }}{{ $isBusinessWorkspace ? ' pb-workspace--business' : '' }}">
    <a class="pb-back-link" href="{{ route('client.applications.index') }}"><span aria-hidden="true">←</span> Semua pengajuan</a>

    <header class="pb-workspace-header">
        <div class="pb-workspace-header__context">
            <p class="pb-kicker pb-kicker--inverse">Ruang pengajuan</p>
            <h1>{{ $application->service->name }}</h1>
            <div class="pb-reference">
                <span>ID Pengajuan: …{{ $shortId }}</span>
                <button type="button" data-copy-value="{{ $application->public_id }}" aria-label="Salin ID pengajuan lengkap">Salin ID</button>
            </div>
        </div>
        <div class="pb-workspace-header__status">
            <span class="pb-status pb-status--{{ $statusPresentation['tone'] }}">{{ $statusPresentation['label'] }}</span>
            <p>{{ $statusPresentation['description'] }}</p>
            @if($application->estimated_completion_at)
                <span>Estimasi yang tercatat: {{ $application->estimated_completion_at->translatedFormat('d M Y') }}</span>
            @endif
        </div>
        @if($isCancelled)
            <div class="pb-workspace-header__progress pb-workspace-header__cancelled">
                <strong>Pengajuan telah dihentikan</strong>
                <span>Riwayat tetap tersimpan sebagai konteks baca-saja.</span>
            </div>
        @else
            <div class="pb-workspace-header__progress">
                @include('components.application-progress', ['status' => $application->status, 'presentation' => $statusPresentation])
            </div>
        @endif
        <div class="pb-workspace-header__next">
            <span>Langkah berikutnya</span>
            <strong>{{ $statusPresentation['next_action'] }}</strong>
            @if($statusPresentation['cta_label'])
                @if($statusPresentation['cta_method'] === 'post')
                    <form method="post" action="{{ $statusPresentation['cta_url'] }}">
                        @csrf
                        <button class="pb-button pb-button--light" type="submit">{{ $statusPresentation['cta_label'] }}</button>
                    </form>
                @else
                    <a class="pb-button pb-button--light" href="{{ $statusPresentation['cta_url'] }}">{{ $statusPresentation['cta_label'] }}</a>
                @endif
            @endif
        </div>
    </header>

    <div class="pb-workspace-layout">
        <div class="pb-workspace-main">
            <section id="data-dokumen" class="pb-workspace-section" tabindex="-1" aria-labelledby="documents-title">
                <div class="pb-section-heading">
                    <div><p class="pb-kicker">Data &amp; Dokumen</p><h2 id="documents-title">Lengkapi informasi dan persyaratan</h2></div>
                    <span class="pb-section-note">File disimpan secara privat</span>
                </div>

                @if($canEditDetails)
                    <livewire:application-details-form :application="$application" />
                @else
                    <div class="pb-locked-summary">
                        <strong>Data pengajuan sudah dikunci pada tahap ini.</strong>
                        <p>Informasi sensitif tidak ditampilkan ulang. Hubungi tim melalui chat pengajuan jika ada data yang perlu diklarifikasi.</p>
                    </div>
                @endif

                @if($application->service->code === 'NPWP_PERSONAL')
                    @php($faceRequirement = $application->requirements->first(fn ($requirement) => $requirement->code === 'FOTO_WAJAH' || str_ends_with($requirement->code, '_FOTO_WAJAH')))
                    <section class="pb-personal-documents" aria-labelledby="personal-documents-title">
                        <div class="pb-personal-documents__heading">
                            <div><p class="pb-kicker">Dokumen Pengajuan</p><h3 id="personal-documents-title">Siapkan dokumen pendukung</h3></div>
                            <p>Pastikan dokumen terlihat jelas dan sesuai persyaratan.</p>
                        </div>
                        <div class="pb-personal-document-grid">
                            @forelse($application->requirements->reject(fn ($requirement) => $faceRequirement && $requirement->is($faceRequirement)) as $requirement)
                                @php($activeDocument = $requirement->documents->where('active', true)->sortByDesc('version_number')->first())
                                @php($canUpload = $canUploadGenerally || ($application->status->value === 'REVISION_REQUIRED' && $requirement->status === 'REVISION_REQUIRED'))
                                @php($canAccessFile = $activeDocument && $activeDocument->scan_status->value === 'PASSED')
                                @php($documentStatus = $activeDocument ? match ($activeDocument->review_status->value) {
                                    'LOCKED' => ['Diterima', 'success'],
                                    'REVISION_REQUIRED', 'REJECTED' => ['Perlu perbaikan', 'danger'],
                                    default => [$activeDocument->scan_status->value === 'PASSED' ? 'Menunggu pemeriksaan' : 'Pemeriksaan keamanan', 'waiting'],
                                } : ['Belum diunggah', $requirement->is_required ? 'action' : 'neutral'])
                                @php($documentKind = match (true) {
                                    $requirement->code === 'KTP' || str_ends_with($requirement->code, '_KTP') => 'ktp',
                                    $requirement->code === 'KK' || str_ends_with($requirement->code, '_KK') => 'kk',
                                    $requirement->code === 'NPWP' || str_ends_with($requirement->code, '_NPWP') => 'npwp',
                                    default => 'document',
                                })
                                <x-personal-document-card
                                    :application="$application" :requirement="$requirement" :active-document="$activeDocument" :can-upload="$canUpload" :can-access-file="$canAccessFile" :document-status="$documentStatus"
                                    :icon="match($documentKind) { 'ktp' => 'images/figma/registration/personal/personal-ktp.svg', 'kk' => 'images/figma/registration/personal/personal-kk.svg', 'npwp' => 'images/figma/registration/personal/personal-npwp.svg', default => 'images/figma/registration/personal/personal-document.svg' }"
                                    :title="match($documentKind) { 'ktp' => 'KTP', 'kk' => 'Kartu Keluarga', 'npwp' => 'NPWP (Jika ada)', default => $requirement->name }"
                                    :description="match($documentKind) { 'ktp' => 'Unggah foto atau salinan KTP.', 'kk' => 'Unggah Kartu Keluarga.', 'npwp' => 'Unggah NPWP jika tersedia.', default => 'Unggah dokumen sesuai persyaratan.' }"
                                    :action-label="match($documentKind) { 'ktp' => 'Unggah KTP', 'kk' => 'Unggah KK', 'npwp' => 'Unggah NPWP', default => 'Unggah dokumen' }"
                                />
                            @empty
                                <div class="pb-inline-empty">Belum ada persyaratan dokumen untuk pengajuan ini.</div>
                            @endforelse
                        </div>
                        @if($faceRequirement)
                            @php($faceDocument = $faceRequirement->documents->where('active', true)->sortByDesc('version_number')->first())
                            @php($faceCanUpload = $canUploadGenerally || ($application->status->value === 'REVISION_REQUIRED' && $faceRequirement->status === 'REVISION_REQUIRED'))
                            @php($faceCanAccess = $faceDocument && $faceDocument->scan_status->value === 'PASSED')
                            @php($faceStatus = $faceDocument ? match ($faceDocument->review_status->value) { 'LOCKED' => ['Diterima', 'success'], 'REVISION_REQUIRED', 'REJECTED' => ['Perlu perbaikan', 'danger'], default => [$faceDocument->scan_status->value === 'PASSED' ? 'Menunggu pemeriksaan' : 'Pemeriksaan keamanan', 'waiting'], } : ['Belum diunggah', $faceRequirement->is_required ? 'action' : 'neutral'])
                            <x-personal-face-document :application="$application" :requirement="$faceRequirement" :active-document="$faceDocument" :can-upload="$faceCanUpload" :can-access-file="$faceCanAccess" :document-status="$faceStatus" />
                        @endif
                    </section>
                @else
                    <section class="pb-business-documents" aria-labelledby="business-documents-title">
                        <div class="pb-business-documents__heading">
                            <div><p class="pb-kicker">Dokumen Pengajuan</p><h3 id="business-documents-title">Siapkan dokumen badan usaha</h3></div>
                            <p>Pastikan dokumen terlihat jelas dan sesuai persyaratan.</p>
                        </div>
                        <div class="pb-business-document-grid">
                            @forelse($application->requirements as $requirement)
                                @php($activeDocument = $requirement->documents->where('active', true)->sortByDesc('version_number')->first())
                                @php($canUpload = $canUploadGenerally || ($application->status->value === 'REVISION_REQUIRED' && $requirement->status === 'REVISION_REQUIRED'))
                                @php($canAccessFile = $activeDocument && $activeDocument->scan_status->value === 'PASSED')
                                @php($documentStatus = $activeDocument ? match ($activeDocument->review_status->value) {
                                    'LOCKED' => ['Diterima', 'success'],
                                    'REVISION_REQUIRED', 'REJECTED' => ['Perlu perbaikan', 'danger'],
                                    default => [$activeDocument->scan_status->value === 'PASSED' ? 'Menunggu pemeriksaan' : 'Pemeriksaan keamanan', 'waiting'],
                                } : ['Belum diunggah', $requirement->is_required ? 'action' : 'neutral'])
                                @php($businessDocument = match ($requirement->code) {
                                    'KTP_PENANGGUNG_JAWAB' => ['Unggah foto atau salinan KTP penanggung jawab utama.', 'Unggah KTP', 'images/figma/registration/personal/personal-ktp.svg'],
                                    'AKTA_NOTARIS' => ['Unggah salinan akta notaris badan usaha.', 'Unggah Akta', 'images/figma/registration/business/business-folder.svg'],
                                    'SK_AHU' => ['Unggah salinan SK AHU badan usaha.', 'Unggah SK AHU', 'images/figma/registration/business/business-folder.svg'],
                                    'SURAT_KUASA' => ['Unggah surat kuasa bila pengajuan diwakilkan.', 'Unggah Surat Kuasa', 'images/figma/registration/business/business-folder.svg'],
                                    default => ['Unggah dokumen sesuai persyaratan.', 'Unggah dokumen', 'images/figma/registration/business/business-folder.svg'],
                                })
                                <x-personal-document-card
                                    :application="$application" :requirement="$requirement" :active-document="$activeDocument" :can-upload="$canUpload" :can-access-file="$canAccessFile" :document-status="$documentStatus"
                                    :icon="$businessDocument[2]" :title="$requirement->name" :description="$businessDocument[0]" :action-label="$businessDocument[1]"
                                />
                            @empty
                                <div class="pb-inline-empty">Belum ada persyaratan dokumen untuk pengajuan ini.</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if($application->status->value === 'DRAFT' && $requiredRequirements->isNotEmpty() && $completeRequired->count() === $requiredRequirements->count())
                    <form class="pb-section-submit" method="post" action="{{ route('client.applications.submit', $application->public_id) }}">
                        @csrf
                        <div><strong>Data dan dokumen sudah lengkap?</strong><p>Setelah dikirim, Anda akan melanjutkan ke tahap pembayaran. Pemeriksaan dilakukan setelah pembayaran terkonfirmasi.</p></div>
                        <button class="pb-button pb-button--primary" type="submit">Kirim pengajuan</button>
                    </form>
                @elseif($application->status->value === 'DRAFT')
                    <form class="pb-section-submit" method="post" action="{{ route('client.applications.submit', $application->public_id) }}">
                        @csrf
                        <div><strong>Data awal sudah lengkap?</strong><p>Lanjutkan untuk membuka tahap pengumpulan dokumen.</p></div>
                        <button class="pb-button pb-button--primary" type="submit">Lanjut ke dokumen</button>
                    </form>
                @elseif($application->status->value === 'REVISION_REQUIRED')
                    <form class="pb-section-submit" method="post" action="{{ route('client.applications.revision.submit', $application->public_id) }}">
                        @csrf
                        <div><strong>Semua perbaikan sudah diunggah?</strong><p>Pastikan semua perbaikan sudah selesai sebelum dikirim.</p></div>
                        <button class="pb-button pb-button--primary" type="submit">Kirim perbaikan</button>
                    </form>
                @endif
            </section>

            @if($showPaymentSection)
            <section id="pembayaran" class="pb-workspace-section" tabindex="-1" aria-labelledby="payment-section-title">
                <div class="pb-section-heading"><div><p class="pb-kicker">Pembayaran</p><h2 id="payment-section-title">Status dan rincian pembayaran</h2></div></div>
                <div class="pb-payment-summary">
                    <dl>
                        <div><dt>Biaya layanan</dt><dd>{{ $application->currency }} {{ number_format((float) $application->price_amount_snapshot, 0, ',', '.') }}</dd></div>
                        <div><dt>Status</dt><dd><span class="pb-status pb-status--{{ $paymentPresentation['tone'] }}">{{ $paymentPresentation['label'] }}</span></dd></div>
                        @if($latestPayment)
                            <div><dt>Metode</dt><dd>{{ $latestPayment->payment_method?->label() ?? 'Belum dipilih' }}</dd></div>
                            <div><dt>Referensi</dt><dd>{{ $latestPayment->reference_id }}</dd></div>
                            @if($latestPayment->expires_at)<div><dt>Berlaku sampai</dt><dd>{{ $latestPayment->expires_at->translatedFormat('d M Y, H:i') }} WIB</dd></div>@endif
                        @endif
                    </dl>
                    @if(in_array($application->status->value, ['DOCUMENTS_READY_FOR_PAYMENT', 'AWAITING_PAYMENT'], true))
                        <a class="pb-button pb-button--primary" href="{{ route('client.payments.show', $application->public_id) }}">{{ $latestPayment ? 'Lihat pembayaran' : 'Pilih metode pembayaran' }}</a>
                    @elseif(!$latestPayment)
                        <p>Pembayaran belum dibuka. Lengkapi data dan dokumen wajib terlebih dahulu.</p>
                    @endif
                </div>
            </section>
            @endif

            @if($showProcessSection)
            <section id="proses" class="pb-workspace-section" tabindex="-1" aria-labelledby="process-title">
                <div class="pb-section-heading"><div><p class="pb-kicker">Proses</p><h2 id="process-title">Riwayat pengajuan</h2></div></div>
                <ol class="pb-timeline">
                    @forelse($timeline as $event)
                        <li>
                            <span class="pb-timeline__marker pb-timeline__marker--{{ $event['tone'] }}" aria-hidden="true"></span>
                            <div>
                                <strong>{{ $event['label'] }}</strong>
                                <p>{{ $event['description'] }}</p>
                                <time datetime="{{ $event['timestamp']->toAtomString() }}">{{ $event['timestamp']->translatedFormat('d M Y, H:i') }}</time>
                            </div>
                        </li>
                    @empty
                        <li class="pb-inline-empty">Belum ada riwayat proses.</li>
                    @endforelse
                </ol>
            </section>
            @endif

            @if($showResultSection)
            <section id="hasil" class="pb-workspace-section" tabindex="-1" aria-labelledby="result-title">
                <div class="pb-section-heading"><div><p class="pb-kicker">Hasil</p><h2 id="result-title">Dokumen hasil terverifikasi</h2></div></div>
                @if($verifiedResults->isNotEmpty())
                    <div class="pb-result-list">
                        @foreach($verifiedResults as $result)
                            <article class="pb-result-row">
                                <div><strong>{{ $result->type->value === 'PRIMARY_RESULT' ? 'Hasil utama' : 'Dokumen pendukung' }}</strong><span>Diverifikasi {{ $result->verified_at?->translatedFormat('d M Y, H:i') }}</span></div>
                                <div><a href="{{ route('client.results.view', $result->public_id) }}" target="_blank" rel="noopener">Lihat</a><a href="{{ route('client.results.download', $result->public_id) }}">Unduh</a></div>
                            </article>
                        @endforeach
                    </div>
                @elseif(in_array($application->status->value, ['RESULT_UPLOADED', 'RESULT_REVIEW'], true))
                    <div class="pb-locked-summary"><strong>{{ $statusPresentation['label'] }}</strong><p>Hasil belum dapat dilihat atau diunduh sampai verifikasi selesai.</p></div>
                @else
                    <div class="pb-inline-empty"><strong>Hasil belum tersedia.</strong><span>Dokumen hasil akan muncul di sini setelah proses selesai dan hasil diverifikasi.</span></div>
                @endif
            </section>
            @endif
        </div>

        <aside class="pb-workspace-aside" aria-label="Tindakan dan bantuan pengajuan">
            <section class="pb-sidebar-summary" aria-labelledby="summary-title">
                <p class="pb-kicker">Ringkasan</p>
                <h2 id="summary-title" class="sr-only">Ringkasan pengajuan</h2>
                <dl>
                    <div><dt>Status pengajuan</dt><dd>{{ $statusPresentation['label'] }}</dd></div>
                    <div><dt>Dokumen wajib</dt><dd>{{ $completeRequired->count() }} dari {{ $requiredRequirements->count() }} tersedia</dd></div>
                    <div><dt>Pembayaran</dt><dd>{{ $paymentPresentation['label'] }}</dd></div>
                    @if($application->estimated_completion_at)
                        <div><dt>Estimasi tercatat</dt><dd>{{ $application->estimated_completion_at->translatedFormat('d M Y') }}</dd></div>
                    @endif
                </dl>
                <div class="pb-sidebar-summary__update">
                    <span>Pembaruan terakhir</span>
                    @if($timeline->first())
                        <strong>{{ $timeline->first()['label'] }}</strong>
                        <p>{{ $timeline->first()['description'] }}</p>
                        <time datetime="{{ $timeline->first()['timestamp']->toAtomString() }}">{{ $timeline->first()['timestamp']->translatedFormat('d M Y, H:i') }}</time>
                    @else
                        <strong>{{ $statusPresentation['label'] }}</strong>
                        <p>{{ $statusPresentation['description'] }}</p>
                    @endif
                </div>
            </section>
            <section class="pb-help-panel">
                <h2>Butuh bantuan?</h2>
                <p>Bantuan umum tersedia di Pusat Bantuan. Pertanyaan khusus pengajuan ini dapat dikirim melalui chat.</p>
                @if($application->chatThread)
                    <a class="pb-button pb-button--secondary" href="{{ route('client.chat.show', $application->chatThread->public_id) }}">Tanya tentang pengajuan ini</a>
                @endif
                <a href="{{ route('qna') }}">Buka Bantuan</a>
            </section>
            @if($canCancel || $isCancelled || $hasConfirmedPayment)
                <section class="pb-application-settings" aria-labelledby="application-settings-title">
                    <p class="pb-kicker">Pengaturan pengajuan</p>
                    <h2 id="application-settings-title">{{ $isCancelled ? 'Pengajuan dibatalkan' : 'Tidak ingin melanjutkan?' }}</h2>
                    @if($isCancelled)
                        <p>Pengajuan ini tidak akan diproses lebih lanjut.</p>
                        @if($cancellationHistory)
                            <dl>
                                <div><dt>Dibatalkan</dt><dd>{{ $cancellationHistory->created_at->translatedFormat('d F Y, H:i') }}</dd></div>
                                <div><dt>Alasan</dt><dd>{{ $cancellationHistory->reason ?: '-' }}</dd></div>
                            </dl>
                        @endif
                        <a class="pb-button pb-button--secondary" href="{{ route('client.services.index') }}">Mulai pengajuan baru</a>
                    @elseif($canCancel)
                        <p>Anda dapat membatalkan selama pembayaran belum dikonfirmasi. Catatan tetap tersimpan.</p>
                        <button class="pb-button pb-button--danger-secondary" type="button" data-cancel-dialog-open>Batalkan pengajuan</button>
                    @else
                        <p>Pembatalan mandiri tidak tersedia setelah pembayaran dikonfirmasi. Jika Anda mengalami kendala, hubungi Tim Bantu Daftarin.</p>
                        @if($application->chatThread)
                            <a class="pb-button pb-button--secondary" href="{{ route('client.chat.show', $application->chatThread->public_id) }}">Buka bantuan</a>
                        @endif
                    @endif
                </section>
            @endif
        </aside>
    </div>
</div>

@if($canCancel)
    <dialog class="pb-cancellation-dialog" data-cancel-dialog data-open-on-load="{{ $errors->has('reason') || $errors->has('reason_other') ? 'true' : 'false' }}" aria-labelledby="cancel-dialog-title" aria-describedby="cancel-dialog-description">
        <form method="post" action="{{ route('client.applications.cancel', $application->public_id) }}">
            @csrf
            @method('PATCH')
            <div class="pb-cancellation-dialog__header">
                <p class="pb-kicker">Pengaturan pengajuan</p>
                <h2 id="cancel-dialog-title">Batalkan pengajuan?</h2>
                <p id="cancel-dialog-description">{{ $application->service->name }} · ID Pengajuan …{{ $shortId }}</p>
            </div>
            <p>Pengajuan akan dihentikan dan tidak akan diproses lebih lanjut. Catatan pengajuan tetap tersimpan.</p>
            <label for="cancellation-reason">Alasan pembatalan</label>
            <select id="cancellation-reason" name="reason" required data-cancellation-reason>
                <option value="">Pilih alasan</option>
                @foreach($cancellationReasons as $value => $label)
                    <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('reason')<span class="pb-field-error">{{ $message }}</span>@enderror
            <div data-cancellation-other @if(old('reason') !== 'OTHER') hidden @endif>
                <label for="cancellation-reason-other">Jelaskan alasan lainnya</label>
                <textarea id="cancellation-reason-other" name="reason_other" rows="3" maxlength="500">{{ old('reason_other') }}</textarea>
                @error('reason_other')<span class="pb-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="pb-cancellation-dialog__actions">
                <button class="pb-button pb-button--secondary" type="button" data-cancel-dialog-close>Kembali</button>
                <button class="pb-button pb-button--danger" type="submit">Ya, batalkan pengajuan</button>
            </div>
        </form>
    </dialog>
@endif
@endsection
