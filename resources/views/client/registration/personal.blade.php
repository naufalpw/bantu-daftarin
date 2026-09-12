@extends('layouts.marketing')

@section('body_class', 'bd-registration-body')

@section('content')
<div class="bd-registration-page bd-personal-page" data-node-id="208:16644" data-name="Desktop">
    <x-site-header />

    <main class="bd-registration-content">
        <div class="bd-registration-back-wrap" data-node-id="208:16646">
            <a class="bd-registration-back" href="{{ route('client.services.index') }}" data-node-id="208:16647">
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

        <section class="bd-personal-intro" data-node-id="208:16650">
            <div class="bd-personal-intro__title">
                <div class="bd-personal-intro__icon"><img src="{{ asset('images/figma/registration/personal/personal-document.svg') }}" alt=""></div>
                <h1 data-node-id="208:16660">Lengkapi Data Diri</h1>
            </div>
            <p data-node-id="208:16662">Masukan Data Sesuai dengan KTP</p>
        </section>

        <livewire:registration-details-form :application="$application" />

        <section class="bd-registration-panel bd-documents-panel" data-node-id="208:16720">
            <div class="bd-registration-section-heading" data-node-id="208:16721">
                <div class="bd-registration-section-icon bd-registration-section-icon--blue">
                    <img src="{{ asset('images/figma/registration/personal/personal-header.svg') }}" alt="">
                </div>
                <div>
                    <h2>Masukan Dokumen</h2>
                    <p>Pastikan dokumen yang di upload jelas dan tidak buram</p>
                </div>
            </div>
            <div class="bd-registration-document-grid" data-node-id="208:16729">
                <x-registration-document-card
                    :application="$application"
                    :requirement="$application->requirements->firstWhere('code', 'KTP')"
                    icon="images/figma/registration/personal/personal-ktp.svg"
                    title="Foto KTP"
                    description="Upload foto KTP anda"
                    button-label="Upload KTP"
                    element-id="personal-ktp"
                />
                <x-registration-document-card
                    :application="$application"
                    :requirement="$application->requirements->firstWhere('code', 'KK')"
                    icon="images/figma/registration/personal/personal-kk.svg"
                    title="Foto Kartu Keluarga"
                    description="Upload Kartu Keluarga"
                    button-label="Upload Dokumen"
                    element-id="personal-kk"
                    icon-wrapped
                />
                <x-registration-document-card
                    :application="$application"
                    :requirement="$application->requirements->firstWhere('code', 'NPWP')"
                    icon="images/figma/registration/personal/personal-npwp.svg"
                    title="NPWP (Jika ada)"
                    description="Upload foto NPWP (opsional)"
                    button-label="Upload NPWP"
                    element-id="personal-npwp"
                />
            </div>
        </section>

        @php($faceRequirement = $application->requirements->firstWhere('code', 'FOTO_WAJAH'))
        <section class="bd-registration-panel bd-face-panel" data-node-id="208:16759">
            <div class="bd-registration-section-heading" data-node-id="208:16760">
                <div class="bd-registration-section-icon bd-registration-section-icon--camera">
                    <img src="{{ asset('images/figma/registration/personal/face-camera.svg') }}" alt="">
                </div>
                <div>
                    <h2>Foto Wajah</h2>
                    <p>Pastikan foto KTP anda terlihat dengan jelas</p>
                </div>
            </div>
            <div class="bd-face-body" data-node-id="208:16768">
                <div class="bd-face-guide" data-node-id="208:16769">
                    <img id="face-guide-image" src="{{ asset('images/figma/registration/personal/face-guide.png') }}" alt="Contoh posisi foto wajah">
                    <video id="face-camera-preview" playsinline hidden></video>
                </div>
                <div class="bd-face-tips" data-node-id="208:16770">
                    <h3>Tips Foto yang Baik :</h3>
                    <ul>
                        <li><img src="{{ asset('images/figma/registration/personal/check.svg') }}" alt="">Pastikan wajah terlihat jelas</li>
                        <li><img src="{{ asset('images/figma/registration/personal/check.svg') }}" alt="">Gunakan pencahayaan yang cukup</li>
                        <li><img src="{{ asset('images/figma/registration/personal/check.svg') }}" alt="">Jangan diruangan yang gelap</li>
                    </ul>

                    @if($faceRequirement)
                        <form id="face-upload-form" data-registration-face-upload method="post" enctype="multipart/form-data" action="{{ route('client.documents.store', [$application->public_id, $faceRequirement->public_id]) }}">
                            @csrf
                            <input id="face-file" type="file" name="file" accept="image/jpeg,image/png" capture="user" hidden>
                            <div class="bd-face-actions">
                                <button id="face-camera-button" class="bd-face-button" type="button">
                                    <img src="{{ asset('images/figma/registration/personal/camera-button.svg') }}" alt="">
                                    <span>Foto Wajah</span>
                                </button>
                                <label class="bd-face-upload" for="face-file">Upload foto</label>
                                <button id="face-capture-button" class="bd-face-capture" type="button" hidden>Ambil foto</button>
                            </div>
                            <p id="face-file-name" class="bd-face-file-name" aria-live="polite"></p>
                        </form>
                    @else
                        <p class="bd-face-unavailable">Requirement foto wajah belum tersedia.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="bd-registration-panel bd-confirm-panel" data-node-id="208:16793">
            <div class="bd-confirm-content">
                <form method="post" action="{{ route('client.applications.submit', $application->public_id) }}">
                    @csrf
                    <button type="submit">Konfirmasi</button>
                </form>
                <div class="bd-confirm-security">
                    <img src="{{ asset('images/figma/registration/personal/lock.svg') }}" alt="">
                    <p>Data Anda aman dan hanya digunakan untuk keperluan pendaftaran NPWP</p>
                </div>
            </div>
        </section>
    </main>
</div>

@endsection
