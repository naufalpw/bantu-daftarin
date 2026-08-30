<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonalApplicationDetail extends BaseModel
{
    use HasFactory;

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
