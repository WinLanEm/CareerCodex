<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGithubJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private bool $isFirstRun,
    )
    {

    }

    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository): void
    {
        $now = CarbonImmutable::now();

        $updatedSince = $this->isFirstRun
            ? $now->subDays(7)
            : CarbonImmutable::parse($this->integration->next_check_provider_instances_at)->subHour();

        $repositories = $this->fetchUserRepositories();

        foreach ($repositories as $repo) {
            $repoName = $repo['full_name'];
            $defaultBranch = $repo['default_branch'];

            $this->syncMergedPullRequests($developerActivityRepository, $repoName, $updatedSince);

            $this->syncCommits($developerActivityRepository, $repoName, $defaultBranch, $updatedSince);
        }
    }

    private function fetchUserRepositories():array
    {
        $allRepositories = [];
        $page = 1;
        do {
            $response = Http::withToken($this->integration->access_token)
                ->get('https://api.github.com/user/repos', [
                    'per_page' => 100,
                    'page' => $page,
                ]);

            $response->throw();

            $repositoriesOnPage = $response->json();
            $allRepositories = array_merge($allRepositories, $repositoriesOnPage);
            $page++;
        } while (!empty($repositoriesOnPage));

        return $allRepositories;
    }

    private function syncMergedPullRequests(
        UpdateOrCreateDeveloperActivityInterface $activityRepository,
        string $repoName,
        CarbonImmutable $updatedSince
    ): void {
        $query = "repo:{$repoName} is:pr is:merged updated:>" . $updatedSince->format('Y-m-d');

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
                    'repository_name' => $repoName,
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
        string $repoName,
        string $defaultBranch,
        CarbonImmutable $updatedSince
    ): void {
        $response = Http::withToken($this->integration->access_token)
            ->get("https://api.github.com/repos/{$repoName}/commits", [
                'sha' => $defaultBranch,
                'since' => $updatedSince->toIso8601String(),
            ]);

        $response->throw();

        foreach ($response->json() as $commit) {
            $commitDetails = $this->fetchCommitDetails($commit['url']);

            $activityRepository->updateOrCreateDeveloperActivity(
                [
                    'integration_id' => $this->integration->id,
                    'type' => 'commit',
                    'external_id' => $commit['sha'],
                    'repository_name' => $repoName,
                    'title' => explode("\n", $commit['commit']['message'])[0],
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
