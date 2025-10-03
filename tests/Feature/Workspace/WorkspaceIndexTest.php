<?php

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_workspace_index_has_success_data_structure()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $res = $this->actingAs($user)->getJson(route('workspace.index'));

        $res->assertOk()
            ->assertJsonStructure([
                'workspaces' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'description',
                        'start_date',
                        'end_date'
                    ]
                ],
                'status',
                'paginator' => [
                    'total',
                    'current_page',
                    'per_page',
                    'last_page',
                ]
            ]);
    }

    public function test_a_unauthorized_user_cant_get_workspaces()
    {
        $workspace = Workspace::factory()->create();

        $res = $this->getJson(route('workspace.index'));

        $res->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
    public function test_a_user_can_only_see_their_own_workspaces()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $workspaceUser1 = Workspace::factory()->create(['user_id' => $user1->id]);
        $workspaceUser2 = Workspace::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson(route('workspace.index'));

        $response->assertOk();

        $response->assertJsonFragment(['id' => $workspaceUser1->id]);
        $response->assertJsonMissing(['id' => $workspaceUser2->id]);
    }
}
