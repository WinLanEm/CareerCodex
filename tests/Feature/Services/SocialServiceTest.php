<?php

namespace Services;

use App\Actions\Github\CheckGitHubAppInstallation;
use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Http\Resources\UrlResource;
use App\Jobs\SyncDeveloperActivities\SyncGithubJob;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\Bus;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialServiceTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->actingAs($this->user)->get(
            route('service.callback', ['service' => $service->value]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(201);
        $response->assertJson(['message' => 'provider successful updated']);

        Bus::assertDispatched(\App\Jobs\SyncDeveloperActivities\SyncGitlabJob::class);
    }

    public function test_it_returns_401_if_repository_fails_to_create_integration(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;

        Socialite::shouldReceive('driver->stateless->user')->andReturn(Mockery::mock(\Laravel\Socialite\Contracts\User::class));

        $repoMock = Mockery::mock(UpdateOrCreateIntegrationRepositoryInterface::class);
        $repoMock->shouldReceive('updateOrCreate')->once()->andReturn(null);
        $this->app->instance(UpdateOrCreateIntegrationRepositoryInterface::class, $repoMock);

        $response = $this->actingAs($this->user)->get(
            route('service.callback', ['service' => $service->value]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(401);
        $response->assertJson(['message' => 'github email not equal to your email']);
    }

    public function test_it_returns_response_from_github_action_if_provided(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;
        Bus::fake();

        Socialite::shouldReceive('driver->stateless->user')->andReturn(Mockery::mock(\Laravel\Socialite\Contracts\User::class));

        $integration = Integration::factory()->make();
        $repoMock = Mockery::mock(UpdateOrCreateIntegrationRepositoryInterface::class);
        $repoMock->shouldReceive('updateOrCreate')->andReturn($integration);
        $this->app->instance(UpdateOrCreateIntegrationRepositoryInterface::class, $repoMock);

        $actionResponse = new UrlResource(['message' => 'GitHub App not installed'], false,403);
        $actionMock = Mockery::mock(CheckGitHubAppInstallation::class);
        $actionMock->shouldReceive('__invoke')->once()->andReturn($actionResponse);
        $this->app->instance(CheckGitHubAppInstallation::class, $actionMock);

        $response = $this->actingAs($this->user)->getJson(
            route('service.callback', ['service' => $service->value]),
            ['Accept' => 'application/json']
        );
        $response->assertStatus(403);
        $response->assertJson(['message' => 'GitHub App not installed','status' => false]);

        Bus::assertNotDispatched(SyncGithubJob::class);
    }

    public function test_it_returns_404_for_unsupported_service(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service.callback', ['service' => 'unsupported-service']),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('service');
    }

    public function test_it_returns_500_when_socialite_throws_exception(): void
    {
        $this->withoutExceptionHandling();
        $service = ServiceConnectionsEnum::GITHUB;

        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('Provider failed'));

        $response = $this->actingAs($this->user)->get(
            route('service.callback', ['service' => $service->value]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(500);
        $response->assertJson(['message' => 'An error occurred during authentication with the service.']);
    }
}
