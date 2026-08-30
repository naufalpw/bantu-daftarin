<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationRequirement extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return ['allowed_extensions' => 'array', 'allowed_mimes' => 'array', 'is_required' => 'boolean', 'active' => 'boolean'];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function serviceRequirement()
    {
        return $this->belongsTo(ServiceRequirement::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'ACCEPTED' => 'Diterima',
            'REJECTED' => 'Ditolak',
            'REVISION_REQUIRED' => 'Perlu perbaikan dokumen',
            default => 'Menunggu pemeriksaan',
        };
    }
}
