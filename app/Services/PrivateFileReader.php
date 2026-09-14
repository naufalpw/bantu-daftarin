<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\ResultDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PrivateFileReader
{
    /** @return array{0:Document,1:resource} */
    public function openDocument(string $publicId, User $user): array
    {
        return DB::transaction(function () use ($publicId, $user): array {
            $identity = Document::query()->where('public_id', $publicId)->firstOrFail();
            $application = Application::query()->lockForUpdate()->findOrFail($identity->application_id);
            ApplicationRequirement::query()->lockForUpdate()->findOrFail($identity->application_requirement_id);
            $document = Document::query()->lockForUpdate()->findOrFail($identity->getKey());

            abort_unless(
                $document->application_id === $application->getKey()
                    && $document->application_requirement_id === $identity->application_requirement_id,
                404
            );

            $document->setRelation('application', $application);
            Gate::forUser($user)->authorize('download', $document);
            $stream = Storage::disk($document->storage_disk)->readStream($document->storage_path);
            abort_unless(is_resource($stream), 404);

            return [$document, $stream];
        });
    }

    /** @return array{0:ResultDocument,1:resource} */
    public function openResult(string $publicId, User $user): array
    {
        return DB::transaction(function () use ($publicId, $user): array {
            $identity = ResultDocument::query()->where('public_id', $publicId)->firstOrFail();
            $application = Application::query()->lockForUpdate()->findOrFail($identity->application_id);
            $result = ResultDocument::query()->lockForUpdate()->findOrFail($identity->getKey());

            abort_unless($result->application_id === $application->getKey(), 404);

            $result->setRelation('application', $application);
            Gate::forUser($user)->authorize('download', $result);
            $stream = Storage::disk($result->storage_disk)->readStream($result->storage_path);
            abort_unless(is_resource($stream), 404);

            return [$result, $stream];
        });
    }
}
