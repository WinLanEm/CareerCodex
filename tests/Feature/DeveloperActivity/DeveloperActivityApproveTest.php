<?php

namespace DeveloperActivity;

use App\Models\DeveloperActivity;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperActivityApproveTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_bulk_update_their_activities()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $activities = DeveloperActivity::factory()->count(3)->create([
            'integration_id' => $integration->id,
            'is_approved' => false
        ]);
        $activityIds = $activities->pluck('id')->toArray();

        $response = $this->actingAs($user)->patchJson(
            route('developer.activity.is_approved.update'),
            ['developer_activity_ids' => $activityIds]
        );

        $response->assertNoContent();
        foreach ($activityIds as $id) {
            $this->assertDatabaseHas('developer_activities', ['id' => $id, 'is_approved' => true]);
        }
    }

    public function test_a_user_cannot_bulk_update_another_users_activities()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $integrationOfUser2 = Integration::factory()->create(['user_id' => $user2->id]);
        $activitiesOfUser2 = DeveloperActivity::factory()->count(2)->create([
            'integration_id' => $integrationOfUser2->id,
            'is_approved' => false
        ]);
        $idsToUpdate = $activitiesOfUser2->pluck('id')->toArray();

        $response = $this->actingAs($user1)->patchJson(
            route('developer.activity.is_approved.update'),
            ['developer_activity_ids' => $idsToUpdate]
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('developer_activities', ['id' => $idsToUpdate[0], 'is_approved' => false]);
    }

    public function test_an_unauthorized_user_cannot_bulk_update()
    {
        $activity = DeveloperActivity::factory()->create();

        $response = $this->patchJson(route('developer.activity.is_approved.update'), [
            'developer_activity_ids' => [$activity->id]
        ]);

        $response->assertStatus(401);
    }

    public function test_it_fails_validation_for_invalid_data()
    {
        $user = User::factory()->create();

        $response1 = $this->actingAs($user)->patchJson(route('developer.activity.is_approved.update'), [
            'developer_activity_ids' => []
        ]);
        $response1->assertStatus(422)->assertJsonValidationErrors('developer_activity_ids');

        $response2 = $this->actingAs($user)->patchJson(route('developer.activity.is_approved.update'), [
            'developer_activity_ids' => [99999]
        ]);
        $response2->assertStatus(422)->assertJsonValidationErrors('developer_activity_ids.0');
    }
}
