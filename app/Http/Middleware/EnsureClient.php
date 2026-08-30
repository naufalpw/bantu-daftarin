<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_active || (! $user->isClient() && ! ($user->isAdmin() && $request->routeIs('client.chat.show')))) {
            abort(403, 'Akses client diperlukan.');
        }

        return $next($request);
    }
}
