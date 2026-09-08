<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ChatPresence;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PresenceHeartbeatController extends Controller
{
    public function __invoke(Request $request, ChatPresence $presence): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && $user->is_active
                && ($user->isClient() || ($user->isAdmin() && $user->admin?->is_active)),
            403,
        );

        $presence->heartbeat($user);

        return response()->noContent();
    }
}
