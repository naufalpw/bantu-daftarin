<form wire:submit="save" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="font-semibold">Data aplikasi</h2>
            <p class="mt-1 text-sm text-slate-500">Perubahan disimpan otomatis saat Anda berpindah dari kolom.</p>
        </div>
        @if($saveState)<span class="text-xs text-emerald-700">{{ $saveState }}</span>@endif
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @if($kind === 'NPWP_PERSONAL')
            <label class="text-sm font-medium md:col-span-2">Nama
                <input wire:model.blur="details.name" wire:blur="save" name="name" required class="mt-1 w-full rounded-lg border-slate-300">
                @error('details.name')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror
            </label>
            <label class="text-sm font-medium">Email
                <input wire:model.blur="details.email" wire:blur="save" name="email" type="email" class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <label class="text-sm font-medium">Jenis kelamin
                <input wire:model.blur="details.gender" wire:blur="save" name="gender" class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <label class="text-sm font-medium">Status pernikahan
                <input wire:model.blur="details.marital_status" wire:blur="save" name="marital_status" class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <label class="text-sm font-medium">Status keluarga
                <input wire:model.blur="details.family_status" wire:blur="save" name="family_status" class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <label class="text-sm font-medium md:col-span-2">Keperluan
                <input wire:model.blur="details.purpose" wire:blur="save" name="purpose" class="mt-1 w-full rounded-lg border-slate-300">
            </label>
        @else
            <label class="text-sm font-medium md:col-span-2">Nama badan usaha
                <input wire:model.blur="details.business_name" wire:blur="save" name="business_name" required class="mt-1 w-full rounded-lg border-slate-300">
                @error('details.business_name')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror
            </label>
            <label class="text-sm font-medium">Jenis badan usaha
                <input wire:model.blur="details.business_type" wire:blur="save" name="business_type" class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <label class="text-sm font-medium">Keperluan
                <input wire:model.blur="details.purpose" wire:blur="save" name="purpose" class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <div class="md:col-span-2 border-t pt-4">
                <h3 class="font-medium">Penanggung jawab utama</h3>
                <div class="mt-3 grid gap-4 md:grid-cols-3">
                    <label class="text-sm font-medium">Nama<input wire:model.blur="representative.name" wire:blur="save" class="mt-1 w-full rounded-lg border-slate-300"></label>
                    <label class="text-sm font-medium">Hubungan<select wire:model.blur="representative.relationship" wire:blur="save" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Pilih hubungan</option>@foreach(['OWNER','DIRECTOR','MANAGEMENT','EMPLOYEE','AUTHORIZED_REPRESENTATIVE','OTHER'] as $relationship)<option value="{{ $relationship }}">{{ str_replace('_', ' ', $relationship) }}</option>@endforeach</select></label>
                    <label class="text-sm font-medium">Email<input wire:model.blur="representative.email" wire:blur="save" type="email" class="mt-1 w-full rounded-lg border-slate-300"></label>
                </div>
            </div>
            <div class="md:col-span-2 border-t pt-4">
                <h3 class="font-medium">Representative tambahan (opsional)</h3>
                <div class="mt-3 grid gap-4 md:grid-cols-3">
                    <label class="text-sm font-medium">Nama<input wire:model.blur="additionalRepresentative.name" wire:blur="save" class="mt-1 w-full rounded-lg border-slate-300"></label>
                    <label class="text-sm font-medium">Hubungan<select wire:model.blur="additionalRepresentative.relationship" wire:blur="save" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Pilih bila ada</option>@foreach(['OWNER','DIRECTOR','MANAGEMENT','EMPLOYEE','AUTHORIZED_REPRESENTATIVE','OTHER'] as $relationship)<option value="{{ $relationship }}">{{ str_replace('_', ' ', $relationship) }}</option>@endforeach</select></label>
                    <label class="text-sm font-medium">Email<input wire:model.blur="additionalRepresentative.email" wire:blur="save" type="email" class="mt-1 w-full rounded-lg border-slate-300"></label>
                </div>
            </div>
        @endif
    </div>
    <button type="submit" class="mt-5 rounded-lg border px-4 py-2 text-sm">Simpan sekarang</button>
</form>
