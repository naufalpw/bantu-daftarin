<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();

        return ['user_id' => $user->getKey(), 'service_id' => $service->getKey(), 'status' => ApplicationStatus::DRAFT, 'price_amount_snapshot' => $service->price_amount, 'currency' => $service->currency];
    }
}
