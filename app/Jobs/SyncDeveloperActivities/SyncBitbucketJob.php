<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRepositorySyncInterface;
use App\Jobs\RegisterWebhook\RegisterBitbucketWebhookJob;
use App\Jobs\SyncDeveloperRepositories\SyncBitbucketRepositoryJob;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncBitbucketJob implements ShouldQueue
{
    use HandlesGitSyncErrors, Queueable, Dispatchable;
    public function __construct(
        readonly protected Integration $integration,
    )
    {}

    public function handle(BitbucketRepositorySyncInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($apiService) {
            $updatedSince = CarbonImmutable::now()->subDays(7);

            $apiService->syncRepositories($this->integration->access_token, function ($repository) use ($updatedSince) {
                SyncBitbucketRepositoryJob::dispatch(
                    $this->integration,
                    $repository['mainbranch']['name'] ?? 'main',
                    $updatedSince,
                    $repository['workspace']['slug'],
                    $repository['slug'],
                )->onQueue('bitbucket');
                RegisterBitbucketWebhookJob::dispatch(
                    $this->integration,
                    $repository['workspace']['slug'],
                    $repository['slug'],
                    $repository['uuid'],
                    $repository['links']['html']['href'],
                    $repository['mainbranch']['name'] ?? 'main',
                )->onQueue('bitbucket');
            });
        });
    }
}
