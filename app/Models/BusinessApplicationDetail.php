<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessApplicationDetail extends BaseModel
{
    use HasFactory;

    public function businessTypeLabel(): string
    {
        return \App\Enums\BusinessType::tryFrom((string) $this->business_type)?->label() ?? (string) $this->business_type;
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
