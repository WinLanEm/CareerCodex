<?php

namespace DeveloperActivity;

use App\Models\DeveloperActivity;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperActivityFindTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_find_their_own_developer_activity()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $activity = DeveloperActivity::factory()->create(['integration_id' => $integration->id]);

        $response = $this->actingAs($user)->getJson(route('developer.activity.find', ['id' => $activity->id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'developer_activity' => [
                'id',
                'title',
                'service',
                'integration_id'
            ]
        ]);
        $response->assertJsonPath('developer_activity.id', $activity->id);
    }

    public function test_a_user_cannot_find_another_users_developer_activity()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $integrationOfUser2 = Integration::factory()->create(['user_id' => $user2->id]);
        $activityOfUser2 = DeveloperActivity::factory()->create(['integration_id' => $integrationOfUser2->id]);

        $response = $this->actingAs($user1)->getJson(route('developer.activity.find', ['id' => $activityOfUser2->id]));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'DeveloperActivity not found']);
    }

    public function test_it_returns_404_if_developer_activity_does_not_exist()
    {
        $user = User::factory()->create();
        $nonExistentId = 99999;

        $response = $this->actingAs($user)->getJson(route('developer.activity.find', ['id' => $nonExistentId]));

        $response->assertStatus(404);
    }

    public function test_an_unauthorized_user_cannot_find_any_developer_activity()
    {
        $activity = DeveloperActivity::factory()->create();

        $response = $this->getJson(route('developer.activity.find', ['id' => $activity->id]));

        $response->assertStatus(401);
    }
}
