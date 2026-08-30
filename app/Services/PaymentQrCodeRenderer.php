<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use chillerlan\QRCode\QRCode;

class PaymentQrCodeRenderer
{
    public function render(?Payment $payment): ?string
    {
        if (! $payment
            || $payment->payment_method !== PaymentMethod::QRIS
            || $payment->status !== PaymentStatus::PENDING
            || ($payment->expires_at?->isPast() ?? false)) {
            return null;
        }

        $qrString = $payment->qrString();
        if (blank($qrString)) {
            return null;
        }

        try {
            return (new QRCode)->render($qrString);
        } catch (\Throwable $exception) {
            logger()->warning('payment_qr_render_failed', [
                'exception_class' => $exception::class,
            ]);

            return null;
        }
    }
}
