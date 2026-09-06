<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Exceptions\InvalidApplicationTransition;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplicationTransitionService
{
    public function __construct(private readonly AuditService $audit, private readonly NotificationService $notifications) {}

    public function transition(Application $application, ApplicationStatus $target, User|Admin|null $actor = null, ?string $reason = null, bool $notify = true): Application
    {
        return DB::transaction(function () use ($application, $target, $actor, $reason, $notify): Application {
            $locked = Application::query()->lockForUpdate()->findOrFail($application->getKey());
            $current = $locked->status instanceof ApplicationStatus ? $locked->status : ApplicationStatus::from($locked->status);

            if ($current === $target) {
                return $locked->load(['user', 'service']);
            }

            if (! $current->canTransitionTo($target)) {
                throw new InvalidApplicationTransition("Tidak dapat mengubah status dari {$current->value} ke {$target->value}.");
            }

            if ($target === ApplicationStatus::CANCELLED
                && (! $actor instanceof User || ! $actor->isClient() || $actor->getKey() !== $locked->user_id)) {
                throw new InvalidApplicationTransition('Pembatalan mandiri hanya dapat dilakukan oleh pemilik pengajuan.');
            }

            $locked->status = $target;
            match ($target) {
                ApplicationStatus::PAYMENT_CONFIRMED => $locked->paid_at = now(),
                ApplicationStatus::DOCUMENTS_ACCEPTED => $locked->documents_accepted_at = now(),
                ApplicationStatus::WAITING_EXTERNAL_PROCESS => $locked->external_process_started_at = now(),
                ApplicationStatus::COMPLETED => $locked->completed_at = now(),
                default => null,
            };
            $locked->save();

            ApplicationStatusHistory::create([
                'application_id' => $locked->getKey(),
                'from_status' => $current->value,
                'to_status' => $target->value,
                'actor_type' => $this->actorType($actor),
                'actor_id' => $this->actorId($actor),
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $this->audit->record('application.status_changed', $locked, [
                'from' => $current->value,
                'to' => $target->value,
                'reason' => $reason,
            ], $actor instanceof User || $actor instanceof Admin ? $actor : null);
            if ($notify) {
                $this->notifications->applicationStatus($locked);
            }

            return $locked->load(['user', 'service']);
        });
    }

    private function actorType(User|Admin|null $actor): string
    {
        return $actor === null ? 'system' : ($actor instanceof Admin || ($actor instanceof User && $actor->isAdmin()) ? 'admin' : 'user');
    }

    private function actorId(User|Admin|null $actor): ?int
    {
        if ($actor instanceof Admin) {
            return $actor->getKey();
        }

        return $actor instanceof User ? ($actor->isAdmin() ? $actor->admin?->getKey() : $actor->getKey()) : null;
    }
}
