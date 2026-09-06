<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Models\Application;

final class ApplicationStatusPresenter
{
    public const CATEGORY_ACTION = 'action';

    public const CATEGORY_PROCESSING = 'processing';

    public const CATEGORY_COMPLETED = 'completed';

    public const CATEGORY_CANCELLED = 'cancelled';

    public static function category(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::DRAFT,
            ApplicationStatus::AWAITING_DOCUMENTS,
            ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
            ApplicationStatus::AWAITING_PAYMENT,
            ApplicationStatus::PAYMENT_CONFIRMED,
            ApplicationStatus::REVISION_REQUIRED => self::CATEGORY_ACTION,
            ApplicationStatus::COMPLETED,
            ApplicationStatus::ARCHIVED => self::CATEGORY_COMPLETED,
            ApplicationStatus::CANCELLED => self::CATEGORY_CANCELLED,
            default => self::CATEGORY_PROCESSING,
        };
    }

    /**
     * @return array{label: string, description: string, tone: string, next_action: string, cta_label: ?string, cta_url: ?string, cta_method: string, stage: int, stage_label: string, section: string}
     */
    public static function for(Application $application): array
    {
        $presentation = self::forStatus($application->status);
        $section = $presentation['section'];

        $presentation['cta_url'] = match ($application->status) {
            ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
            ApplicationStatus::AWAITING_PAYMENT => route('client.payments.show', $application->public_id),
            default => route('client.applications.show', $application->public_id).'#'.$section,
        };

        if ($presentation['cta_label'] === null) {
            $presentation['cta_url'] = null;
        }

        return $presentation;
    }

    /**
     * @return array{label: string, description: string, tone: string, next_action: string, cta_label: ?string, cta_method: string, stage: int, stage_label: string, section: string}
     */
    public static function forStatus(ApplicationStatus $status): array
    {
        return match ($status) {
            ApplicationStatus::DRAFT => self::make('Pengajuan belum dikirim', 'Data pengajuan masih dapat dilengkapi.', 'neutral', 'Periksa dan lengkapi data pengajuan.', 'Lanjutkan pengajuan', 'get', 1, 'Data & dokumen', 'data-dokumen'),
            ApplicationStatus::AWAITING_DOCUMENTS => self::make('Lengkapi dokumen', 'Dokumen wajib belum lengkap.', 'action', 'Unggah dokumen yang belum tersedia.', 'Unggah dokumen', 'get', 1, 'Data & dokumen', 'data-dokumen'),
            ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT => self::make('Dokumen siap untuk pembayaran', 'Dokumen minimum telah lengkap.', 'action', 'Pilih metode pembayaran.', 'Lanjut ke pembayaran', 'get', 2, 'Pembayaran', 'pembayaran'),
            ApplicationStatus::AWAITING_PAYMENT => self::make('Menunggu pembayaran', 'Pembayaran belum diterima.', 'action', 'Selesaikan instruksi pembayaran yang aktif.', 'Lihat pembayaran', 'get', 2, 'Pembayaran', 'pembayaran'),
            ApplicationStatus::PAYMENT_CONFIRMED => self::make('Pembayaran diterima', 'Dokumen siap dikirim kepada tim.', 'success', 'Kirim dokumen untuk mulai diperiksa.', 'Kirim untuk diperiksa', 'post', 2, 'Pembayaran', 'pembayaran'),
            ApplicationStatus::DOCUMENTS_SUBMITTED => self::make('Dokumen telah dikirim', 'Pengajuan menunggu pemeriksaan dimulai.', 'waiting', 'Tidak ada tindakan yang diperlukan saat ini.', null, 'get', 3, 'Pemeriksaan', 'proses'),
            ApplicationStatus::UNDER_REVIEW => self::make('Dokumen sedang diperiksa', 'Tim sedang memeriksa kelengkapan dokumen.', 'waiting', 'Tunggu hasil pemeriksaan.', null, 'get', 3, 'Pemeriksaan', 'proses'),
            ApplicationStatus::REVISION_REQUIRED => self::make('Dokumen perlu diperbaiki', 'Satu atau beberapa dokumen memerlukan perbaikan.', 'danger', 'Baca instruksi dan unggah versi baru.', 'Perbaiki dokumen', 'get', 3, 'Pemeriksaan', 'data-dokumen'),
            ApplicationStatus::REVISION_SUBMITTED => self::make('Perbaikan telah dikirim', 'Dokumen pengganti menunggu pemeriksaan ulang.', 'waiting', 'Tunggu pemeriksaan ulang.', null, 'get', 3, 'Pemeriksaan', 'proses'),
            ApplicationStatus::DOCUMENTS_ACCEPTED => self::make('Dokumen diterima', 'Semua dokumen telah disetujui.', 'success', 'Tunggu estimasi proses dari tim.', null, 'get', 3, 'Pemeriksaan', 'proses'),
            ApplicationStatus::ESTIMATE_PENDING => self::make('Jadwal proses sedang disiapkan', 'Tim sedang menentukan estimasi proses.', 'waiting', 'Tunggu pembaruan jadwal.', null, 'get', 4, 'Proses eksternal', 'proses'),
            ApplicationStatus::IN_PROGRESS => self::make('Pengajuan sedang diproses', 'Proses administrasi sedang berjalan.', 'info', 'Pantau proses dan estimasi yang tersedia.', null, 'get', 4, 'Proses eksternal', 'proses'),
            ApplicationStatus::WAITING_EXTERNAL_PROCESS => self::make('Menunggu proses instansi', 'Proses dilanjutkan secara manual di luar aplikasi.', 'waiting', 'Tunggu pembaruan hasil dari tim.', null, 'get', 4, 'Proses eksternal', 'proses'),
            ApplicationStatus::RESULT_UPLOADED => self::make('Hasil sedang disiapkan', 'Dokumen hasil telah diterima tim dan belum dapat dibuka.', 'waiting', 'Tunggu verifikasi hasil.', null, 'get', 5, 'Hasil', 'hasil'),
            ApplicationStatus::RESULT_REVIEW => self::make('Hasil sedang diverifikasi', 'Tim sedang memastikan hasil sebelum tersedia.', 'waiting', 'Tunggu verifikasi hasil.', null, 'get', 5, 'Hasil', 'hasil'),
            ApplicationStatus::COMPLETED => self::make('Pengajuan selesai', 'Hasil terverifikasi tersedia di ruang pengajuan.', 'success', 'Lihat atau unduh hasil terverifikasi.', 'Lihat hasil', 'get', 6, 'Selesai', 'hasil'),
            ApplicationStatus::ARCHIVED => self::make('Pengajuan diarsipkan', 'Pengajuan disimpan sebagai riwayat baca-saja.', 'neutral', 'Buka kembali informasi pengajuan bila diperlukan.', 'Buka arsip', 'get', 6, 'Selesai', 'ringkasan'),
            ApplicationStatus::CANCELLED => self::make('Dibatalkan', 'Pengajuan ini telah dibatalkan dan tidak akan diproses lebih lanjut.', 'danger', 'Tidak ada tindakan lanjutan untuk pengajuan ini.', null, 'get', 1, 'Dibatalkan', 'ringkasan'),
        };
    }

    /**
     * @return array{label: string, description: string, tone: string, next_action: string, cta_label: ?string, cta_method: string, stage: int, stage_label: string, section: string}
     */
    private static function make(string $label, string $description, string $tone, string $nextAction, ?string $ctaLabel, string $ctaMethod, int $stage, string $stageLabel, string $section): array
    {
        return [
            'label' => $label,
            'description' => $description,
            'tone' => $tone,
            'next_action' => $nextAction,
            'cta_label' => $ctaLabel,
            'cta_method' => $ctaMethod,
            'stage' => $stage,
            'stage_label' => $stageLabel,
            'section' => $section,
        ];
    }
}
