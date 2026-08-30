<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRequirementFactory extends Factory
{
    protected $model = ServiceRequirement::class;

    public function definition(): array
    {
        return ['service_id' => Service::factory(), 'code' => fake()->unique()->regexify('[A-Z_]{4,16}'), 'name' => fake()->words(2, true), 'is_required' => true, 'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'], 'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'], 'max_size_bytes' => 5 * 1024 * 1024, 'sort_order' => 10, 'active' => true];
    }
}
