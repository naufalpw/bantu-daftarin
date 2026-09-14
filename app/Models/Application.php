<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'price_amount_snapshot' => 'decimal:2',
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'estimated_completion_at' => 'datetime',
            'documents_accepted_at' => 'datetime',
            'external_process_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function personalDetails()
    {
        return $this->hasOne(PersonalApplicationDetail::class);
    }

    public function businessDetails()
    {
        return $this->hasOne(BusinessApplicationDetail::class);
    }

    public function representatives()
    {
        return $this->hasMany(BusinessRepresentative::class);
    }

    public function requirements()
    {
        return $this->hasMany(ApplicationRequirement::class)->orderBy('sort_order');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    public function estimateHistories()
    {
        return $this->hasMany(ApplicationEstimateHistory::class);
    }

    public function consents()
    {
        return $this->hasMany(ApplicationConsent::class);
    }

    public function chatThread()
    {
        return $this->hasOne(ChatThread::class);
    }

    public function resultDocuments()
    {
        return $this->hasMany(ResultDocument::class);
    }

    public function hasAllRequiredDocuments(): bool
    {
        $requiredRequirements = $this->requirements()->where('active', true)->where('is_required', true)->get();

        return $requiredRequirements->isNotEmpty() && $requiredRequirements->every(function (ApplicationRequirement $requirement): bool {
            return $this->documents()
                ->where('application_requirement_id', $requirement->id)
                ->where('active', true)
                ->where('scan_status', 'PASSED')
                ->whereNull('deletion_scheduled_at')
                ->whereNull('deleted_at')
                ->exists();
        });
    }

    public function hasVerifiedPrimaryResult(): bool
    {
        return $this->resultDocuments()
            ->where('type', 'PRIMARY_RESULT')
            ->where('verification_status', 'VERIFIED')
            ->where('scan_status', 'PASSED')
            ->whereNull('deletion_scheduled_at')
            ->whereNull('deleted_at')
            ->exists();
    }
}
