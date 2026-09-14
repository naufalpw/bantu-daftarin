@php
    $relationshipLabels = [
        'OWNER' => 'Pemilik',
        'DIRECTOR' => 'Direktur',
        'MANAGEMENT' => 'Pengurus',
        'EMPLOYEE' => 'Karyawan',
        'AUTHORIZED_REPRESENTATIVE' => 'Penerima kuasa',
        'OTHER' => 'Lainnya',
    ];
@endphp

<form wire:submit="save" class="pb-details-form">
    <div class="pb-form-heading">
        <div>
            <h3>Data pengajuan</h3>
            <p>Perubahan disimpan otomatis setelah Anda selesai mengisi kolom.</p>
        </div>
        <div class="pb-live-status" aria-live="polite">
            <span wire:loading wire:target="save">Menyimpan...</span>
            @if($saveState)<span wire:loading.remove wire:target="save">{{ $saveState }}</span>@endif
        </div>
    </div>

    @if($kind === 'NPWP_PERSONAL')
        <div class="pb-form-grid">
            <label class="pb-field pb-field--wide"><span>Nama lengkap <b>*</b></span><input wire:model.blur="details.name" @error('details.name') aria-describedby="error-details-name" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.name') ? 'true' : 'false' }}" name="name" required autocomplete="name">@error('details.name')<small id="error-details-name" class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Email</span><input wire:model.blur="details.email" @error('details.email') aria-describedby="error-details-email" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.email') ? 'true' : 'false' }}" name="email" type="email" autocomplete="email">@error('details.email')<small id="error-details-email" class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Jenis kelamin</span><select wire:model.blur="details.gender" @error('details.gender') aria-describedby="error-details-gender" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.gender') ? 'true' : 'false' }}" name="gender"><option value="">Pilih jenis kelamin</option><option value="Pria">Pria</option><option value="Wanita">Wanita</option></select>@error('details.gender')<small id="error-details-gender" class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Status perkawinan</span><select wire:model.blur="details.marital_status" @error('details.marital_status') aria-describedby="error-details-marital_status" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.marital_status') ? 'true' : 'false' }}" name="marital_status"><option value="">Pilih status perkawinan</option><option value="Lajang">Lajang</option><option value="Kawin">Kawin</option><option value="Cerai Hidup">Cerai Hidup</option><option value="Cerai Mati">Cerai Mati</option></select>@error('details.marital_status')<small id="error-details-marital_status" class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Status dalam keluarga</span><select wire:model.blur="details.family_status" @error('details.family_status') aria-describedby="error-details-family_status" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.family_status') ? 'true' : 'false' }}" name="family_status"><option value="">Pilih status dalam keluarga</option><option value="Suami">Suami</option><option value="Istri">Istri</option><option value="Anak">Anak</option></select>@error('details.family_status')<small id="error-details-family_status" class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>NIK <b>*</b></span><input wire:model.blur="details.nik" wire:blur="save" aria-invalid="{{ $errors->has('details.nik') ? 'true' : 'false' }}" name="nik" inputmode="numeric" maxlength="16" autocomplete="off" aria-describedby="nik-help {{ $errors->has('details.nik') ? 'error-details-nik' : '' }}"><small id="nik-help">16 digit, disimpan terenkripsi.</small>@error('details.nik')<small id="error-details-nik" class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Nomor KK <b>*</b></span><input wire:model.blur="details.family_card_number" wire:blur="save" aria-invalid="{{ $errors->has('details.family_card_number') ? 'true' : 'false' }}" name="family_card_number" inputmode="numeric" maxlength="16" autocomplete="off" aria-describedby="kk-help {{ $errors->has('details.family_card_number') ? 'error-details-family_card_number' : '' }}"><small id="kk-help">16 digit, disimpan terenkripsi.</small>@error('details.family_card_number')<small id="error-details-family_card_number" class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field pb-field--wide"><span>Keperluan NPWP</span><input wire:model.blur="details.purpose" @error('details.purpose') aria-describedby="error-details-purpose" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.purpose') ? 'true' : 'false' }}" name="purpose">@error('details.purpose')<small id="error-details-purpose" class="pb-field__error">{{ $message }}</small>@enderror</label>
        </div>
    @else
        <section class="pb-business-form-group" aria-labelledby="business-data-title">
            <div class="pb-business-form-group__heading">
                <h4 id="business-data-title">Data badan usaha</h4>
                <p>Isi informasi badan usaha sesuai dokumen pendukung yang akan diunggah.</p>
            </div>
            <div class="pb-form-grid">
                <label class="pb-field pb-field--wide"><span>Nama badan usaha <b>*</b></span><input wire:model.blur="details.business_name" @error('details.business_name') aria-describedby="error-details-business_name" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.business_name') ? 'true' : 'false' }}" name="business_name" required>@error('details.business_name')<small id="error-details-business_name" class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field pb-field--wide"><span>Jenis badan usaha <b>*</b></span><select wire:model.blur="details.business_type" @error('details.business_type') aria-describedby="error-details-business_type" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.business_type') ? 'true' : 'false' }}" name="business_type" required><option value="">Pilih jenis badan usaha</option>@foreach(\App\Enums\BusinessType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>@error('details.business_type')<small id="error-details-business_type" class="pb-field__error">{{ $message }}</small>@enderror</label>
                @if(($details['business_type'] ?? null) === 'OTHER')
                    <label class="pb-field pb-field--wide"><span>Jenis badan usaha lainnya <b>*</b></span><input wire:model.blur="details.business_type_other" @error('details.business_type_other') aria-describedby="error-details-business_type_other" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.business_type_other') ? 'true' : 'false' }}" name="business_type_other" required>@error('details.business_type_other')<small id="error-details-business_type_other" class="pb-field__error">{{ $message }}</small>@enderror</label>
                @endif
                <label class="pb-field pb-field--wide"><span>Keperluan NPWP</span><input wire:model.blur="details.purpose" @error('details.purpose') aria-describedby="error-details-purpose" @enderror wire:blur="save" aria-invalid="{{ $errors->has('details.purpose') ? 'true' : 'false' }}" name="purpose">@error('details.purpose')<small id="error-details-purpose" class="pb-field__error">{{ $message }}</small>@enderror</label>
            </div>
        </section>

        <fieldset class="pb-fieldset pb-business-fieldset">
            <legend>Penanggung jawab utama</legend>
            <p>Isi data penanggung jawab utama pengajuan.</p>
            <div class="pb-form-grid">
                <label class="pb-field"><span>Nama <b>*</b></span><input wire:model.blur="representative.name" @error('representative.name') aria-describedby="error-representative-name" @enderror wire:blur="save" aria-invalid="{{ $errors->has('representative.name') ? 'true' : 'false' }}" required>@error('representative.name')<small id="error-representative-name" class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field"><span>Hubungan <b>*</b></span><select wire:model.blur="representative.relationship" @error('representative.relationship') aria-describedby="error-representative-relationship" @enderror wire:blur="save" aria-invalid="{{ $errors->has('representative.relationship') ? 'true' : 'false' }}" required><option value="">Pilih hubungan</option>@foreach($relationshipLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('representative.relationship')<small id="error-representative-relationship" class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field pb-field--wide"><span>Email</span><input wire:model.blur="representative.email" @error('representative.email') aria-describedby="error-representative-email" @enderror wire:blur="save" aria-invalid="{{ $errors->has('representative.email') ? 'true' : 'false' }}" type="email">@error('representative.email')<small id="error-representative-email" class="pb-field__error">{{ $message }}</small>@enderror</label>
            </div>
        </fieldset>

        <fieldset class="pb-fieldset pb-business-fieldset pb-business-fieldset--optional">
            <legend>Penanggung jawab tambahan <small>Opsional</small></legend>
            <p>Isi data penanggung jawab tambahan jika diperlukan.</p>
            <div class="pb-form-grid">
                <label class="pb-field"><span>Nama</span><input wire:model.blur="additionalRepresentative.name" @error('additionalRepresentative.name') aria-describedby="error-additionalRepresentative-name" @enderror wire:blur="save" aria-invalid="{{ $errors->has('additionalRepresentative.name') ? 'true' : 'false' }}">@error('additionalRepresentative.name')<small id="error-additionalRepresentative-name" class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field"><span>Hubungan</span><select wire:model.blur="additionalRepresentative.relationship" @error('additionalRepresentative.relationship') aria-describedby="error-additionalRepresentative-relationship" @enderror wire:blur="save" aria-invalid="{{ $errors->has('additionalRepresentative.relationship') ? 'true' : 'false' }}"><option value="">Pilih hubungan</option>@foreach($relationshipLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('additionalRepresentative.relationship')<small id="error-additionalRepresentative-relationship" class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field pb-field--wide"><span>Email</span><input wire:model.blur="additionalRepresentative.email" @error('additionalRepresentative.email') aria-describedby="error-additionalRepresentative-email" @enderror wire:blur="save" aria-invalid="{{ $errors->has('additionalRepresentative.email') ? 'true' : 'false' }}" type="email">@error('additionalRepresentative.email')<small id="error-additionalRepresentative-email" class="pb-field__error">{{ $message }}</small>@enderror</label>
            </div>
        </fieldset>
    @endif

    <div class="pb-form-actions">
        <div class="bd-phone-only pb-live-status" aria-live="polite"><span wire:loading wire:target="save">Menyimpan...</span>@if($saveState)<span wire:loading.remove wire:target="save">{{ $saveState }}</span>@endif</div>
        <button type="submit" class="pb-button pb-button--secondary">Simpan data</button>
    </div>
</form>
