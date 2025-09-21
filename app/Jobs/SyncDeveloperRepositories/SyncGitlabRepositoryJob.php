<?php

namespace App\Jobs\SyncDeveloperRepositories;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Services\HttpServices\GitlabApiServiceInterface;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SyncGitlabRepositoryJob implements ShouldQueue
{
    use HandlesGitSyncErrors, Queueable;
    protected int $maxActivities = 10;
    public function __construct(
        readonly protected Integration $integration,
        readonly protected string $defaultBranch,
        readonly protected CarbonImmutable $updatedSince,
        readonly protected int $projectId,
        readonly protected string $repoName,
    )
    {}

    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository,GitlabApiServiceInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($developerActivityRepository, $apiService) {
            $client = Http::withToken($this->integration->access_token);
            $this->syncMergedMergeRequests($developerActivityRepository,$client,$apiService);
            if ($this->maxActivities <= 0) {
                return;
            }
            $this->syncCommits($developerActivityRepository,$client,$apiService);
        });
    }

    private function syncMergedMergeRequests(UpdateOrCreateDeveloperActivityInterface $activityRepository,PendingRequest $client,GitlabApiServiceInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        $mergeRequests = $apiService->getMergedPullRequests($client,$this->projectId,$this->maxActivities,$this->updatedSince);

        foreach ($mergeRequests as $mr) {
            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'pull_request',
                'external_id' => $mr['iid'],
                'repository_name' => $this->repoName,
                'title' => mb_substr($mr['title'], 0, 255),
                'url' => $mr['web_url'],
                'completed_at' => CarbonImmutable::parse($mr['merged_at']),
                'additions' => $mr['stats']['additions'] ?? 0,
                'deletions' => $mr['stats']['deletions'] ?? 0,
            ]);
        }
    }

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository,PendingRequest $client,GitlabApiServiceInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        $commits = $apiService->getCommits($client,$this->projectId,$this->maxActivities,$this->updatedSince,$this->defaultBranch);

        foreach ($commits as $commit) {
            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'commit',
                'external_id' => $commit['id'],
                'repository_name' => $this->repoName,
                'title' => mb_substr($commit['title'], 0, 255),
                'url' => $commit['web_url'],
                'completed_at' => CarbonImmutable::parse($commit['committed_date']),
                'additions' => $commit['stats']['additions'] ?? 0,
                'deletions' => $commit['stats']['deletions'] ?? 0,
            ]);
        }
    }
}
