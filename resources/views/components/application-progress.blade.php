@php
    $compact = $compact ?? false;
    $steps = [
        'AWAITING_DOCUMENTS' => 'Data & dokumen',
        'AWAITING_PAYMENT' => 'Pembayaran',
        'UNDER_REVIEW' => 'Pemeriksaan',
        'IN_PROGRESS' => 'Proses eksternal',
        'RESULT_REVIEW' => 'Hasil',
        'COMPLETED' => 'Selesai',
    ];
    $current = $status->value;
    $currentIndex = match ($current) {
        'DRAFT', 'AWAITING_DOCUMENTS', 'DOCUMENTS_READY_FOR_PAYMENT' => 0,
        'AWAITING_PAYMENT', 'PAYMENT_CONFIRMED', 'DOCUMENTS_SUBMITTED' => 1,
        'UNDER_REVIEW', 'REVISION_REQUIRED', 'REVISION_SUBMITTED', 'DOCUMENTS_ACCEPTED', 'ESTIMATE_PENDING' => 2,
        'IN_PROGRESS', 'WAITING_EXTERNAL_PROCESS' => 3,
        'RESULT_UPLOADED', 'RESULT_REVIEW' => 4,
        'COMPLETED', 'ARCHIVED' => 5,
        default => 0,
    };
@endphp

<nav class="bd-client-progress{{ $compact ? ' bd-client-progress--compact' : '' }}" aria-label="Progress aplikasi">
    <ol>
        @foreach($steps as $step => $label)
            @php($stepState = $loop->index < $currentIndex ? 'complete' : ($loop->index === $currentIndex ? 'current' : 'future'))
            <li class="bd-client-progress__step bd-client-progress__step--{{ $stepState }}" @if($stepState === 'current') aria-current="step" @endif>
                <span class="bd-client-progress__marker">
                    @if($stepState === 'complete')
                        <span aria-hidden="true">✓</span>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>
                <span class="bd-client-progress__label">{{ $label }}</span>
                @if($current === 'REVISION_REQUIRED' && $loop->index === 2)
                    <span class="bd-client-progress__note">Perlu perbaikan dokumen</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
