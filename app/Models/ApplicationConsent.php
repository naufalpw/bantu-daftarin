<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationConsent extends BaseModel
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }
}
