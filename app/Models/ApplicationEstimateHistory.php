<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationEstimateHistory extends BaseModel
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['previous_estimated_completion_at' => 'datetime', 'new_estimated_completion_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
