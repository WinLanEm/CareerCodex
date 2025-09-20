<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Http\Middleware\ThrottleGithubApi;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGithubRepositoryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private string $repoName,
        readonly private string $defaultBranch,
        readonly private CarbonImmutable $updatedSince
    )
    {

    }

    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository): void
    {
        try {
            $this->syncMergedPullRequests($developerActivityRepository);
            $this->syncCommits($developerActivityRepository);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if ($e->response->status() === 409) {
                //пустой репозиторий, не надо обрабатывать
            } else {
                throw $e;
            }
        }
    }

    private function syncMergedPullRequests(
        UpdateOrCreateDeveloperActivityInterface $activityRepository,
    ): void {
        $query = "repo:{$this->repoName} is:pr is:merged updated:>" . $this->updatedSince->format('Y-m-d');

        $response = Http::withToken($this->integration->access_token)
            ->get('https://api.github.com/search/issues', ['q' => $query]);

        $response->throw();

        $pullRequests = $response->json('items',[]);

        if (empty($pullRequests)) {
            return;
        }

        foreach ($pullRequests as $pr) {
            $prDetails = $this->fetchPullRequestDetails($pr['pull_request']['url']);

            $activityRepository->updateOrCreateDeveloperActivity(
                [
                    'integration_id' => $this->integration->id,
                    'type' => 'pull_request',
                    'external_id' => $pr['number'],
                    'repository_name' => $this->repoName,
                    'title' => $pr['title'],
                    'url' => $pr['html_url'],
                    'completed_at' => CarbonImmutable::parse($pr['closed_at']),
                    'additions' => $prDetails['additions'] ?? 0,
                    'deletions' => $prDetails['deletions'] ?? 0,
                ]
            );
        }
    }


    private function syncCommits(
        UpdateOrCreateDeveloperActivityInterface $activityRepository,
    ): void {
        $response = Http::withToken($this->integration->access_token)
            ->get("https://api.github.com/repos/{$this->repoName}/commits", [
                'sha' => $this->defaultBranch,
                'since' => $this->updatedSince->toIso8601String(),
            ]);

        $response->throw();

        foreach ($response->json() as $commit) {
            $commitDetails = $this->fetchCommitDetails($commit['url']);

            $activityRepository->updateOrCreateDeveloperActivity(
                [
                    'integration_id' => $this->integration->id,
                    'type' => 'commit',
                    'external_id' => $commit['sha'],
                    'repository_name' => $this->repoName,
                    'title' => mb_substr($commit['commit']['message'], 0, 255),
                    'url' => $commit['html_url'],
                    'completed_at' => CarbonImmutable::parse($commit['commit']['author']['date']),
                    'additions' => $commitDetails['stats']['additions'] ?? 0,
                    'deletions' => $commitDetails['stats']['deletions'] ?? 0,
                ]
            );
        }
    }

    private function fetchPullRequestDetails(string $url): array
    {
        try {
            $response = Http::withToken($this->integration->access_token)->get($url);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error("Could not fetch PR details from {$url}: ",[
                'integration_id' => $this->integration->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
            ]);
            return [];
        }
    }

    private function fetchCommitDetails(string $url): array
    {
        try {
            $response = Http::withToken($this->integration->access_token)->get($url);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error("Could not fetch commit details from {$url}: ",[
                'integration_id' => $this->integration->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
            ]);
            return [];
        }
    }
}
