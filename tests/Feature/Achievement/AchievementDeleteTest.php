<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_delete_their_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(
            route('achievement.delete', ['id' => $achievement->id])
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('achievements', ['id' => $achievement->id]);
    }

    public function test_a_user_cannot_delete_another_users_achievement()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integration2 = Integration::factory()->create(['user_id' => $user2->id]);
        $integrationInstance2 = IntegrationInstance::factory()->create(['integration_id' => $integration2->id]);

        $achievementOfUser2 = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance2->id,
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->deleteJson(
            route('achievement.delete', ['id' => $achievementOfUser2->id])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('achievements', ['id' => $achievementOfUser2->id]);
    }

    public function test_an_unauthorized_user_cannot_delete_any_achievement()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(route('achievement.delete', ['id' => $achievement->id]));

        $response->assertStatus(401);
        $this->assertDatabaseHas('achievements', ['id' => $achievement->id]);
    }
}
