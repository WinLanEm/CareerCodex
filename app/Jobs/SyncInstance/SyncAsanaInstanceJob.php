<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\Integrations\UpdateIntegrationRepositoryInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaProjectServiceInterface;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class SyncAsanaInstanceJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors, Dispatchable;
    public function __construct(
        readonly protected int $instanceId,
        readonly protected Integration $integration,
        readonly private string $projectGid,
        readonly private string $projectName,
    ) {}

    public function handle(WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
                           AsanaProjectServiceInterface $apiService,
                           UpdateIntegrationRepositoryInterface $integrationRepository
    )
    {
        $this->executeWithHandling(function () use ($repository, $apiService,$integrationRepository) {
            $apiService->syncCompletedIssuesForProject(
                $this->projectGid,
                $repository,
                $this->projectName,
                $this->integration->access_token,
                function ($task) use ($repository) {
                    if ($task['completed']) {
                        $carbonDate = Carbon::parse($task['completed_at']);
                        $repository->updateOrCreate([
                            'title' => $task['name'],
                            'description' => $task['notes'],
                            'link' => $task['permalink_url'],
                            'date' => $carbonDate,
                            'is_approved' => false,
                            'is_from_provider' => true,
                            'integration_instance_id' => $this->instanceId,
                            'project_name' => $this->projectName,
                        ]);
                    }
                }
            );
        });
    }
}
