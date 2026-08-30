<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->isAdmin() || ($user->isClient() && $document->application->user_id === $user->getKey());
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document) && $document->scan_status->value === 'PASSED' && ($document->active || $user->isAdmin()) && $document->deleted_at === null;
    }

    public function upload(User $user, Document $document): bool
    {
        return $user->isClient() && $document->application->user_id === $user->getKey();
    }

    public function review(User $user, Document $document): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->isClient() && $document->application->user_id === $user->getKey();
    }
}
