<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use App\Support\ApplicationStatusPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApplicationStatusPresenterTest extends TestCase
{
    #[DataProvider('statusPresentations')]
    public function test_every_backend_status_has_one_user_facing_presentation(
        ApplicationStatus $status,
        string $label,
        int $stage,
        ?string $cta,
    ): void {
        $presentation = ApplicationStatusPresenter::forStatus($status);

        $this->assertSame($label, $presentation['label']);
        $this->assertSame($stage, $presentation['stage']);
        $this->assertSame($cta, $presentation['cta_label']);
        $this->assertNotSame('', $presentation['description']);
        $this->assertNotSame('', $presentation['next_action']);
    }

    public static function statusPresentations(): array
    {
        return [
            'draft' => [ApplicationStatus::DRAFT, 'Pengajuan belum dikirim', 1, 'Lanjutkan pengajuan'],
            'awaiting documents' => [ApplicationStatus::AWAITING_DOCUMENTS, 'Lengkapi dokumen', 1, 'Unggah dokumen'],
            'ready for payment' => [ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT, 'Dokumen siap untuk pembayaran', 2, 'Lanjut ke pembayaran'],
            'awaiting payment' => [ApplicationStatus::AWAITING_PAYMENT, 'Menunggu pembayaran', 2, 'Lihat pembayaran'],
            'payment confirmed' => [ApplicationStatus::PAYMENT_CONFIRMED, 'Pembayaran diterima', 2, 'Kirim untuk diperiksa'],
            'documents submitted' => [ApplicationStatus::DOCUMENTS_SUBMITTED, 'Dokumen telah dikirim', 3, null],
            'under review' => [ApplicationStatus::UNDER_REVIEW, 'Dokumen sedang diperiksa', 3, null],
            'revision required' => [ApplicationStatus::REVISION_REQUIRED, 'Dokumen perlu diperbaiki', 3, 'Perbaiki dokumen'],
            'revision submitted' => [ApplicationStatus::REVISION_SUBMITTED, 'Perbaikan telah dikirim', 3, null],
            'documents accepted' => [ApplicationStatus::DOCUMENTS_ACCEPTED, 'Dokumen diterima', 3, null],
            'estimate pending' => [ApplicationStatus::ESTIMATE_PENDING, 'Jadwal proses sedang disiapkan', 4, null],
            'in progress' => [ApplicationStatus::IN_PROGRESS, 'Pengajuan sedang diproses', 4, null],
            'waiting external' => [ApplicationStatus::WAITING_EXTERNAL_PROCESS, 'Menunggu proses instansi', 4, null],
            'result uploaded' => [ApplicationStatus::RESULT_UPLOADED, 'Hasil sedang disiapkan', 5, null],
            'result review' => [ApplicationStatus::RESULT_REVIEW, 'Hasil sedang diverifikasi', 5, null],
            'completed' => [ApplicationStatus::COMPLETED, 'Pengajuan selesai', 6, 'Lihat hasil'],
            'archived' => [ApplicationStatus::ARCHIVED, 'Pengajuan diarsipkan', 6, 'Buka arsip'],
        ];
    }
}
