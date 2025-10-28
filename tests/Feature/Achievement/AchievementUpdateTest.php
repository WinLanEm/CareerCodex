<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_update_their_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $updateData = [
            'title' => 'Updated Achievement Title',
            'description' => 'Updated description.',
        ];

        $response = $this->actingAs($user)->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            $updateData
        );

        $response->assertOk();
        $response->assertJsonPath('achievement.title', 'Updated Achievement Title');
        $this->assertDatabaseHas('achievements', [
            'id' => $achievement->id,
            'title' => 'Updated Achievement Title',
        ]);
    }

    public function test_a_user_cannot_update_another_users_achievement()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integration2 = Integration::factory()->create(['user_id' => $user2->id]);
        $integrationInstance2 = IntegrationInstance::factory()->create(['integration_id' => $integration2->id]);

        $achievementOfUser2 = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance2->id,
            'user_id' => $user2->id,
        ]);

        $updateData = ['title' => 'Hacked Title'];

        $response = $this->actingAs($user1)->patchJson(
            route('achievement.update', ['id' => $achievementOfUser2->id]),
            $updateData
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('achievements', ['title' => 'Hacked Title']);
    }

    public function test_an_unauthorized_user_cannot_update_any_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $response = $this->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            ['title' => 'Guest Update']
        );

        $response->assertStatus(401);
    }

    public function test_it_allows_partial_update_with_only_one_field()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
            'title' => 'Original Title',
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($user)->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            ['description' => 'A new, updated description.']
        );

        $response->assertOk();
        $this->assertDatabaseHas('achievements', [
            'id' => $achievement->id,
            'title' => 'Original Title',
            'description' => 'A new, updated description.'
        ]);
    }

    public function test_it_fails_validation_for_invalid_data_types()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $invalidData = [
            'title' => str_repeat('a', 256),
            'date' => 'invalid-date-format',
        ];

        $response = $this->actingAs($user)->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            $invalidData
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'date']);
    }

    public function test_user_can_update_achievement_with_integration_instance()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $updateData = [
            'title' => 'Updated via Integration',
            'project_name' => 'New Project Name',
        ];

        $response = $this->actingAs($user)->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            $updateData
        );

        $response->assertOk();
        $this->assertDatabaseHas('achievements', [
            'id' => $achievement->id,
            'title' => 'Updated via Integration',
            'project_name' => 'New Project Name',
        ]);
    }

    public function test_user_cannot_update_achievement_from_different_integration()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integration1 = Integration::factory()->create(['user_id' => $user1->id]);
        $integration2 = Integration::factory()->create(['user_id' => $user2->id]);

        $integrationInstance1 = IntegrationInstance::factory()->create(['integration_id' => $integration1->id]);
        $integrationInstance2 = IntegrationInstance::factory()->create(['integration_id' => $integration2->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance2->id,
            'user_id' => $user2->id,
        ]);

        $updateData = ['title' => 'Unauthorized Update'];

        $response = $this->actingAs($user1)->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            $updateData
        );

        $response->assertStatus(403);
    }
}
