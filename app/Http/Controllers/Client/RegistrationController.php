<?php

namespace App\Http\Controllers\Client;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function businessTypes(): View
    {
        return view('client.registration.business-type', [
            'businessService' => Service::query()->where('code', 'NPWP_BUSINESS')->firstOrFail(),
            'businessTypes' => BusinessType::cases(),
        ]);
    }

    public function personalEntry(): RedirectResponse
    {
        $application = $this->openApplication('NPWP_PERSONAL');
        if ($application) {
            return redirect()->route('npwp.personal.application', $application->public_id);
        }

        $service = Service::query()->where('code', 'NPWP_PERSONAL')->firstOrFail();

        return redirect()->route('client.applications.create', $service->public_id);
    }

    public function businessEntry(): RedirectResponse
    {
        return redirect()->route('npwp.business.types');
    }

    public function personal(string $publicId): View
    {
        $application = $this->application($publicId, 'NPWP_PERSONAL');
        $this->authorize('update', $application);

        return view('client.registration.personal', ['application' => $application]);
    }

    public function business(string $publicId): View
    {
        $application = $this->application($publicId, 'NPWP_BUSINESS');
        $this->authorize('update', $application);

        return view('client.registration.business', ['application' => $application]);
    }

    private function application(string $publicId, string $serviceCode): Application
    {
        $application = Application::query()
            ->where('public_id', $publicId)
            ->where('user_id', request()->user()->getKey())
            ->with(['service', 'requirements.documents', 'personalDetails', 'businessDetails', 'representatives'])
            ->firstOrFail();

        abort_unless($application->service->code === $serviceCode, 404);

        return $application;
    }

    private function openApplication(string $serviceCode): ?Application
    {
        return Application::query()
            ->where('user_id', request()->user()->getKey())
            ->whereHas('service', fn ($query) => $query->where('code', $serviceCode))
            ->whereIn('status', [ApplicationStatus::DRAFT->value, ApplicationStatus::AWAITING_DOCUMENTS->value])
            ->latest('id')
            ->first();
    }
}
