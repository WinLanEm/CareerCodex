<?php

namespace App\Services\HttpServices;


use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Github\GithubActivityFetchInterface;
use App\Contracts\Services\HttpServices\Github\GithubCheckIfAppInstalledInterface;
use App\Contracts\Services\HttpServices\Github\GithubRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Github\GithubRepositorySyncInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\User;

class GithubApiService implements GithubRepositorySyncInterface, GithubActivityFetchInterface, GithubRegisterWebhookInterface,GithubCheckIfAppInstalledInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
        private EloquentWebhookRepositoryInterface $webhookRepository,
    )
    {}

    public function syncRepositories(string $token, \Closure $closure): void
    {
        $page = 1;
        $client = Http::withToken($token);
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
    public function getMergedPullRequests(string $token,string $searchQuery, int $limit): array
    {
        $client = Http::withToken($token);
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
    public function getCommits(string $token,string $owner, string $repo, string $branch, string $since, int $limit): array
    {
        $client = Http::withToken($token);
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
    public function registerWebhook(Integration $integration,string $fullRepoName):array
    {
        return $this->throttleService->for(ServiceConnectionsEnum::GITHUB,function () use ($integration,$fullRepoName){
            $token = $integration->access_token;
            $client = Http::withToken($token)->acceptJson();
            $webhookUrl = route('webhook', ['service' => 'github']);
            $webhookSecret = config('services.github_integration.webhook_secret');

            $url = config('services.github_integration.get_hooks_url');
            $url = str_replace('{fullRepoName}', $fullRepoName, $url);

            $existingHooksResponse = $client->get($url);
            $existingHooksResponse->throw();
            $existingHooks = $existingHooksResponse->json();

            foreach ($existingHooks as $hook) {
                if (isset($hook['config']['url']) && $hook['config']['url'] === $webhookUrl) {
                    if (!$hook['active']) {
                        $client->patch("{$url}/{$hook['id']}", ['active' => true])->throw();
                    }
                    $webhook = $this->webhookRepository->find(
                        function (Builder $query) use($hook){
                            return $query->where('webhook_id', $hook['id']);
                        }
                    );
                    if ($webhook) {
                        return $webhook->toArray();
                    }
                    $client->delete("{$url}/{$hook['id']}")->throw();
                    break;
                }
            }

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
                    'insecure_ssl' => config('app.env') !== 'production' ? '1' : '0', // Безопасное определение insecure_ssl
                ],
            ];

            $response = $client->post($url, $payload);
            $response->throw();

            if(!$response->successful()) {
                return [];
            }
            $newHook = $response->json();

            return [
                'integration_id' => $integration->id,
                'repository' => $fullRepoName,
                'webhook_id' => $newHook['id'],
                'secret' => $webhookSecret,
                'events' => json_encode($newHook['events']),
                'active' => $newHook['active'],
            ];
        });
    }

    public function checkIfAppIsInstalled(User $user):bool
    {
        return $this->throttleService->for(ServiceConnectionsEnum::GITHUB,function () use ($user){
            $client = Http::withToken($user->token)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json']);

            $url = config('services.github_integration.check_app_installation_url');
            $response = $client->get($url);

            $response->throw();

            $appSlug = config('services.github_integration.app_slug');
            foreach ($response->json('installations', []) as $installation) {
                if (isset($installation['app_slug']) && $installation['app_slug'] === $appSlug) {
                    return true;
                }
            }

            return false;
        });
    }
}
