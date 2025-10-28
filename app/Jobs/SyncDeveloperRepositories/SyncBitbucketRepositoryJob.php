<?php

namespace App\Jobs\SyncDeveloperRepositories;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketActivityFetchInterface;
use App\Models\Integration;
use App\Traits\HandlesGitSyncErrors;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncBitbucketRepositoryJob implements ShouldQueue
{
    use HandlesGitSyncErrors, Queueable;
    protected int $maxActivities = 10;
    public function __construct(
        readonly protected Integration $integration,
        readonly protected string $defaultBranch,
        readonly protected CarbonImmutable $updatedSince,
        readonly protected string $workspaceSlug,
        readonly protected string $repoSlug,
    )
    {}


    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository,BitbucketActivityFetchInterface $apiService):void
    {
        $this->executeWithHandling(function () use ($developerActivityRepository, $apiService) {
            $this->syncMergedPullRequests($developerActivityRepository,$apiService);
            if ($this->maxActivities <= 0) {
                return;
            }
            $this->syncCommits($developerActivityRepository,$apiService);
        });
    }

    private function syncMergedPullRequests(UpdateOrCreateDeveloperActivityInterface $activityRepository,BitbucketActivityFetchInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        $pullRequests = $apiService->getMergedPullRequests($this->integration->access_token,$this->workspaceSlug,$this->repoSlug,$this->maxActivities,$this->updatedSince);


        foreach ($pullRequests as $pr) {

            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            if (isset($pr['links']['diffstat']['href'])) {
                $additionsAndDeletions = $apiService->getExtendedInfo($this->integration->access_token,$pr['links']['diffstat']['href']);
            }

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'pull_request',
                'external_id' => $pr['id'],
                'repository_name' => $pr['destination']['repository']['full_name'],
                'title' => mb_substr($pr['title'], 0, 255),
                'url' => $pr['links']['html']['href'],
                'is_from_provider' => true,
                'completed_at' => CarbonImmutable::parse($pr['updated_on']),
                'additions' => $additionsAndDeletions['additions'],
                'deletions' => $additionsAndDeletions['deletions'],
            ]);
        }
    }

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository,BitbucketActivityFetchInterface $apiService): void
    {
        if ($this->maxActivities <= 0) return;

        $commits = $apiService->getCommits($this->integration->access_token,$this->workspaceSlug,$this->repoSlug,$this->maxActivities,$this->defaultBranch);

        foreach ($commits as $commit) {
            // Сначала фильтруем по дате, так как Bitbucket API не позволяет это в запросе
            if (CarbonImmutable::parse($commit['date']) < $this->updatedSince) {
                continue;
            }

            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            $additionsAndDeletions = ['additions' => 0, 'deletions' => 0];
            if (isset($commit['links']['diffstat']['href'])) {
                $additionsAndDeletions = $apiService->getExtendedInfo($this->integration->access_token,$commit['links']['diffstat']['href']);
            }

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'commit',
                'external_id' => $commit['hash'],
                'repository_name' => $commit['repository']['full_name'],
                'title' => mb_substr($commit['message'], 0, 255),
                'is_from_provider' => true,
                'url' => $commit['links']['html']['href'],
                'completed_at' => CarbonImmutable::parse($commit['date']),
                'additions' => $additionsAndDeletions['additions'],
                'deletions' => $additionsAndDeletions['deletions'],
            ]);
        }
    }
}

