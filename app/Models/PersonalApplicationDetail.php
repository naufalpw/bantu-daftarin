<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonalApplicationDetail extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'family_card_number' => 'encrypted',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
