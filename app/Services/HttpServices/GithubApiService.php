<?php

namespace App\Services\HttpServices;


use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Github\GithubActivityFetchInterface;
use App\Contracts\Services\HttpServices\Github\GithubRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Github\GithubRepositorySyncInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GithubApiService implements GithubRepositorySyncInterface, GithubActivityFetchInterface, GithubRegisterWebhookInterface
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
    public function registerWebhook(Integration $integration,string $fullRepoName,UpdateOrCreateWebhookRepositoryInterface $repository): void
    {
        $token = $integration->access_token;
        $client = Http::withToken($token);
        $webhookUrl = route('webhook', ['service' => 'github']);

        $url = config('services.github_integration.get_hooks_url');
        $url = str_replace('{fullRepoName}', $fullRepoName, $url);

        $existingHooksResponse = $client->get($url);
        $existingHooksResponse->throw();
        $existingHooks = $existingHooksResponse->json();

        foreach ($existingHooks as $hook) {
            if (isset($hook['config']['url']) && $hook['config']['url'] === $webhookUrl) {
                // Если не активен — активируем
                if (!$hook['active']) {
                    $client->patch("{$url}/{$hook['id']}", ['active' => true])->throw();
                }
                return;
            }
        }

        $webhookSecret = bin2hex(random_bytes(32));

        $payload = [
            'name' => 'web',
            'active' => true,
            'events' => [
                'push',
                'pull_request',
            ],
            'config' => [
                'url' => $webhookUrl,
                'content_type' => 'json',
                'secret' => $webhookSecret,
                'insecure_ssl' => '1',
                //изменить на 0 в проде
            ],
        ];

        $response = $client->post($url, $payload);
        $response->throw();

        $hook = $response->json();

        $repository->updateOrCreateWebhook(
            [
                'integration_id' => $integration->id,
                'repository' => $fullRepoName,
                'webhook_id' => $hook['id'],
                'secret' => $webhookSecret,
                'events' => $hook['events'] ? json_encode($hook['events']) : json_encode(['push', 'pull_request']),
                'active' => $hook['active'],
            ]
        );
    }
}
