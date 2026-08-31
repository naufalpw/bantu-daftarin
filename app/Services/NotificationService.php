<?php

namespace App\Services;

use App\Enums\ResultDocumentType;
use App\Enums\ResultVerificationStatus;
use App\Models\Application;
use App\Models\Notification;
use App\Models\ResultDocument;
use App\Models\User;
use App\Notifications\ApplicationUpdateNotification;
use App\Notifications\ResultAvailableNotification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function database(User $user, string $type, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->getKey(),
            'type' => $type,
            'data' => $data,
        ]);
    }

    public function applicationStatus(Application $application): void
    {
        $this->database($application->user, 'application.status_changed', [
            'application_id' => $application->public_id,
            'status' => $application->status->value,
            'label' => $application->status->label(),
        ]);
        $application->user->notify(new ApplicationUpdateNotification('Status aplikasi diperbarui', 'Status aplikasi Anda sekarang: '.$application->status->label().'.'));
    }

    public function estimateChanged(Application $application): void
    {
        $this->database($application->user, 'application.estimate_changed', [
            'application_id' => $application->public_id,
            'estimated_completion_at' => $application->estimated_completion_at?->toIso8601String(),
        ]);
        $application->user->notify(new ApplicationUpdateNotification('Estimasi aplikasi diperbarui', 'Admin memperbarui estimasi penyelesaian aplikasi Anda.'));
    }

    public function paymentConfirmed(Application $application): void
    {
        $this->database($application->user, 'payment.confirmed', [
            'application_id' => $application->public_id,
            'payment_status' => 'PAID',
        ]);
        $application->user->notify(new ApplicationUpdateNotification('Pembayaran terkonfirmasi', 'Pembayaran aplikasi Anda telah terkonfirmasi.'));
    }

    public function resultAvailable(ResultDocument $result): void
    {
        $payload = DB::transaction(function () use ($result): ?array {
            $lockedResult = ResultDocument::query()
                ->lockForUpdate()
                ->findOrFail($result->getKey());

            $lockedResult->loadMissing('application.user');

            if ($lockedResult->type !== ResultDocumentType::PRIMARY_RESULT
                || $lockedResult->verification_status !== ResultVerificationStatus::VERIFIED
                || $lockedResult->deleted_at !== null) {
                return null;
            }

            $application = $lockedResult->application;
            $alreadyNotified = Notification::query()
                ->where('user_id', $application->user_id)
                ->where('type', 'result.available')
                ->get()
                ->contains(function (Notification $notification) use ($application, $lockedResult): bool {
                    $data = $notification->data ?? [];

                    return (string) ($data['application_id'] ?? '') === (string) $application->public_id
                        && (string) ($data['result_id'] ?? '') === (string) $lockedResult->public_id;
                });

            if ($alreadyNotified) {
                return null;
            }

            $this->database($application->user, 'result.available', [
                'application_id' => $application->public_id,
                'result_id' => $lockedResult->public_id,
            ]);

            return [$application->user, (string) $application->public_id, (string) $lockedResult->public_id];
        });

        if ($payload !== null) {
            [$user, $applicationId, $resultId] = $payload;
            $user->notify(new ResultAvailableNotification($applicationId, $resultId));
        }
    }
}
