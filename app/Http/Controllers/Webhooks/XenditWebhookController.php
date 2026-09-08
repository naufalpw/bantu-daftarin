<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
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
    private const ERROR_PERMANENT_VALIDATION = 'permanent_validation_failed';

    private const ERROR_TRANSIENT_PROCESSING = 'transient_processing_failed';

    private const OUTCOME_ALREADY_PROCESSED = 'already_processed';

    private const OUTCOME_PERMANENTLY_REJECTED = 'permanently_rejected';

    private const OUTCOME_PROCESSED = 'processed';

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
        $eventId = $this->eventId($request, $payload);

        try {
            WebhookEvent::query()->firstOrCreate(
                ['provider' => 'xendit', 'event_id' => $eventId],
                [
                    'event_type' => $this->eventType($payload),
                    'payload' => $payload,
                    'received_at' => now(),
                    'status' => 'RECEIVED',
                ],
            );
        } catch (QueryException $exception) {
            if (! WebhookEvent::query()->where('provider', 'xendit')->where('event_id', $eventId)->exists()) {
                logger()->error('xendit_webhook_ledger_write_failed', ['exception_class' => $exception::class, 'event_id' => $eventId]);

                return response()->json(['message' => 'Webhook tidak dapat diproses.'], 500);
            }
        }

        try {
            $outcome = $this->processEvent($eventId);
        } catch (\Throwable $exception) {
            WebhookEvent::query()
                ->where('provider', 'xendit')
                ->where('event_id', $eventId)
                ->where('status', 'RECEIVED')
                ->update(['error_message' => self::ERROR_TRANSIENT_PROCESSING]);
            logger()->error('xendit_webhook_processing_failed', ['exception_class' => $exception::class, 'event_id' => $eventId]);

            return response()->json(['message' => 'Webhook tidak dapat diproses.'], 500);
        }

        if ($outcome === self::OUTCOME_ALREADY_PROCESSED) {
            return response()->json(['message' => 'Event sudah diproses.']);
        }

        if ($outcome === self::OUTCOME_PERMANENTLY_REJECTED) {
            return response()->json(['message' => 'Webhook tidak dapat diproses.'], 422);
        }

        return response()->json(['message' => 'Webhook diterima.']);
    }

    private function processEvent(string $eventId): string
    {
        return DB::transaction(function () use ($eventId): string {
            $event = WebhookEvent::query()
                ->where('provider', 'xendit')
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->status === 'PROCESSED') {
                return self::OUTCOME_ALREADY_PROCESSED;
            }

            if ($event->status === 'REJECTED' && $event->error_message === self::ERROR_PERMANENT_VALIDATION) {
                return self::OUTCOME_PERMANENTLY_REJECTED;
            }

            // Rows rejected by the former status-blind handler did not distinguish
            // validation failures from transient processing failures. Reconcile them
            // once using the original ledger payload, then persist an explicit result.
            if ($event->status === 'REJECTED') {
                $event->forceFill([
                    'status' => 'RECEIVED',
                    'error_message' => null,
                    'processed_at' => null,
                ])->save();
            }

            $payload = $event->payload;

            try {
                $normalized = $this->normalize($payload);
                $reference = $normalized['reference'];
                $status = $normalized['status'];

                $paymentReference = Payment::query()
                    ->select(['id', 'application_id'])
                    ->where('reference_id', $reference)
                    ->first();
                if (! $paymentReference) {
                    throw new \DomainException('Reference pembayaran tidak ditemukan.');
                }

                // Lock in the same order as client cancellation: application, then payment.
                $application = Application::query()
                    ->with('user')
                    ->lockForUpdate()
                    ->findOrFail($paymentReference->application_id);
                $payment = Payment::query()->lockForUpdate()->findOrFail($paymentReference->id);
                $payment->setRelation('application', $application);
                if ($normalized['amount'] === null || number_format((float) $normalized['amount'], 2, '.', '') !== number_format((float) $payment->amount, 2, '.', '') || $normalized['currency'] !== strtoupper($payment->currency)) {
                    throw new \DomainException('Nominal atau mata uang pembayaran tidak sesuai.');
                }
                if (filled($payment->external_id) && $normalized['external_ids'] !== [] && ! in_array((string) $payment->external_id, $normalized['external_ids'], true)) {
                    throw new \DomainException('Identitas invoice tidak sesuai.');
                }
                if (filled($normalized['payer_email']) && strtolower($normalized['payer_email']) !== strtolower($payment->application->user->email)) {
                    throw new \DomainException('Identitas pembayaran tidak sesuai.');
                }
                if ($payment->payment_method?->xenditChannelCode() && filled($normalized['channel_code']) && $payment->payment_method->xenditChannelCode() !== $normalized['channel_code']) {
                    throw new \DomainException('Channel pembayaran tidak sesuai.');
                }

                $targetStatus = $this->targetStatus($normalized['event'], $status, $normalized['is_v3']);
                if ($targetStatus !== null && $payment->status !== $targetStatus && $payment->status->canTransitionTo($targetStatus)) {
                    if ($targetStatus === PaymentStatus::PAID) {
                        $payment->forceFill(['status' => PaymentStatus::PAID, 'paid_at' => now(), 'provider_payload' => $payload])->save();
                    } else {
                        $payment->forceFill(['status' => $targetStatus, 'failed_at' => now(), 'provider_payload' => $payload])->save();
                    }
                } elseif ($targetStatus !== null && $payment->status !== $targetStatus) {
                    $this->audit->record('payment.webhook_ignored', $payment, ['event_id' => $event->event_id, 'current_status' => $payment->status->value, 'incoming_status' => $targetStatus->value]);
                } elseif ($targetStatus === null) {
                    $payment->forceFill(['provider_payload' => $payload])->save();
                }

                if ($targetStatus === PaymentStatus::PAID && $payment->status === PaymentStatus::PAID) {
                    if ($application->status === ApplicationStatus::AWAITING_PAYMENT) {
                        $application = $this->transitions->transition($application, ApplicationStatus::PAYMENT_CONFIRMED, null, 'xendit_webhook');
                        $this->notifications->paymentConfirmed($application);
                    } elseif ($application->status === ApplicationStatus::CANCELLED) {
                        $this->audit->record('payment.received_after_application_cancelled', $payment, [
                            'application_id' => $application->public_id,
                            'event_id' => $event->event_id,
                        ]);
                    }
                }

                $event->forceFill([
                    'status' => 'PROCESSED',
                    'error_message' => null,
                    'processed_at' => now(),
                ])->save();
                $this->audit->record('payment.webhook_processed', $payment, ['event_id' => $event->event_id, 'status' => $status]);
            } catch (\DomainException $exception) {
                $event->forceFill([
                    'status' => 'REJECTED',
                    'error_message' => self::ERROR_PERMANENT_VALIDATION,
                    'processed_at' => now(),
                ])->save();
                logger()->warning('xendit_webhook_validation_failed', ['exception_class' => $exception::class, 'event_id' => $eventId]);

                return self::OUTCOME_PERMANENTLY_REJECTED;
            }

            return self::OUTCOME_PROCESSED;
        });
    }

    private function eventId(Request $request, array $payload): string
    {
        $eventId = (string) ($request->header('x-event-id') ?: ($payload['id'] ?? $payload['event_id'] ?? ''));
        if (filled($eventId)) {
            return $eventId;
        }

        return 'payload-'.hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function eventType(array $payload): ?string
    {
        return filled($payload['event'] ?? null)
            ? (string) $payload['event']
            : (filled($payload['status'] ?? null) ? (string) $payload['status'] : null);
    }

    /** @return array{reference:string,status:string,event:string,is_v3:bool,amount:float|int|string|null,currency:string,external_ids:array<int,string>,payer_email:string,channel_code:string} */
    private function normalize(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $isV3 = filled($payload['event'] ?? null) && $data !== [];
        $event = strtolower((string) ($payload['event'] ?? 'legacy'));
        $status = strtoupper((string) ($data['status'] ?? $payload['status'] ?? ''));
        $reference = (string) ($data['reference_id'] ?? $payload['external_id'] ?? '');
        $externalIds = array_values(array_filter(array_unique(array_map('strval', [
            $data['payment_request_id'] ?? null,
            $data['payment_id'] ?? null,
            $payload['id'] ?? null,
        ]))));

        if ($isV3) {
            if (! in_array($event, ['payment.capture', 'payment.authorization', 'payment.failure', 'payment_request.expiry'], true)) {
                throw new \DomainException('Event pembayaran tidak valid.');
            }
            if ($event === 'payment_request.expiry' && blank($status)) {
                $status = 'EXPIRED';
            }
        } elseif (! in_array($status, ['PAID', 'SETTLED', 'EXPIRED', 'FAILED'], true)) {
            throw new \DomainException('Payload pembayaran tidak valid.');
        }

        if (blank($reference)) {
            throw new \DomainException('Reference pembayaran tidak valid.');
        }

        return [
            'reference' => $reference,
            'status' => $status,
            'event' => $event,
            'is_v3' => $isV3,
            'amount' => $data['request_amount'] ?? $data['amount'] ?? $payload['amount'] ?? null,
            'currency' => strtoupper((string) ($data['currency'] ?? $payload['currency'] ?? '')),
            'external_ids' => $externalIds,
            'payer_email' => (string) ($data['customer']['email'] ?? $payload['payer_email'] ?? ''),
            'channel_code' => strtoupper((string) ($data['channel_code'] ?? $payload['channel_code'] ?? '')),
        ];
    }

    private function targetStatus(string $event, string $status, bool $isV3): ?PaymentStatus
    {
        if (! $isV3) {
            return ($status === 'PAID' || $status === 'SETTLED') ? PaymentStatus::PAID : ($status === 'EXPIRED' ? PaymentStatus::EXPIRED : PaymentStatus::FAILED);
        }

        return match ($event) {
            'payment.capture' => match ($status) {
                'SUCCEEDED' => PaymentStatus::PAID,
                'FAILED' => PaymentStatus::FAILED,
                'EXPIRED' => PaymentStatus::EXPIRED,
                default => null,
            },
            'payment.failure' => PaymentStatus::FAILED,
            'payment_request.expiry' => PaymentStatus::EXPIRED,
            default => null,
        };
    }
}
