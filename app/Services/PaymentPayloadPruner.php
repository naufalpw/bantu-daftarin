<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PaymentPayloadPruner
{
    private const PRUNABLE_PAYMENT_STATUSES = [
        PaymentStatus::FAILED,
        PaymentStatus::EXPIRED,
        PaymentStatus::CANCELLED,
        PaymentStatus::REFUNDED,
    ];

    public function pruneWebhook(int $eventId, CarbonInterface $cutoff): bool
    {
        return DB::transaction(function () use ($eventId, $cutoff): bool {
            $event = WebhookEvent::query()->lockForUpdate()->find($eventId);
            if (! $event || ! $this->webhookIsPrunable($event, $cutoff)) {
                return false;
            }

            $event->forceFill([
                'payload' => [
                    '_pruned' => true,
                    'pruned_at' => now()->toIso8601String(),
                    'original_event_id' => $event->event_id,
                    'original_provider' => $event->provider,
                ],
            ])->save();

            return true;
        });
    }

    public function prunePayment(int $paymentId, CarbonInterface $cutoff): bool
    {
        return DB::transaction(function () use ($paymentId, $cutoff): bool {
            $payment = Payment::query()->lockForUpdate()->find($paymentId);
            if (! $payment || ! $this->paymentIsPrunable($payment, $cutoff)) {
                return false;
            }

            $payment->forceFill([
                'provider_payload' => [
                    '_pruned' => true,
                    'pruned_at' => now()->toIso8601String(),
                    'external_id' => $payment->external_id,
                    'reference_id' => $payment->reference_id,
                ],
            ])->save();

            return true;
        });
    }

    private function webhookIsPrunable(WebhookEvent $event, CarbonInterface $cutoff): bool
    {
        $isTerminal = $event->status === 'PROCESSED'
            || ($event->status === 'REJECTED' && $event->error_message === 'permanent_validation_failed');

        return $isTerminal
            && $event->created_at?->lessThanOrEqualTo($cutoff)
            && is_array($event->payload)
            && ($event->payload['_pruned'] ?? false) !== true;
    }

    private function paymentIsPrunable(Payment $payment, CarbonInterface $cutoff): bool
    {
        return in_array($payment->status, self::PRUNABLE_PAYMENT_STATUSES, true)
            && $payment->updated_at?->lessThanOrEqualTo($cutoff)
            && is_array($payment->provider_payload)
            && ($payment->provider_payload['_pruned'] ?? false) !== true;
    }
}
