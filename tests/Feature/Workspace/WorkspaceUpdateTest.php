<?php

namespace Tests\Feature\Workspace;

use App\Enums\WorkspaceTypeEnum;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_update_workspace()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'name' => 'Updated Workspace Name',
            'type' => WorkspaceTypeEnum::PERSONAL->value,
            'description' => 'Updated description',
            'start_date' => '2025-11-01',
            'end_date' => '2026-11-01',
        ];

        $response = $this->actingAs($user)->patchJson(route('workspace.update', ['id' => $workspace->id]), $updateData);

        $response->assertOk();
        $response->assertJsonStructure([
            'workspace' => [
                'id', 'name', 'type', 'description', 'start_date', 'end_date',
            ]
        ]);
        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Workspace Name',
            'type' => WorkspaceTypeEnum::PERSONAL->value,
        ]);
    }

    public function test_it_returns_403_if_workspace_does_not_exist_or_not_belongs_to_user()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $updateData = ['name' => 'Invalid update'];

        $response = $this->actingAs($user)->patchJson(route('workspace.update', ['id' => $workspace->id]), $updateData);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update_workspace()
    {
        $workspace = Workspace::factory()->create();
        $updateData = ['name' => 'Should not be updated'];

        $response = $this->patchJson(route('workspace.update', ['id' => $workspace->id]), $updateData);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_it_fails_validation_with_invalid_data()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $invalidData = [
            'name' => str_repeat('a', 256),
            'type' => 'invalid_type',
            'start_date' => '20251101',
            'end_date' => '2025-10-10',
        ];

        $response = $this->actingAs($user)->patchJson(route('workspace.update', ['id' => $workspace->id]), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'type', 'start_date']);
    }

    public function test_it_requires_end_date_to_be_after_start_date()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $invalidDates = [
            'start_date' => '2025-10-10',
            'end_date' => '2025-10-09'
        ];

        $response = $this->actingAs($user)->patchJson(route('workspace.update', ['id' => $workspace->id]), $invalidDates);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }

    public function test_it_allows_partial_update()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $partialData = ['description' => 'Only this field updated'];

        $response = $this->actingAs($user)->patchJson(route('workspace.update', ['id' => $workspace->id]), $partialData);

        $response->assertOk();
        $response->assertJsonFragment(['description' => 'Only this field updated']);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'description' => 'Only this field updated'
        ]);
    }
}
