<?php

namespace Tests\Feature\User;

use App\Http\Controllers\Webhook\WebhookCallbackController;
use App\Models\User;
use App\Repositories\Email\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendEmailVerifyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_user_can_resend_a_email_verification()
    {
        Mail::fake();

        $password = 'password123';

        $user = User::factory()->create([
            'password' => $password,
            'email_verified_at' => null,
        ]);

        $res = $this->postJson(route('resend'),['email' => $user->email]);

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $res->assertStatus(200)
            ->assertJson([
                'message' => 'Verification code sent to your email address.',
                'status' => true
            ]);
    }

    public function test_a_verified_user_cant_resend_a_email_verification()
    {
        Mail::fake();

        $password = 'password123';

        $user = User::factory()->create([
            'password' => $password,
        ]);

        $res = $this->postJson(route('resend'),['email' => $user->email]);


        $res->assertStatus(422)
            ->assertJson([
                'message' => 'Email has already been verified.',
                'status' => false
            ]);
    }

    public function test_a_user_cant_resend_a_email_more_often_then_1_times_per_2_minute()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->postJson(route('resend'), ['email' => $user->email])
            ->assertStatus(200);

        $this->postJson(route('resend'), ['email' => $user->email])
            ->assertStatus(429);
    }
}
