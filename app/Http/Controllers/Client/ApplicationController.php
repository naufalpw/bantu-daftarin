<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Enums\PaymentMethod;
use App\Http\Requests\Client\StoreApplicationRequest;
use App\Http\Requests\Client\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Service;
use App\Services\ApplicationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationWorkflowService $workflow) {}

    public function create(Request $request, string $service): View
    {
        $serviceModel = Service::where('public_id', $service)->firstOrFail();
        abort_unless($serviceModel->isBookable(), 404, 'Layanan belum tersedia.');

        return view('client.applications.create', [
            'service' => $serviceModel,
            'selectedBusinessType' => $request->string('business_type')->toString(),
        ]);
    }

    public function index(): View
    {
        return view('client.dashboard', [
            'applications' => Application::with(['service', 'chatThread'])->where('user_id', request()->user()->getKey())->latest()->paginate(10),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $service = Service::where('public_id', $request->string('service_public_id')->toString())->firstOrFail();
        abort_unless($service->code === $request->string('kind')->toString(), 422, 'Jenis layanan tidak sesuai.');
        abort_unless($service->isBookable(), 422, 'Layanan belum dapat dipesan.');

        $details = $request->only(['name', 'nik', 'family_card_number', 'email', 'marital_status', 'family_status', 'gender', 'business_name', 'business_type', 'business_type_other', 'purpose']);
        $application = $this->workflow->createDraft($request->user(), $service, $details, $request->input('representative'), $request->input('additional_representative'));

        return redirect()->route($service->code === 'NPWP_PERSONAL' ? 'npwp.personal.application' : 'npwp.business.application', $application->public_id)->with('status', 'Draft aplikasi berhasil dibuat.');
    }

    public function show(string $publicId): View
    {
        $application = $this->find($publicId);
        $this->authorize('view', $application);

        return view('client.applications.show', ['application' => $application->load(['service', 'requirements.documents', 'personalDetails', 'businessDetails', 'representatives', 'payments', 'statusHistories', 'estimateHistories', 'resultDocuments', 'chatThread'])]);
    }

    public function update(UpdateApplicationRequest $request, string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('update', $application);
        $details = $request->only(['name', 'nik', 'family_card_number', 'email', 'marital_status', 'family_status', 'gender', 'business_name', 'business_type', 'business_type_other', 'purpose']);
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

    public function payment(Request $request, string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('submit', $application);
        $method = PaymentMethod::tryFrom(strtoupper($request->string('payment_method')->toString())) ?? PaymentMethod::BCA;
        $this->workflow->createPayment($application, $request->user(), $method);

        return redirect()->route('client.payments.show', $application->public_id);
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
