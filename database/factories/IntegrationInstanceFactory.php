<?php

namespace Database\Factories;

use App\Models\IntegrationInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationInstanceFactory extends Factory
{
    protected $model = IntegrationInstance::class;

    public function definition(): array
    {
        return [
            'integration_id' => $this->faker->uuid(),
            'external_id' => $this->faker->uuid(),
            'has_websocket' => $this->faker->boolean(),
            'site_url' => $this->faker->url(),
        ];
    }
}
