@php
    $presentation = $presentation ?? \App\Support\ApplicationStatusPresenter::forStatus($status);
    $stages = ['Data & dokumen', 'Pembayaran', 'Pemeriksaan', 'Proses eksternal', 'Hasil', 'Selesai'];
@endphp

<div class="pb-progress" aria-label="Tahap pengajuan">
    <div class="pb-progress__mobile">
        <span>Tahap {{ $presentation['stage'] }} dari 6</span>
        <strong>{{ $presentation['stage_label'] }}</strong>
        <div class="pb-progress__bar" role="progressbar" aria-label="Kemajuan pengajuan" aria-valuemin="1" aria-valuemax="6" aria-valuenow="{{ $presentation['stage'] }}">
            <span style="width: {{ ($presentation['stage'] / 6) * 100 }}%"></span>
        </div>
    </div>
    <ol class="pb-progress__desktop">
        @foreach($stages as $stage)
            @php($state = $loop->iteration < $presentation['stage'] ? 'complete' : ($loop->iteration === $presentation['stage'] ? 'current' : 'future'))
            <li class="is-{{ $state }}" @if($state === 'current') aria-current="step" @endif>
                <span class="pb-progress__marker" aria-hidden="true">{{ $state === 'complete' ? '✓' : $loop->iteration }}</span>
                <span>{{ $stage }}</span>
            </li>
        @endforeach
    </ol>
</div>
