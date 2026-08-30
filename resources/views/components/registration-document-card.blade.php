@props([
    'application',
    'requirement',
    'icon',
    'title',
    'description',
    'buttonLabel',
    'maxLabel' => 'JPG, PNG (Max. 5mb)',
    'elementId',
    'iconWrapped' => false,
])

@php($activeDocument = $requirement?->documents?->firstWhere('active', true))

<article class="bd-registration-document-card" data-requirement-code="{{ $requirement?->code }}">
    <div class="bd-registration-document-card__icon{{ $iconWrapped ? ' bd-registration-document-card__icon--wrapped' : '' }}">
        <img src="{{ asset($icon) }}" alt="">
    </div>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>

    @if($requirement)
        <form method="post" enctype="multipart/form-data" action="{{ route('client.documents.store', [$application->public_id, $requirement->public_id]) }}">
            @csrf
            <input id="{{ $elementId }}" type="file" name="file" accept=".jpg,.jpeg,.png,.pdf" required hidden onchange="this.form.submit()">
            <label class="bd-registration-upload-button" for="{{ $elementId }}">
                <img src="{{ asset('images/figma/registration/personal/upload.svg') }}" alt="">
                <span>{{ $buttonLabel }}</span>
            </label>
        </form>
    @else
        <span class="bd-registration-upload-button bd-registration-upload-button--disabled">Belum tersedia</span>
    @endif

    <small>{{ $maxLabel }}</small>
    @if($activeDocument)
        <div class="bd-registration-document-status">
            <span>Dokumen tersimpan</span>
            <a href="{{ route('client.documents.view', $activeDocument->public_id) }}" target="_blank" rel="noopener">Lihat</a>
        </div>
    @endif
</article>
