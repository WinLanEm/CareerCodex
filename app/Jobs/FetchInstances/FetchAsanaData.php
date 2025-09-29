<?php

namespace App\Jobs\FetchInstances;

use App\Contracts\Services\HttpServices\Asana\AsanaWorkspaceServiceInterface;
use App\Jobs\ProcessProjectJobs\ProcessAsanaProjectJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchAsanaData implements ShouldQueue
{
    use Queueable, Dispatchable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
    )
    {
    }

    public function handle(AsanaWorkspaceServiceInterface $apiService): void
    {
        $this->executeWithHandling(function () use ($apiService) {
            $client = Http::withToken($this->integration->access_token);
            $workspaces = $apiService->getWorkspaces($this->integration->access_token,$client);
            foreach ($workspaces as $workspace) {
                $projects = $apiService->getProjects($this->integration->access_token,$workspace['gid'],$client);

                foreach ($projects as $project) {
                    ProcessAsanaProjectJob::dispatch(
                        $this->integration,
                        $project,
                        $workspace['gid']
                    )->onQueue('asana');;
                }
            }
        });
    }
}
