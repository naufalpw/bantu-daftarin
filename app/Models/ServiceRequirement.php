<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceRequirement extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return ['allowed_extensions' => 'array', 'allowed_mimes' => 'array', 'is_required' => 'boolean', 'active' => 'boolean'];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
