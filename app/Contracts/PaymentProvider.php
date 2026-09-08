<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Application;
use App\Models\Payment;

interface PaymentProvider
{
    public function supports(PaymentMethod $method): bool;

    /** @return array{external_id:string, checkout_url:string|null, expires_at:\DateTimeInterface|null, payload:array} */
    public function createPayment(Application $application, Payment $payment, PaymentMethod $method): array;
}
