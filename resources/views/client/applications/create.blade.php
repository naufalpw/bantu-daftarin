@extends('layouts.client')

@section('body_class', 'bd-client-body bd-client-create-body')

@section('content')
    @php($businessType = old('business_type', $selectedBusinessType))

    <div class="bd-client-page bd-create-page">
        <a class="bd-client-back" href="{{ route('client.services.index') }}">
            <img src="{{ asset('images/figma/register/back.svg') }}" alt="">
            <span>Kembali ke layanan</span>
        </a>

        <section class="bd-client-hero bd-create-hero">
            <div>
                <span class="bd-client-eyebrow">Langkah awal</span>
                <h1>{{ $service->name }}</h1>
                <p>Lengkapi data dasar untuk membuat draft aplikasi. Detail dan dokumen dapat dilanjutkan setelah draft dibuat.</p>
            </div>
            <span class="bd-create-hero__step">01 <small>/ 02</small></span>
        </section>

        <form method="post" action="{{ route('client.applications.store') }}" class="bd-client-form-card">
            @csrf
            <input type="hidden" name="service_public_id" value="{{ $service->public_id }}">
            <input type="hidden" name="kind" value="{{ $service->code }}">

            <div class="bd-client-form-card__heading">
                <div>
                    <span class="bd-client-eyebrow">Data aplikasi</span>
                    <h2>Mulai pendaftaran Anda</h2>
                    <p>Data dapat dilengkapi dan diperbarui pada halaman pendaftaran berikutnya.</p>
                </div>
                <span class="bd-client-form-card__required">* Wajib diisi</span>
            </div>

            @if($service->code === 'NPWP_PERSONAL')
                <section class="bd-client-form-section" aria-labelledby="personal-basic-data-title">
                    <h3 id="personal-basic-data-title">Data perseorangan</h3>
                    <div class="bd-client-form-grid">
                        <label class="bd-client-field bd-client-field--wide">
                            <span>Nama lengkap <b>*</b></span>
                            <input name="name" value="{{ old('name', auth()->user()->name) }}" required autocomplete="name">
                        </label>
                        <label class="bd-client-field">
                            <span>Email</span>
                            <input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email">
                        </label>
                        <label class="bd-client-field">
                            <span>Jenis kelamin</span>
                            <input name="gender" value="{{ old('gender') }}">
                        </label>
                        <label class="bd-client-field">
                            <span>Status pernikahan</span>
                            <input name="marital_status" value="{{ old('marital_status') }}">
                        </label>
                        <label class="bd-client-field">
                            <span>Status dalam keluarga</span>
                            <input name="family_status" value="{{ old('family_status') }}">
                        </label>
                        <label class="bd-client-field bd-client-field--wide">
                            <span>Keperluan NPWP</span>
                            <input name="purpose" value="{{ old('purpose') }}">
                        </label>
                    </div>
                </section>
            @else
                <section class="bd-client-form-section" aria-labelledby="business-basic-data-title">
                    <h3 id="business-basic-data-title">Data badan usaha</h3>
                    <div class="bd-client-form-grid">
                        <label class="bd-client-field bd-client-field--wide">
                            <span>Nama badan usaha <b>*</b></span>
                            <input name="business_name" value="{{ old('business_name') }}" required>
                        </label>
                        <label class="bd-client-field">
                            <span>Jenis badan usaha</span>
                            <select id="business-type" name="business_type">
                                <option value="">Pilih jenis badan usaha</option>
                                @foreach(\App\Enums\BusinessType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected($businessType === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label id="business-type-other-field" class="bd-client-field" @if($businessType !== 'OTHER') hidden @endif>
                            <span>Jenis badan usaha lainnya <b>*</b></span>
                            <input id="business-type-other" name="business_type_other" value="{{ old('business_type_other') }}" @if($businessType === 'OTHER') required @endif>
                        </label>
                        <label class="bd-client-field bd-client-field--wide">
                            <span>Keperluan NPWP</span>
                            <input name="purpose" value="{{ old('purpose') }}">
                        </label>
                    </div>
                </section>

                <section class="bd-client-form-section" aria-labelledby="primary-representative-title">
                    <div class="bd-client-form-section__heading">
                        <div>
                            <h3 id="primary-representative-title">Penanggung jawab utama</h3>
                            <p>Data primary representative untuk aplikasi badan usaha.</p>
                        </div>
                    </div>
                    <div class="bd-client-form-grid bd-client-form-grid--three">
                        <label class="bd-client-field">
                            <span>Nama <b>*</b></span>
                            <input name="representative[name]" value="{{ old('representative.name') }}" required>
                        </label>
                        <label class="bd-client-field">
                            <span>Hubungan <b>*</b></span>
                            <select name="representative[relationship]" required>
                                <option value="">Pilih hubungan</option>
                                @foreach(['OWNER','DIRECTOR','MANAGEMENT','EMPLOYEE','AUTHORIZED_REPRESENTATIVE','OTHER'] as $relationship)
                                    <option value="{{ $relationship }}" @selected(old('representative.relationship') === $relationship)>{{ str_replace('_', ' ', $relationship) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="bd-client-field">
                            <span>Email</span>
                            <input name="representative[email]" type="email" value="{{ old('representative.email') }}" autocomplete="email">
                        </label>
                    </div>
                </section>

                <section class="bd-client-form-section" aria-labelledby="additional-representative-title">
                    <div class="bd-client-form-section__heading">
                        <div>
                            <h3 id="additional-representative-title">Representative tambahan</h3>
                            <p>Opsional. Isi seluruh data jika representative tambahan digunakan.</p>
                        </div>
                        <span class="bd-client-form-section__optional">Opsional</span>
                    </div>
                    <div class="bd-client-form-grid bd-client-form-grid--three">
                        <label class="bd-client-field">
                            <span>Nama</span>
                            <input name="additional_representative[name]" value="{{ old('additional_representative.name') }}">
                        </label>
                        <label class="bd-client-field">
                            <span>Hubungan</span>
                            <select name="additional_representative[relationship]"><option value="">Pilih bila ada</option>@foreach(['OWNER','DIRECTOR','MANAGEMENT','EMPLOYEE','AUTHORIZED_REPRESENTATIVE','OTHER'] as $relationship)<option value="{{ $relationship }}" @selected(old('additional_representative.relationship') === $relationship)>{{ str_replace('_', ' ', $relationship) }}</option>@endforeach</select>
                        </label>
                        <label class="bd-client-field">
                            <span>Email</span>
                            <input name="additional_representative[email]" type="email" value="{{ old('additional_representative.email') }}" autocomplete="email">
                        </label>
                    </div>
                </section>
            @endif

            <label class="bd-client-consent">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                <span>Saya menyetujui pemrosesan data untuk layanan ini sesuai informasi aplikasi. Saya memahami Bantu Daftarin bukan sistem resmi pemerintah.</span>
            </label>

            <div class="bd-client-form-card__footer">
                <span>Draft akan dibuat sebelum Anda melanjutkan ke halaman Figma.</span>
                <button class="bd-client-button bd-client-button--primary" type="submit">Simpan dan lanjutkan</button>
            </div>
        </form>
    </div>

    @if($service->code === 'NPWP_BUSINESS')
        <script>
            (() => {
                const type = document.getElementById('business-type');
                const otherField = document.getElementById('business-type-other-field');
                const otherInput = document.getElementById('business-type-other');
                if (!type || !otherField || !otherInput) return;
                const update = () => {
                    const isOther = type.value === 'OTHER';
                    otherField.hidden = !isOther;
                    otherInput.required = isOther;
                    if (!isOther) otherInput.value = '';
                };
                type.addEventListener('change', update);
                update();
            })();
        </script>
    @endif
@endsection
