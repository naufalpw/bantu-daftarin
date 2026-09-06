<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentGatewayException;
use App\Models\Application;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XenditPaymentProvider implements PaymentProvider
{
    public function supports(PaymentMethod $method): bool
    {
        return $method->provider() === 'xendit';
    }

    public function createPayment(Application $application, Payment $payment, PaymentMethod $method): array
    {
        $channelCode = $method->xenditChannelCode();
        $secret = (string) config('services.xendit.secret_key');
        if (blank($channelCode) || blank($secret)) {
            throw new PaymentGatewayException('Payment provider belum dikonfigurasi.');
        }

        $paymentUrl = route('client.payments.show', $application->public_id);
        $channelProperties = [
            'success_return_url' => $paymentUrl,
            'failure_return_url' => $paymentUrl,
        ];

        if ($payment->expires_at) {
            $channelProperties['expires_at'] = $payment->expires_at->utc()->toIso8601String();
        }

        if (in_array($method, [PaymentMethod::BCA, PaymentMethod::BRI], true)) {
            $channelProperties['display_name'] = Str::limit((string) ($application->user->name ?? 'Bantu Daftarin'), 255, '');
        }

        $requestPayload = [
            'reference_id' => $payment->reference_id,
            'type' => 'PAY',
            'country' => 'ID',
            'currency' => strtoupper((string) $payment->currency),
            'request_amount' => (float) $payment->amount,
            'channel_code' => $channelCode,
            'channel_properties' => $channelProperties,
            'description' => Str::limit('Bantu Daftarin - '.(string) $application->service->name, 1000, ''),
            'metadata' => [
                'application_public_id' => (string) $application->public_id,
                'payment_reference_id' => (string) $payment->reference_id,
            ],
        ];

        try {
            $response = Http::withBasicAuth($secret, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'api-version' => (string) config('services.xendit.payment_api_version', '2024-11-11'),
                    'idempotency-key' => (string) $payment->reference_id,
                ])
                ->asJson()
                ->timeout((int) config('services.xendit.timeout', 20))
                ->post($this->endpoint(), $requestPayload);
        } catch (\Throwable $exception) {
            logger()->error('xendit_payment_provider_failure', ['exception_class' => $exception::class]);
            throw new PaymentGatewayException('Payment provider tidak dapat dihubungi.');
        }

        if (! $response->successful()) {
            logger()->error('xendit_payment_provider_http_failure', ['status' => $response->status()]);
            throw new PaymentGatewayException('Payment provider tidak dapat memproses pembayaran.');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new PaymentGatewayException('Respons payment provider tidak valid.');
        }

        $externalId = (string) ($payload['payment_request_id'] ?? $payload['id'] ?? $payload['payment_id'] ?? '');
        if (blank($externalId)) {
            throw new PaymentGatewayException('Respons payment provider tidak memiliki identitas pembayaran.');
        }

        return [
            'external_id' => $externalId,
            'checkout_url' => $this->checkoutUrl($payload),
            'expires_at' => $this->expiresAt($payload, $payment),
            'payload' => $payload,
        ];
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.xendit.api_base_url', 'https://api.xendit.co'), '/').'/v3/payment_requests';
    }

    private function checkoutUrl(array $payload): ?string
    {
        foreach ((array) ($payload['actions'] ?? []) as $action) {
            if (! is_array($action)) {
                continue;
            }

            $descriptor = strtoupper((string) ($action['descriptor'] ?? ''));
            $type = strtoupper((string) ($action['type'] ?? ''));
            $value = $action['value'] ?? $action['url'] ?? null;
            if (($descriptor === 'WEB_URL' || $type === 'REDIRECT_CUSTOMER') && is_string($value) && $this->isHttpsUrl($value)) {
                return $value;
            }
        }

        return null;
    }

    private function expiresAt(array $payload, Payment $payment): ?CarbonImmutable
    {
        $value = Arr::get($payload, 'channel_properties.expires_at')
            ?? Arr::get($payload, 'expires_at')
            ?? $payment->expires_at?->toIso8601String();
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return $payment->expires_at?->toImmutable();
        }
    }

    private function isHttpsUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false && parse_url($value, PHP_URL_SCHEME) === 'https';
    }
}
