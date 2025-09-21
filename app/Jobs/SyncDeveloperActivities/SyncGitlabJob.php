<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Contracts\Services\HttpServices\GitlabApiServiceInterface;
use App\Jobs\SyncDeveloperRepositories\SyncGitlabRepositoryJob;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

class SyncGitlabJob implements ShouldQueue
{
    use HandlesGitSyncErrors, Queueable, Dispatchable;
    public function __construct(
        readonly protected Integration $integration,
    ) {}

    public function handle(GitlabApiServiceInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($apiService) {
            $updatedSince = CarbonImmutable::now()->subDays(7);
            $client = Http::withToken($this->integration->access_token);

            $apiService->syncRepositories($client, function ($repository) use ($updatedSince) {
                SyncGitlabRepositoryJob::dispatch(
                    $this->integration,
                    $repository['default_branch'],
                    $updatedSince,
                    $repository['id'],
                    $repository['path_with_namespace'],
                )->onQueue('gitlab');
            });
        });
    }
}
