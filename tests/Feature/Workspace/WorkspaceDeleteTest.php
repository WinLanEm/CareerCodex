<?php

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class WorkspaceDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_delete_their_workspace()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson(route('workspace.delete', ['id' => $workspace->id]));

        $response->assertNoContent();

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }

    public function test_a_user_cannot_delete_another_users_workspace()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $workspaceOfUser2 = Workspace::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->deleteJson(route('workspace.delete', ['id' => $workspaceOfUser2->id]));

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'workspace not found',
            'status' => false,
        ]);

        $this->assertDatabaseHas('workspaces', ['id' => $workspaceOfUser2->id]);
    }

    public function test_an_unauthorized_user_cannot_delete_any_workspace()
    {
        $workspace = Workspace::factory()->create();

        $response = $this->deleteJson(route('workspace.delete', ['id' => $workspace->id]));

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);

        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);
    }
}
