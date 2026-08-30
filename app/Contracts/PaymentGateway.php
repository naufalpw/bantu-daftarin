<?php

namespace App\Contracts;

use App\Models\Application;
use App\Models\Payment;

interface PaymentGateway
{
    /** @return array{external_id:string, checkout_url:string, expires_at:\DateTimeInterface|null, payload:array} */
    public function createInvoice(Application $application, Payment $payment): array;
}
