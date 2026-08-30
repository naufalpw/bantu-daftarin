<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        return ['user_id' => $user->getKey(), 'email' => $user->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true];
    }
}
