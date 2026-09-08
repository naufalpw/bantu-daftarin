<?php

namespace App\Http\Controllers\Client;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;

class RegistrationController extends Controller
{
    public function businessTypes(): RedirectResponse
    {
        $service = Service::query()->where('code', 'NPWP_BUSINESS')->firstOrFail();

        return redirect()->route('client.applications.create', $service->public_id);
    }

    public function personalEntry(): RedirectResponse
    {
        $application = $this->openApplication('NPWP_PERSONAL');
        if ($application) {
            return redirect()->to(route('client.applications.show', $application->public_id).'#data-dokumen');
        }

        $service = Service::query()->where('code', 'NPWP_PERSONAL')->firstOrFail();

        return redirect()->route('client.applications.create', $service->public_id);
    }

    public function businessEntry(): RedirectResponse
    {
        $application = $this->openApplication('NPWP_BUSINESS');
        if ($application) {
            return redirect()->to(route('client.applications.show', $application->public_id).'#data-dokumen');
        }

        $service = Service::query()->where('code', 'NPWP_BUSINESS')->firstOrFail();

        return redirect()->route('client.applications.create', $service->public_id);
    }

    public function personal(string $publicId): RedirectResponse
    {
        $application = $this->application($publicId, 'NPWP_PERSONAL');
        $this->authorize('view', $application);

        return redirect()->to(route('client.applications.show', $application->public_id).'#data-dokumen');
    }

    public function business(string $publicId): RedirectResponse
    {
        $application = $this->application($publicId, 'NPWP_BUSINESS');
        $this->authorize('view', $application);

        return redirect()->to(route('client.applications.show', $application->public_id).'#data-dokumen');
    }

    private function application(string $publicId, string $serviceCode): Application
    {
        $application = Application::query()
            ->where('public_id', $publicId)
            ->where('user_id', request()->user()->getKey())
            ->with('service')
            ->firstOrFail();

        abort_unless($application->service->code === $serviceCode, 404);

        return $application;
    }

    private function openApplication(string $serviceCode): ?Application
    {
        return Application::query()
            ->where('user_id', request()->user()->getKey())
            ->whereHas('service', fn ($query) => $query->where('code', $serviceCode))
            ->whereNotIn('status', [ApplicationStatus::COMPLETED->value, ApplicationStatus::ARCHIVED->value, ApplicationStatus::CANCELLED->value])
            ->latest('id')
            ->first();
    }
}
