<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementApproveTest extends TestCase
{
    use RefreshDatabase;
    public function test_an_authorized_user_can_bulk_update_their_achievements()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        $achievements = Achievement::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
            'is_approved' => false
        ]);

        $achievementIds = $achievements->pluck('id')->toArray();

        $response = $this->actingAs($user)->patchJson(
            route('achievements.approved'),
            ['achievement_ids' => $achievementIds]
        );

        $response->assertNoContent();
        $this->assertDatabaseHas('achievements', ['id' => $achievementIds[0], 'is_approved' => true]);
    }

    public function test_a_user_cannot_bulk_update_another_users_achievements()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $workspaceOfUser2 = Workspace::factory()->create(['user_id' => $user2->id]);
        $achievementsOfUser2 = Achievement::factory()->count(2)->create([
            'workspace_id' => $workspaceOfUser2->id,
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

        $achievementUser1 = Achievement::factory()->create(['workspace_id' => Workspace::factory()->create(['user_id' => $user1->id])->id, 'is_approved' => false]);
        $achievementUser2 = Achievement::factory()->create(['workspace_id' => Workspace::factory()->create(['user_id' => $user2->id])->id, 'is_approved' => false]);

        $mixedIds = [$achievementUser1->id, $achievementUser2->id];

        $response = $this->actingAs($user1)->patchJson(
            route('achievements.approved'),
            ['achievement_ids' => $mixedIds]
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('achievements', ['id' => $achievementUser1->id, 'is_approved' => false]);
    }

    public function test_an_unauthorized_user_cannot_bulk_update_achievements()
    {
        $achievement = Achievement::factory()->create();

        $response = $this->patchJson(route('achievements.approved'), ['achievement_ids' => [$achievement->id]]);

        $response->assertStatus(401);
    }
}
