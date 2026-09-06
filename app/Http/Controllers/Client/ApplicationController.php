<?php

namespace App\Http\Controllers\Client;

use App\Enums\ApplicationCancellationReason;
use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\CancelApplicationRequest;
use App\Http\Requests\Client\StoreApplicationRequest;
use App\Http\Requests\Client\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Service;
use App\Services\ApplicationCancellationService;
use App\Services\ApplicationWorkflowService;
use App\Support\ApplicationStatusPresenter;
use App\Support\ApplicationTimelinePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationWorkflowService $workflow,
        private readonly ApplicationCancellationService $cancellations,
    ) {}

    public function create(Request $request, string $service): View
    {
        $serviceModel = Service::with(['requirements' => fn ($query) => $query->where('active', true)])
            ->where('public_id', $service)
            ->firstOrFail();
        abort_unless($serviceModel->isBookable(), 404, 'Layanan belum tersedia.');

        return view('client.applications.create', [
            'service' => $serviceModel,
            'selectedBusinessType' => $request->string('business_type')->toString(),
        ]);
    }

    public function index(): View
    {
        $applications = Application::query()
            ->with(['service', 'chatThread', 'payments', 'statusHistories'])
            ->where('user_id', request()->user()->getKey())
            ->latest('updated_at')
            ->get();
        $activeApplications = $applications
            ->reject(fn (Application $application): bool => in_array(ApplicationStatusPresenter::category($application->status), [ApplicationStatusPresenter::CATEGORY_COMPLETED, ApplicationStatusPresenter::CATEGORY_CANCELLED], true));
        $priorityApplication = $activeApplications->first(fn (Application $application): bool => ApplicationStatusPresenter::category($application->status) === ApplicationStatusPresenter::CATEGORY_ACTION)
            ?? $activeApplications->first();
        $recentUpdates = $applications
            ->flatMap(fn (Application $application) => $application->statusHistories->map(fn ($history) => ['application' => $application, 'history' => $history]))
            ->sortByDesc(fn (array $item) => $item['history']->created_at)
            ->take(3)
            ->values();

        return view('client.dashboard', [
            'applications' => $applications,
            'activeApplications' => $activeApplications,
            'dashboardApplications' => $activeApplications->take(3),
            'priorityApplication' => $priorityApplication,
            'priorityPresentation' => $priorityApplication ? ApplicationStatusPresenter::for($priorityApplication) : null,
            'recentUpdates' => $recentUpdates,
            'services' => Service::query()->whereIn('status', ['ACTIVE', 'COMING_SOON'])->orderBy('sort_order')->get(),
        ]);
    }

    public function applicationsIndex(Request $request): View
    {
        $applications = Application::query()
            ->with(['service', 'chatThread'])
            ->where('user_id', request()->user()->getKey())
            ->latest('updated_at')
            ->get();
        $groups = [
            ApplicationStatusPresenter::CATEGORY_ACTION => $applications->filter(fn (Application $application): bool => ApplicationStatusPresenter::category($application->status) === ApplicationStatusPresenter::CATEGORY_ACTION)->values(),
            ApplicationStatusPresenter::CATEGORY_PROCESSING => $applications->filter(fn (Application $application): bool => ApplicationStatusPresenter::category($application->status) === ApplicationStatusPresenter::CATEGORY_PROCESSING)->values(),
            ApplicationStatusPresenter::CATEGORY_COMPLETED => $applications->filter(fn (Application $application): bool => ApplicationStatusPresenter::category($application->status) === ApplicationStatusPresenter::CATEGORY_COMPLETED)->values(),
            ApplicationStatusPresenter::CATEGORY_CANCELLED => $applications->filter(fn (Application $application): bool => ApplicationStatusPresenter::category($application->status) === ApplicationStatusPresenter::CATEGORY_CANCELLED)->values(),
        ];
        $filter = $request->string('status')->toString();
        $filter = in_array($filter, array_keys($groups), true) ? $filter : 'all';

        return view('client.applications.index', [
            'applications' => $filter === 'all' ? $applications : $groups[$filter],
            'counts' => collect($groups)->map->count(),
            'filter' => $filter,
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $service = Service::where('public_id', $request->string('service_public_id')->toString())->firstOrFail();
        abort_unless($service->code === $request->string('kind')->toString(), 422, 'Jenis layanan tidak sesuai.');
        abort_unless($service->isBookable(), 422, 'Layanan belum dapat dipesan.');

        $details = $request->only(['name', 'nik', 'family_card_number', 'email', 'marital_status', 'family_status', 'gender', 'business_name', 'business_type', 'business_type_other', 'purpose']);
        $application = $this->workflow->createDraft($request->user(), $service, $details, $request->input('representative'), $request->input('additional_representative'));

        return redirect()->route('client.applications.show', $application->public_id)
            ->with('status', 'Draft pengajuan berhasil dibuat. Lanjutkan data dan dokumen Anda.');
    }

    public function show(string $publicId): View
    {
        $application = $this->find($publicId);
        $this->authorize('view', $application);

        $application->load(['service', 'requirements.documents', 'personalDetails', 'businessDetails', 'representatives', 'payments', 'statusHistories', 'estimateHistories', 'resultDocuments', 'chatThread']);

        return view('client.applications.show', [
            'application' => $application,
            'statusPresentation' => ApplicationStatusPresenter::for($application),
            'timeline' => ApplicationTimelinePresenter::for($application),
            'canCancel' => $this->cancellations->canBeCancelledByClient($application, request()->user()),
            'cancellationReasons' => ApplicationCancellationReason::options(),
            'cancellationHistory' => $application->statusHistories
                ->first(fn ($history): bool => $history->to_status === ApplicationStatus::CANCELLED),
        ]);
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
        if ($application->status === ApplicationStatus::CANCELLED) {
            return redirect()->route('client.applications.show', $application->public_id)
                ->withErrors(['payment' => 'Pembayaran tidak dapat dilanjutkan untuk pengajuan yang telah dibatalkan.']);
        }
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

    public function cancel(CancelApplicationRequest $request, string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('cancel', $application);
        $reason = ApplicationCancellationReason::from($request->string('reason')->toString());
        $this->cancellations->cancel($application, $request->user(), $reason, $request->input('reason_other'));

        return redirect()->route('client.applications.show', $application->public_id)
            ->with('status', 'Pengajuan telah dibatalkan. Riwayatnya tetap tersimpan.');
    }

    private function find(string $publicId): Application
    {
        return Application::query()->where('public_id', $publicId)->where('user_id', request()->user()->getKey())->firstOrFail();
    }
}
