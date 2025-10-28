<?php

namespace Services;

use App\Actions\Github\CheckGitHubAppInstallation;
use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Http\Resources\UrlResource;
use App\Jobs\SyncDeveloperActivities\SyncGithubJob;
use App\Jobs\SyncDeveloperActivities\SyncGitlabJob;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialServiceTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function generateValidState(bool $issueToken = true): string
    {
        $stateData = [
            'timestamp' => now()->timestamp,
            'issue_token' => $issueToken,
            'user_id' => $this->user->id
        ];

        $state = base64_encode(json_encode($stateData));
        $signature = hash_hmac('sha256', $state, config('app.key'));

        return $state . '.' . $signature;
    }

    public function test_it_creates_integration_and_dispatches_job_on_success(): void
    {
        Bus::fake();
        $service = ServiceConnectionsEnum::GITLAB;

        $socialiteUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        Socialite::shouldReceive('driver->stateless->user')->andReturn($socialiteUser);

        $integration = Integration::factory()->create([
            'user_id' => $this->user->id,
            'service' => $service->value,
        ]);

        $repoMock = Mockery::mock(UpdateOrCreateIntegrationRepositoryInterface::class);
        $repoMock->shouldReceive('updateOrCreate')->once()->with($service, $socialiteUser)->andReturn($integration);
        $this->app->instance(UpdateOrCreateIntegrationRepositoryInterface::class, $repoMock);

        $strategyMock = Mockery::mock(GetIntegrationInstanceStrategyInterface::class);
        $strategyMock->shouldReceive('getInstance')->once()->with($integration);
        $this->app->instance(GetIntegrationInstanceStrategyInterface::class, $strategyMock);

        $response = $this->actingAs($this->user)->get(
            route('service.callback', [
                'service' => $service->value,
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Provider successfully connected', 'status' => true]);
    }

    public function test_it_returns_401_if_repository_fails_to_create_integration(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;

        Socialite::shouldReceive('driver->stateless->user')->andReturn(Mockery::mock(\Laravel\Socialite\Contracts\User::class));

        $repoMock = Mockery::mock(UpdateOrCreateIntegrationRepositoryInterface::class);
        $repoMock->shouldReceive('updateOrCreate')->once()->andReturn(null);
        $this->app->instance(UpdateOrCreateIntegrationRepositoryInterface::class, $repoMock);

        $response = $this->actingAs($this->user)->get(
            route('service.callback', [
                'service' => $service->value,
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(401);
        $response->assertJson(['message' => 'github email not equal to your email', 'status' => false]);
    }

    public function test_it_returns_response_from_github_action_if_provided(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;
        Bus::fake();

        Socialite::shouldReceive('driver->stateless->user')->andReturn(Mockery::mock(\Laravel\Socialite\Contracts\User::class));

        $integration = Integration::factory()->create([
            'user_id' => $this->user->id,
            'service' => $service->value,
        ]);

        $repoMock = Mockery::mock(UpdateOrCreateIntegrationRepositoryInterface::class);
        $repoMock->shouldReceive('updateOrCreate')->andReturn($integration);
        $this->app->instance(UpdateOrCreateIntegrationRepositoryInterface::class, $repoMock);

        $actionResponse = new UrlResource(['message' => 'GitHub App not installed'], false, 403);
        $actionMock = Mockery::mock(CheckGitHubAppInstallation::class);
        $actionMock->shouldReceive('__invoke')->once()->andReturn($actionResponse);
        $this->app->instance(CheckGitHubAppInstallation::class, $actionMock);

        $response = $this->actingAs($this->user)->get(
            route('service.callback', [
                'service' => $service->value,
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(403);
        $response->assertJson(['message' => 'GitHub App not installed', 'status' => false]);

        Bus::assertNotDispatched(SyncGithubJob::class);
    }

    public function test_it_returns_422_for_unsupported_service(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service.callback', [
                'service' => 'unsupported-service',
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => '"unsupported-service" is not valid service. Available: github, gitlab, bitbucket, jira, asana']);
    }

    public function test_it_returns_500_when_socialite_throws_exception(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;

        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('Provider failed'));

        $response = $this->actingAs($this->user)->get(
            route('service.callback', [
                'service' => $service->value,
                'state' => $this->generateValidState()
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(500);
        $response->assertJson(['message' => 'An error occurred during authentication with the service.', 'status' => false]);
    }

    public function test_it_returns_401_for_missing_state(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;

        $response = $this->actingAs($this->user)->get(
            route('service.callback', ['service' => $service->value]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Missing state parameter', 'status' => false]);
    }

    public function test_it_returns_401_for_invalid_state_signature(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;

        $invalidState = base64_encode(json_encode(['timestamp' => now()->timestamp])) . '.invalid_signature';

        $response = $this->actingAs($this->user)->get(
            route('service.callback', [
                'service' => $service->value,
                'state' => $invalidState
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid state signature', 'status' => false]);
    }
}
