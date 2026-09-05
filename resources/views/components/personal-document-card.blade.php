@props([
    'application',
    'requirement',
    'activeDocument' => null,
    'canUpload' => false,
    'canAccessFile' => false,
    'documentStatus' => ['Belum diunggah', 'neutral'],
    'icon',
    'title',
    'description',
    'actionLabel',
])

@php
    $inputId = 'personal-document-'.$requirement->public_id;
    $extensions = collect($requirement->allowed_extensions ?? [])->map(fn ($extension) => '.'.strtolower($extension))->implode(',');
    $formats = collect($requirement->allowed_extensions ?? [])->map(fn ($extension) => strtoupper($extension))->implode(', ');
    $maxMegabytes = $requirement->max_size_bytes ? (int) round($requirement->max_size_bytes / 1024 / 1024) : null;
@endphp

<article class="pb-personal-document-card{{ $activeDocument ? ' pb-personal-document-card--uploaded' : '' }}" data-requirement-code="{{ $requirement->code }}">
    <div class="pb-personal-document-card__top">
        <div class="pb-personal-document-card__icon"><img src="{{ asset($icon) }}" alt=""></div>
        <span class="pb-status pb-document-status pb-status--{{ $documentStatus[1] }}">{{ $documentStatus[0] }}</span>
    </div>
    <div class="pb-personal-document-card__heading">
        <h3>{{ $title }}</h3>
        <span class="pb-personal-document-card__requirement">{{ $requirement->is_required ? 'Wajib' : 'Opsional' }}</span>
    </div>
    <p class="pb-personal-document-card__description">{{ $description }}</p>

    @if($activeDocument)
        <p class="pb-personal-document-card__file">Versi {{ $activeDocument->version_number }} &middot; {{ $activeDocument->review_status->label() }}</p>
        @if($activeDocument->rejection_reason || $activeDocument->revision_instruction)
            <div class="pb-personal-document-card__revision" role="note">
                <strong>Perlu diperbaiki</strong>
                @if($activeDocument->rejection_reason)<p>Alasan: {{ $activeDocument->rejection_reason }}</p>@endif
                @if($activeDocument->revision_instruction)<p>Instruksi: {{ $activeDocument->revision_instruction }}</p>@endif
            </div>
        @endif
    @endif

    <div class="pb-personal-document-card__footer">
        @if($canUpload)
            <form method="post" enctype="multipart/form-data" action="{{ route('client.documents.store', [$application->public_id, $requirement->public_id]) }}" class="pb-personal-upload-form">
                @csrf
                <input id="{{ $inputId }}" class="pb-visually-hidden-file" type="file" name="file" required accept="{{ $extensions }}" data-file-input data-auto-submit data-file-name-target="{{ $inputId }}-name">
                <label class="pb-button pb-button--primary" for="{{ $inputId }}">{{ $activeDocument ? 'Ganti dokumen' : $actionLabel }}</label>
                <span id="{{ $inputId }}-name" class="pb-personal-upload-form__filename" aria-live="polite">{{ $activeDocument ? 'Pilih file untuk mengganti dokumen.' : 'Belum ada file dipilih.' }}</span>
            </form>
        @endif

        @if($canAccessFile)
            <div class="pb-file-actions pb-personal-document-card__actions">
                <a href="{{ route('client.documents.view', $activeDocument->public_id) }}" target="_blank" rel="noopener">Lihat</a>
                <a href="{{ route('client.documents.download', $activeDocument->public_id) }}">Unduh</a>
                @if($canUpload)
                    <form method="post" action="{{ route('client.documents.destroy', $activeDocument->public_id) }}" data-confirm="Hapus versi aktif dokumen ini?">
                        @csrf @method('DELETE')
                        <button type="submit">Hapus</button>
                    </form>
                @endif
            </div>
        @endif
        <p class="pb-personal-document-card__meta">
            {{ $formats }}
            @if($maxMegabytes)
                &middot; Maks. {{ $maxMegabytes }} MB
            @endif
        </p>
    </div>
</article>
