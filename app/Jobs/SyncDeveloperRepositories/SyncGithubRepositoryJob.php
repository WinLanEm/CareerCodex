<?php

namespace App\Jobs\SyncDeveloperRepositories;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Services\HttpServices\Github\GithubActivityFetchInterface;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGithubRepositoryJob implements ShouldQueue
{
    use HandlesGitSyncErrors, Queueable;
    protected int $maxActivities = 10;
    public function __construct(
        readonly protected Integration $integration,
        readonly protected string $defaultBranch,
        readonly protected CarbonImmutable $updatedSince,
        readonly protected string $repoName,
    ) {}

    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository, GithubActivityFetchInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($developerActivityRepository, $apiService) {
            $this->syncMergedPullRequests($developerActivityRepository,$apiService);
            if ($this->maxActivities <= 0) {
                return;
            }
            $this->syncCommits($developerActivityRepository,$apiService);
        });
    }

    private function syncMergedPullRequests(UpdateOrCreateDeveloperActivityInterface $activityRepository, GithubActivityFetchInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        $searchQuery = "repo:{$this->repoName} is:pr is:merged updated:>" . $this->updatedSince->format('Y-m-d');

        $pullRequests = $apiService->getMergedPullRequests($this->integration, $searchQuery,$this->maxActivities);

        foreach ($pullRequests ?? [] as $pr) {
            if (empty($pr)) continue;

            $this->maxActivities--;

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'pull_request',
                'external_id' => $pr['number'],
                'repository_name' => $pr['repository']['nameWithOwner'],
                'title' => mb_substr($pr['title'], 0, 255),
                'url' => $pr['url'],
                'is_from_provider' => true,
                'completed_at' => CarbonImmutable::parse($pr['mergedAt']),
                'additions' => $pr['additions'] ?? 0,
                'deletions' => $pr['deletions'] ?? 0,
            ]);
        }
    }

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository, GithubActivityFetchInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        [$owner, $repo] = explode('/', $this->repoName);

        $commits = $apiService->getCommits($this->integration, $owner, $repo,$this->defaultBranch,$this->updatedSince->toISOString(),$this->maxActivities);

        foreach ($commits ?? [] as $commit) {
            $this->maxActivities--;
            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'commit',
                'external_id' => $commit['oid'],
                'repository_name' => $this->repoName,
                'title' => mb_substr($commit['message'], 0, 255),
                'url' => $commit['url'],
                'is_from_provider' => true,
                'completed_at' => CarbonImmutable::parse($commit['committedDate']),
                'additions' => $commit['additions'] ?? 0,
                'deletions' => $commit['deletions'] ?? 0,
            ]);
        }
    }
}
