<?php

namespace Tests\Feature\User;

use App\Contracts\Repositories\Email\GenerateVerificationCodeRepositoryInterface;
use App\Models\User;
use App\Repositories\Email\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerifyUserTest extends TestCase
{
    use DatabaseMigrations;
    public function test_a_user_can_be_registered_and_a_verification_email_is_sent()
    {
        Mail::fake();

        $password = 'password12345';
        $data = [
            'email' => fake()->email,
            'name' => fake()->name,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $response = $this->postJson(route('register'), $data);
        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($data) {
            return $mail->hasTo($data['email']);
        });

        $verificationCodeRepo = $this->app->make(GenerateVerificationCodeRepositoryInterface::class);
        $code = $verificationCodeRepo->generate($user);

        $verifyData = [
            'email' => $data['email'],
            'code' => $code,
            'issue_token' => true,
        ];

        $res = $this->post(route('verify'),$verifyData);

        $res->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Email verify successfully.',
                'user' => [
                    'email' => $data['email'],
                    'name' => $data['name'],
                ]
            ]);
    }
    public function test_a_user_cant_be_verified_with_invalid_code()
    {
        Mail::fake();

        $password = 'password12345';
        $data = [
            'email' => fake()->email,
            'name' => fake()->name,
            'password' => $password,
            'password_confirmation' => $password
        ];

        $response = $this->postJson(route('register'), $data);
        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($data) {
            return $mail->hasTo($data['email']);
        });

        $verificationCodeRepo = $this->app->make(GenerateVerificationCodeRepositoryInterface::class);
        $code = $verificationCodeRepo->generate($user);

        $verifyData = [
            'email' => $data['email'],
            'code' => '123'
        ];

        $res = $this->post(route('verify'),$verifyData);

        $res->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid verification code.',
            ]);
    }
    public function test_a_user_cant_be_verified_after_verification_code_is_expired()
    {
        Mail::fake();

        $password = 'password12345';
        $data = [
            'email' => fake()->email,
            'name' => fake()->name,
            'password' => $password,
            'password_confirmation' => $password
        ];

        $response = $this->postJson(route('register'), $data);
        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($data) {
            return $mail->hasTo($data['email']);
        });

        $verificationCodeRepo = $this->app->make(GenerateVerificationCodeRepositoryInterface::class);
        $code = $verificationCodeRepo->generate($user);

        $verifyData = [
            'email' => $data['email'],
            'code' => $code
        ];

        $user->update([
            'verification_code_expires_at' => now()->subHour()
        ]);

        $res = $this->post(route('verify'),$verifyData);

        $res->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Verification code has expired.',
            ]);
    }
}
