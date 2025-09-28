<?php

namespace App\Jobs\FetchInstances;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Services\HttpServices\Jira\JiraWorkspaceServiceInterface;
use App\Contracts\Services\HttpServices\JiraApiServiceInterface;
use App\Jobs\ProcessProjectJobs\ProcessAsanaProjectJob;
use App\Jobs\ProcessProjectJobs\ProcessJiraProjectJob;
use App\Jobs\SyncInstance\SyncJiraInstanceJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchJiraData implements ShouldQueue
{
    use Queueable, Dispatchable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
        readonly private bool        $isFirstRun
    )
    {
    }

    public function handle(
        JiraWorkspaceServiceInterface $apiService,
    ): void {
        $this->executeWithHandling(function () use ($apiService) {
            $sites = $apiService->getWorkspaces($this->integration->access_token);

            foreach ($sites as $site) {
                $cloudId = $site['id'];
                $siteUrl = $site['url'];

                SyncJiraInstanceJob::dispatch(
                    $this->integration,
                    $this->isFirstRun,
                    $cloudId,
                    $siteUrl
                )->onQueue('jira');
            }
        });
    }
}
