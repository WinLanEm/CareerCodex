<?php

namespace App\Jobs\FetchInstances;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Services\HttpServices\AsanaApiServiceInterface;
use App\Jobs\SyncInstance\SyncAsanaInstanceJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchAsanaInstances implements ShouldQueue
{
    use Queueable, Dispatchable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
        readonly private bool        $isFirstRun
    )
    {
    }

    public function handle(UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository,AsanaApiServiceInterface $apiService): void
    {
        $this->executeWithHandling(function () use ($instanceRepository, $apiService) {
            $client = Http::withToken($this->integration->access_token);
            $workspaces = $apiService->getWorkspaces($this->integration->access_token,$client);
            $this->makeProviderInstance($workspaces, $instanceRepository,$apiService);
        });
    }

    private function makeProviderInstance(array $workspaces, UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository,AsanaApiServiceInterface $apiService)
    {
        foreach ($workspaces as $workspace) {
            $workspaceGid = $workspace['gid']; // В Asana это 'gid'

            // В Asana нет прямого URL для workspace в API-ответе,
            $siteUrl = 'https://app.asana.com/0/' . $workspaceGid . '/list';

            $instanceRepository->updateOrCreate(
                $this->integration->id,
                $workspaceGid,
                $siteUrl
            );

            SyncAsanaInstanceJob::dispatch(
                $this->integration,
                $this->isFirstRun,
                $workspaceGid,
            )->onQueue('asana');
        }
    }
}
