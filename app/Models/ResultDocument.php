<?php

namespace App\Models;

use App\Enums\DocumentScanStatus;
use App\Enums\ResultDocumentType;
use App\Enums\ResultVerificationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResultDocument extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return [
            'original_filename' => 'encrypted',
            'type' => ResultDocumentType::class,
            'scan_status' => DocumentScanStatus::class,
            'verification_status' => ResultVerificationStatus::class,
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'retention_until' => 'datetime',
            'deletion_scheduled_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by_admin_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }
}
