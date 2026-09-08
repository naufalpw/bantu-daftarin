@props(['status' => null, 'label' => null, 'tone' => null])
@php
    $value = $status instanceof \App\Enums\ApplicationStatus ? $status->value : (string) $status;
    $label = $label ?? ($status instanceof \App\Enums\ApplicationStatus ? $status->label() : $value);
    $tone = $tone ?? match ($value) {
        'DOCUMENTS_SUBMITTED', 'REVISION_SUBMITTED', 'RESULT_REVIEW' => 'attention',
        'UNDER_REVIEW', 'IN_PROGRESS', 'WAITING_EXTERNAL_PROCESS' => 'info',
        'DOCUMENTS_ACCEPTED', 'COMPLETED' => 'success',
        'REVISION_REQUIRED', 'CANCELLED' => 'danger',
        default => 'neutral',
    };
@endphp
<span {{ $attributes->class(['bd-admin-status', 'bd-admin-status--'.$tone]) }}>{{ $label }}</span>
