<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends BaseModel
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }
}
