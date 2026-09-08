<?php

namespace App\Services;

use App\Contracts\MalwareScanner;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewAction;
use App\Enums\ResultDocumentType;
use App\Enums\ResultVerificationStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationEstimateHistory;
use App\Models\ResultDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminWorkflowService
{
    public function __construct(
        private readonly ApplicationTransitionService $transitions,
        private readonly DocumentWorkflowService $documents,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
        private readonly MalwareScanner $scanner,
    ) {}

    public function beginReview(Application $application, Admin $admin): Application
    {
        if (! in_array($application->status, [ApplicationStatus::DOCUMENTS_SUBMITTED, ApplicationStatus::REVISION_SUBMITTED], true)) {
            throw new \DomainException('Aplikasi belum siap untuk pemeriksaan.');
        }

        $application->forceFill(['assigned_admin_id' => $admin->getKey()])->save();
        $application->chatThread()->update(['assigned_admin_id' => $admin->getKey()]);

        return $this->transitions->transition($application, ApplicationStatus::UNDER_REVIEW, $admin);
    }

    public function reviewDocument($document, Admin $admin, DocumentReviewAction $action, ?string $reason, ?string $instruction): Application
    {
        $this->documents->review($document, $admin, $action, $reason, $instruction);

        return $document->application->fresh(['requirements', 'documents']);
    }

    public function finalizeReview(Application $application, Admin $admin, ?string $reason = null): Application
    {
        if ($application->status !== ApplicationStatus::UNDER_REVIEW) {
            throw new \DomainException('Aplikasi belum berada pada tahap pemeriksaan.');
        }

        $application->load(['requirements', 'documents']);
        $hasRevision = $application->requirements->contains(fn ($requirement) => $requirement->is_required && $requirement->status === 'REVISION_REQUIRED');
        $allAccepted = $application->requirements->where('is_required', true)->every(fn ($requirement) => $requirement->status === 'ACCEPTED');

        if ($hasRevision || ! $allAccepted) {
            if (! $hasRevision) {
                throw new \DomainException('Semua dokumen wajib harus diterima atau dibuka untuk revisi.');
            }

            return $this->transitions->transition($application, ApplicationStatus::REVISION_REQUIRED, $admin, $reason);
        }

        return $this->transitions->transition($application, ApplicationStatus::DOCUMENTS_ACCEPTED, $admin, $reason);
    }

    public function setEstimate(Application $application, Admin $admin, \DateTimeInterface $estimate, string $reason): Application
    {
        if (! in_array($application->status, [ApplicationStatus::DOCUMENTS_ACCEPTED, ApplicationStatus::ESTIMATE_PENDING], true) || ! $application->hasAllRequiredDocuments()) {
            throw new \DomainException('Estimasi hanya dapat ditentukan setelah seluruh dokumen diterima.');
        }
        if (blank($reason)) {
            throw new \DomainException('Alasan estimasi wajib diisi.');
        }

        return DB::transaction(function () use ($application, $admin, $estimate, $reason): Application {
            if ($application->status === ApplicationStatus::DOCUMENTS_ACCEPTED) {
                $application = $this->transitions->transition($application, ApplicationStatus::ESTIMATE_PENDING, $admin);
            }

            ApplicationEstimateHistory::create([
                'application_id' => $application->getKey(),
                'admin_id' => $admin->getKey(),
                'previous_estimated_completion_at' => $application->estimated_completion_at,
                'new_estimated_completion_at' => $estimate,
                'reason' => $reason,
                'created_at' => now(),
            ]);
            $application->forceFill(['estimated_completion_at' => $estimate])->save();
            $this->audit->record('application.estimate_changed', $application, ['reason' => $reason], $admin);
            $application = $this->transitions->transition($application, ApplicationStatus::IN_PROGRESS, $admin);
            $this->notifications->estimateChanged($application);

            return $application;
        });
    }

    public function markWaitingExternal(Application $application, Admin $admin): Application
    {
        return $this->transitions->transition($application, ApplicationStatus::WAITING_EXTERNAL_PROCESS, $admin);
    }

    public function uploadResult(Application $application, Admin $admin, UploadedFile $file, ResultDocumentType $type): ResultDocument
    {
        if (! in_array($application->status, [ApplicationStatus::WAITING_EXTERNAL_PROCESS, ApplicationStatus::RESULT_UPLOADED, ApplicationStatus::RESULT_REVIEW], true)) {
            throw new \DomainException('Hasil belum dapat diunggah pada tahap ini.');
        }

        $this->assertResultFile($file);
        $extension = strtolower($file->getClientOriginalExtension());
        $stored = (string) Str::uuid().'.'.$extension;
        $path = 'applications/'.$application->public_id.'/results/'.$stored;
        $quarantinePath = 'applications/'.$application->public_id.'/results/'.$stored;
        if (! Storage::disk('quarantine')->putFileAs(dirname($quarantinePath), $file, $stored)) {
            throw new \DomainException('File hasil tidak dapat disimpan untuk pemeriksaan.');
        }
        $scan = $this->scanner->scan(Storage::disk('quarantine')->path($quarantinePath));
        if (! ($scan['available'] ?? false) || ! ($scan['clean'] ?? false)) {
            Storage::disk('quarantine')->delete($quarantinePath);
            throw new \DomainException('File hasil gagal pemeriksaan keamanan.');
        }
        try {
            if (! Storage::disk('private')->put($path, Storage::disk('quarantine')->get($quarantinePath))) {
                throw new \DomainException('File hasil tidak dapat dipindahkan ke penyimpanan private.');
            }
        } catch (\Throwable $exception) {
            Storage::disk('quarantine')->delete($quarantinePath);
            throw $exception;
        }
        Storage::disk('quarantine')->delete($quarantinePath);
        if (app()->environment('production') && ! is_numeric(config('files.retention_days'))) {
            Storage::disk('private')->delete($path);
            throw new \DomainException('Retensi dokumen hasil belum dikonfigurasi untuk production.');
        }

        try {
            $result = $application->resultDocuments()->create([
                'type' => $type,
                'original_filename' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
                'stored_filename' => $stored,
                'storage_disk' => 'private',
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'size_bytes' => (int) $file->getSize(),
                'sha256_checksum' => hash_file('sha256', $file->getRealPath()),
                'scan_status' => 'PASSED',
                'verification_status' => ResultVerificationStatus::PENDING,
                'uploaded_by_admin_id' => $admin->getKey(),
                'uploaded_at' => now(),
                'retention_until' => is_numeric(config('files.retention_days')) ? now()->addDays((int) config('files.retention_days')) : null,
            ]);
            $this->audit->record('result.uploaded', $result, ['application_id' => $application->public_id, 'type' => $type->value], $admin);

            if ($application->status === ApplicationStatus::WAITING_EXTERNAL_PROCESS) {
                $this->transitions->transition($application, ApplicationStatus::RESULT_UPLOADED, $admin);
            }

            return $result;
        } catch (\Throwable $exception) {
            Storage::disk('private')->delete($path);
            Storage::disk('quarantine')->delete($quarantinePath);
            throw $exception;
        }
    }

    public function beginResultReview(Application $application, Admin $admin): Application
    {
        return $this->transitions->transition($application, ApplicationStatus::RESULT_REVIEW, $admin);
    }

    public function verifyResult(ResultDocument $result, Admin $admin, bool $verified, ?string $reason = null): ResultDocument
    {
        if ($result->application->status !== ApplicationStatus::RESULT_REVIEW || $result->scan_status->value !== 'PASSED' || $result->deleted_at !== null) {
            throw new \DomainException('Hasil belum dapat diverifikasi.');
        }
        if (! $verified && blank($reason)) {
            throw new \DomainException('Berikan alasan jika hasil tidak diverifikasi.');
        }

        $result->forceFill([
            'verification_status' => $verified ? ResultVerificationStatus::VERIFIED : ResultVerificationStatus::REJECTED,
            'verified_by_admin_id' => $admin->getKey(),
            'verified_at' => now(),
            'rejection_reason' => $verified ? null : $reason,
        ])->save();
        $this->audit->record('result.verification_changed', $result, ['verified' => $verified, 'reason' => $reason], $admin);

        if ($verified && $result->type === ResultDocumentType::PRIMARY_RESULT) {
            try {
                $this->notifications->resultAvailable($result);
            } catch (\Throwable $exception) {
                logger()->warning('result_available_notification_failed', [
                    'exception_class' => $exception::class,
                ]);
            }
        }

        return $result->fresh();
    }

    public function complete(Application $application, Admin $admin): Application
    {
        if ($application->status !== ApplicationStatus::RESULT_REVIEW || ! $application->hasVerifiedPrimaryResult()) {
            throw new \DomainException('Aplikasi belum memiliki hasil utama yang diverifikasi.');
        }

        return $this->transitions->transition($application, ApplicationStatus::COMPLETED, $admin);
    }

    public function archive(Application $application, Admin $admin): Application
    {
        return $this->transitions->transition($application, ApplicationStatus::ARCHIVED, $admin, 'admin_archive');
    }

    private function assertResultFile(UploadedFile $file): void
    {
        if (! $file->isValid() || ! in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'pdf'], true) || ! in_array(strtolower((string) $file->getMimeType()), config('files.allowed_mimes'), true) || (int) $file->getSize() > 15 * 1024 * 1024) {
            throw new \DomainException('File hasil tidak valid atau melebihi batas 15 MB.');
        }
        $path = $file->getRealPath();
        $header = is_string($path) && is_file($path) ? file_get_contents($path, false, null, 0, 12) : false;
        $extension = strtolower($file->getClientOriginalExtension());
        $valid = match ($extension) {
            'jpg', 'jpeg' => is_string($header) && str_starts_with($header, "\xFF\xD8\xFF"),
            'png' => is_string($header) && str_starts_with($header, "\x89PNG\x0D\x0A\x1A\x0A"),
            'pdf' => is_string($header) && str_starts_with($header, '%PDF-'),
            default => false,
        };
        if (! $valid) {
            throw new \DomainException('Isi file hasil tidak sesuai dengan formatnya.');
        }
        if ($extension === 'pdf') {
            $contents = file_get_contents($path);
            $size = filesize($path);
            $tail = is_int($size) && $size > 0 ? file_get_contents($path, false, null, max(0, $size - 1048576)) : false;
            if (! is_string($contents) || ! is_string($tail) || ! str_contains($tail, '%%EOF') || preg_match('/\/(?:JavaScript|JS|Launch|EmbeddedFile|OpenAction)\b/i', $contents)) {
                throw new \DomainException('Dokumen PDF hasil tidak dapat diproses dengan aman.');
            }
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true) && @getimagesize($path) === false) {
            throw new \DomainException('Gambar hasil tidak dapat dibaca.');
        }
    }
}
