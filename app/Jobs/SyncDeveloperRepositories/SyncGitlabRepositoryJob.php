<?php

namespace App\Jobs\SyncDeveloperRepositories;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabActivityFetchInterface;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository,GitlabActivityFetchInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($developerActivityRepository, $apiService) {
            $this->syncMergedMergeRequests($developerActivityRepository,$apiService);
            if ($this->maxActivities <= 0) {
                return;
            }
            $this->syncCommits($developerActivityRepository,$apiService);
        });
    }

    private function syncMergedMergeRequests(UpdateOrCreateDeveloperActivityInterface $activityRepository,GitlabActivityFetchInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        $mergeRequests = $apiService->getMergedPullRequests($this->integration->access_token,$this->projectId,$this->maxActivities,$this->updatedSince);

        foreach ($mergeRequests as $mr) {
            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'pull_request',
                'external_id' => $mr['iid'],
                'is_from_provider' => true,
                'repository_name' => $this->repoName,
                'title' => mb_substr($mr['title'], 0, 255),
                'url' => $mr['web_url'],
                'completed_at' => CarbonImmutable::parse($mr['merged_at']),
                'additions' => $mr['stats']['additions'] ?? 0,
                'deletions' => $mr['stats']['deletions'] ?? 0,
            ]);
        }
    }

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository,GitlabActivityFetchInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        $commits = $apiService->getCommits($this->integration->access_token,$this->projectId,$this->maxActivities,$this->updatedSince,$this->defaultBranch);

        foreach ($commits as $commit) {
            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'commit',
                'external_id' => $commit['id'],
                'repository_name' => $this->repoName,
                'is_from_provider' => true,
                'title' => mb_substr($commit['title'], 0, 255),
                'url' => $commit['web_url'],
                'completed_at' => CarbonImmutable::parse($commit['committed_date']),
                'additions' => $commit['stats']['additions'] ?? 0,
                'deletions' => $commit['stats']['deletions'] ?? 0,
            ]);
        }
    }
}
