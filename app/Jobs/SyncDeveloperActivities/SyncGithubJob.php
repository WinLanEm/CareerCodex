<?php

namespace App\Jobs\SyncDeveloperActivities;


use App\Contracts\Services\HttpServices\Github\GithubRepositorySyncInterface;
use App\Jobs\RegisterWebhook\RegisterGithubWebhookJob;
use App\Jobs\SyncDeveloperRepositories\SyncGithubRepositoryJob;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

class SyncGithubJob implements ShouldQueue
{
    use HandlesGitSyncErrors, Queueable, Dispatchable, InteractsWithQueue;
    public function __construct(
        readonly protected Integration $integration,
    )
    {}

    public function handle(GithubRepositorySyncInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($apiService) {
            $updatedSince = CarbonImmutable::now()->subDays(7);

            $apiService->syncRepositories($this->integration->access_token, function ($repository) use ($updatedSince) {
                SyncGithubRepositoryJob::dispatch(
                    $this->integration,
                    $repository['default_branch'],
                    $updatedSince,
                    $repository['full_name'],
                )->onQueue('github');
                RegisterGithubWebhookJob::dispatch(
                    $this->integration,
                    $repository['full_name'],
                    $repository['html_url'],
                    $repository['default_branch'],
                )->onQueue('github');
            });
        });
    }
}
