<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return ['status' => ServiceStatus::class, 'price_amount' => 'decimal:2', 'activated_at' => 'datetime'];
    }

    public function requirements()
    {
        return $this->hasMany(ServiceRequirement::class)->orderBy('sort_order');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function isBookable(): bool
    {
        return $this->status === ServiceStatus::ACTIVE && $this->price_amount !== null && $this->requirements()->where('active', true)->where('is_required', true)->exists();
    }
}
