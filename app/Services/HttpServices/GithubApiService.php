<?php

namespace App\Services\HttpServices;


use App\Contracts\Services\HttpServices\GithubApiServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use Illuminate\Http\Client\PendingRequest;

class GithubApiService implements GithubApiServiceInterface
{
    private const GRAPHQL_URL = 'https://api.github.com/graphql';

    public function __construct(
        private ThrottleServiceInterface $throttleService,
    )
    {}

    public function syncRepositories(PendingRequest $client, \Closure $closure): void
    {
        $page = 1;
        do {
            $repositoriesOnPage = $this->throttleService->for(
                ServiceConnectionsEnum::GITHUB,
                function () use ($client, &$page) {
                    $url = config('services.github_integration.sync_repositories_url');
                    $response = $client->get($url, [
                        'per_page' => 100,
                        'page' => $page,
                    ]);
                    $response->throw();
                    $page++;
                    return $response->json();
                },
            );

            foreach ($repositoriesOnPage as $repo) {
                $closure($repo);
            }

        } while (!empty($repositoriesOnPage));
    }
    public function getMergedPullRequests(PendingRequest $client,string $searchQuery, int $limit): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::GITHUB,
            function () use ($searchQuery, $limit,$client) {
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
                $url = config('services.github_integration.graph_ql_url');
                $response = $client->post($url, [
                    'query' => $graphqlQuery,
                    'variables' => [
                        'searchQuery' => $searchQuery,
                        'max' => $limit,
                    ],
                ]);
                $response->throw();
                return $response->json('data.search.nodes', []);
            },
        );
    }
    public function getCommits(PendingRequest $client,string $owner, string $repo, string $branch, string $since, int $limit): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::GITHUB,
            function () use ($owner, $repo, $branch, $since, $limit,$client) {
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
                $url = config('services.github_integration.graph_ql_url');
                $response = $client->post($url, [
                    'query' => $graphqlQuery,
                    'variables' => [
                        'owner' => $owner,
                        'repo' => $repo,
                        'branch' => $branch,
                        'since' => $since,
                        'max' => $limit,
                    ],
                ]);
                $response->throw();
                return $response->json('data.repository.ref.target.history.nodes', []);
            },
        );
    }
}
