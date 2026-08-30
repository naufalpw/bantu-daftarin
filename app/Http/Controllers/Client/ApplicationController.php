<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreApplicationRequest;
use App\Http\Requests\Client\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Service;
use App\Services\ApplicationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationWorkflowService $workflow) {}

    public function create(string $service): View
    {
        $serviceModel = Service::where('public_id', $service)->firstOrFail();
        abort_unless($serviceModel->isBookable(), 404, 'Layanan belum tersedia.');

        return view('client.applications.create', ['service' => $serviceModel]);
    }

    public function index(): View
    {
        return view('client.dashboard', [
            'applications' => Application::with(['service'])->where('user_id', request()->user()->getKey())->latest()->paginate(10),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $service = Service::where('public_id', $request->string('service_public_id')->toString())->firstOrFail();
        abort_unless($service->code === $request->string('kind')->toString(), 422, 'Jenis layanan tidak sesuai.');
        abort_unless($service->isBookable(), 422, 'Layanan belum dapat dipesan.');

        $details = $request->only(['name', 'email', 'marital_status', 'family_status', 'gender', 'business_name', 'business_type', 'purpose']);
        $application = $this->workflow->createDraft($request->user(), $service, $details, $request->input('representative'), $request->input('additional_representative'));

        return redirect()->route('client.applications.show', $application->public_id)->with('status', 'Draft aplikasi berhasil dibuat.');
    }

    public function show(string $publicId): View
    {
        $application = $this->find($publicId);
        $this->authorize('view', $application);

        return view('client.applications.show', ['application' => $application->load(['service', 'requirements.documents', 'personalDetails', 'businessDetails', 'representatives', 'payments', 'statusHistories', 'estimateHistories', 'resultDocuments'])]);
    }

    public function update(UpdateApplicationRequest $request, string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('update', $application);
        $details = $request->only(['name', 'email', 'marital_status', 'family_status', 'gender', 'business_name', 'business_type', 'purpose']);
        $this->workflow->saveDetails($application, $details, $request->input('representative'), $request->user(), $request->input('additional_representative'));

        return back()->with('status', 'Data aplikasi tersimpan.');
    }

    public function submit(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('submit', $application);
        $this->workflow->submitForDocuments($application, request()->user());

        return back()->with('status', 'Aplikasi masuk ke tahap pengumpulan dokumen.');
    }

    public function payment(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('submit', $application);
        $payment = $this->workflow->createPayment($application, request()->user());

        return redirect()->away($payment->checkout_url);
    }

    public function submitDocuments(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('submit', $application);
        $this->workflow->submitDocuments($application, request()->user());

        return back()->with('status', 'Dokumen dikirim untuk pemeriksaan admin.');
    }

    public function submitRevision(string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('submit', $application);
        $this->workflow->submitRevision($application, request()->user());

        return back()->with('status', 'Perbaikan dokumen dikirim.');
    }

    private function find(string $publicId): Application
    {
        return Application::query()->where('public_id', $publicId)->where('user_id', request()->user()->getKey())->firstOrFail();
    }
}
