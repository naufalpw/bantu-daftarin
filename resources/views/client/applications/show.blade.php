@extends('layouts.client')

@section('body_class', 'bd-client-body bd-client-application-body')

@section('content')
    @php
        $isEditable = in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS'], true);
        $statusTone = match ($application->status->value) {
            'COMPLETED', 'ARCHIVED' => 'success',
            'REVISION_REQUIRED' => 'danger',
            'AWAITING_PAYMENT', 'DOCUMENTS_READY_FOR_PAYMENT' => 'warning',
            default => 'primary',
        };
        $documentIcon = $application->service->code === 'NPWP_BUSINESS'
            ? 'images/figma/registration/business/business-folder.svg'
            : 'images/figma/registration/personal/personal-document.svg';
        $verifiedResults = $application->resultDocuments->filter(fn ($result) => $result->verification_status->value === 'VERIFIED');
    @endphp

    <div class="bd-client-page bd-application-page">
        <a class="bd-client-back" href="{{ route('client.dashboard') }}">
            <img src="{{ asset('images/figma/register/back.svg') }}" alt="">
            <span>Kembali ke aplikasi</span>
        </a>

        <header class="bd-application-header">
            <div class="bd-application-header__copy">
                <span class="bd-client-eyebrow">Detail aplikasi</span>
                <h1>{{ $application->service->name }}</h1>
                <p><span>Nomor aplikasi</span><code>{{ $application->public_id }}</code></p>
            </div>
            <span class="bd-client-status bd-client-status--{{ $statusTone }}">{{ $application->status->label() }}</span>
        </header>

        <div class="bd-application-layout">
            <div class="bd-application-main">
                <section class="bd-client-card bd-application-progress-card" aria-labelledby="application-progress-title">
                    <div class="bd-client-card__heading">
                        <div>
                            <span class="bd-client-eyebrow">Perjalanan aplikasi</span>
                            <h2 id="application-progress-title">Pantau proses pendaftaran</h2>
                        </div>
                        <span class="bd-client-card__hint">Status diperbarui dari sistem</span>
                    </div>
                    @include('components.application-progress', ['status' => $application->status])
                </section>

                @if($isEditable)
                    <livewire:application-details-form :application="$application" />
                @endif

                <section class="bd-client-card bd-application-documents" aria-labelledby="application-documents-title">
                    <div class="bd-client-card__heading">
                        <div class="bd-application-card-title">
                            <span class="bd-application-card-title__icon">
                                <img src="{{ asset($documentIcon) }}" alt="">
                            </span>
                            <div>
                                <span class="bd-client-eyebrow">Data &amp; dokumen</span>
                                <h2 id="application-documents-title">Checklist dokumen</h2>
                            </div>
                        </div>
                        <span class="bd-client-card__hint">File disimpan secara private</span>
                    </div>
                    <p class="bd-application-card-intro">Pastikan dokumen yang diunggah jelas dan dapat dibaca. Akses dokumen dibatasi sesuai izin aplikasi.</p>

                    <div class="bd-application-requirements">
                        @foreach($application->requirements as $requirement)
                            @php($activeDocument = $requirement->documents->where('active', true)->first())
                            @php($requirementTone = match ($requirement->status) {
                                'ACCEPTED' => 'success',
                                'REVISION_REQUIRED', 'REJECTED' => 'danger',
                                default => 'muted',
                            })
                            <article class="bd-application-requirement">
                                <div class="bd-application-requirement__header">
                                    <div>
                                        <h3>{{ $requirement->name }} @if($requirement->is_required)<b aria-label="wajib">*</b>@endif</h3>
                                        <p>{{ strtoupper(implode(', ', $requirement->allowed_extensions)) }} <span aria-hidden="true">·</span> maksimal {{ number_format($requirement->max_size_bytes / 1048576, 0) }} MB</p>
                                    </div>
                                    <span class="bd-client-status bd-client-status--{{ $requirementTone }}">{{ $requirement->statusLabel() }}</span>
                                </div>

                                @if($activeDocument)
                                    <div class="bd-application-document-row">
                                        <div>
                                            <strong>Versi {{ $activeDocument->version_number }}</strong>
                                            <span>{{ $activeDocument->review_status->label() }}</span>
                                        </div>
                                        <div class="bd-application-document-actions">
                                            <a class="bd-client-link" target="_blank" rel="noopener" href="{{ route('client.documents.view', $activeDocument->public_id) }}">Lihat</a>
                                            <a class="bd-client-link" href="{{ route('client.documents.download', $activeDocument->public_id) }}">Unduh</a>
                                            @if($isEditable || ($application->status->value === 'REVISION_REQUIRED' && $requirement->status === 'REVISION_REQUIRED'))
                                                <form method="post" action="{{ route('client.documents.destroy', $activeDocument->public_id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="bd-client-link bd-client-link--danger" type="submit" onclick="return confirm('Hapus versi aktif ini?')">Hapus</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if(in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS', 'DOCUMENTS_READY_FOR_PAYMENT', 'AWAITING_PAYMENT'], true) || ($application->status->value === 'REVISION_REQUIRED' && $requirement->status === 'REVISION_REQUIRED'))
                                    <form method="post" enctype="multipart/form-data" action="{{ route('client.documents.store', [$application->public_id, $requirement->public_id]) }}" class="bd-application-upload-form">
                                        @csrf
                                        <label class="bd-client-file-input">
                                            <span>Pilih file</span>
                                            <input type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf">
                                        </label>
                                        <button class="bd-client-button bd-client-button--secondary" type="submit">{{ $activeDocument ? 'Unggah versi baru' : 'Unggah dokumen' }}</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>

                @if($verifiedResults->isNotEmpty())
                    <section class="bd-client-card bd-application-results" aria-labelledby="application-results-title">
                        <div class="bd-client-card__heading">
                            <div>
                                <span class="bd-client-eyebrow">Hasil layanan</span>
                                <h2 id="application-results-title">Hasil layanan tersedia</h2>
                            </div>
                            <span class="bd-client-status bd-client-status--success">Terverifikasi</span>
                        </div>
                        <div class="bd-application-results__list">
                            @foreach($verifiedResults as $result)
                                <div class="bd-application-result-row">
                                    <div>
                                        <strong>{{ str_replace('_', ' ', $result->type->value) }}</strong>
                                        <span>Dokumen hasil telah diverifikasi</span>
                                    </div>
                                    <div class="bd-application-document-actions">
                                        <a class="bd-client-link" target="_blank" rel="noopener" href="{{ route('client.results.view', $result->public_id) }}">Lihat</a>
                                        <a class="bd-client-link" href="{{ route('client.results.download', $result->public_id) }}">Unduh hasil</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="bd-application-sidebar">
                <section class="bd-client-card bd-application-next-action" aria-labelledby="next-action-title">
                    <span class="bd-client-eyebrow">Berikutnya</span>
                    <h2 id="next-action-title">Langkah berikutnya</h2>
                    <div class="bd-application-next-action__body">
                        @if($application->status->value === 'DRAFT')
                            <p>Periksa data aplikasi Anda sebelum melanjutkan ke pengumpulan dokumen.</p>
                            <form method="post" action="{{ route('client.applications.submit', $application->public_id) }}">@csrf<button class="bd-client-button bd-client-button--primary" type="submit">Lanjut ke dokumen</button></form>
                        @elseif($application->status->value === 'AWAITING_DOCUMENTS')
                            <p class="bd-client-notice bd-client-notice--warning">Lengkapi seluruh dokumen wajib terlebih dahulu. Setelah itu tombol pembayaran akan tersedia.</p>
                        @elseif(in_array($application->status->value, ['DOCUMENTS_READY_FOR_PAYMENT', 'AWAITING_PAYMENT'], true))
                            <p>Dokumen siap diproses. Lanjutkan pembayaran untuk meneruskan aplikasi.</p>
                            <a href="{{ route('client.payments.show', $application->public_id) }}" class="bd-client-button bd-client-button--primary">Bayar {{ $application->currency }} {{ number_format((float) $application->price_amount_snapshot, 0, ',', '.') }}</a>
                        @elseif($application->status->value === 'PAYMENT_CONFIRMED')
                            <p>Pembayaran telah terkonfirmasi. Kirim dokumen untuk pemeriksaan admin.</p>
                            <form method="post" action="{{ route('client.applications.documents.submit', $application->public_id) }}">@csrf<button class="bd-client-button bd-client-button--primary" type="submit">Kirim dokumen untuk diperiksa</button></form>
                        @elseif($application->status->value === 'REVISION_REQUIRED')
                            <p class="bd-client-notice bd-client-notice--danger">Beberapa dokumen perlu diperbaiki sebelum dapat diperiksa kembali.</p>
                            <form method="post" action="{{ route('client.applications.revision.submit', $application->public_id) }}">@csrf<button class="bd-client-button bd-client-button--primary" type="submit">Kirim perbaikan</button></form>
                        @else
                            <p>Aplikasi sedang diproses. Perubahan status akan tampil pada aktivitas Anda.</p>
                            <a class="bd-client-button bd-client-button--secondary" href="{{ route('client.activity.show', $application->public_id) }}">Lihat aktivitas</a>
                        @endif
                    </div>
                </section>

                <section class="bd-client-card bd-application-history" aria-labelledby="application-history-title">
                    <div class="bd-client-card__heading">
                        <div>
                            <span class="bd-client-eyebrow">Pembaruan aplikasi</span>
                            <h2 id="application-history-title">Riwayat status</h2>
                        </div>
                    </div>
                    <ol class="bd-application-history__list">
                        @forelse($application->statusHistories->sortByDesc('created_at') as $history)
                            <li>
                                <span class="bd-application-history__dot" aria-hidden="true"></span>
                                <div>
                                    <strong>{{ $history->to_status->label() }}</strong>
                                    <span>{{ $history->created_at->translatedFormat('d M Y H:i') }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="bd-application-history__empty">Belum ada pembaruan status.</li>
                        @endforelse
                    </ol>
                    <a class="bd-client-link bd-application-history__link" href="{{ route('client.activity.show', $application->public_id) }}">Lihat detail aktivitas</a>
                </section>

                @if($application->chatThread)
                    <a class="bd-application-chat" href="{{ route('client.chat.show', $application->chatThread->public_id) }}">
                        <span class="bd-application-chat__icon" aria-hidden="true">↗</span>
                        <span>
                            <strong>Chat dengan admin</strong>
                            <small>Tanyakan hal terkait aplikasi ini.</small>
                        </span>
                    </a>
                @endif
            </aside>
        </div>
    </div>
@endsection
