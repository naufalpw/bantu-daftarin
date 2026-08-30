<?php

namespace Tests\Feature\Authorization;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_access_another_clients_application_by_public_id(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $owner->id, 'service_id' => $service->id, 'status' => ApplicationStatus::DRAFT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);

        $response = $this->actingAs($other)->get(route('client.applications.show', $application->public_id));

        $response->assertNotFound();
    }

    public function test_client_cannot_enter_admin_panel(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'));

        $response->assertForbidden();
    }
}
