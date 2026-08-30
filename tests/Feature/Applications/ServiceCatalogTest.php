<?php

namespace Tests\Feature\Applications;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\ServiceRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_service_is_bookable_only_with_price_and_required_active_requirement(): void
    {
        $service = Service::factory()->create(['status' => ServiceStatus::ACTIVE, 'price_amount' => null]);
        $this->assertFalse($service->isBookable());

        $service->forceFill(['price_amount' => 100000])->save();
        $this->assertFalse($service->fresh()->isBookable());

        ServiceRequirement::factory()->create(['service_id' => $service->id, 'is_required' => false, 'active' => true]);
        $this->assertFalse($service->fresh()->isBookable());

        ServiceRequirement::factory()->create(['service_id' => $service->id, 'is_required' => true, 'active' => true]);
        $this->assertTrue($service->fresh()->isBookable());
    }

    public function test_coming_soon_service_is_never_bookable_even_if_manipulated(): void
    {
        $service = Service::factory()->create(['status' => ServiceStatus::COMING_SOON, 'price_amount' => null]);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'is_required' => true, 'active' => true]);

        $this->assertFalse($service->isBookable());
    }
}
