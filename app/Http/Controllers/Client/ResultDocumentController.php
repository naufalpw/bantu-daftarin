<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ResultDocument;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResultDocumentController extends Controller
{
    public function download(Request $request, string $resultId)
    {
        $result = ResultDocument::where('public_id', $resultId)->firstOrFail();
        $this->authorize('download', $result);
        $disk = Storage::disk($result->storage_disk);
        abort_unless($disk->exists($result->storage_path), 404);
        $stream = $disk->readStream($result->storage_path);
        abort_unless(is_resource($stream), 404);
        $this->recordAccess($result, $request, 'DOWNLOAD');
        $downloadName = $this->downloadName($result->original_filename, $result->extension, 'result');

        return response()->streamDownload(fn () => $this->stream($stream), $downloadName, ['Content-Type' => $result->mime_type]);
    }

    public function preview(Request $request, string $resultId)
    {
        $result = ResultDocument::where('public_id', $resultId)->firstOrFail();
        $this->authorize('download', $result);
        $disk = Storage::disk($result->storage_disk);
        abort_unless($disk->exists($result->storage_path), 404);
        $stream = $disk->readStream($result->storage_path);
        abort_unless(is_resource($stream), 404);
        $this->recordAccess($result, $request, 'VIEW');
        $downloadName = $this->downloadName($result->original_filename, $result->extension, 'result');

        return response()->stream(fn () => $this->stream($stream), 200, [
            'Content-Type' => $result->mime_type,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function recordAccess(ResultDocument $result, Request $request, string $action): void
    {
        app(AuditService::class)->record('result.accessed', $result, ['action' => $action], $request->user(), $request);
    }

    private function downloadName(?string $originalName, string $extension, string $fallback): string
    {
        $name = str_replace(['\\', '/'], '_', (string) $originalName);
        $name = trim((string) preg_replace('/[^A-Za-z0-9._ -]/', '_', $name), '. ');

        return $name !== '' ? $name : $fallback.'.'.$extension;
    }

    private function stream($stream): void
    {
        try {
            fpassthru($stream);
        } finally {
            fclose($stream);
        }
    }
}
