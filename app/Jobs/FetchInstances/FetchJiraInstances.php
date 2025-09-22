<?php

namespace App\Jobs\FetchInstances;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Services\HttpServices\JiraApiServiceInterface;
use App\Jobs\SyncInstance\SyncJiraInstanceJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchJiraInstances implements ShouldQueue
{
    use Queueable, Dispatchable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
        readonly private bool        $isFirstRun
    )
    {
    }

    public function handle(UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository,JiraApiServiceInterface $apiService): void
    {
        $this->executeWithHandling(function () use ($instanceRepository,$apiService) {
            $client = Http::withToken($this->integration->access_token);
            $sites = $apiService->getWorkspaces($this->integration->access_token,$client);
            $this->processSitesAndDispatchJobs($sites,$instanceRepository);
        });
    }


    private function processSitesAndDispatchJobs(array $sites, UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository): void
    {
        foreach ($sites as $site) {
            $cloudId = $site['id'];

            $instance = $instanceRepository->updateOrCreate(
                $this->integration->id,
                $cloudId,
                $site['url']
            );

            SyncJiraInstanceJob::dispatch(
                $instance->id,
                $this->integration,
                $this->isFirstRun,
                $cloudId,
                $site['url']
            );
        }
    }
}
