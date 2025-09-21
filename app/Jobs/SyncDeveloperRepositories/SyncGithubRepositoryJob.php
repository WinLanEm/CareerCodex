<?php

namespace App\Jobs\SyncDeveloperRepositories;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;

class SyncGithubRepositoryJob extends SyncRepositoryBaseJob
{
    private const GRAPHQL_URL = 'https://api.github.com/graphql';


    public function __construct(
        Integration $integration,
        string $defaultBranch,
        CarbonImmutable $updatedSince,
        readonly private string $repoName,
    ) {
        parent::__construct($integration, $defaultBranch, $updatedSince);
    }

    protected function sync(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository, PendingRequest $client): void
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

        $searchQuery = "repo:{$this->repoName} is:pr is:merged updated:>" . $this->updatedSince->format('Y-m-d');

        $graphqlQuery = <<<'GQL'
        query ($searchQuery: String!, $max: Int!) {
          search(query: $searchQuery, type: ISSUE, first: $max) {
            nodes {
              ... on PullRequest {
                number
                title
                url
                mergedAt
                additions
                deletions
                repository {
                  nameWithOwner
                }
              }
            }
          }
        }
        GQL;

        $response = $client->post(self::GRAPHQL_URL, [
            'query' => $graphqlQuery,
            'variables' => [
                'searchQuery' => $searchQuery,
                'max' => $this->maxActivities,
            ],
        ]);

        $response->throw();

        // Данные лежат в data.search.nodes
        $pullRequests = $response->json('data.search.nodes', []);

        foreach ($pullRequests as $pr) {
            // Пропускаем, если узел не является PR (хотя в нашем запросе это маловероятно)
            if (empty($pr)) continue;

            $this->maxActivities--;

            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'pull_request',
                'external_id' => $pr['number'],
                'repository_name' => $pr['repository']['nameWithOwner'],
                'title' => mb_substr($pr['title'], 0, 255),
                'url' => $pr['url'],
                'completed_at' => CarbonImmutable::parse($pr['mergedAt']),
                'additions' => $pr['additions'] ?? 0,
                'deletions' => $pr['deletions'] ?? 0,
            ]);
        }
    }

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository, PendingRequest $client): void
    {
        if ($this->maxActivities <= 0) return;

        [$owner, $repo] = explode('/', $this->repoName);

        $graphqlQuery = <<<'GQL'
        query ($owner: String!, $repo: String!, $branch: String!, $since: GitTimestamp!, $max: Int!) {
          repository(owner: $owner, name: $repo) {
            ref(qualifiedName: $branch) {
              target {
                ... on Commit {
                  history(since: $since, first: $max) {
                    nodes {
                      oid
                      message
                      url
                      committedDate
                      additions
                      deletions
                    }
                  }
                }
              }
            }
          }
        }
        GQL;

        $response = $client->post(self::GRAPHQL_URL, [
            'query' => $graphqlQuery,
            'variables' => [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $this->defaultBranch,
                'since' => $this->updatedSince->toIso8601String(),
                'max' => $this->maxActivities,
            ],
        ]);

        $response->throw();

        $commits = $response->json('data.repository.ref.target.history.nodes', []);

        foreach ($commits as $commit) {
            $this->maxActivities--;
            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'commit',
                'external_id' => $commit['oid'],
                'repository_name' => $this->repoName,
                'title' => mb_substr($commit['message'], 0, 255),
                'url' => $commit['url'],
                'completed_at' => CarbonImmutable::parse($commit['committedDate']),
                'additions' => $commit['additions'] ?? 0,
                'deletions' => $commit['deletions'] ?? 0,
            ]);
        }
    }
}
