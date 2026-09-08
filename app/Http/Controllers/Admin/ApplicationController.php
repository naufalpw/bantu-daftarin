<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentReviewAction;
use App\Enums\ResultDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstimateRequest;
use App\Http\Requests\Admin\ReviewDocumentRequest;
use App\Http\Requests\Admin\UploadResultRequest;
use App\Http\Requests\Admin\VerifyResultRequest;
use App\Models\Application;
use App\Models\Document;
use App\Models\ResultDocument;
use App\Services\AdminWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private readonly AdminWorkflowService $workflow) {}

    public function index(): View
    {
        return view('admin.applications.index');
    }

    public function show(string $publicId): View
    {
        $application = $this->find($publicId);
        $this->authorize('view', $application);
        $application->load([
            'user', 'service', 'requirements.documents.reviews', 'personalDetails', 'businessDetails', 'representatives',
            'payments', 'statusHistories', 'estimateHistories.admin', 'resultDocuments', 'chatThread',
        ]);

        return view('admin.applications.show', ['application' => $application]);
    }

    public function beginReview(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->beginReview($application, request()->user()->admin);

        return back()->with('status', 'Pengajuan masuk ke tahap pemeriksaan.');
    }

    public function reviewDocument(ReviewDocumentRequest $request, string $documentId): RedirectResponse
    {
        $document = Document::where('public_id', $documentId)->firstOrFail()->load(['application', 'requirement']);
        $this->authorize('review', $document);
        $action = DocumentReviewAction::from($request->string('action')->toString());
        $this->workflow->reviewDocument($document, $request->user()->admin, $action, $request->input('reason'), $request->input('instruction'));

        return back()->with('status', 'Keputusan dokumen tersimpan.');
    }

    public function finalizeReview(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->finalizeReview($application, request()->user()->admin, request()->input('reason'));

        return back()->with('status', 'Hasil pemeriksaan pengajuan tersimpan.');
    }

    public function estimate(EstimateRequest $request, string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->setEstimate($application, request()->user()->admin, new \DateTimeImmutable($request->string('estimated_completion_at')->toString()), $request->string('reason')->toString());

        return back()->with('status', 'Estimasi tersimpan.');
    }

    public function waitingExternal(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->markWaitingExternal($application, request()->user()->admin);

        return back()->with('status', 'Status proses eksternal diperbarui.');
    }

    public function uploadResult(UploadResultRequest $request, string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->uploadResult($application, request()->user()->admin, $request->file('file'), ResultDocumentType::from($request->string('type')->toString()));

        return back()->with('status', 'Dokumen hasil berhasil diunggah.');
    }

    public function beginResultReview(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->beginResultReview($application, request()->user()->admin);

        return back()->with('status', 'Hasil masuk ke tahap pemeriksaan.');
    }

    public function verifyResult(VerifyResultRequest $request, string $resultId): RedirectResponse
    {
        $result = ResultDocument::where('public_id', $resultId)->firstOrFail();
        $this->authorize('adminAction', $result);
        $this->workflow->verifyResult($result, request()->user()->admin, $request->boolean('verified'), $request->input('reason'));

        return back()->with('status', 'Verifikasi hasil tersimpan.');
    }

    public function complete(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->complete($application, request()->user()->admin);

        return back()->with('status', 'Pengajuan ditandai selesai.');
    }

    public function archive(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('adminAction', $application);
        $this->workflow->archive($application, request()->user()->admin);

        return back()->with('status', 'Pengajuan diarsipkan.');
    }

    private function find(string $publicId): Application
    {
        return Application::where('public_id', $publicId)->firstOrFail();
    }
}
