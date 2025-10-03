<?php

namespace Tests\Feature\Workspace;

use App\Enums\WorkspaceTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class WorkspaceCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_create_a_workspace_with_valid_data()
    {
        $user = User::factory()->create();
        $postData = [
            'name' => 'My New Workspace',
            'type' => WorkspaceTypeEnum::WORK->value,
            'description' => 'A description for the new workspace.',
            'start_date' => '2025-10-01',
            'end_date' => '2026-10-01',
        ];

        $response = $this->actingAs($user)->postJson(route('workspace.create'), $postData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'workspace' => [
                'id', 'name', 'type', 'description', 'start_date', 'end_date',
            ]
        ]);
        $this->assertDatabaseHas('workspaces', [
            'user_id' => $user->id,
            'name' => 'My New Workspace',
        ]);
    }

    public function test_an_unauthorized_user_cannot_create_a_workspace()
    {
        $postData = ['name' => 'My New Workspace', 'type' => WorkspaceTypeEnum::WORK->value];

        $response = $this->postJson(route('workspace.create'), $postData);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('workspaces', ['name' => 'My New Workspace']);
    }

    public function test_it_fails_if_name_is_missing()
    {
        $user = User::factory()->create();
        $postData = ['type' => WorkspaceTypeEnum::WORK->value];

        $response = $this->actingAs($user)->postJson(route('workspace.create'), $postData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_it_fails_if_type_is_missing_or_invalid()
    {
        $user = User::factory()->create();

        $responseMissing = $this->actingAs($user)->postJson(route('workspace.create'), ['name' => 'Test Name']);
        $responseMissing->assertStatus(422)->assertJsonValidationErrors('type');

        $responseInvalid = $this->actingAs($user)->postJson(route('workspace.create'), [
            'name' => 'Test Name',
            'type' => 'invalid-enum-value'
        ]);
        $responseInvalid->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_it_fails_if_name_is_longer_than_255_characters()
    {
        $user = User::factory()->create();
        $postData = [
            'name' => str_repeat('a', 256),
            'type' => WorkspaceTypeEnum::WORK->value,
        ];

        $response = $this->actingAs($user)->postJson(route('workspace.create'), $postData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_it_fails_if_end_date_is_before_start_date()
    {
        $user = User::factory()->create();
        $postData = [
            'name' => 'Time Machine Project',
            'type' => WorkspaceTypeEnum::PERSONAL->value,
            'start_date' => '2025-10-01',
            'end_date' => '2025-09-30',
        ];

        $response = $this->actingAs($user)->postJson(route('workspace.create'), $postData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('end_date');
    }
}
