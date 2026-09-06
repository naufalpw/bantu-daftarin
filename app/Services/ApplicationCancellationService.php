<?php

namespace App\Services;

use App\Enums\ApplicationCancellationReason;
use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Models\Application;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationCancellationService
{
    /** @var list<ApplicationStatus> */
    private const CANCELLABLE_STATUSES = [
        ApplicationStatus::DRAFT,
        ApplicationStatus::AWAITING_DOCUMENTS,
        ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
        ApplicationStatus::AWAITING_PAYMENT,
    ];

    /** @var list<PaymentStatus> */
    private const CONFIRMED_PAYMENT_STATUSES = [
        PaymentStatus::PAID,
        PaymentStatus::REFUND_REQUESTED,
        PaymentStatus::REFUNDING,
        PaymentStatus::REFUNDED,
    ];

    public function __construct(private readonly ApplicationTransitionService $transitions) {}

    public function canBeCancelledByClient(Application $application, User $client): bool
    {
        if (! $client->isClient() || $application->user_id !== $client->getKey()) {
            return false;
        }

        if (! in_array($application->status, self::CANCELLABLE_STATUSES, true)) {
            return false;
        }

        return ! $application->payments()
            ->where(function ($query): void {
                $query->whereIn('status', array_map(fn (PaymentStatus $status): string => $status->value, self::CONFIRMED_PAYMENT_STATUSES))
                    ->orWhereNotNull('paid_at');
            })
            ->exists();
    }

    public function cancel(Application $application, User $client, ApplicationCancellationReason $reason, ?string $otherReason = null): Application
    {
        return DB::transaction(function () use ($application, $client, $reason, $otherReason): Application {
            $lockedApplication = Application::query()
                ->lockForUpdate()
                ->findOrFail($application->getKey());

            if ($lockedApplication->user_id !== $client->getKey() || ! $client->isClient()) {
                throw new AuthorizationException('Anda tidak dapat membatalkan pengajuan ini.');
            }

            if ($lockedApplication->status === ApplicationStatus::CANCELLED) {
                return $lockedApplication->load(['user', 'service']);
            }

            /** @var Collection<int, Payment> $payments */
            $payments = $lockedApplication->payments()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (! in_array($lockedApplication->status, self::CANCELLABLE_STATUSES, true)) {
                throw new \DomainException('Pengajuan tidak dapat dibatalkan pada tahap ini.');
            }

            if ($payments->contains(fn (Payment $payment): bool => $payment->paid_at !== null || in_array($payment->status, self::CONFIRMED_PAYMENT_STATUSES, true))) {
                throw new \DomainException('Pembatalan mandiri tidak tersedia setelah pembayaran dikonfirmasi.');
            }

            $reasonText = $reason->label();
            if ($reason === ApplicationCancellationReason::OTHER) {
                $reasonText .= ': '.trim((string) $otherReason);
            }

            return $this->transitions->transition(
                $lockedApplication,
                ApplicationStatus::CANCELLED,
                $client,
                $reasonText,
                notify: false,
            );
        });
    }
}
