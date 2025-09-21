<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Contracts\Services\HttpServices\BitbucketApiServiceInterface;
use App\Jobs\SyncDeveloperRepositories\SyncBitbucketRepositoryJob;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

class SyncBitbucketJob implements ShouldQueue
{
    use HandlesGitSyncErrors, Queueable, Dispatchable;
    public function __construct(
        readonly protected Integration $integration,
    )
    {}

    public function handle(BitbucketApiServiceInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($apiService) {
            $updatedSince = CarbonImmutable::now()->subDays(7);
            $client = Http::withToken($this->integration->access_token);

            $apiService->syncRepositories($client, function ($repository) use ($updatedSince) {
                SyncBitbucketRepositoryJob::dispatch(
                    $this->integration,
                    $repository['mainbranch']['name'] ?? 'main',
                    $updatedSince,
                    $repository['workspace']['slug'],
                    $repository['slug'],
                )->onQueue('bitbucket');
            });
        });
    }
}
