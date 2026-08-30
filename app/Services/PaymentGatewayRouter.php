<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentGatewayException;
use App\Models\Application;
use App\Models\Payment;

class PaymentGatewayRouter implements PaymentGateway
{
    /** @param array<int, PaymentProvider> $providers */
    public function __construct(private readonly array $providers) {}

    public function createInvoice(Application $application, Payment $payment, PaymentMethod $method): array
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($method)) {
                return $provider->createPayment($application, $payment, $method);
            }
        }

        throw new PaymentGatewayException('Metode pembayaran belum tersedia.');
    }
}
