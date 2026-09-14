<?php

namespace App\Http\Controllers\Client;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UploadDocumentRequest;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Services\AuditService;
use App\Services\DocumentWorkflowService;
use App\Services\PrivateFileReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentWorkflowService $documents,
        private readonly AuditService $audit,
        private readonly PrivateFileReader $files,
    ) {}

    public function store(UploadDocumentRequest $request, string $applicationId, string $requirementId): RedirectResponse
    {
        $application = Application::where('public_id', $applicationId)->where('user_id', $request->user()->getKey())->firstOrFail();
        $this->authorize('view', $application);
        $requirement = ApplicationRequirement::where('public_id', $requirementId)->where('application_id', $application->getKey())->firstOrFail();
        $this->documents->upload($application, $requirement, $request->file('file'), $request->user());

        $message = $application->fresh()->status === ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT
            ? 'Dokumen lengkap. Silakan lanjut ke pembayaran.'
            : 'Dokumen berhasil diunggah dan menunggu pemeriksaan.';

        return back()->with('status', $message);
    }

    public function destroy(Request $request, string $documentId): RedirectResponse
    {
        $document = Document::where('public_id', $documentId)->firstOrFail();
        $this->authorize('delete', $document);
        $this->documents->destroy($document, $request->user(), $request);

        return back()->with('status', 'Dokumen dihapus dari versi aktif.');
    }

    public function download(Request $request, string $documentId)
    {
        [$document, $stream] = $this->files->openDocument($documentId, $request->user());
        $this->recordAccess($document, $request, 'DOWNLOAD');
        $downloadName = $this->downloadName($document->original_filename, $document->extension, 'document');

        return response()->streamDownload(fn () => $this->stream($stream), $downloadName, ['Content-Type' => $document->mime_type]);
    }

    public function preview(Request $request, string $documentId)
    {
        [$document, $stream] = $this->files->openDocument($documentId, $request->user());
        $this->recordAccess($document, $request, 'VIEW');
        $downloadName = $this->downloadName($document->original_filename, $document->extension, 'document');

        return response()->stream(fn () => $this->stream($stream), 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function recordAccess(Document $document, Request $request, string $action): void
    {
        $this->audit->record('document.accessed', $document, ['action' => $action], $request->user(), $request);
        DocumentAccessLog::create([
            'document_id' => $document->getKey(),
            'actor_type' => $request->user()->isAdmin() ? 'admin' : 'user',
            'actor_id' => $request->user()->isAdmin() ? $request->user()->admin?->getKey() : $request->user()->getKey(),
            'action' => $action,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
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
