<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition()
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 100),
            'title' => $this->faker->title(),
            'description' => $this->faker->paragraph(),
            'result' => $this->faker->sentence(),
            'hours_spent' => $this->faker->numberBetween(1, 100),
            'date' => $this->faker->date(),
            'skills' => json_encode($this->faker->words(3)),
            'is_approved' => $this->faker->boolean(),
            'is_from_provider' => false,
            'project_name' => $this->faker->company(),
            'link' => $this->faker->optional()->url(),
        ];
    }
}
