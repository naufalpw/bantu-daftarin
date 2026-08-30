@extends('layouts.marketing')

@section('body_class', 'bd-registration-body')

@section('content')
<div class="bd-registration-page bd-business-page" data-node-id="208:20520" data-name="Desktop">
    <x-site-header />

    <main class="bd-registration-content">
        <div class="bd-registration-back-wrap" data-node-id="208:20522">
            <a class="bd-registration-back" href="{{ route('npwp.business.types') }}" data-node-id="208:20523">
                <img src="{{ asset('images/figma/register/back.svg') }}" alt="">
                <span>Kembali</span>
            </a>
        </div>

        @if(session('status') || $errors->any())
            <div class="bd-registration-feedback">
                @if(session('status'))<p class="bd-registration-feedback__status">{{ session('status') }}</p>@endif
                @if($errors->any())<ul class="bd-registration-feedback__errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
            </div>
        @endif

        <livewire:registration-details-form :application="$application" />

        <section class="bd-registration-panel bd-documents-panel bd-business-documents-panel" data-node-id="208:20579">
            <div class="bd-registration-section-heading" data-node-id="208:20580">
                <div class="bd-registration-section-icon bd-registration-section-icon--folder">
                    <img src="{{ asset('images/figma/registration/business/business-folder.svg') }}" alt="">
                </div>
                <div>
                    <h2>Masukan Dokumen Perusahaan</h2>
                    <p>Pastikan dokumen yang di upload jelas dan tidak buram</p>
                </div>
            </div>
            <div class="bd-registration-document-grid bd-business-document-grid" data-node-id="208:20587">
                <x-registration-document-card
                    :application="$application"
                    :requirement="$application->requirements->firstWhere('code', 'AKTA_NOTARIS')"
                    icon="images/figma/registration/business/business-document.svg"
                    title="Akta Notaris"
                    description="Upload foto Akta Notaris anda"
                    button-label="Upload Akta Notaris"
                    max-label="JPG, PNG (Max. 10mb)"
                    element-id="business-akta-notaris"
                    icon-wrapped
                />
                <x-registration-document-card
                    :application="$application"
                    :requirement="$application->requirements->firstWhere('code', 'SK_AHU')"
                    icon="images/figma/registration/business/business-document.svg"
                    title="SK-AHU"
                    description="Upload foto SK-AHU anda"
                    button-label="Upload SK-AHU"
                    max-label="JPG, PNG (Max. 10mb)"
                    element-id="business-sk-ahu"
                    icon-wrapped
                />
            </div>
        </section>

        <section class="bd-registration-panel bd-business-extra-documents">
            <div class="bd-registration-section-heading">
                <div class="bd-registration-section-icon bd-registration-section-icon--folder">
                    <img src="{{ asset('images/figma/registration/business/business-folder.svg') }}" alt="">
                </div>
                <div>
                    <h2>Dokumen Tambahan</h2>
                    <p>Dokumen wajib dan conditional tetap mengikuti proses aplikasi.</p>
                </div>
            </div>
            <div class="bd-registration-document-grid bd-business-extra-document-grid">
                <x-registration-document-card
                    :application="$application"
                    :requirement="$application->requirements->firstWhere('code', 'KTP_PENANGGUNG_JAWAB')"
                    icon="images/figma/registration/business/business-document.svg"
                    title="KTP Penanggung Jawab"
                    description="Upload foto KTP penanggung jawab"
                    button-label="Upload KTP"
                    max-label="JPG, PNG (Max. 5mb)"
                    element-id="business-ktp-penanggung-jawab"
                    icon-wrapped
                />
                <x-registration-document-card
                    :application="$application"
                    :requirement="$application->requirements->firstWhere('code', 'SURAT_KUASA')"
                    icon="images/figma/registration/business/business-document.svg"
                    title="Surat Kuasa"
                    description="Upload jika diwakilkan (opsional)"
                    button-label="Upload Surat Kuasa"
                    max-label="JPG, PNG (Max. 5mb)"
                    element-id="business-surat-kuasa"
                    icon-wrapped
                />
            </div>
        </section>

        <section class="bd-registration-panel bd-confirm-panel" data-node-id="208:20616">
            <div class="bd-confirm-content">
                <form method="post" action="{{ route('client.applications.submit', $application->public_id) }}">
                    @csrf
                    <button type="submit">Konfirmasi</button>
                </form>
                <div class="bd-confirm-security">
                    <img src="{{ asset('images/figma/registration/business/lock.svg') }}" alt="">
                    <p>Data Anda aman dan hanya digunakan untuk keperluan pendaftaran NPWP</p>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
