<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabActivityFetchInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabRepositorySyncInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitlabApiService implements GitlabActivityFetchInterface, GitlabRegisterWebhookInterface, GitlabRepositorySyncInterface
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
                ServiceConnectionsEnum::GITLAB,
                function () use($client, &$page) {
                    $url = config('services.gitlab_integration.sync_repositories_url');
                    $response = $client->get($url, [
                        'membership' => true,
                        'per_page' => 100,
                        'page' => $page,
                    ]);

                    $response->throw();
                    $page++;
                    return $response->json();
                },
            );

            foreach ($repositoriesOnPage as $repository) {
                $closure($repository);
            }
        } while (!empty($repositoriesOnPage));
    }

    public function getMergedPullRequests(string $token,int $projectId, int $limit,CarbonImmutable $updatedSince): array
    {
        $client = Http::withToken($token);
        return $this->throttleService->for(
            ServiceConnectionsEnum::GITLAB,
            function () use($client, $updatedSince,$projectId, $limit) {
                $url = config('services.gitlab_integration.get_merged_pull_requests_url');
                $url = str_replace('{projectId}', $projectId, $url);
                $response = $client
                    ->get($url, [
                        'state' => 'merged',
                        'updated_after' => $updatedSince->toIso8601String(),
                        'with_stats' => true, // Попросить GitLab сразу включить статистику
                        'per_page' => $limit,
                        'order_by' => 'updated_at',
                        'sort' => 'desc',
                    ]);

                $response->throw();
                return $response->json();
            },
        );
    }
    public function getCommits(string $token,int $projectId, int $limit,CarbonImmutable $updatedSince,string $branch): array
    {
        $client = Http::withToken($token);
        return $this->throttleService->for(
            ServiceConnectionsEnum::GITLAB,
            function () use($client, $projectId, $limit, $updatedSince,$branch) {
                $url = config('services.gitlab_integration.get_commits_url');
                $url = str_replace('{projectId}', $projectId, $url);
                $response = $client
                    ->get($url, [
                        'ref_name' => $branch,
                        'since' => $updatedSince->toIsoString(),
                        'with_stats' => true,
                        'per_page' => $limit,
                    ]);

                $response->throw();
                return $response->json();
            },
        );
    }

    public function registerWebhook(Integration $integration, string $projectId,string $fullName): array
    {
        return $this->throttleService->for(ServiceConnectionsEnum::GITLAB,function () use($integration,$projectId,$fullName) {
            $token = $integration->access_token;
            $client = Http::withToken($token);
            $webhookSecret = bin2hex(random_bytes(32));

            $url = config('services.gitlab_integration.get_hooks_url');
            $url = str_replace('{projectId}', $projectId, $url);

            $existingHooksResponse = $client->get($url);
            $existingHooksResponse->throw();
            $existingHooks = $existingHooksResponse->json();

            $webhookUrl = route('webhook', ['service' => 'gitlab']);

            foreach ($existingHooks as $hook) {
                if (isset($hook['url']) && $hook['url'] === $webhookUrl) {

                    $webhook = $this->webhookRepository->find(
                        function (Builder $query) use($hook) {
                            return $query->where('webhook_id', $hook['id']);
                        }
                    );

                    if($webhook) {
                        return $webhook->toArray();
                    }
                    $client->delete("{$url}/{$hook['id']}")->throw();
                    break;
                }
            }



            $payload = [
                'url' => $webhookUrl,
                'push_events' => true,
                'merge_requests_events' => true,
                'enable_ssl_verification' => config('app.env') !== 'production' ? true : false,
                // в проде true
                'token' => $webhookSecret,
            ];

            $response = $client->post($url, $payload);
            $response->throw();

            if(!$response->successful()) {
                return [];
            }

            $hook = $response->json();

            return [
                'integration_id' => $integration->id,
                'repository' => $fullName,
                'webhook_id' => $hook['id'],
                'repository_id' => $projectId,
                'secret' => $webhookSecret,
                'events' => json_encode(['push_events', 'merge_requests_events']),
                'active' => true, // В GitLab нет поля active, берем true по умолчанию
            ];
        });
    }
}
