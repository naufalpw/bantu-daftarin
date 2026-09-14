@extends('layouts.client')

@section('context_title', 'Mulai pengajuan')
@section('body_class', 'pb-create-body')

@section('content')
@php
    $businessType = old('business_type', $selectedBusinessType);
    $relationshipLabels = [
        'OWNER' => 'Pemilik',
        'DIRECTOR' => 'Direktur',
        'MANAGEMENT' => 'Pengurus',
        'EMPLOYEE' => 'Karyawan',
        'AUTHORIZED_REPRESENTATIVE' => 'Penerima kuasa',
        'OTHER' => 'Lainnya',
    ];
@endphp

<div class="pb-page pb-create">
    <a class="pb-back-link" href="{{ route('client.services.index') }}">
        <span aria-hidden="true">←</span> Kembali ke layanan
    </a>

    <header class="pb-page-heading">
        <p class="pb-kicker">Persyaratan pengajuan</p>
        <h1>{{ $service->name }}</h1>
        <p>Lihat biaya dan dokumen yang perlu disiapkan. Setelah data awal dan persetujuan disimpan, draft pengajuan dibuat.</p>
    </header>

    <div class="pb-start-layout">
        <aside class="pb-start-summary" aria-labelledby="start-summary-title">
            <h2 id="start-summary-title">Sebelum mulai</h2>
            <dl>
                <div>
                    <dt>Biaya layanan</dt>
                    <dd>{{ $service->currency }} {{ number_format((float) $service->price_amount, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt>Dokumen yang disiapkan</dt>
                    <dd>
                        <details open data-phone-disclosure class="pb-start-requirements">
                        <summary>Lihat persyaratan dokumen</summary>
                        <ul>
                            @foreach($service->requirements as $requirement)
                                <li>
                                    {{ $requirement->name }}
                                    <small>{{ $requirement->is_required ? 'Wajib' : 'Opsional' }}@if($requirement->condition), {{ $requirement->condition }}@endif</small>
                                </li>
                            @endforeach
                        </ul>
                        </details>
                    </dd>
                </div>
            </dl>
            <p class="pb-private-note">Dokumen disimpan secara privat dan hanya dapat dibuka oleh pihak yang berwenang dalam pengajuan.</p>
        </aside>

        <form method="post" action="{{ route('client.applications.store') }}" class="pb-form-surface">
            @csrf
            <input type="hidden" name="service_public_id" value="{{ $service->public_id }}">
            <input type="hidden" name="kind" value="{{ $service->code }}">

            <div class="pb-form-heading">
                <div>
                    <p class="pb-kicker">Data awal</p>
                    <h2>Informasi untuk membuat draft</h2>
                </div>
                <span><b>*</b> Wajib diisi</span>
            </div>

            @if($service->code === 'NPWP_PERSONAL')
                <div class="pb-form-grid">
                    <label class="pb-field pb-field--wide">
                        <span>Nama lengkap <b>*</b></span>
                        <input name="name" value="{{ old('name', auth()->user()->name) }}" required autocomplete="name">
                        @error('name')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="pb-field pb-field--wide">
                        <span>Email pengajuan</span>
                        <input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email">
                        @error('email')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="pb-field pb-field--wide">
                        <span>Keperluan NPWP <small>Opsional</small></span>
                        <input name="purpose" value="{{ old('purpose') }}">
                        @error('purpose')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
            @else
                <div class="pb-form-grid">
                    <label class="pb-field pb-field--wide">
                        <span>Nama badan usaha <b>*</b></span>
                        <input name="business_name" value="{{ old('business_name') }}" required>
                        @error('business_name')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="pb-field pb-field--wide">
                        <span>Jenis badan usaha <b>*</b></span>
                        <select id="business-type" name="business_type" required>
                            <option value="">Pilih jenis badan usaha</option>
                            @foreach(\App\Enums\BusinessType::cases() as $type)
                                <option value="{{ $type->value }}" @selected($businessType === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('business_type')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label id="business-type-other-field" class="pb-field pb-field--wide" @if($businessType !== 'OTHER') hidden @endif>
                        <span>Jenis badan usaha lainnya <b>*</b></span>
                        <input id="business-type-other" name="business_type_other" value="{{ old('business_type_other') }}" @if($businessType === 'OTHER') required @endif>
                        @error('business_type_other')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="pb-field">
                        <span>Nama penanggung jawab <b>*</b></span>
                        <input name="representative[name]" value="{{ old('representative.name') }}" required autocomplete="name">
                        @error('representative.name')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="pb-field">
                        <span>Hubungan dengan badan usaha <b>*</b></span>
                        <select name="representative[relationship]" required>
                            <option value="">Pilih hubungan</option>
                            @foreach($relationshipLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('representative.relationship') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('representative.relationship')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="pb-field pb-field--wide">
                        <span>Email penanggung jawab <small>Opsional</small></span>
                        <input name="representative[email]" type="email" value="{{ old('representative.email') }}" autocomplete="email">
                        @error('representative.email')<small class="pb-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="pb-field pb-field--wide">
                        <span>Keperluan NPWP <small>Opsional</small></span>
                        <input name="purpose" value="{{ old('purpose') }}">
                    </label>
                </div>
            @endif

            <label class="pb-consent">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                <span>Saya menyetujui pemrosesan data untuk layanan ini. Saya memahami Bantu Daftarin adalah layanan bantuan administrasi, bukan portal resmi pemerintah.</span>
            </label>
            @error('consent')<small class="pb-field__error">{{ $message }}</small>@enderror

            <div class="pb-form-actions">
                <p>Setelah draft dibuat, data lengkap dan dokumen dikelola dari ruang pengajuan.</p>
                <button class="pb-button pb-button--primary" type="submit">Buat draft pengajuan</button>
            </div>
        </form>
    </div>
</div>

@endsection
