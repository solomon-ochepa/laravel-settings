<?php

namespace SolomonOchepa\Settings\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SolomonOchepa\Settings\Tests\App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(16),
        ];
    }
}
