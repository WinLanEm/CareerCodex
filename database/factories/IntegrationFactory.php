<?php

namespace Database\Factories;

use App\Enums\AuthServiceEnum;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service' => $this->faker->randomElement(AuthServiceEnum::getValues()),
            'service_user_id' => $this->faker->uuid,
            'access_token' => encrypt(Str::random(40)),
            'refresh_token' => encrypt(Str::random(40)),
            'expires_at' => now()->addMonth(),
        ];
    }
}
