@php
    $steps = [
        'AWAITING_DOCUMENTS' => 'Data & dokumen',
        'AWAITING_PAYMENT' => 'Pembayaran',
        'UNDER_REVIEW' => 'Pemeriksaan',
        'IN_PROGRESS' => 'Proses eksternal',
        'RESULT_REVIEW' => 'Hasil',
        'COMPLETED' => 'Selesai',
    ];
    $order = array_keys($steps);
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
<ol class="mb-6 grid gap-2 sm:grid-cols-6" aria-label="Progress aplikasi">@foreach($steps as $step => $label)<li class="rounded-lg p-3 text-xs {{ $loop->index <= $currentIndex ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200' }}"><span class="font-semibold">{{ $loop->iteration }}.</span> {{ $label }}@if($current === 'REVISION_REQUIRED' && $loop->index === 2)<span class="block mt-1 text-[11px]">Perlu perbaikan dokumen</span>@endif</li>@endforeach</ol>
