<?php

namespace Tests\Feature\User;

use App\Http\Resources\User\UserWrapperResource;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeUserTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_user_can_see_me_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('me'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);
    }
    public function test_a_unauthorized_user_cant_see_me_data()
    {
        $user = User::factory()->create();

        $response = $this->getJson(route('me'));

        $response->assertStatus(401);
    }
}
