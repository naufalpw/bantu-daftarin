<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Models\Application;
use App\Models\Payment;

class FakePaymentGateway implements PaymentGateway
{
    public function createInvoice(Application $application, Payment $payment, PaymentMethod $method): array
    {
        return [
            'external_id' => 'fake-'.$payment->reference_id,
            'checkout_url' => route('testing.fake-payments.checkout', $payment->public_id),
            'expires_at' => now()->addDay(),
            'payload' => [
                'fake' => true,
                'external_id' => $payment->reference_id,
                'payment_method' => $method->value,
                'channel_code' => $method->xenditChannelCode(),
            ],
        ];
    }
}
