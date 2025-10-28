<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutUserTest extends TestCase
{
    use DatabaseMigrations;
    public function test_a_user_can_logout()
    {
        $password = 'password123';
        $user = User::factory()->create(['password' => $password]);

        $response = $this->actingAs($user)->postJson(route('logout'));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully.',
                'status' => true
            ]);
    }

    public function test_a_user_cannot_logout_when_the_token_is_invalid()
    {
        $password = 'password123';
        $user = User::factory()->create(['password' => $password]);

        $response = $this->postJson(route('logout'));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
