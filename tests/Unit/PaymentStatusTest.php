<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_paid_payment_cannot_be_downgraded_by_a_late_failure_callback(): void
    {
        $this->assertFalse(PaymentStatus::PAID->canTransitionTo(PaymentStatus::FAILED));
        $this->assertFalse(PaymentStatus::PAID->canTransitionTo(PaymentStatus::EXPIRED));
        $this->assertTrue(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::PAID));
    }
}
