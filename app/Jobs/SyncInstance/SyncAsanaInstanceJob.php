<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Contracts\Services\HttpServices\AsanaApiServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use App\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepository;
use App\Traits\HandlesSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SyncAsanaInstanceJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors, Dispatchable;
    public function __construct(
        readonly protected int $instanceId,
        readonly protected Integration $integration,
        readonly protected bool $isFirstRun,
        readonly protected string $cloudId,
    ) {}

    public function handle(WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
                           AsanaApiServiceInterface $apiService,
                           UpdateIntegrationInstanceRepositoryInterface $integrationRepository
    )
    {
        $this->executeWithHandling(function () use ($repository, $apiService,$integrationRepository) {
            $now = CarbonImmutable::now();

            $updatedSince = $this->isFirstRun
                ? $now->subDays(7)
                : CarbonImmutable::parse($this->integration->next_check_provider_instances_at)->subHour();

            $client = Http::withToken($this->integration->access_token);
            $this->sync($repository, $apiService, $updatedSince,$client);

            $this->updateNextCheckTime($integrationRepository, $now);
        });
    }
    private function sync(WorkspaceAchievementUpdateOrCreateRepository $repository,AsanaApiServiceInterface $apiService,CarbonImmutable $updatedSince,PendingRequest $client):void
    {
        $projects = $apiService->getProjects($this->integration->access_token,$this->cloudId,$client);
        foreach ($projects as $project) {
            $apiService->syncCompletedIssuesForProject(
                $project['gid'],
                $repository,
                $project['name'],
                $updatedSince->toIso8601String(),
                $this->integration->access_token,
                $client,
                function ($task) use ($repository, $project) {
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
                            'project_name' => $project['name'],
                        ]);
                    }
                }
            );
        }
    }
    private function updateNextCheckTime(UpdateIntegrationInstanceRepositoryInterface $repository, CarbonImmutable $checkTime): void
    {
        $repository->update($this->integration, [
            'next_check_provider_instances_at' => $checkTime->addHour()
        ]);
    }
}
