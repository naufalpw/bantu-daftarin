<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\ApplicationTransitionService;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class XenditWebhookController extends Controller
{
    public function __construct(
        private readonly ApplicationTransitionService $transitions,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.xendit.callback_token');
        $providedToken = (string) $request->header('x-callback-token');
        if (blank($configuredToken) || blank($providedToken) || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Webhook tidak valid.'], 401);
        }

        $payload = $request->json()->all();
        $eventId = (string) ($request->header('x-event-id') ?: ($payload['id'] ?? ''));
        if (blank($eventId)) {
            return response()->json(['message' => 'Event ID diperlukan.'], 422);
        }

        if (WebhookEvent::query()->where('provider', 'xendit')->where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Event sudah diproses.']);
        }

        try {
            $event = WebhookEvent::create([
                'provider' => 'xendit',
                'event_id' => $eventId,
                'event_type' => $payload['status'] ?? null,
                'payload' => $payload,
                'received_at' => now(),
                'status' => 'RECEIVED',
            ]);
        } catch (QueryException $exception) {
            if (WebhookEvent::query()->where('provider', 'xendit')->where('event_id', $eventId)->exists()) {
                return response()->json(['message' => 'Event sudah diproses.']);
            }
            logger()->error('xendit_webhook_ledger_write_failed', ['exception_class' => $exception::class, 'event_id' => $eventId]);

            return response()->json(['message' => 'Webhook tidak dapat diproses.'], 500);
        }

        try {
            DB::transaction(function () use ($payload, $event): void {
                $reference = (string) ($payload['external_id'] ?? '');
                $status = strtoupper((string) ($payload['status'] ?? ''));
                $amount = $payload['amount'] ?? null;
                $currency = strtoupper((string) ($payload['currency'] ?? ''));
                if (blank($reference) || ! in_array($status, ['PAID', 'SETTLED', 'EXPIRED', 'FAILED'], true)) {
                    throw new \DomainException('Payload pembayaran tidak valid.');
                }

                $payment = Payment::with('application.user')->where('reference_id', $reference)->lockForUpdate()->first();
                if (! $payment) {
                    throw new \DomainException('Reference pembayaran tidak ditemukan.');
                }
                if ($amount === null || number_format((float) $amount, 2, '.', '') !== number_format((float) $payment->amount, 2, '.', '') || $currency !== strtoupper($payment->currency)) {
                    throw new \DomainException('Nominal atau mata uang pembayaran tidak sesuai.');
                }
                if (isset($payload['id']) && filled($payment->external_id) && (string) $payload['id'] !== (string) $payment->external_id) {
                    throw new \DomainException('Identitas invoice tidak sesuai.');
                }
                if (isset($payload['payer_email']) && strtolower((string) $payload['payer_email']) !== strtolower($payment->application->user->email)) {
                    throw new \DomainException('Identitas pembayaran tidak sesuai.');
                }

                $targetStatus = ($status === 'PAID' || $status === 'SETTLED') ? PaymentStatus::PAID : ($status === 'EXPIRED' ? PaymentStatus::EXPIRED : PaymentStatus::FAILED);
                if ($payment->status !== $targetStatus && $payment->status->canTransitionTo($targetStatus)) {
                    if ($targetStatus === PaymentStatus::PAID) {
                        $payment->forceFill(['status' => PaymentStatus::PAID, 'paid_at' => now(), 'provider_payload' => $payload])->save();
                    } else {
                        $payment->forceFill(['status' => $targetStatus, 'failed_at' => now(), 'provider_payload' => $payload])->save();
                    }
                } elseif ($payment->status !== $targetStatus) {
                    $this->audit->record('payment.webhook_ignored', $payment, ['event_id' => $event->event_id, 'current_status' => $payment->status->value, 'incoming_status' => $targetStatus->value]);
                }

                if ($targetStatus === PaymentStatus::PAID && $payment->status === PaymentStatus::PAID && $payment->application->status->value === 'AWAITING_PAYMENT') {
                    $application = $this->transitions->transition($payment->application, ApplicationStatus::PAYMENT_CONFIRMED, null, 'xendit_webhook');
                    $this->notifications->paymentConfirmed($application);
                }

                $event->forceFill(['status' => 'PROCESSED', 'processed_at' => now()])->save();
                $this->audit->record('payment.webhook_processed', $payment, ['event_id' => $event->event_id, 'status' => $status]);
            });
        } catch (\Throwable $exception) {
            $event->forceFill(['status' => 'REJECTED', 'error_message' => 'validation_or_processing_failed'])->save();
            logger()->error('xendit_webhook_processing_failed', ['exception_class' => $exception::class, 'event_id' => $eventId]);

            return response()->json(['message' => 'Webhook tidak dapat diproses.'], 422);
        }

        return response()->json(['message' => 'Webhook diterima.']);
    }
}
