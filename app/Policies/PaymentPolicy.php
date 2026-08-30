<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->isAdmin() || ($user->isClient() && $payment->application->user_id === $user->getKey());
    }
}
