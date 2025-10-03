<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_delete_their_achievement()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        $achievement = Achievement::factory()->create(['workspace_id' => $workspace->id]);

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

        $workspaceOfUser2 = Workspace::factory()->create(['user_id' => $user2->id]);
        $achievementOfUser2 = Achievement::factory()->create(['workspace_id' => $workspaceOfUser2->id]);

        $response = $this->actingAs($user1)->deleteJson(
            route('achievement.delete', ['id' => $achievementOfUser2->id])
        );

        $response->assertStatus(404);
        $response->assertJson(['message' => 'achievement not found']);

        $this->assertDatabaseHas('achievements', ['id' => $achievementOfUser2->id]);
    }

    public function test_an_unauthorized_user_cannot_delete_any_achievement()
    {
        $achievement = Achievement::factory()->create();

        $response = $this->deleteJson(route('achievement.delete', ['id' => $achievement->id]));

        $response->assertStatus(401);
        $this->assertDatabaseHas('achievements', ['id' => $achievement->id]);
    }
}
