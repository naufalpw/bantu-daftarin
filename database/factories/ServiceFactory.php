<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return ['code' => fake()->unique()->regexify('[A-Z_]{6,20}'), 'name' => fake()->words(3, true), 'description' => fake()->sentence(), 'status' => ServiceStatus::ACTIVE, 'price_amount' => 100000, 'currency' => 'IDR', 'sort_order' => 10, 'activated_at' => now()];
    }
}
