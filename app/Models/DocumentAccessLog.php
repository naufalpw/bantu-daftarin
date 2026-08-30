<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentAccessLog extends BaseModel
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
