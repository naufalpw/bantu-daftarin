<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookEvent extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return ['payload' => 'array', 'received_at' => 'datetime', 'processed_at' => 'datetime'];
    }
}
