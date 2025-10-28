<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementFindTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_find_their_own_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('achievement.find', ['id' => $achievement->id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'achievement' => [
                'id', 'title', 'description', 'date'
            ]
        ]);
        $response->assertJsonPath('achievement.id', $achievement->id);
    }

    public function test_an_unauthorized_user_cannot_find_any_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson(route('achievement.find', ['id' => $achievement->id]));

        $response->assertStatus(401);
    }

    public function test_it_returns_404_if_achievement_does_not_exist()
    {
        $user = User::factory()->create();
        $nonExistentId = 99999;

        $response = $this->actingAs($user)->getJson(route('achievement.find', ['id' => $nonExistentId]));

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'achievement not found',
            'status' => false,
        ]);
    }

    public function test_a_user_cant_currently_see_another_users_achievement()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integration2 = Integration::factory()->create(['user_id' => $user2->id]);
        $integrationInstance2 = IntegrationInstance::factory()->create(['integration_id' => $integration2->id]);

        $achievementOfUser2 = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance2->id,
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->getJson(route('achievement.find', ['id' => $achievementOfUser2->id]));

        $response->assertStatus(403);
    }
}
