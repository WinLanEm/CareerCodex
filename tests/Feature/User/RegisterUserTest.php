<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Repositories\Email\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;
    public function test_a_user_can_be_registered():void
    {
        Mail::fake();

        $password = fake()->password;
        $data = [
            'email' => fake()->email,
            'password' => $password,
            'name' => fake()->name,
            'password_confirmation' => $password,
        ];

        $res = $this->postJson(route('register'), $data);

        $res->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'User registered successfully. Please check your email for verification.'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
        ]);

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($data) {
            return $mail->hasTo($data['email']);
        });
    }
    public function test_a_user_cant_be_register_with_invalid_email():void
    {
        $password = fake()->password;
        $data = [
            'email' => "213",
            'password' => $password,
            'name' => fake()->name,
            'password_confirmation' => $password,
        ];
        $res = $this->postJson(route('register'), $data);

        $res->assertStatus(422)
            ->assertInvalid('email');
    }

    public function test_a_user_cant_be_register_without_unique_email():void
    {
        $password = 'password12345';
        $user = User::factory()->create([
            'password' => $password,
        ]);

        $data = [
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
            'name' => fake()->name,
        ];
        $notUniqueRes = $this->postJson(route('register'), $data);
        $notUniqueRes->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Email already exists.'
            ]);
    }
}
