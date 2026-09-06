<?php

namespace App\Support;

use App\Enums\ApplicationStatus;

final class AdminApplicationPresenter
{
    /** @return array<string, string> */
    public static function filters(): array
    {
        return [
            'all' => 'Semua',
            'review' => 'Perlu ditinjau',
            'revision' => 'Revisi masuk',
            'accepted' => 'Dokumen diterima',
            'processing' => 'Sedang diproses',
            'result' => 'Hasil perlu review',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /** @return list<string> */
    public static function statusesFor(string $filter): array
    {
        return match ($filter) {
            'review' => [ApplicationStatus::DOCUMENTS_SUBMITTED->value, ApplicationStatus::UNDER_REVIEW->value],
            'revision' => [ApplicationStatus::REVISION_SUBMITTED->value],
            'accepted' => [ApplicationStatus::DOCUMENTS_ACCEPTED->value, ApplicationStatus::ESTIMATE_PENDING->value],
            'processing' => [ApplicationStatus::IN_PROGRESS->value, ApplicationStatus::WAITING_EXTERNAL_PROCESS->value, ApplicationStatus::RESULT_UPLOADED->value],
            'result' => [ApplicationStatus::RESULT_REVIEW->value],
            'completed' => [ApplicationStatus::COMPLETED->value, ApplicationStatus::ARCHIVED->value],
            'cancelled' => [ApplicationStatus::CANCELLED->value],
            default => [],
        };
    }

    public static function queuePriority(ApplicationStatus $status): int
    {
        return match ($status) {
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::REVISION_SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
            ApplicationStatus::RESULT_REVIEW => 1,
            ApplicationStatus::DOCUMENTS_ACCEPTED,
            ApplicationStatus::ESTIMATE_PENDING,
            ApplicationStatus::IN_PROGRESS,
            ApplicationStatus::WAITING_EXTERNAL_PROCESS,
            ApplicationStatus::RESULT_UPLOADED => 2,
            ApplicationStatus::COMPLETED,
            ApplicationStatus::ARCHIVED => 4,
            ApplicationStatus::CANCELLED => 5,
            default => 3,
        };
    }

    /** @return array{label: string, description: string} */
    public static function nextAction(ApplicationStatus $status): array
    {
        return match ($status) {
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::REVISION_SUBMITTED => ['label' => 'Mulai pemeriksaan', 'description' => 'Dokumen siap diperiksa.'],
            ApplicationStatus::UNDER_REVIEW => ['label' => 'Tinjau dokumen', 'description' => 'Selesaikan keputusan pada dokumen aktif.'],
            ApplicationStatus::DOCUMENTS_ACCEPTED,
            ApplicationStatus::ESTIMATE_PENDING => ['label' => 'Tetapkan estimasi', 'description' => 'Dokumen telah diterima.'],
            ApplicationStatus::IN_PROGRESS => ['label' => 'Perbarui proses', 'description' => 'Tandai saat proses berlanjut ke instansi.'],
            ApplicationStatus::WAITING_EXTERNAL_PROCESS => ['label' => 'Unggah hasil', 'description' => 'Hasil dapat diunggah saat tersedia.'],
            ApplicationStatus::RESULT_UPLOADED => ['label' => 'Mulai review hasil', 'description' => 'Hasil menunggu pemeriksaan.'],
            ApplicationStatus::RESULT_REVIEW => ['label' => 'Verifikasi hasil', 'description' => 'Pastikan hasil utama telah diverifikasi.'],
            ApplicationStatus::COMPLETED => ['label' => 'Arsipkan bila perlu', 'description' => 'Hasil terverifikasi tersedia untuk klien.'],
            ApplicationStatus::ARCHIVED => ['label' => 'Riwayat baca-saja', 'description' => 'Pengajuan telah diarsipkan.'],
            ApplicationStatus::CANCELLED => ['label' => 'Tidak ada tindakan', 'description' => 'Pengajuan dibatalkan oleh klien dan tetap tersimpan sebagai riwayat.'],
            default => ['label' => 'Pantau pengajuan', 'description' => 'Belum ada tindakan admin pada tahap ini.'],
        };
    }
}
