<?php

namespace App\Services;

use Illuminate\Support\Arr;

class PaymentPayloadMinimizer
{
    public function checkout(array $payload): array
    {
        $minimized = Arr::only($payload, [
            'id',
            'payment_request_id',
            'payment_id',
            'reference_id',
            'external_id',
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
            'expires_at',
            'created',
            'updated',
        ]);

        $minimized['channel_properties'] = $this->channelProperties($minimized['channel_properties'] ?? null);
        $minimized['actions'] = $this->actions($minimized['actions'] ?? null);

        return $this->withoutEmptyOptionalValues($minimized);
    }

    public function webhookEvent(array $payload): array
    {
        $minimized = Arr::only($payload, [
            'id',
            'event_id',
            'event',
            'status',
            'external_id',
            'amount',
            'currency',
            'payer_email',
            'channel_code',
            'created',
            'updated',
            'data',
        ]);

        if (is_array($minimized['data'] ?? null)) {
            $data = Arr::only($minimized['data'], [
                'id',
                'payment_id',
                'payment_request_id',
                'reference_id',
                'status',
                'request_amount',
                'amount',
                'currency',
                'channel_code',
                'actions',
                'channel_properties',
                'customer',
                'expires_at',
                'created',
                'updated',
            ]);
            $data['actions'] = $this->actions($data['actions'] ?? null);
            $data['channel_properties'] = $this->channelProperties($data['channel_properties'] ?? null);
            $data['customer'] = is_array($data['customer'] ?? null)
                ? Arr::only($data['customer'], ['email'])
                : [];
            $minimized['data'] = $this->withoutEmptyOptionalValues($data);
        }

        return $this->withoutEmptyOptionalValues($minimized);
    }

    public function paymentFromWebhook(array $incomingPayload, ?array $existingPayload): array
    {
        $data = is_array($incomingPayload['data'] ?? null) ? $incomingPayload['data'] : $incomingPayload;
        $existingPayload ??= [];

        $minimized = [
            'id' => (string) ($data['id'] ?? $data['payment_id'] ?? $data['payment_request_id'] ?? ($existingPayload['id'] ?? '')),
            'reference_id' => (string) ($data['reference_id'] ?? $incomingPayload['external_id'] ?? ($existingPayload['reference_id'] ?? '')),
            'status' => strtoupper((string) ($data['status'] ?? $incomingPayload['status'] ?? ($existingPayload['status'] ?? ''))),
            'currency' => strtoupper((string) ($data['currency'] ?? $incomingPayload['currency'] ?? ($existingPayload['currency'] ?? ''))),
            'amount' => $data['request_amount'] ?? $data['amount'] ?? $incomingPayload['amount'] ?? ($existingPayload['amount'] ?? null),
            'channel_code' => strtoupper((string) ($data['channel_code'] ?? $incomingPayload['channel_code'] ?? ($existingPayload['channel_code'] ?? ''))),
            'actions' => $this->actions($data['actions'] ?? ($existingPayload['actions'] ?? null)),
            'channel_properties' => $this->channelProperties($data['channel_properties'] ?? ($existingPayload['channel_properties'] ?? null)),
            'expires_at' => $data['channel_properties']['expires_at'] ?? $data['expires_at'] ?? ($existingPayload['expires_at'] ?? null),
            'updated' => now()->toIso8601String(),
        ];

        return $this->withoutEmptyOptionalValues($minimized);
    }

    private function actions(mixed $actions): array
    {
        if (! is_array($actions)) {
            return [];
        }

        return array_values(array_filter(array_map(function (mixed $action): ?array {
            if (! is_array($action)) {
                return null;
            }

            return $this->withoutEmptyOptionalValues(Arr::only($action, [
                'action',
                'type',
                'descriptor',
                'url',
                'value',
                'qr_string',
                'virtual_account_number',
            ]));
        }, $actions), fn (?array $action): bool => $action !== null && $action !== []));
    }

    private function channelProperties(mixed $properties): array
    {
        return is_array($properties)
            ? $this->withoutEmptyOptionalValues(Arr::only($properties, ['expires_at']))
            : [];
    }

    private function withoutEmptyOptionalValues(array $payload): array
    {
        return array_filter($payload, fn (mixed $value): bool => $value !== '' && $value !== null && $value !== []);
    }
}
