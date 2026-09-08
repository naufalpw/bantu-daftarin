<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewAction;
use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentWorkflowService
{
    public function __construct(
        private readonly DocumentFileService $files,
        private readonly AuditService $audit,
        private readonly ApplicationTransitionService $transitions,
    ) {}

    public function upload(Application $application, ApplicationRequirement $requirement, UploadedFile $file, User $actor): Document
    {
        if ($application->user_id !== $actor->getKey() || $requirement->application_id !== $application->getKey()) {
            throw new \DomainException('Dokumen tidak terkait dengan pengajuan ini.');
        }
        if (! $this->canClientUpload($application, $requirement)) {
            throw new \DomainException('Dokumen terkunci pada tahap ini atau belum dibuka untuk revisi.');
        }

        $metadata = $this->files->store($file, $application, $requirement);

        try {
            return DB::transaction(function () use ($application, $requirement, $metadata, $actor): Document {
                $lockedApplication = Application::query()->lockForUpdate()->findOrFail($application->getKey());
                $lockedRequirement = ApplicationRequirement::query()->lockForUpdate()->findOrFail($requirement->getKey());
                if ($lockedRequirement->application_id !== $lockedApplication->getKey() || ! $this->canClientUpload($lockedApplication, $lockedRequirement)) {
                    throw new \DomainException('Dokumen terkunci pada tahap ini atau belum dibuka untuk revisi.');
                }
                $lastVersion = (int) $lockedApplication->documents()->where('application_requirement_id', $lockedRequirement->getKey())->max('version_number');
                $lockedApplication->documents()->where('application_requirement_id', $lockedRequirement->getKey())->where('active', true)->update(['active' => false]);
                $document = $lockedApplication->documents()->create(array_merge($metadata, [
                    'application_requirement_id' => $lockedRequirement->getKey(),
                    'version_number' => $lastVersion + 1,
                    'review_status' => DocumentReviewStatus::PENDING,
                    'uploaded_by_user_id' => $actor->getKey(),
                    'uploaded_at' => now(),
                    'active' => true,
                ]));
                $lockedRequirement->forceFill(['status' => 'PENDING'])->save();
                $this->audit->record('document.uploaded', $document, [
                    'application_id' => $application->public_id,
                    'requirement_code' => $lockedRequirement->code,
                    'version_number' => $document->version_number,
                    'checksum' => $document->sha256_checksum,
                ], $actor);

                if ($lockedApplication->status === ApplicationStatus::AWAITING_DOCUMENTS && $lockedApplication->hasAllRequiredDocuments()) {
                    $this->transitions->transition(
                        $lockedApplication,
                        ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
                        $actor,
                        'Seluruh dokumen wajib berhasil diunggah.',
                    );
                }

                return $document;
            });
        } catch (\Throwable $exception) {
            if (isset($metadata['storage_path'])) {
                Storage::disk($metadata['storage_disk'])->delete($metadata['storage_path']);
            }
            throw $exception;
        }
    }

    public function review(Document $document, Admin $admin, DocumentReviewAction $action, ?string $reason = null, ?string $instruction = null): Document
    {
        if ($document->application->status !== ApplicationStatus::UNDER_REVIEW || ! $document->active || $document->scan_status->value !== 'PASSED' || $document->review_status !== DocumentReviewStatus::PENDING) {
            throw new \DomainException('Dokumen tidak dapat diperiksa.');
        }

        return DB::transaction(function () use ($document, $admin, $action, $reason, $instruction): Document {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->getKey());
            $lockedApplication = Application::query()->lockForUpdate()->findOrFail($locked->application_id);
            if ($lockedApplication->status !== ApplicationStatus::UNDER_REVIEW || ! $locked->active || $locked->scan_status !== DocumentScanStatus::PASSED || $locked->review_status !== DocumentReviewStatus::PENDING) {
                throw new \DomainException('Dokumen tidak dapat diperiksa.');
            }
            $status = match ($action) {
                DocumentReviewAction::ACCEPT => DocumentReviewStatus::LOCKED,
                DocumentReviewAction::REJECT => DocumentReviewStatus::REJECTED,
                DocumentReviewAction::REQUEST_REVISION => DocumentReviewStatus::REVISION_REQUIRED,
            };

            if ($action !== DocumentReviewAction::ACCEPT && blank($reason)) {
                throw new \DomainException('Berikan alasan untuk dokumen yang ditolak atau perlu diperbaiki.');
            }

            $locked->forceFill([
                'review_status' => $status,
                'reviewed_by_admin_id' => $admin->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => $action === DocumentReviewAction::ACCEPT ? null : $reason,
                'revision_instruction' => $action !== DocumentReviewAction::ACCEPT ? $instruction : null,
            ])->save();

            $locked->requirement()->update([
                'status' => $action === DocumentReviewAction::ACCEPT ? 'ACCEPTED' : 'REVISION_REQUIRED',
            ]);

            DocumentReview::create([
                'document_id' => $locked->getKey(),
                'reviewer_admin_id' => $admin->getKey(),
                'action' => $action,
                'reason' => $reason,
                'instruction' => $instruction,
            ]);
            $this->audit->record('document.reviewed', $locked, ['action' => $action->value, 'reason' => $reason], $admin);

            return $locked->fresh(['requirement', 'application']);
        });
    }

    public function canClientUpload(Application $application, ApplicationRequirement $requirement): bool
    {
        if (! $requirement->active) {
            return false;
        }
        if (in_array($application->status, [ApplicationStatus::DRAFT, ApplicationStatus::AWAITING_DOCUMENTS, ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT, ApplicationStatus::AWAITING_PAYMENT], true)) {
            return true;
        }

        return $application->status === ApplicationStatus::REVISION_REQUIRED && $requirement->status === 'REVISION_REQUIRED';
    }
}
