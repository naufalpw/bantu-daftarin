<?php

namespace Tests\Feature\Client;

use App\Enums\ApplicationStatus;
use App\Enums\ServiceStatus;
use App\Models\Application;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedClientUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_client_pages_render_the_final_client_shell(): void
    {
        $user = User::factory()->create(['name' => 'Synthetic Client']);
        $personalService = $this->bookableService('NPWP_PERSONAL', 'NPWP Perseorangan');
        $businessService = $this->bookableService('NPWP_BUSINESS', 'NPWP Badan Usaha');
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $personalService->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => $personalService->price_amount,
            'currency' => $personalService->currency,
        ]);

        $responses = [
            $this->actingAs($user)->get(route('client.dashboard')),
            $this->actingAs($user)->get(route('client.services.index')),
            $this->actingAs($user)->get(route('client.applications.create', $personalService->public_id)),
            $this->actingAs($user)->get(route('client.applications.create', $businessService->public_id)),
            $this->actingAs($user)->get(route('client.applications.show', $application->public_id)),
        ];

        foreach ($responses as $response) {
            $response->assertOk()
                ->assertSee('bd-client-header')
                ->assertDontSee('class="border-b bg-white"');
        }

        $responses[0]->assertSee('Aplikasi Anda');
        $responses[1]->assertSee('Layanan yang tersedia');
        $responses[2]->assertSee('Data perseorangan');
        $responses[3]->assertSee('Penanggung jawab utama');
        $responses[4]->assertSee('Checklist dokumen');
    }

    public function test_personal_entry_keeps_existing_draft_redirect_to_final_registration(): void
    {
        $user = User::factory()->create();
        $service = $this->bookableService('NPWP_PERSONAL', 'NPWP Perseorangan');
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
            'price_amount_snapshot' => $service->price_amount,
            'currency' => $service->currency,
        ]);

        $this->actingAs($user)
            ->get(route('npwp.personal'))
            ->assertRedirect(route('npwp.personal.application', $application->public_id));
    }

    private function bookableService(string $code, string $name): Service
    {
        $service = Service::factory()->create([
            'code' => $code,
            'name' => $name,
            'status' => ServiceStatus::ACTIVE,
            'price_amount' => 175000,
        ]);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => $code.'_KTP']);

        return $service;
    }
}
