<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentGatewayDefinitiveException;
use App\Models\Application;
use App\Models\Payment;

/**
 * PayPal is intentionally a separate provider boundary.
 *
 * The MVP has no approved PayPal SDK/credential configuration, so this
 * provider never manufactures a checkout or success response.
 */
class PayPalPaymentProvider implements PaymentProvider
{
    public function supports(PaymentMethod $method): bool
    {
        return $method === PaymentMethod::PAYPAL;
    }

    public function createPayment(Application $application, Payment $payment, PaymentMethod $method): array
    {
        throw new PaymentGatewayDefinitiveException('PayPal belum tersedia.');
    }
}
