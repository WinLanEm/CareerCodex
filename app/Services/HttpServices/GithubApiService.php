<?php

namespace App\Services\HttpServices;


use App\Contracts\Services\HttpServices\GithubApiServiceInterface;
use App\Exceptions\ApiRateLimitExceededException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Redis;

class GithubApiService implements GithubApiServiceInterface
{
    private const GRAPHQL_URL = 'https://api.github.com/graphql';

    public function syncRepositories(PendingRequest $client, \Closure $closure): void
    {
        $page = 1;
        do {
            $repositoriesOnPage = Redis::throttle('github-api')->allow(50)->every(60)->block(5)->then(
                function () use ($client, &$page) {
                    $response = $client->get('https://api.github.com/user/repos', [
                        'per_page' => 100,
                        'page' => $page,
                    ]);
                    $response->throw();
                    $page++;
                    return $response->json();
                },
                function () {
                    throw new ApiRateLimitExceededException('GitHub API rate limit exceeded while fetching repositories.',30);
                }
            );

            foreach ($repositoriesOnPage as $repo) {
                $closure($repo);
            }

        } while (!empty($repositoriesOnPage));
    }
    public function getMergedPullRequests(PendingRequest $client,string $searchQuery, int $limit): array
    {
        return Redis::throttle('github-api')->allow(50)->every(60)->block(5)->then(
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
                $response = $client->post(self::GRAPHQL_URL, [
                    'query' => $graphqlQuery,
                    'variables' => [
                        'searchQuery' => $searchQuery,
                        'max' => $limit,
                    ],
                ]);
                $response->throw();
                return $response->json('data.search.nodes', []);
            },
            function () {
                throw new ApiRateLimitExceededException('GitHub API rate limit exceeded.',30);
            }
        );
    }
    public function getCommits(PendingRequest $client,string $owner, string $repo, string $branch, string $since, int $limit): array
    {
        return Redis::throttle('github-api')->allow(50)->every(60)->block(5)->then(
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
                $response = $client->post(self::GRAPHQL_URL, [
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
            function () {
                throw new ApiRateLimitExceededException('GitHub API rate limit exceeded.',30);
            }
        );
    }
}
