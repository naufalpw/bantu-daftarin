<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ChatPresence
{
    public const HEARTBEAT_INTERVAL_SECONDS = 40;

    public const ONLINE_THRESHOLD_SECONDS = 120;

    public const RETENTION_HOURS = 24;

    /**
     * @return array{is_online:bool,label:string}
     */
    public function forUser(?User $user): array
    {
        if (! $user?->is_active) {
            return $this->inactiveStatus();
        }

        return $this->statusForTimestamp($this->lastSeenAt($user));
    }

    /**
     * @return array{is_online:bool,label:string}
     */
    public function forSupportTeam(): array
    {
        try {
            $userIds = Admin::query()
                ->where('is_active', true)
                ->where('role', UserRole::SUPER_ADMIN->value)
                ->whereHas('user', fn (Builder $users) => $users
                    ->where('is_active', true)
                    ->where('role', UserRole::SUPER_ADMIN->value))
                ->pluck('user_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
        } catch (Throwable) {
            return $this->inactiveStatus();
        }

        return $this->forUserIds($userIds);
    }

    public function heartbeat(User $user): void
    {
        try {
            $this->repository()->put(
                $this->keyFor($user),
                now()->timestamp,
                now()->addHours(self::RETENTION_HOURS),
            );
        } catch (Throwable) {
            // Presence is non-critical and must never interrupt the authenticated workspace.
        }
    }

    public function keyFor(User|int $user): string
    {
        return 'chat-presence:user:'.($user instanceof User ? $user->getKey() : $user);
    }

    public function lastSeenAt(User|int $user): ?CarbonInterface
    {
        try {
            $timestamp = $this->repository()->get($this->keyFor($user));
        } catch (Throwable) {
            return null;
        }

        if (! is_numeric($timestamp)) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $timestamp)->setTimezone(config('app.timezone'));
    }

    public function storeName(): string
    {
        return (string) config('cache.presence_store', 'file');
    }

    /**
     * @param  list<int>  $userIds
     * @return array{is_online:bool,label:string}
     */
    private function forUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter($userIds, fn (int $id): bool => $id > 0)));

        if ($userIds === []) {
            return $this->inactiveStatus();
        }

        $lastSeenAt = null;
        foreach ($userIds as $userId) {
            $candidate = $this->lastSeenAt($userId);
            if ($candidate && (! $lastSeenAt || $candidate->isAfter($lastSeenAt))) {
                $lastSeenAt = $candidate;
            }
        }

        return $this->statusForTimestamp($lastSeenAt);
    }

    /**
     * @return array{is_online:bool,label:string}
     */
    private function statusForTimestamp(?CarbonInterface $lastSeenAt): array
    {
        if (! $lastSeenAt) {
            return $this->inactiveStatus();
        }

        $currentTime = now();
        if ($lastSeenAt->greaterThanOrEqualTo($currentTime->copy()->subSeconds(self::ONLINE_THRESHOLD_SECONDS))) {
            return ['is_online' => true, 'label' => 'Online'];
        }

        if ($lastSeenAt->greaterThanOrEqualTo($currentTime->copy()->subHour())) {
            return ['is_online' => false, 'label' => 'Terakhir aktif beberapa menit lalu'];
        }

        if ($lastSeenAt->isSameDay($currentTime)) {
            return ['is_online' => false, 'label' => 'Terakhir aktif hari ini'];
        }

        if ($lastSeenAt->isSameDay($currentTime->copy()->subDay())) {
            return ['is_online' => false, 'label' => 'Terakhir aktif kemarin'];
        }

        return $this->inactiveStatus();
    }

    /**
     * @return array{is_online:bool,label:string}
     */
    private function inactiveStatus(): array
    {
        return ['is_online' => false, 'label' => 'Sedang tidak aktif'];
    }

    private function repository(): Repository
    {
        return Cache::store($this->storeName());
    }
}
