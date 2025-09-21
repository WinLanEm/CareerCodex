<?php

namespace App\Jobs\SyncDeveloperRepositories;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncBitbucketRepositoryJob extends SyncRepositoryBaseJob
{
    public function __construct(
        Integration $integration,
        string $defaultBranch,
        CarbonImmutable $updatedSince,
        readonly private string $workspaceSlug,
        readonly private string $repoSlug,
    )
    {
        parent::__construct($integration, $defaultBranch,$updatedSince);
    }

    protected function sync(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository,PendingRequest $client): void
    {
        $this->syncMergedPullRequests($developerActivityRepository, $client);
        if ($this->maxActivities <= 0) {
            return;
        }
        $this->syncCommits($developerActivityRepository, $client);
    }

    private function syncMergedPullRequests(UpdateOrCreateDeveloperActivityInterface $activityRepository, PendingRequest $client): void
    {
        if ($this->maxActivities <= 0) return;

        $updatedSinceQuery = $this->updatedSince->toIso8601String();
        $response = $client->get("https://api.bitbucket.org/2.0/repositories/{$this->workspaceSlug}/{$this->repoSlug}/pullrequests", [
            'state' => 'MERGED',
            'pagelen' => $this->maxActivities,
            'sort' => '-updated_on',
            'q' => "updated_on >= \"{$updatedSinceQuery}\"",
        ]);
        $response->throw();
        $pullRequests = $response->json('values', []);

        foreach ($pullRequests as $pr) {

            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            $additions = 0;
            $deletions = 0;

            if (isset($pr['links']['diffstat']['href'])) {
                //Доп статистика для пулл реквестов
                $diffStatResponse = $client->get($pr['links']['diffstat']['href']);
                if ($diffStatResponse->ok()) {
                    foreach ($diffStatResponse->json('values', []) as $stat) {
                        $additions += $stat['lines_added'] ?? 0;
                        $deletions += $stat['lines_removed'] ?? 0;
                    }
                }
            }

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'pull_request',
                'external_id' => $pr['id'],
                'repository_name' => $pr['destination']['repository']['full_name'],
                'title' => mb_substr($pr['title'], 0, 255),
                'url' => $pr['links']['html']['href'],
                'completed_at' => CarbonImmutable::parse($pr['updated_on']),
                'additions' => $additions,
                'deletions' => $deletions,
            ]);
        }
    }

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository, PendingRequest $client): void
    {
        if ($this->maxActivities <= 0) return;

        $response = $client->get("https://api.bitbucket.org/2.0/repositories/{$this->workspaceSlug}/{$this->repoSlug}/commits", [
            'include' => $this->defaultBranch,
            'pagelen' => $this->maxActivities,
        ]);
        $response->throw();
        $commits = $response->json('values', []);

        foreach ($commits as $commit) {
            // Сначала фильтруем по дате, так как Bitbucket API не позволяет это в запросе
            if (CarbonImmutable::parse($commit['date']) < $this->updatedSince) {
                continue;
            }

            if ($this->maxActivities <= 0) break;

            $this->maxActivities--;

            $additions = 0;
            $deletions = 0;

            if (isset($commit['links']['diffstat']['href'])) {
                //Доп статистика для комитов
                $statsResponse = $client->get($commit['links']['diffstat']['href']);
                if ($statsResponse->ok()) {
                    foreach ($statsResponse->json('values', []) as $stat) {
                        $additions += $stat['lines_added'] ?? 0;
                        $deletions += $stat['lines_removed'] ?? 0;
                    }
                }
            }

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'commit',
                'external_id' => $commit['hash'],
                'repository_name' => $commit['repository']['full_name'],
                'title' => mb_substr($commit['message'], 0, 255),
                'url' => $commit['links']['html']['href'],
                'completed_at' => CarbonImmutable::parse($commit['date']),
                'additions' => $additions,
                'deletions' => $deletions,
            ]);
        }
    }
}

