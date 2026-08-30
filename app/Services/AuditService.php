<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function record(string $event, ?Model $auditable = null, array $properties = [], User|Admin|null $actor = null, ?Request $request = null): AuditLog
    {
        $request ??= app()->bound('request') ? request() : null;
        $actorType = null;
        $actorId = null;

        if ($actor instanceof Admin) {
            $actorType = 'admin';
            $actorId = $actor->getKey();
        } elseif ($actor instanceof User) {
            $actorType = $actor->isAdmin() ? 'admin' : 'user';
            $actorId = $actor->isAdmin() ? $actor->admin?->getKey() : $actor->getKey();
        }

        return AuditLog::create([
            'event' => $event,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'properties' => $properties,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
