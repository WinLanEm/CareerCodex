<?php

namespace Achievement;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_create_an_achievement_in_their_workspace()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $postData = [
            'title' => 'New Awesome Achievement',
            'description' => 'A detailed description.',
            'result' => 'A great result.',
            'workspace_id' => $workspace->id,
        ];

        $response = $this->actingAs($user)->postJson(route('achievement.create'), $postData);

        $response->assertStatus(201);
        $response->assertJsonStructure(['achievement' => ['id', 'title']]);
        $this->assertDatabaseHas('achievements', ['title' => 'New Awesome Achievement']);
    }

    public function test_it_returns_an_error_if_user_tries_to_create_in_another_users_workspace()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $workspaceOfUser2 = Workspace::factory()->create(['user_id' => $user2->id]);

        $postData = [
            'title' => 'Unauthorized Achievement',
            'description' => 'desc',
            'result' => 'res',
            'workspace_id' => $workspaceOfUser2->id,
        ];

        $response = $this->actingAs($user1)->postJson(route('achievement.create'), $postData);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Achievement create failed']);
        $this->assertDatabaseMissing('achievements', ['title' => 'Unauthorized Achievement']);
    }

    public function test_an_unauthorized_user_cannot_create_an_achievement()
    {
        $workspace = Workspace::factory()->create();
        $postData = ['title' => 'Test', 'description' => 'd', 'result' => 'r', 'workspace_id' => $workspace->id];

        $response = $this->postJson(route('achievement.create'), $postData);

        $response->assertStatus(401);
    }

    public function test_it_fails_validation_when_required_fields_are_missing()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('achievement.create'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'description', 'result', 'workspace_id']);
    }
}
