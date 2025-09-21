<?php

namespace App\Jobs\SyncDeveloperRepositories;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

class SyncGitlabRepositoryJob extends SyncRepositoryBaseJob
{
    public function __construct(
        Integration $integration,
        string $defaultBranch,
        CarbonImmutable $updatedSince,
        readonly private int $projectId,
        readonly private string $repoName,
    )
    {
        parent::__construct($integration, $defaultBranch,$updatedSince);
    }

    protected function sync(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository,PendingRequest $client): void
    {
        $this->syncMergedMergeRequests($developerActivityRepository,$client);
        if ($this->maxActivities <= 0) {
            return;
        }
        $this->syncCommits($developerActivityRepository,$client);
    }

    private function syncMergedMergeRequests(UpdateOrCreateDeveloperActivityInterface $activityRepository,PendingRequest $client): void
    {
        if ($this->maxActivities <= 0) return;
        $response = $client
            ->get("https://gitlab.com/api/v4/projects/{$this->projectId}/merge_requests", [
                'state' => 'merged',
                'updated_after' => $this->updatedSince->toIso8601String(),
                'with_stats' => true, // Попросить GitLab сразу включить статистику
                'per_page' => $this->maxActivities,
                'order_by' => 'updated_at',
                'sort' => 'desc',
            ]);

        $response->throw();
        $mergeRequests = $response->json();

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

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository,PendingRequest $client): void
    {
        if ($this->maxActivities <= 0) return;

        $response = $client
            ->get("https://gitlab.com/api/v4/projects/{$this->projectId}/repository/commits", [
                'ref_name' => $this->defaultBranch,
                'since' => $this->updatedSince->toIso8601String(),
                'with_stats' => true,
                'per_page' => $this->maxActivities,
            ]);

        $response->throw();
        $commits = $response->json();

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
