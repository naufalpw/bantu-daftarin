@if($kind === 'NPWP_PERSONAL')
    <form wire:submit="save" class="bd-registration-panel bd-personal-data-panel" data-node-id="208:16663">
        <div class="bd-registration-section-heading" data-node-id="208:16664">
            <div class="bd-registration-section-icon bd-registration-section-icon--blue">
                <img src="{{ asset('images/figma/registration/personal/personal-header.svg') }}" alt="">
            </div>
            <h2>Masukan Data Diri</h2>
        </div>

        <div class="bd-personal-data-grid" data-node-id="208:16671">
            <div class="bd-registration-field-column">
                <label class="bd-registration-field">
                    <span>Nama</span>
                    <input wire:model.blur="details.name" wire:blur="save" name="name" required placeholder="Masukan  nama Lengkap" autocomplete="name">
                    @error('details.name')<small>{{ $message }}</small>@enderror
                </label>
                <label class="bd-registration-field">
                    <span>Keperluan pwp</span>
                    <input wire:model.blur="details.purpose" wire:blur="save" name="purpose" placeholder="Masukan Keperluan">
                </label>
                <label class="bd-registration-field">
                    <span>Email Pengguna</span>
                    <input wire:model.blur="details.email" wire:blur="save" name="email" type="email" placeholder="Masukan Email" autocomplete="email">
                    @error('details.email')<small>{{ $message }}</small>@enderror
                </label>
                <fieldset class="bd-registration-field bd-registration-gender">
                    <legend>Jenis Kelamin</legend>
                    <div>
                        <label><input wire:model="details.gender" wire:change="save" type="radio" name="gender" value="Laki-Laki"> <span>Laki-Laki</span></label>
                        <label><input wire:model="details.gender" wire:change="save" type="radio" name="gender" value="Wanita"> <span>Wanita</span></label>
                    </div>
                </fieldset>
            </div>

            <div class="bd-registration-field-column bd-registration-field-column--right">
                <label class="bd-registration-field">
                    <span>Masukan Nomor Kartu Keluarga</span>
                    <input wire:model.blur="details.family_card_number" wire:blur="save" name="family_card_number" inputmode="numeric" maxlength="16" placeholder="Masukan KK" autocomplete="off">
                    @error('details.family_card_number')<small>{{ $message }}</small>@enderror
                </label>
                <label class="bd-registration-field">
                    <span>Masukan NIK</span>
                    <input wire:model.blur="details.nik" wire:blur="save" name="nik" inputmode="numeric" maxlength="16" placeholder="Masukan NIK" autocomplete="off">
                    @error('details.nik')<small>{{ $message }}</small>@enderror
                </label>
                <label class="bd-registration-field">
                    <span>Status Pernikahan</span>
                    <select wire:model.blur="details.marital_status" wire:change="save" name="marital_status">
                        <option value="">Pilih Status</option>
                        <option value="Belum Menikah">Belum Menikah</option>
                        <option value="Menikah">Menikah</option>
                        <option value="Cerai Hidup">Cerai Hidup</option>
                        <option value="Cerai Mati">Cerai Mati</option>
                    </select>
                </label>
            </div>
        </div>

        @if($saveState)<p class="bd-registration-save-state">{{ $saveState }}</p>@endif
        <button type="submit" class="bd-registration-save-button">Simpan data</button>
    </form>
@else
    <form wire:submit="save" class="bd-registration-panel bd-business-data-panel" data-node-id="208:20526">
        <div class="bd-registration-section-heading" data-node-id="208:20527">
            <div class="bd-registration-section-icon bd-registration-section-icon--blue">
                <img src="{{ asset('images/figma/registration/business/business-header.svg') }}" alt="">
            </div>
            <h2>Data Kuasa</h2>
        </div>

        <div class="bd-business-data-grid" data-node-id="208:20534">
            <div class="bd-registration-field-column">
                <label class="bd-registration-field">
                    <span>Nama 1</span>
                    <input wire:model.blur="representative.name" wire:blur="save" name="representative_name" required placeholder="Masukan  nama Lengkap" autocomplete="name">
                    @error('representative.name')<small>{{ $message }}</small>@enderror
                </label>
                <label class="bd-registration-field">
                    <span>Nama 2</span>
                    <input wire:model.blur="additionalRepresentative.name" wire:blur="save" name="additional_representative_name" placeholder="Masukan  nama Lengkap" autocomplete="off">
                    @error('additionalRepresentative.name')<small>{{ $message }}</small>@enderror
                </label>
                <label class="bd-registration-field">
                    <span>Email Pengguna</span>
                    <input wire:model.blur="representative.email" wire:blur="save" name="representative_email" type="email" placeholder="Masukan Email" autocomplete="email">
                    @error('representative.email')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <div class="bd-registration-field-column bd-registration-field-column--right">
                <div class="bd-registration-static-field">
                    <span>Masukan Akta notaris</span>
                    <div>Masukan Akta Notaris <img class="bd-registration-static-field__caret" src="{{ asset('images/figma/registration/business/business-caret-akta.svg') }}" alt=""></div>
                </div>
                <div class="bd-registration-static-field">
                    <span>Masukan SK_AHU</span>
                    <div>Masukan&nbsp; SK <img class="bd-registration-static-field__caret" src="{{ asset('images/figma/registration/business/business-caret-sk.svg') }}" alt=""></div>
                </div>
            </div>
        </div>

        @if($saveState)<p class="bd-registration-save-state">{{ $saveState }}</p>@endif
        <button type="submit" class="bd-registration-save-button">Simpan data</button>
    </form>
@endif
