<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case DRAFT = 'DRAFT';
    case AWAITING_DOCUMENTS = 'AWAITING_DOCUMENTS';
    case DOCUMENTS_READY_FOR_PAYMENT = 'DOCUMENTS_READY_FOR_PAYMENT';
    case AWAITING_PAYMENT = 'AWAITING_PAYMENT';
    case PAYMENT_CONFIRMED = 'PAYMENT_CONFIRMED';
    case DOCUMENTS_SUBMITTED = 'DOCUMENTS_SUBMITTED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case DOCUMENTS_ACCEPTED = 'DOCUMENTS_ACCEPTED';
    case ESTIMATE_PENDING = 'ESTIMATE_PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case WAITING_EXTERNAL_PROCESS = 'WAITING_EXTERNAL_PROCESS';
    case RESULT_UPLOADED = 'RESULT_UPLOADED';
    case RESULT_REVIEW = 'RESULT_REVIEW';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED = 'ARCHIVED';
    case REVISION_REQUIRED = 'REVISION_REQUIRED';
    case REVISION_SUBMITTED = 'REVISION_SUBMITTED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::AWAITING_DOCUMENTS => 'Menunggu dokumen',
            self::DOCUMENTS_READY_FOR_PAYMENT => 'Dokumen siap dibayar',
            self::AWAITING_PAYMENT => 'Menunggu pembayaran',
            self::PAYMENT_CONFIRMED => 'Pembayaran terkonfirmasi',
            self::DOCUMENTS_SUBMITTED => 'Dokumen dikirim',
            self::UNDER_REVIEW => 'Sedang diperiksa',
            self::DOCUMENTS_ACCEPTED => 'Dokumen diterima',
            self::ESTIMATE_PENDING => 'Menunggu estimasi',
            self::IN_PROGRESS => 'Sedang diproses',
            self::WAITING_EXTERNAL_PROCESS => 'Menunggu proses eksternal',
            self::RESULT_UPLOADED => 'Hasil tersedia untuk diperiksa',
            self::RESULT_REVIEW => 'Hasil sedang diperiksa',
            self::COMPLETED => 'Selesai',
            self::ARCHIVED => 'Diarsipkan',
            self::REVISION_REQUIRED => 'Perlu perbaikan dokumen',
            self::REVISION_SUBMITTED => 'Perbaikan dikirim',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, match ($this) {
            self::DRAFT => [self::AWAITING_DOCUMENTS],
            self::AWAITING_DOCUMENTS => [self::DOCUMENTS_READY_FOR_PAYMENT],
            self::DOCUMENTS_READY_FOR_PAYMENT => [self::AWAITING_PAYMENT],
            self::AWAITING_PAYMENT => [self::PAYMENT_CONFIRMED],
            self::PAYMENT_CONFIRMED => [self::DOCUMENTS_SUBMITTED],
            self::DOCUMENTS_SUBMITTED => [self::UNDER_REVIEW],
            self::UNDER_REVIEW => [self::DOCUMENTS_ACCEPTED, self::REVISION_REQUIRED],
            self::DOCUMENTS_ACCEPTED => [self::ESTIMATE_PENDING],
            self::ESTIMATE_PENDING => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::WAITING_EXTERNAL_PROCESS],
            self::WAITING_EXTERNAL_PROCESS => [self::RESULT_UPLOADED],
            self::RESULT_UPLOADED => [self::RESULT_REVIEW],
            self::RESULT_REVIEW => [self::COMPLETED],
            self::COMPLETED => [self::ARCHIVED],
            self::REVISION_REQUIRED => [self::REVISION_SUBMITTED],
            self::REVISION_SUBMITTED => [self::UNDER_REVIEW],
            self::ARCHIVED => [],
        }, true);
    }
}
