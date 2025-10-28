<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementApproveTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_bulk_update_their_achievements()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievements = Achievement::factory()->count(3)->create([
            'user_id' => $user->id,
            'integration_instance_id' => $integrationInstance->id,
            'is_approved' => false
        ]);

        $achievementIds = $achievements->pluck('id')->toArray();

        $response = $this->actingAs($user)->patchJson(
            route('achievements.approved'),
            ['achievement_ids' => $achievementIds]
        );

        // Если возвращает 500, добавим debug
        if ($response->status() !== 204) {
            dump($response->json());
        }

        $response->assertNoContent();
        $this->assertDatabaseHas('achievements', ['id' => $achievementIds[0], 'is_approved' => true]);
    }

    public function test_a_user_cannot_bulk_update_another_users_achievements()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integration2 = Integration::factory()->create(['user_id' => $user2->id]);
        $integrationInstance2 = IntegrationInstance::factory()->create(['integration_id' => $integration2->id]);

        $achievementsOfUser2 = Achievement::factory()->count(2)->create([
            'user_id' => $user2->id,
            'integration_instance_id' => $integrationInstance2->id,
            'is_approved' => false
        ]);

        $idsToUpdate = $achievementsOfUser2->pluck('id')->toArray();

        $response = $this->actingAs($user1)->patchJson(
            route('achievements.approved'),
            ['achievement_ids' => $idsToUpdate]
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('achievements', ['id' => $idsToUpdate[0], 'is_approved' => false]);
    }

    public function test_a_user_cannot_bulk_update_a_mixed_list_of_owned_and_unowned_achievements()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integration1 = Integration::factory()->create(['user_id' => $user1->id]);
        $integrationInstance1 = IntegrationInstance::factory()->create(['integration_id' => $integration1->id]);

        $integration2 = Integration::factory()->create(['user_id' => $user2->id]);
        $integrationInstance2 = IntegrationInstance::factory()->create(['integration_id' => $integration2->id]);

        $achievementUser1 = Achievement::factory()->create([
            'user_id' => $user1->id,
            'integration_instance_id' => $integrationInstance1->id,
            'is_approved' => false
        ]);

        $achievementUser2 = Achievement::factory()->create([
            'user_id' => $user2->id,
            'integration_instance_id' => $integrationInstance2->id,
            'is_approved' => false
        ]);

        $mixedIds = [$achievementUser1->id, $achievementUser2->id];

        $response = $this->actingAs($user1)->patchJson(
            route('achievements.approved'),
            ['achievement_ids' => $mixedIds]
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('achievements', [
            'id' => $achievementUser1->id,
            'is_approved' => false
        ]);
    }

    public function test_an_unauthorized_user_cannot_bulk_update_achievements()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        $achievement = Achievement::factory()->create([
            'user_id' => $user->id,
            'integration_instance_id' => $integrationInstance->id,
        ]);

        $response = $this->patchJson(
            route('achievements.approved'),
            ['achievement_ids' => [$achievement->id]]
        );

        $response->assertStatus(401);
    }
}
