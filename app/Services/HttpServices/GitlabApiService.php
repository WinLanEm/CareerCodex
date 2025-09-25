<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabActivityFetchInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabRepositorySyncInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GitlabApiService implements GitlabActivityFetchInterface, GitlabRegisterWebhookInterface, GitlabRepositorySyncInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
    )
    {}
    public function syncRepositories(PendingRequest $client, \Closure $closure): void
    {
        $page = 1;
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

    public function getMergedPullRequests(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince): array
    {
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
    public function getCommits(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince,string $branch): array
    {
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

            $url = config('services.gitlab_integration.get_hooks_url');
            $url = str_replace('{projectId}', $projectId, $url);

            $existingHooksResponse = $client->get($url);
            $existingHooksResponse->throw();
            $existingHooks = $existingHooksResponse->json();

            $webhookUrl = route('webhook', ['service' => 'gitlab']);


            foreach ($existingHooks as $hook) {
                if (isset($hook['url']) && $hook['url'] === $webhookUrl) {
                    return [];
                }
            }

            $webhookSecret = bin2hex(random_bytes(32));

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
