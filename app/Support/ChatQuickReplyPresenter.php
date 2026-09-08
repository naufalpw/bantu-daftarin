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
                'general-information' => 'Terima kasih sudah menghubungi kami. Ceritakan kendala yang Anda alami.',
            ];
        }

        return [
            'documents-incomplete' => 'Dokumen Anda belum lengkap. Periksa kembali dokumen yang masih diperlukan pada pengajuan.',
            'reupload-document' => 'Dokumen perlu diunggah ulang. Anda dapat menggantinya dari bagian Dokumen pada pengajuan.',
            'documents-under-review' => 'Dokumen Anda sudah kami terima dan sedang diperiksa.',
            'payment-received' => 'Pembayaran pengajuan Anda sudah diterima. Perkembangan berikutnya dapat dilihat di ruang pengajuan.',
            'process-ongoing' => 'Pengajuan Anda masih diproses. Perkembangan terbaru dapat dilihat di ruang pengajuan.',
            'result-available' => 'Hasil pengajuan Anda sudah tersedia. Buka ruang pengajuan untuk melihat hasil yang telah diverifikasi.',
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
