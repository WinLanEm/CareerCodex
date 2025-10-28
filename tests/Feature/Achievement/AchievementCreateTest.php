<?php

namespace Achievement;

use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_create_an_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $postData = [
            'title' => 'New Awesome Achievement',
            'description' => 'A detailed description.',
            'result' => 'A great result.',
            'integration_instance_id' => $integrationInstance->id,
            'project_name' => 'Test Project',
            'date' => '2025-10-29',
            'hours_spent' => 5,
        ];

        $response = $this->actingAs($user)->postJson(route('achievement.create'), $postData);

        $response->assertStatus(201);
        $response->assertJsonStructure(['achievement' => ['id', 'title']]);
        $this->assertDatabaseHas('achievements', ['title' => 'New Awesome Achievement']);
    }

    public function test_an_unauthorized_user_cannot_create_an_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $postData = [
            'title' => 'Test',
            'description' => 'd',
            'result' => 'r',
            'integration_instance_id' => $integrationInstance->id,
            'project_name' => 'Test Project',
            'date' => '2025-10-29',
        ];

        $response = $this->postJson(route('achievement.create'), $postData);

        $response->assertStatus(401);
    }

    public function test_it_fails_validation_when_required_fields_are_missing()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('achievement.create'), []);

        $response->assertStatus(422);

        $errors = $response->json('errors');

        $response->assertJsonValidationErrors(['title', 'description', 'result']);
    }

    public function test_it_creates_achievement_with_optional_fields()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $postData = [
            'title' => 'Achievement with Optional Fields',
            'description' => 'Description',
            'result' => 'Result',
            'integration_instance_id' => $integrationInstance->id,
            'project_name' => 'Optional Project',
            'date' => '2025-10-29',
            'hours_spent' => 10,
            'skills' => ['PHP', 'Laravel'],
            'link' => 'https://example.com',
        ];

        $response = $this->actingAs($user)->postJson(route('achievement.create'), $postData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('achievements', [
            'title' => 'Achievement with Optional Fields',
            'project_name' => 'Optional Project',
            'hours_spent' => 10,
        ]);
    }
}
