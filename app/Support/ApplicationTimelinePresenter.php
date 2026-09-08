<?php

namespace App\Support;

use App\Models\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class ApplicationTimelinePresenter
{
    /** @return Collection<int, array{timestamp: Carbon, label: string, description: string, tone: string}> */
    public static function for(Application $application): Collection
    {
        $statusEvents = $application->statusHistories->map(function ($history): array {
            $status = ApplicationStatusPresenter::forStatus($history->to_status);

            return [
                'timestamp' => $history->created_at,
                'label' => $status['label'],
                'description' => $status['description'],
                'tone' => $status['tone'],
            ];
        });

        $paymentEvents = $application->payments->map(function ($payment): array {
            $status = PaymentStatusPresenter::for($payment->status, $payment->status?->value === 'PENDING' && $payment->expires_at?->isPast());
            $timestamp = $payment->paid_at ?? $payment->failed_at ?? $payment->updated_at ?? $payment->created_at;

            return [
                'timestamp' => $timestamp,
                'label' => $status['label'],
                'description' => $payment->payment_method
                    ? 'Metode '.$payment->payment_method->label().', referensi '.$payment->reference_id.'.'
                    : 'Referensi '.$payment->reference_id.'.',
                'tone' => $status['tone'],
            ];
        });

        return $statusEvents
            ->concat($paymentEvents)
            ->filter(fn (array $event): bool => $event['timestamp'] !== null)
            ->sortByDesc('timestamp')
            ->values();
    }
}
