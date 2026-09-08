@props([
    'application',
    'requirement',
    'activeDocument' => null,
    'canUpload' => false,
    'canAccessFile' => false,
    'documentStatus' => ['Belum diunggah', 'neutral'],
])

@php
    $id = 'personal-face-'.$requirement->public_id;
    $extensions = collect($requirement->allowed_extensions ?? [])->map(fn ($extension) => '.'.strtolower($extension))->implode(',');
    $maxMegabytes = $requirement->max_size_bytes ? (int) round($requirement->max_size_bytes / 1024 / 1024) : null;
@endphp

<section class="pb-personal-face" aria-labelledby="{{ $id }}-title" data-face-upload>
    <div class="pb-personal-face__header">
        <div class="pb-personal-face__identity">
            <span class="pb-personal-face__camera"><img src="{{ asset('images/figma/registration/personal/face-camera.svg') }}" alt=""></span>
            <div><h3 id="{{ $id }}-title">Foto Wajah</h3><p>Pastikan wajah terlihat jelas sebelum mengambil atau mengunggah foto.</p></div>
        </div>
        <span class="pb-status pb-document-status pb-status--{{ $documentStatus[1] }}">{{ $documentStatus[0] }}</span>
    </div>
    <div class="pb-personal-face__body">
        <div class="pb-personal-face__visual">
            <img data-face-guide src="{{ asset('images/figma/registration/personal/face-guide.png') }}" alt="Contoh posisi foto wajah">
            <video data-face-preview playsinline hidden></video>
        </div>
        <div class="pb-personal-face__content">
            <div><h4>Tips foto</h4><ul class="pb-personal-face__tips"><li>Pastikan wajah terlihat jelas</li><li>Gunakan pencahayaan yang cukup</li><li>Hindari ruangan yang gelap</li></ul></div>
            @if($activeDocument)
                <p class="pb-personal-face__file">Foto wajah tersedia &middot; Versi {{ $activeDocument->version_number }}</p>
                @if($activeDocument->rejection_reason || $activeDocument->revision_instruction)
                    <div class="pb-personal-document-card__revision" role="note"><strong>Perlu diperbaiki</strong>@if($activeDocument->rejection_reason)<p>Alasan: {{ $activeDocument->rejection_reason }}</p>@endif @if($activeDocument->revision_instruction)<p>Instruksi: {{ $activeDocument->revision_instruction }}</p>@endif</div>
                @endif
            @endif
            @if($canUpload)
                <form method="post" enctype="multipart/form-data" action="{{ route('client.documents.store', [$application->public_id, $requirement->public_id]) }}" class="pb-personal-face__form" data-face-form>
                    @csrf
                    <input class="pb-visually-hidden-file" type="file" accept="image/jpeg,image/png" data-face-camera-input>
                    <input id="{{ $id }}-file" class="pb-visually-hidden-file" type="file" name="file" required accept="{{ $extensions }}" data-face-file-input data-file-name-target="{{ $id }}-name">
                    <div class="pb-personal-face__buttons">
                        <button class="pb-button pb-button--primary" type="button" data-face-start><img src="{{ asset('images/figma/registration/personal/camera-button.svg') }}" alt="">Ambil foto wajah</button>
                        <label class="pb-button pb-button--secondary" for="{{ $id }}-file">Unggah foto</label>
                    </div>
                    <button class="pb-button pb-button--primary pb-personal-face__capture" type="button" hidden data-face-capture>Gunakan foto ini</button>
                    <p id="{{ $id }}-name" class="pb-personal-upload-form__filename" aria-live="polite">{{ $activeDocument ? 'Pilih file untuk mengganti foto.' : 'Belum ada file dipilih.' }}</p>
                </form>
            @endif
            @if($canAccessFile)
                <div class="pb-file-actions pb-personal-face__actions"><a href="{{ route('client.documents.view', $activeDocument->public_id) }}" target="_blank" rel="noopener">Lihat</a><a href="{{ route('client.documents.download', $activeDocument->public_id) }}">Unduh</a></div>
            @endif
            <p class="pb-personal-face__privacy">
                Dokumen hanya dapat diakses sesuai hak akses pengajuan.
                @if($maxMegabytes)
                    Maks. {{ $maxMegabytes }} MB.
                @endif
            </p>
            <p class="pb-personal-face__camera-message" aria-live="polite" data-face-message></p>
        </div>
    </div>
</section>
