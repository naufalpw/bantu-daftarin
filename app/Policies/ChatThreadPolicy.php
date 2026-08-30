<?php

namespace App\Policies;

use App\Models\ChatThread;
use App\Models\User;

class ChatThreadPolicy
{
    public function view(User $user, ChatThread $thread): bool
    {
        return $user->isAdmin() || ($user->isClient() && $thread->client_user_id === $user->getKey());
    }

    public function send(User $user, ChatThread $thread): bool
    {
        return $this->view($user, $thread);
    }
}
