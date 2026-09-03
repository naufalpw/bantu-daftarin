<form wire:submit="save" class="bd-client-card bd-application-details-form">
    <div class="bd-client-card__heading">
        <div>
            <span class="bd-client-eyebrow">Data aplikasi</span>
            <h2>Lengkapi informasi Anda</h2>
            <p>Perubahan disimpan otomatis saat Anda berpindah dari kolom.</p>
        </div>
        @if($saveState)
            <span class="bd-client-form-save-state" role="status">{{ $saveState }}</span>
        @endif
    </div>

    <div class="bd-client-form-grid">
        @if($kind === 'NPWP_PERSONAL')
            <label class="bd-client-field bd-client-field--wide">
                <span>Nama lengkap <b>*</b></span>
                <input wire:model.blur="details.name" wire:blur="save" name="name" required autocomplete="name">
                @error('details.name')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>Email</span>
                <input wire:model.blur="details.email" wire:blur="save" name="email" type="email" autocomplete="email">
                @error('details.email')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>Jenis kelamin</span>
                <input wire:model.blur="details.gender" wire:blur="save" name="gender">
                @error('details.gender')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>Status pernikahan</span>
                <input wire:model.blur="details.marital_status" wire:blur="save" name="marital_status">
                @error('details.marital_status')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>Status dalam keluarga</span>
                <input wire:model.blur="details.family_status" wire:blur="save" name="family_status">
                @error('details.family_status')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>NIK</span>
                <input wire:model.blur="details.nik" wire:blur="save" name="nik" inputmode="numeric" maxlength="16" autocomplete="off">
                @error('details.nik')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>Nomor KK</span>
                <input wire:model.blur="details.family_card_number" wire:blur="save" name="family_card_number" inputmode="numeric" maxlength="16" autocomplete="off">
                @error('details.family_card_number')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field bd-client-field--wide">
                <span>Keperluan NPWP</span>
                <input wire:model.blur="details.purpose" wire:blur="save" name="purpose">
                @error('details.purpose')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
        @else
            <label class="bd-client-field bd-client-field--wide">
                <span>Nama badan usaha <b>*</b></span>
                <input wire:model.blur="details.business_name" wire:blur="save" name="business_name" required>
                @error('details.business_name')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>Jenis badan usaha</span>
                <input wire:model.blur="details.business_type" wire:blur="save" name="business_type">
                @error('details.business_type')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field">
                <span>Jenis badan usaha lainnya</span>
                <input wire:model.blur="details.business_type_other" wire:blur="save" name="business_type_other" placeholder="Isi jika memilih OTHER">
                @error('details.business_type_other')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>
            <label class="bd-client-field bd-client-field--wide">
                <span>Keperluan NPWP</span>
                <input wire:model.blur="details.purpose" wire:blur="save" name="purpose">
                @error('details.purpose')<small class="bd-client-field__error">{{ $message }}</small>@enderror
            </label>

            <div class="bd-client-form-subsection">
                <div class="bd-client-form-subsection__heading">
                    <div>
                        <h3>Penanggung jawab utama</h3>
                        <p>Primary representative aplikasi.</p>
                    </div>
                </div>
                <div class="bd-client-form-grid bd-client-form-grid--three">
                    <label class="bd-client-field">
                        <span>Nama <b>*</b></span>
                        <input wire:model.blur="representative.name" wire:blur="save" name="representative[name]" required>
                        @error('representative.name')<small class="bd-client-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="bd-client-field">
                        <span>Hubungan <b>*</b></span>
                        <select wire:model.blur="representative.relationship" wire:blur="save" name="representative[relationship]" required>
                            <option value="">Pilih hubungan</option>
                            @foreach(['OWNER','DIRECTOR','MANAGEMENT','EMPLOYEE','AUTHORIZED_REPRESENTATIVE','OTHER'] as $relationship)
                                <option value="{{ $relationship }}">{{ str_replace('_', ' ', $relationship) }}</option>
                            @endforeach
                        </select>
                        @error('representative.relationship')<small class="bd-client-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="bd-client-field">
                        <span>Email</span>
                        <input wire:model.blur="representative.email" wire:blur="save" name="representative[email]" type="email" autocomplete="email">
                        @error('representative.email')<small class="bd-client-field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>

            <div class="bd-client-form-subsection">
                <div class="bd-client-form-subsection__heading">
                    <div>
                        <h3>Representative tambahan</h3>
                        <p>Isi seluruh data jika representative tambahan digunakan.</p>
                    </div>
                    <span class="bd-client-form-section__optional">Opsional</span>
                </div>
                <div class="bd-client-form-grid bd-client-form-grid--three">
                    <label class="bd-client-field">
                        <span>Nama</span>
                        <input wire:model.blur="additionalRepresentative.name" wire:blur="save" name="additional_representative[name]">
                        @error('additionalRepresentative.name')<small class="bd-client-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="bd-client-field">
                        <span>Hubungan</span>
                        <select wire:model.blur="additionalRepresentative.relationship" wire:blur="save" name="additional_representative[relationship]"><option value="">Pilih bila ada</option>@foreach(['OWNER','DIRECTOR','MANAGEMENT','EMPLOYEE','AUTHORIZED_REPRESENTATIVE','OTHER'] as $relationship)<option value="{{ $relationship }}">{{ str_replace('_', ' ', $relationship) }}</option>@endforeach</select>
                        @error('additionalRepresentative.relationship')<small class="bd-client-field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="bd-client-field">
                        <span>Email</span>
                        <input wire:model.blur="additionalRepresentative.email" wire:blur="save" name="additional_representative[email]" type="email" autocomplete="email">
                        @error('additionalRepresentative.email')<small class="bd-client-field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </div>
        @endif
    </div>

    <div class="bd-client-form-card__footer">
        <span>Data tetap tersimpan pada draft aplikasi Anda.</span>
        <button type="submit" class="bd-client-button bd-client-button--secondary">Simpan sekarang</button>
    </div>
</form>
