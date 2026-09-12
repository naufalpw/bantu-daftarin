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
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;

class DocumentWorkflowService
{
    private const DELETION_CLAIM_RETRY_MINUTES = 15;

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

                $existingDocuments = $lockedApplication->documents()
                    ->where('application_requirement_id', $lockedRequirement->getKey())
                    ->lockForUpdate()
                    ->get();
                $lastVersion = (int) $existingDocuments->max('version_number');
                Document::query()
                    ->whereKey($existingDocuments->modelKeys())
                    ->where('active', true)
                    ->update(['active' => false]);
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
        $identity = Document::query()
            ->select(['id', 'application_id', 'application_requirement_id'])
            ->findOrFail($document->getKey());

        return DB::transaction(function () use ($identity, $admin, $action, $reason, $instruction): Document {
            $lockedApplication = Application::query()->lockForUpdate()->findOrFail($identity->application_id);
            $lockedRequirement = ApplicationRequirement::query()->lockForUpdate()->findOrFail($identity->application_requirement_id);
            $locked = Document::query()->lockForUpdate()->findOrFail($identity->getKey());

            if ($lockedRequirement->application_id !== $lockedApplication->getKey()
                || $locked->application_id !== $lockedApplication->getKey()
                || $locked->application_requirement_id !== $lockedRequirement->getKey()
                || $lockedApplication->status !== ApplicationStatus::UNDER_REVIEW
                || ! $locked->active
                || $locked->scan_status !== DocumentScanStatus::PASSED
                || $locked->review_status !== DocumentReviewStatus::PENDING
                || $locked->deletion_scheduled_at !== null
                || $locked->deleted_at !== null) {
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

            $lockedRequirement->forceFill([
                'status' => $action === DocumentReviewAction::ACCEPT ? 'ACCEPTED' : 'REVISION_REQUIRED',
            ])->save();

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

    public function destroy(Document $document, User $actor, ?Request $request = null): Document
    {
        $identity = Document::query()
            ->select(['id', 'application_id', 'application_requirement_id'])
            ->findOrFail($document->getKey());

        $claim = DB::transaction(function () use ($identity, $actor): array {
            [$lockedApplication, $lockedRequirement, $locked] = $this->lockDocumentHierarchy($identity);

            if (! $actor->isClient() || $lockedApplication->user_id !== $actor->getKey()) {
                throw new AuthorizationException;
            }

            if (! $locked->active || $locked->deleted_at !== null) {
                throw (new ModelNotFoundException)->setModel(Document::class, [$locked->getKey()]);
            }

            $claimExpired = $locked->deletion_scheduled_at === null
                || $locked->deletion_scheduled_at->lessThanOrEqualTo(now()->subMinutes(self::DELETION_CLAIM_RETRY_MINUTES));
            if (! $claimExpired || ! $this->canClientUpload($lockedApplication, $lockedRequirement)) {
                throw new AuthorizationException('Dokumen terkunci.');
            }

            $claimedAt = now();
            $locked->forceFill(['deletion_scheduled_at' => $claimedAt])->save();

            return [
                'id' => $locked->getKey(),
                'application_id' => $lockedApplication->getKey(),
                'application_requirement_id' => $lockedRequirement->getKey(),
                'storage_disk' => $locked->storage_disk,
                'storage_path' => $locked->storage_path,
                'claimed_at' => $claimedAt->toIso8601String(),
            ];
        });

        try {
            $deleted = Storage::disk($claim['storage_disk'])->delete($claim['storage_path']);
        } catch (UnableToDeleteFile $exception) {
            $this->releaseDestroyClaim($claim);

            throw new \DomainException('Dokumen belum dapat dihapus. Silakan coba lagi.', previous: $exception);
        }

        if (! $deleted) {
            $this->releaseDestroyClaim($claim);

            throw new \DomainException('Dokumen belum dapat dihapus. Silakan coba lagi.');
        }

        return DB::transaction(function () use ($identity, $claim, $actor, $request): Document {
            [, , $locked] = $this->lockDocumentHierarchy($identity);
            if (! $this->ownsDestroyClaim($locked, $claim)) {
                throw new \LogicException('Document deletion claim changed before finalization.');
            }

            $locked->forceFill([
                'active' => false,
                'deleted_at' => now(),
                'deletion_reason' => 'client_deleted_before_payment',
            ])->save();
            $this->audit->record('document.deleted', $locked, ['reason' => 'client_deleted_before_payment'], $actor, $request);

            return $locked->fresh(['application', 'requirement']);
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

    /**
     * @return array{0:Application,1:ApplicationRequirement,2:Document}
     */
    private function lockDocumentHierarchy(Document $identity): array
    {
        $lockedApplication = Application::query()->lockForUpdate()->findOrFail($identity->application_id);
        $lockedRequirement = ApplicationRequirement::query()->lockForUpdate()->findOrFail($identity->application_requirement_id);
        $locked = Document::query()->lockForUpdate()->findOrFail($identity->getKey());

        if ($lockedRequirement->application_id !== $lockedApplication->getKey()
            || $locked->application_id !== $lockedApplication->getKey()
            || $locked->application_requirement_id !== $lockedRequirement->getKey()) {
            throw new \DomainException('Dokumen tidak terkait dengan pengajuan ini.');
        }

        return [$lockedApplication, $lockedRequirement, $locked];
    }

    /** @param array{id:int,application_id:int,application_requirement_id:int,storage_disk:string,storage_path:string,claimed_at:string} $claim */
    private function releaseDestroyClaim(array $claim): void
    {
        $identity = Document::query()
            ->select(['id', 'application_id', 'application_requirement_id'])
            ->find($claim['id']);
        if (! $identity) {
            return;
        }

        DB::transaction(function () use ($identity, $claim): void {
            [, , $locked] = $this->lockDocumentHierarchy($identity);
            if ($locked->deleted_at === null && $this->ownsDestroyClaim($locked, $claim)) {
                $locked->forceFill(['deletion_scheduled_at' => null])->save();
            }
        });
    }

    /** @param array{claimed_at:string} $claim */
    private function ownsDestroyClaim(Document $document, array $claim): bool
    {
        return $document->deletion_scheduled_at !== null
            && $document->deletion_scheduled_at->equalTo(CarbonImmutable::parse($claim['claimed_at']));
    }
}
