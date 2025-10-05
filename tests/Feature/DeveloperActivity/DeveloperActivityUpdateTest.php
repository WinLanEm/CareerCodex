<?php

namespace DeveloperActivity;

use App\Models\DeveloperActivity;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperActivityUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_update_their_developer_activity()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $activity = DeveloperActivity::factory()->create([
            'integration_id' => $integration->id,
            'is_approved' => false
        ]);

        $updateData = [
            'title' => 'Updated Activity Title',
            'is_approved' => true,
        ];

        $response = $this->actingAs($user)->patchJson(
            route('developer.activity.update', ['id' => $activity->id]),
            $updateData
        );

        $response->assertOk();
        $response->assertJsonPath('developer_activity.title', 'Updated Activity Title');
        $this->assertDatabaseHas('developer_activities', [
            'id' => $activity->id,
            'title' => 'Updated Activity Title',
            'is_approved' => true,
        ]);
    }

    public function test_a_user_cannot_update_another_users_developer_activity()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $integrationOfUser2 = Integration::factory()->create(['user_id' => $user2->id]);
        $activityOfUser2 = DeveloperActivity::factory()->create([
            'integration_id' => $integrationOfUser2->id,
            'title' => 'Original Title'
        ]);

        $updateData = ['title' => 'Hacked Title'];

        $response = $this->actingAs($user1)->patchJson(
            route('developer.activity.update', ['id' => $activityOfUser2->id]),
            $updateData
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('developer_activities', ['title' => 'Original Title']);
    }

    public function test_an_unauthorized_user_cannot_update_any_activity()
    {
        $activity = DeveloperActivity::factory()->create();

        $response = $this->patchJson(
            route('developer.activity.update', ['id' => $activity->id]),
            ['title' => 'Guest Update']
        );

        $response->assertStatus(401);
    }

    public function test_it_fails_validation_for_invalid_data()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $activity = DeveloperActivity::factory()->create(['integration_id' => $integration->id]);

        $invalidData = [
            'title' => str_repeat('A', 300),
            'is_approved' => 'not-a-boolean',
        ];

        $response = $this->actingAs($user)->patchJson(
            route('developer.activity.update', ['id' => $activity->id]),
            $invalidData
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'is_approved']);
    }
}
