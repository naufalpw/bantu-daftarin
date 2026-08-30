<?php

namespace App\Models;

use App\Enums\BusinessRelationship;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessRepresentative extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return ['relationship' => BusinessRelationship::class, 'is_primary' => 'boolean'];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
