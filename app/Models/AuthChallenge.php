<?php

namespace App\Models;

use App\Enums\AuthChallengeType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuthChallenge extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return [
            'type' => AuthChallengeType::class,
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'locked_until' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function isLocked(): bool
    {
        return $this->locked_until && now()->lessThan($this->locked_until);
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
