<?php

namespace App\Policies;

use App\Models\ResultDocument;
use App\Models\User;

class ResultDocumentPolicy
{
    public function view(User $user, ResultDocument $result): bool
    {
        return $result->deleted_at === null && ($user->isAdmin() || ($user->isClient() && $result->application->user_id === $user->getKey() && $result->verification_status->value === 'VERIFIED'));
    }

    public function download(User $user, ResultDocument $result): bool
    {
        return $this->view($user, $result);
    }

    public function adminAction(User $user, ResultDocument $result): bool
    {
        return $user->isAdmin();
    }
}
