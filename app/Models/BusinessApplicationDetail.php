<?php

namespace App\Models;

use App\Enums\BusinessType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessApplicationDetail extends BaseModel
{
    use HasFactory;

    public function businessTypeLabel(): string
    {
        return BusinessType::tryFrom((string) $this->business_type)?->label() ?? (string) $this->business_type;
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
