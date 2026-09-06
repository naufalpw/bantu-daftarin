<?php

namespace App\Support;

use App\Models\ChatThread;

final class ChatQuickReplyPresenter
{
    /** @return array<string, string> */
    public static function forThread(ChatThread $thread): array
    {
        if ($thread->isGeneralSupport()) {
            return [
                'general-information' => 'Terima kasih telah menghubungi Tim Bantu Daftarin. Mohon jelaskan kendala yang Anda alami agar kami dapat membantu.',
            ];
        }

        return [
            'documents-incomplete' => 'Dokumen pada pengajuan Anda belum lengkap. Silakan periksa kembali dokumen yang diperlukan pada halaman pengajuan.',
            'reupload-document' => 'Silakan unggah ulang dokumen melalui bagian Dokumen pada halaman pengajuan agar dapat kami periksa kembali.',
            'documents-under-review' => 'Dokumen Anda sudah kami terima dan sedang dalam proses pemeriksaan.',
            'payment-received' => 'Pembayaran untuk pengajuan Anda telah diterima. Silakan pantau perkembangan selanjutnya melalui halaman pengajuan.',
            'process-ongoing' => 'Pengajuan Anda masih dalam proses. Perkembangan terbaru dapat dipantau melalui halaman pengajuan.',
            'result-available' => 'Hasil pengajuan Anda sudah tersedia. Silakan buka halaman pengajuan untuk melihat hasil yang telah diverifikasi.',
        ];
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'general-information' => 'Informasi bantuan umum',
            'documents-incomplete' => 'Dokumen belum lengkap',
            'reupload-document' => 'Unggah ulang dokumen',
            'documents-under-review' => 'Dokumen sedang diperiksa',
            'payment-received' => 'Pembayaran diterima',
            'process-ongoing' => 'Proses masih berlangsung',
            'result-available' => 'Hasil tersedia',
        ];
    }
}
