<?php

namespace Database\Factories;

use App\Enums\DeveloperActivityEnum;
use App\Models\DeveloperActivity;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeveloperActivityFactory extends Factory
{
    protected $model = DeveloperActivity::class;

    public function definition(): array
    {
        return [
            'integration_id' => Integration::factory(),
            'external_id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(6),
            'repository_name' => $this->faker->slug(2),
            'type' => $this->faker->randomElement(DeveloperActivityEnum::getValues()),
            'is_approved' => $this->faker->boolean(80),
            'completed_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'url' => $this->faker->url,
            'additions' => $this->faker->numberBetween(0, 500),
            'deletions' => $this->faker->numberBetween(0, 500),
        ];
    }
}
