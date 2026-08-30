<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationStatusHistory extends BaseModel
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['from_status' => ApplicationStatus::class, 'to_status' => ApplicationStatus::class, 'created_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
