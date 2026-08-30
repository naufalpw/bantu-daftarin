<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\ApplicationUpdateNotification;

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
}
