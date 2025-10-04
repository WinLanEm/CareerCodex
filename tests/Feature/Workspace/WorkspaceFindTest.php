<?php

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceFindTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_find_their_workspace()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(route('workspace.find', ['id' => $workspace->id]));

        $response->assertOk();

        $response->assertJsonStructure([
            'workspace' => [
                'id',
                'name',
                'type',
                'description',
                'start_date',
                'end_date',
            ],
            'status'
        ]);

        $response->assertJson([
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ],
            'status' => true,
        ]);
    }

    public function test_it_returns_404_if_workspace_is_not_found()
    {
        $user = User::factory()->create();
        $nonExistentId = 9999;

        $response = $this->actingAs($user)->getJson(route('workspace.find', ['id' => $nonExistentId]));

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Workspace not found',
            'status' => false
        ]);
    }

    public function test_a_user_cannot_view_another_users_workspace()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $workspaceOfUser2 = Workspace::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson(route('workspace.find', ['id' => $workspaceOfUser2->id]));

        $response->assertStatus(404);
    }

    public function test_an_unauthorized_user_cannot_find_any_workspace()
    {
        $workspace = Workspace::factory()->create();

        $response = $this->getJson(route('workspace.find', ['id' => $workspace->id]));

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
