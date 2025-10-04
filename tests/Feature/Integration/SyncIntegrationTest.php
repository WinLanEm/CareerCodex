<?php

namespace Integration;

use App\Enums\ServiceConnectionsEnum;
use App\Jobs\FetchInstances\FetchAsanaData;
use App\Jobs\FetchInstances\FetchJiraData;
use App\Jobs\SyncDeveloperActivities\SyncBitbucketJob;
use App\Jobs\SyncDeveloperActivities\SyncGithubJob;
use App\Jobs\SyncDeveloperActivities\SyncGitlabJob;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_jobs_for_all_user_integrations(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $integrationsData = [
            ['service' => ServiceConnectionsEnum::JIRA->value, 'job' => FetchJiraData::class],
            ['service' => ServiceConnectionsEnum::ASANA->value, 'job' => FetchAsanaData::class],
            ['service' => ServiceConnectionsEnum::GITHUB->value, 'job' => SyncGithubJob::class],
            ['service' => ServiceConnectionsEnum::GITLAB->value, 'job' => SyncGitlabJob::class],
            ['service' => ServiceConnectionsEnum::BITBUCKET->value, 'job' => SyncBitbucketJob::class],
        ];

        foreach ($integrationsData as $data) {
            Integration::factory()->create([
                'user_id' => $user->id,
                'service' => $data['service'],
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('service.sync'));

        $response->assertNoContent();

        foreach ($integrationsData as $data) {
            Bus::assertDispatched($data['job']);
        }
    }

    public function test_it_handles_user_without_integrations(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('service.sync'));

        $response->assertNoContent();

        Bus::assertNothingDispatched();
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson(route('service.sync'));

        $response->assertUnauthorized();
    }

    public function test_it_throttles_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('service.sync'))->assertNoContent();

        $this->actingAs($user)->getJson(route('service.sync'))->assertStatus(429);
    }
}
