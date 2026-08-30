<?php

namespace App\Models;

use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return [
            'original_filename' => 'encrypted',
            'scan_status' => DocumentScanStatus::class,
            'review_status' => DocumentReviewStatus::class,
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'retention_until' => 'datetime',
            'deletion_scheduled_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function requirement()
    {
        return $this->belongsTo(ApplicationRequirement::class, 'application_requirement_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function reviews()
    {
        return $this->hasMany(DocumentReview::class);
    }
}
