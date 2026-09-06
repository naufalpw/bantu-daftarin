<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isClient() || $user->isAdmin();
    }

    public function view(User $user, Application $application): bool
    {
        return $user->isAdmin() || ($user->isClient() && $application->user_id === $user->getKey());
    }

    public function update(User $user, Application $application): bool
    {
        return $user->isClient() && $application->user_id === $user->getKey() && in_array($application->status->value, ['DRAFT', 'AWAITING_DOCUMENTS'], true);
    }

    public function submit(User $user, Application $application): bool
    {
        return $user->isClient() && $application->user_id === $user->getKey();
    }

    public function cancel(User $user, Application $application): bool
    {
        return $user->isClient() && $application->user_id === $user->getKey();
    }

    public function adminAction(User $user, Application $application): bool
    {
        return $user->isAdmin();
    }
}
