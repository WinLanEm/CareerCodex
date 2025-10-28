<?php

namespace Services;

use App\Contracts\Repositories\User\UpdateOrCreateUserRepositoryInterface;
use App\Enums\AuthServiceEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use DatabaseMigrations;

    private function generateValidState(): string
    {
        $stateData = [
            'timestamp' => now()->timestamp,
            'random' => 'test_' . uniqid(),
            'issue_token' => true,
        ];

        $state = base64_encode(json_encode($stateData));
        $signature = hash_hmac('sha256', $state, config('app.key'));

        return $state . '.' . $signature;
    }

    public function test_it_authenticates_user_and_returns_token_on_success(): void
    {
        $provider = AuthServiceEnum::GITHUB;

        $socialiteUserMock = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $socialiteUserMock->shouldReceive('getId')->andReturn('12345');
        $socialiteUserMock->shouldReceive('getName')->andReturn('Test User');
        $socialiteUserMock->shouldReceive('getEmail')->andReturn('test@example.com');

        Socialite::shouldReceive('driver->stateless->user')->andReturn($socialiteUserMock);

        $userRepositoryMock = Mockery::mock(UpdateOrCreateUserRepositoryInterface::class);
        $this->app->instance(UpdateOrCreateUserRepositoryInterface::class, $userRepositoryMock);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now()
        ]);

        $userRepositoryMock->shouldReceive('updateOrCreateProviderUser')
            ->once()
            ->with($socialiteUserMock, $provider)
            ->andReturn($user);

        $response = $this->get(
            route('auth.callback', [
                'provider' => $provider->value,
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'token',
            'message',
            'user' => [
                'name',
                'email',
                'id',
                'provider',
                'email_verified_at',
            ]
        ]);
    }

    public function test_it_returns_error_when_socialite_fails(): void
    {
        $provider = AuthServiceEnum::GITHUB;

        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('Something went wrong with the provider'));

        $response = $this->get(
            route('auth.callback', [
                'provider' => $provider->value,
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(500);
        $response->assertJson([
            'message' => 'An error occurred during authentication with the service.'
        ]);
    }

    public function test_it_fails_validation_for_invalid_provider(): void
    {
        $invalidProvider = 'not-a-real-provider';

        $response = $this->get(
            route('auth.callback', [
                'provider' => $invalidProvider,
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('provider');
    }

    public function test_it_returns_401_for_missing_state(): void
    {
        $provider = AuthServiceEnum::GITHUB;

        $response = $this->get(
            route('auth.callback', ['provider' => $provider->value]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Missing state parameter', 'status' => false]);
    }

    public function test_it_returns_401_for_invalid_state_signature(): void
    {
        $provider = AuthServiceEnum::GITHUB;

        $invalidState = base64_encode(json_encode(['timestamp' => now()->timestamp])) . '.invalid_signature';

        $response = $this->get(
            route('auth.callback', [
                'provider' => $provider->value,
                'state' => $invalidState
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid state signature', 'status' => false]);
    }
}
