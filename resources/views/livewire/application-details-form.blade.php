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
            <label class="pb-field pb-field--wide"><span>Nama lengkap <b>*</b></span><input wire:model.blur="details.name" wire:blur="save" name="name" required autocomplete="name">@error('details.name')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Email</span><input wire:model.blur="details.email" wire:blur="save" name="email" type="email" autocomplete="email">@error('details.email')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Jenis kelamin</span><select wire:model.blur="details.gender" wire:blur="save" name="gender"><option value="">Pilih jenis kelamin</option><option value="Pria">Pria</option><option value="Wanita">Wanita</option></select>@error('details.gender')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Status perkawinan</span><select wire:model.blur="details.marital_status" wire:blur="save" name="marital_status"><option value="">Pilih status perkawinan</option><option value="Lajang">Lajang</option><option value="Kawin">Kawin</option><option value="Cerai Hidup">Cerai Hidup</option><option value="Cerai Mati">Cerai Mati</option></select>@error('details.marital_status')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Status dalam keluarga</span><select wire:model.blur="details.family_status" wire:blur="save" name="family_status"><option value="">Pilih status dalam keluarga</option><option value="Suami">Suami</option><option value="Istri">Istri</option><option value="Anak">Anak</option></select>@error('details.family_status')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>NIK <b>*</b></span><input wire:model.blur="details.nik" wire:blur="save" name="nik" inputmode="numeric" maxlength="16" autocomplete="off" aria-describedby="nik-help"><small id="nik-help">16 digit, disimpan terenkripsi.</small>@error('details.nik')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field"><span>Nomor KK <b>*</b></span><input wire:model.blur="details.family_card_number" wire:blur="save" name="family_card_number" inputmode="numeric" maxlength="16" autocomplete="off" aria-describedby="kk-help"><small id="kk-help">16 digit, disimpan terenkripsi.</small>@error('details.family_card_number')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            <label class="pb-field pb-field--wide"><span>Keperluan NPWP</span><input wire:model.blur="details.purpose" wire:blur="save" name="purpose">@error('details.purpose')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
        </div>
    @else
        <section class="pb-business-form-group" aria-labelledby="business-data-title">
            <div class="pb-business-form-group__heading">
                <h4 id="business-data-title">Data badan usaha</h4>
                <p>Isi informasi badan usaha sesuai dokumen pendukung yang akan diunggah.</p>
            </div>
            <div class="pb-form-grid">
                <label class="pb-field pb-field--wide"><span>Nama badan usaha <b>*</b></span><input wire:model.blur="details.business_name" wire:blur="save" name="business_name" required>@error('details.business_name')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field pb-field--wide"><span>Jenis badan usaha <b>*</b></span><select wire:model.blur="details.business_type" wire:blur="save" name="business_type" required><option value="">Pilih jenis badan usaha</option>@foreach(\App\Enums\BusinessType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>@error('details.business_type')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
                @if(($details['business_type'] ?? null) === 'OTHER')
                    <label class="pb-field pb-field--wide"><span>Jenis badan usaha lainnya <b>*</b></span><input wire:model.blur="details.business_type_other" wire:blur="save" name="business_type_other" required>@error('details.business_type_other')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
                @endif
                <label class="pb-field pb-field--wide"><span>Keperluan NPWP</span><input wire:model.blur="details.purpose" wire:blur="save" name="purpose">@error('details.purpose')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            </div>
        </section>

        <fieldset class="pb-fieldset pb-business-fieldset">
            <legend>Penanggung jawab utama</legend>
            <p>Isi data penanggung jawab utama pengajuan.</p>
            <div class="pb-form-grid">
                <label class="pb-field"><span>Nama <b>*</b></span><input wire:model.blur="representative.name" wire:blur="save" required>@error('representative.name')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field"><span>Hubungan <b>*</b></span><select wire:model.blur="representative.relationship" wire:blur="save" required><option value="">Pilih hubungan</option>@foreach($relationshipLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('representative.relationship')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field pb-field--wide"><span>Email</span><input wire:model.blur="representative.email" wire:blur="save" type="email">@error('representative.email')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            </div>
        </fieldset>

        <fieldset class="pb-fieldset pb-business-fieldset pb-business-fieldset--optional">
            <legend>Penanggung jawab tambahan <small>Opsional</small></legend>
            <p>Isi data penanggung jawab tambahan jika diperlukan.</p>
            <div class="pb-form-grid">
                <label class="pb-field"><span>Nama</span><input wire:model.blur="additionalRepresentative.name" wire:blur="save">@error('additionalRepresentative.name')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field"><span>Hubungan</span><select wire:model.blur="additionalRepresentative.relationship" wire:blur="save"><option value="">Pilih hubungan</option>@foreach($relationshipLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('additionalRepresentative.relationship')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
                <label class="pb-field pb-field--wide"><span>Email</span><input wire:model.blur="additionalRepresentative.email" wire:blur="save" type="email">@error('additionalRepresentative.email')<small class="pb-field__error">{{ $message }}</small>@enderror</label>
            </div>
        </fieldset>
    @endif

    <div class="pb-form-actions"><button type="submit" class="pb-button pb-button--secondary">Simpan data</button></div>
</form>
