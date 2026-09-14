<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\ResultDocument;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;

class ExpiredFilePurger
{
    private const CLAIM_RETRY_MINUTES = 15;

    public function __construct(private readonly AuditService $audit) {}

    public function purge(string $modelClass, int $fileId): bool
    {
        $claim = $this->claim($modelClass, $fileId);
        if ($claim === null) {
            return false;
        }

        try {
            $deleted = Storage::disk($claim['storage_disk'])->delete($claim['storage_path']);
        } catch (UnableToDeleteFile $exception) {
            $this->release($claim);

            throw $exception;
        }

        if (! $deleted) {
            $this->release($claim);

            return false;
        }

        $this->finalize($claim);

        return true;
    }

    /**
     * Claim an expired file under the same parent-first locks used by workflow writers.
     *
     * @return array{model_class:class-string<Document|ResultDocument>,id:int,storage_disk:string,storage_path:string,claimed_at:string}|null
     */
    public function claim(string $modelClass, int $fileId): ?array
    {
        $this->assertSupportedModel($modelClass);

        return DB::transaction(function () use ($modelClass, $fileId): ?array {
            $file = $this->lockFile($modelClass, $fileId);
            if (! $file || ! $this->isEligible($file)) {
                return null;
            }

            $claimedAt = now();
            $file->forceFill(['deletion_scheduled_at' => $claimedAt])->save();

            return [
                'model_class' => $modelClass,
                'id' => $file->getKey(),
                'storage_disk' => $file->storage_disk,
                'storage_path' => $file->storage_path,
                'claimed_at' => $claimedAt->toIso8601String(),
            ];
        });
    }

    /** @param array{model_class:class-string<Document|ResultDocument>,id:int,storage_disk:string,storage_path:string,claimed_at:string} $claim */
    public function finalize(array $claim): void
    {
        DB::transaction(function () use ($claim): void {
            $file = $this->lockFile($claim['model_class'], $claim['id']);
            if (! $file || ! $this->isOwnedClaim($file, $claim)) {
                throw new \LogicException('File purge claim changed before finalization.');
            }

            $file->forceFill([
                'deleted_at' => now(),
                'deletion_reason' => 'retention_expired',
            ])->save();

            $this->audit->record('file.purged', $file, ['reason' => 'retention_expired']);
        });
    }

    /** @param array{model_class:class-string<Document|ResultDocument>,id:int,storage_disk:string,storage_path:string,claimed_at:string} $claim */
    public function release(array $claim): void
    {
        DB::transaction(function () use ($claim): void {
            $file = $this->lockFile($claim['model_class'], $claim['id']);
            if ($file && $file->deleted_at === null && $this->isOwnedClaim($file, $claim)) {
                $file->forceFill(['deletion_scheduled_at' => null])->save();
            }
        });
    }

    /** @param class-string<Document|ResultDocument> $modelClass */
    private function lockFile(string $modelClass, int $fileId): Document|ResultDocument|null
    {
        $identity = $modelClass::query()->find($fileId);
        if (! $identity) {
            return null;
        }

        Application::query()->lockForUpdate()->findOrFail($identity->application_id);

        if ($modelClass === Document::class) {
            ApplicationRequirement::query()->lockForUpdate()->findOrFail($identity->application_requirement_id);
        }

        $file = $modelClass::query()->lockForUpdate()->find($fileId);
        if (! $file || $file->application_id !== $identity->application_id) {
            return null;
        }

        if ($file instanceof Document && $file->application_requirement_id !== $identity->application_requirement_id) {
            return null;
        }

        return $file;
    }

    private function isEligible(Document|ResultDocument $file): bool
    {
        $claimExpired = $file->deletion_scheduled_at === null
            || $file->deletion_scheduled_at->lessThanOrEqualTo(now()->subMinutes(self::CLAIM_RETRY_MINUTES));

        return $file->deleted_at === null
            && $file->retention_until?->isPast()
            && $claimExpired;
    }

    /** @param array{claimed_at:string} $claim */
    private function isOwnedClaim(Model $file, array $claim): bool
    {
        return $file->deletion_scheduled_at !== null
            && $file->deletion_scheduled_at->equalTo(CarbonImmutable::parse($claim['claimed_at']));
    }

    private function assertSupportedModel(string $modelClass): void
    {
        if (! in_array($modelClass, [Document::class, ResultDocument::class], true)) {
            throw new \InvalidArgumentException('Unsupported file model for retention purge.');
        }
    }
}
