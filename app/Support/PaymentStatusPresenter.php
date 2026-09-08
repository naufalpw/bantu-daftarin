<?php

namespace App\Support;

use App\Enums\PaymentStatus;

final class PaymentStatusPresenter
{
    /** @return array{label: string, tone: string} */
    public static function for(?PaymentStatus $status, bool $expiredPending = false): array
    {
        if ($expiredPending && $status === PaymentStatus::PENDING) {
            return ['label' => 'Pembayaran kedaluwarsa', 'tone' => 'danger'];
        }

        return match ($status) {
            PaymentStatus::PENDING => ['label' => 'Menunggu pembayaran', 'tone' => 'action'],
            PaymentStatus::PAID => ['label' => 'Pembayaran berhasil', 'tone' => 'success'],
            PaymentStatus::FAILED => ['label' => 'Pembayaran gagal', 'tone' => 'danger'],
            PaymentStatus::EXPIRED => ['label' => 'Pembayaran kedaluwarsa', 'tone' => 'danger'],
            PaymentStatus::CANCELLED => ['label' => 'Pembayaran dibatalkan', 'tone' => 'neutral'],
            PaymentStatus::REFUND_REQUESTED => ['label' => 'Pengembalian diajukan', 'tone' => 'action'],
            PaymentStatus::REFUNDING => ['label' => 'Pengembalian diproses', 'tone' => 'waiting'],
            PaymentStatus::REFUNDED => ['label' => 'Dana telah dikembalikan', 'tone' => 'success'],
            default => ['label' => 'Belum ada pembayaran', 'tone' => 'neutral'],
        };
    }
}
