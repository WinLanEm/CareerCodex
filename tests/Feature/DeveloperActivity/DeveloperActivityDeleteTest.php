<?php

namespace DeveloperActivity;

use App\Models\DeveloperActivity;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperActivityDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_delete_their_developer_activity()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $activity = DeveloperActivity::factory()->create(['integration_id' => $integration->id]);

        $response = $this->actingAs($user)->deleteJson(
            route('developer.activity.delete', ['id' => $activity->id])
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('developer_activities', ['id' => $activity->id]);
    }

    public function test_a_user_cannot_delete_another_users_developer_activity()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $integrationOfUser2 = Integration::factory()->create(['user_id' => $user2->id]);
        $activityOfUser2 = DeveloperActivity::factory()->create(['integration_id' => $integrationOfUser2->id]);

        $response = $this->actingAs($user1)->deleteJson(
            route('developer.activity.delete', ['id' => $activityOfUser2->id])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('developer_activities', ['id' => $activityOfUser2->id]);
    }

    public function test_an_unauthorized_user_cannot_delete_any_activity()
    {
        $activity = DeveloperActivity::factory()->create();

        $response = $this->deleteJson(route('developer.activity.delete', ['id' => $activity->id]));

        $response->assertStatus(401);
        $this->assertDatabaseHas('developer_activities', ['id' => $activity->id]);
    }
}
