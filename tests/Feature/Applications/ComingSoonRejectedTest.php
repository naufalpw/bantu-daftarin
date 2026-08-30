<?php

namespace Tests\Feature\Applications;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonRejectedTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_rejects_coming_soon_service_even_when_request_is_manipulated(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'TAX_REPORTING', 'status' => ServiceStatus::COMING_SOON, 'price_amount' => null]);

        $response = $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_PERSONAL',
            'consent' => 1,
            'name' => 'Synthetic Client',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('applications', ['service_id' => $service->id]);
    }
}
