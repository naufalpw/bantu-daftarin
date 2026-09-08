<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Support\PaymentStatusPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaymentStatusPresenterTest extends TestCase
{
    #[DataProvider('paymentPresentations')]
    public function test_every_payment_status_has_one_user_facing_label(PaymentStatus $status, string $label): void
    {
        $presentation = PaymentStatusPresenter::for($status);

        $this->assertSame($label, $presentation['label']);
        $this->assertNotSame('', $presentation['tone']);
    }

    public function test_expired_pending_payment_uses_expired_presentation(): void
    {
        $presentation = PaymentStatusPresenter::for(PaymentStatus::PENDING, true);

        $this->assertSame('Pembayaran kedaluwarsa', $presentation['label']);
        $this->assertSame('danger', $presentation['tone']);
    }

    public static function paymentPresentations(): array
    {
        return [
            'pending' => [PaymentStatus::PENDING, 'Menunggu pembayaran'],
            'paid' => [PaymentStatus::PAID, 'Pembayaran berhasil'],
            'failed' => [PaymentStatus::FAILED, 'Pembayaran gagal'],
            'expired' => [PaymentStatus::EXPIRED, 'Pembayaran kedaluwarsa'],
            'cancelled' => [PaymentStatus::CANCELLED, 'Pembayaran dibatalkan'],
            'refund requested' => [PaymentStatus::REFUND_REQUESTED, 'Pengembalian diajukan'],
            'refunding' => [PaymentStatus::REFUNDING, 'Pengembalian diproses'],
            'refunded' => [PaymentStatus::REFUNDED, 'Dana telah dikembalikan'],
        ];
    }
}
