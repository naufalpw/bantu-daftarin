<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentGatewayDefinitiveException;
use App\Exceptions\PaymentGatewayException;
use App\Models\Application;
use App\Models\Payment;

class CoordinatedPaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $barrierDirectory,
        private readonly string $outcome,
    ) {}

    public function createInvoice(Application $application, Payment $payment, PaymentMethod $method): array
    {
        $isSuccess = in_array($this->outcome, ['success', 'success_after_webhook'], true);
        $readyMarker = $isSuccess ? 'success.ready' : 'failure.ready';
        file_put_contents($this->marker($readyMarker), (string) $payment->reference_id, LOCK_EX);
        $this->awaitMarkers(['success.ready', 'failure.ready']);

        if ($this->outcome === 'definitive_failure') {
            throw new PaymentGatewayDefinitiveException('Synthetic definitive provider rejection');
        }

        if ($this->outcome === 'failure') {
            throw new PaymentGatewayException('Synthetic transient provider failure');
        }

        $this->awaitMarkers([$this->outcome === 'success_after_webhook' ? 'webhook.finished' : 'failure.finished']);
        file_put_contents($this->marker('success.released'), (string) $payment->reference_id, LOCK_EX);

        return [
            'external_id' => 'provider-'.$payment->reference_id,
            'checkout_url' => 'https://example.test/checkout/'.$payment->reference_id,
            'expires_at' => $payment->expires_at,
            'payload' => [
                'payment_request_id' => 'provider-'.$payment->reference_id,
                'reference_id' => $payment->reference_id,
                'status' => 'REQUIRES_ACTION',
                'currency' => $payment->currency,
                'request_amount' => (float) $payment->amount,
                'channel_code' => $method->xenditChannelCode(),
            ],
        ];
    }

    private function awaitMarkers(array $markers): void
    {
        $deadline = microtime(true) + 10;

        while (microtime(true) < $deadline) {
            if (collect($markers)->every(fn (string $marker): bool => is_file($this->marker($marker)))) {
                return;
            }

            usleep(10_000);
        }

        throw new \RuntimeException('Timed out waiting for the deterministic payment test barrier.');
    }

    private function marker(string $name): string
    {
        return $this->barrierDirectory.DIRECTORY_SEPARATOR.$name;
    }
}
