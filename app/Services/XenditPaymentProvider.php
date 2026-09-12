<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentGatewayAmbiguousException;
use App\Exceptions\PaymentGatewayDefinitiveException;
use App\Models\Application;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
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
            throw new PaymentGatewayDefinitiveException('Payment provider belum dikonfigurasi.');
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
        } catch (ConnectionException $exception) {
            logger()->error('xendit_payment_provider_failure', ['exception_class' => $exception::class]);
            throw new PaymentGatewayAmbiguousException('Payment provider tidak dapat dihubungi.', previous: $exception);
        }

        if (! $response->successful()) {
            $errorCode = strtoupper((string) ($response->json('error_code') ?? ''));
            logger()->error('xendit_payment_provider_http_failure', [
                'status' => $response->status(),
                'error_code' => $errorCode !== '' ? $errorCode : null,
            ]);

            if ($this->isDefinitiveRejection($response->status(), $errorCode)) {
                throw new PaymentGatewayDefinitiveException('Payment provider menolak permintaan pembayaran.');
            }

            throw new PaymentGatewayAmbiguousException('Payment provider tidak dapat memastikan hasil permintaan pembayaran.');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new PaymentGatewayAmbiguousException('Respons payment provider tidak valid.');
        }

        $externalId = (string) ($payload['payment_request_id'] ?? $payload['id'] ?? $payload['payment_id'] ?? '');
        if (blank($externalId)) {
            throw new PaymentGatewayAmbiguousException('Respons payment provider tidak memiliki identitas pembayaran.');
        }

        return [
            'external_id' => $externalId,
            'checkout_url' => $this->checkoutUrl($payload),
            'expires_at' => $this->expiresAt($payload, $payment),
            'payload' => $payload,
            'payload' => $this->minimizePayload($payload),
        ];
    }

    private function isDefinitiveRejection(int $status, string $errorCode): bool
    {
        $knownRejections = [
            400 => [
                'INVALID_VALUE_ERROR',
                'API_VALIDATION_ERROR',
                'CARD_EXPIRED',
                'INVALID_PAYMENT_DETAILS',
                'INVALID_TOKEN',
            ],
            401 => [
                'INVALID_API_KEY',
                'INVALID_MERCHANT_CREDENTIALS',
                'INVALID_TOKEN',
            ],
            403 => [
                'REQUEST_FORBIDDEN_ERROR',
                'CHANNEL_NOT_ACTIVATED',
                'UNSUPPORTED_CONTENT_TYPE',
                'SKIP_3DS_FORBIDDEN',
                'INVALID_MERCHANT_SETTINGS',
                'ACCOUNT_ACCESS_BLOCKED',
            ],
            404 => ['DATA_NOT_FOUND', 'CALLBACK_URL_NOT_FOUND'],
        ];

        return in_array($errorCode, $knownRejections[$status] ?? [], true);
    }

    private function minimizePayload(array $payload): array
    {
        $allowedKeys = [
            'id',
            'payment_request_id',
            'payment_id',
            'reference_id',
            'type',
            'status',
            'country',
            'currency',
            'request_amount',
            'amount',
            'capture_amount',
            'channel_code',
            'channel_properties',
            'actions',
            'description',
            'expires_at',
            'created',
            'updated',
        ];

        $minimized = Arr::only($payload, $allowedKeys);

        if (isset($minimized['channel_properties']) && is_array($minimized['channel_properties'])) {
            $minimized['channel_properties'] = Arr::only($minimized['channel_properties'], [
                'expires_at',
                'customer_name',
                'display_name',
            ]);
        }

        if (isset($minimized['actions']) && is_array($minimized['actions'])) {
            $minimized['actions'] = array_values(array_filter(array_map(function ($action) {
                if (! is_array($action)) {
                    return null;
                }

                return Arr::only($action, [
                    'action',
                    'type',
                    'descriptor',
                    'url',
                    'value',
                    'qr_string',
                    'virtual_account_number',
                ]);
            }, $minimized['actions'])));
        }

        return $minimized;
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
