<?php

namespace Achievement;

use App\Models\Achievement;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_get_their_achievements()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        Achievement::factory()->count(5)->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
            'is_approved' => true
        ]);

        $response = $this->actingAs($user)->getJson(route('achievements.index', ['is_approved' => true]));
        $response->assertOk();
        $response->assertJsonCount(5, 'achievements');
    }

    public function test_an_unauthorized_user_cannot_access_achievements()
    {
        $response = $this->getJson(route('achievements.index', ['is_approved' => true]));
        $response->assertStatus(401);
    }

    public function test_is_approved_parameter_is_required()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson(route('achievements.index'));
        $response->assertStatus(422)->assertJsonValidationErrors('is_approved');
    }

    public function test_it_filters_achievements_by_date_range()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        $integrationInstance = IntegrationInstance::factory()->create(['integration_id' => $integration->id]);

        Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
            'date' => '2025-10-05',
            'is_approved' => true
        ]);
        Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
            'date' => '2025-10-15',
            'is_approved' => true
        ]);
        Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance->id,
            'user_id' => $user->id,
            'date' => '2025-10-25',
            'is_approved' => true
        ]);

        $response = $this->actingAs($user)->getJson(route('achievements.index', [
            'is_approved' => true,
            'start_date' => '2025-10-10',
            'end_date' => '2025-10-20'
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'achievements');
        $response->assertJsonPath('achievements.0.date', '2025-10-15');
    }

    public function test_a_user_cannot_see_another_users_achievements()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integration2 = Integration::factory()->create(['user_id' => $user2->id]);
        $integrationInstance2 = IntegrationInstance::factory()->create(['integration_id' => $integration2->id]);

        Achievement::factory()->create([
            'integration_instance_id' => $integrationInstance2->id,
            'user_id' => $user2->id,
            'is_approved' => true
        ]);

        $response = $this->actingAs($user1)->getJson(route('achievements.index', ['is_approved' => true]));

        $response->assertOk();
        $response->assertJsonCount(0, 'achievements');
    }
}
