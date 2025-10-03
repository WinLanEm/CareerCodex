<?php

namespace DeveloperActivity;

use App\Enums\DeveloperActivityEnum;
use App\Models\DeveloperActivity;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperActivityIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_get_their_developer_activities()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);
        DeveloperActivity::factory()->count(5)->create(['integration_id' => $integration->id]);

        $response = $this->actingAs($user)->getJson(route('developer.activity.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'developer_activities' => ['*' => ['id', 'title', 'type']],
            'paginator' => ['total', 'current_page']
        ]);
        $response->assertJsonCount(5, 'developer_activities');
    }

    public function test_an_unauthorized_user_cannot_get_any_activities()
    {
        $response = $this->getJson(route('developer.activity.index'));

        $response->assertStatus(401);
    }

    public function test_it_filters_activities_correctly()
    {
        $user = User::factory()->create();
        $integration = Integration::factory()->create(['user_id' => $user->id]);

        DeveloperActivity::factory()->count(2)->create([
            'integration_id' => $integration->id,
            'type' => DeveloperActivityEnum::COMMIT->value,
            'is_approved' => true,
            'completed_at' => '2025-10-15 12:00:00',
        ]);

        DeveloperActivity::factory()->create(['integration_id' => $integration->id, 'type' => DeveloperActivityEnum::PULL_REQUEST->value]);
        DeveloperActivity::factory()->create(['integration_id' => $integration->id, 'is_approved' => false]);
        DeveloperActivity::factory()->create(['integration_id' => $integration->id, 'completed_at' => '2025-09-01 10:00:00']);

        // Act
        $response = $this->actingAs($user)->getJson(route('developer.activity.index', [
            'type' => DeveloperActivityEnum::COMMIT->value,
            'is_approved' => true,
            'start_at' => '2025-10-01',
            'end_at' => '2025-10-30',
        ]));

        $response->assertOk();
        $response->assertJsonCount(2, 'developer_activities');
    }

    public function test_a_user_cannot_see_another_users_activities()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $integrationOfUser2 = Integration::factory()->create(['user_id' => $user2->id]);
        DeveloperActivity::factory()->count(3)->create(['integration_id' => $integrationOfUser2->id]);

        $response = $this->actingAs($user1)->getJson(route('developer.activity.index'));

        $response->assertOk();
        $response->assertJsonCount(0, 'developer_activities');
    }

    public function test_it_fails_validation_for_invalid_date_range()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('developer.activity.index', [
            'start_at' => '2025-10-20',
            'end_at' => '2025-10-10',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('end_at');
    }
}
