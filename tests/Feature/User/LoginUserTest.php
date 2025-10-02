<?php

namespace Tests\Feature\User;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;
    public function test_a_user_can_login():void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => $password
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => $password,
        ];
        $loginRes = $this->postJson(route('login'),$loginData);
        $loginRes->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => [
                    'email',
                    'name',
                    'email_verified_at',
                    'provider',
                    'created_at',
                    'id'
                ],
                'status'
            ]);
    }

    public function test_a_user_cant_login_with_invalid_password():void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => $password
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password555',
        ];
        $loginRes = $this->postJson(route('login'),$loginData);
        $loginRes->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid login details',
                'status' => false,
            ]);
    }
    public function test_a_user_cant_login_with_invalid_email():void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => $password
        ]);

        $loginData = [
            'email' => 'ignat48@gmail.com',
            'password' => $password,
        ];
        $loginRes = $this->postJson(route('login'),$loginData);
        $loginRes->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid login details',
                'status' => false,
            ]);
    }
    public function test_a_user_cant_login_without_verified_email():void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => $password,
            'email_verified_at' => null
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => $password,
        ];
        $loginRes = $this->postJson(route('login'),$loginData);
        $loginRes->assertStatus(403)
            ->assertJson([
                'message' => 'Email not verified.',
                'status' => false,
            ]);
    }
}
