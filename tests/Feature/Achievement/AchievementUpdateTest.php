<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Models\User;
use App\Models\Workspace;
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
            'workspace_id' => null,
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
        $workspaceOfUser2 = Workspace::factory()->create(['user_id' => $user2->id]);
        $achievementOfUser2 = Achievement::factory()->create(['workspace_id' => $workspaceOfUser2->id]);

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
        $achievement = Achievement::factory()->create();

        $response = $this->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            ['title' => 'Guest Update']
        );

        $response->assertStatus(401);
    }

    public function test_it_allows_partial_update_with_only_one_field()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);


        $achievement = Achievement::factory()->create([
            'title' => 'Original Title',
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            ['description' => 'A new, updated description.']
        );

        // Assert
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
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        $achievement = Achievement::factory()->create(['workspace_id' => $workspace->id]);

        $invalidData = [
            'title' => str_repeat('a', 256),
            'hours_spent' => 'not-an-integer',
            'date' => 'invalid-date-format',
        ];

        $response = $this->actingAs($user)->patchJson(
            route('achievement.update', ['id' => $achievement->id]),
            $invalidData
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'hours_spent', 'date']);
    }
}
