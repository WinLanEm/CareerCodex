<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketActivityFetchInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRepositorySyncInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use App\Models\Webhook;
use App\Repositories\Webhook\EloquentWebhookRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitbucketApiService implements BitbucketRepositorySyncInterface, BitbucketRegisterWebhookInterface, BitbucketActivityFetchInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
        private EloquentWebhookRepository $webhookRepository,
    )
    {}
    public function syncRepositories(string $token,\Closure $closure):void
    {
        $nextPageUrl = config('services.bitbucket_integration.sync_repositories_url');
        $client = Http::withToken($token);
        do {
            $repositoriesOnPage = $this->throttleService->for(
                ServiceConnectionsEnum::BITBUCKET,
                function () use ($client, &$nextPageUrl)
                {
                    $response = $client->get($nextPageUrl);
                    $response->throw();

                    $pageJson = $response->json();
                    $nextPageUrl = $pageJson['next'] ?? null;

                    return $pageJson['values'];
                },
            );

            foreach ($repositoriesOnPage as $repository) {
                $closure($repository);
            }
        } while ($nextPageUrl);
    }
    public function getMergedPullRequests(string $token,string $workspaceSlug, string $repoSlug,int $limit,CarbonImmutable $updatedSince): array
    {
        $client = Http::withToken($token);
        return $this->throttleService->for(
            ServiceConnectionsEnum::BITBUCKET,
            function () use($client, $workspaceSlug,$repoSlug, $limit,$updatedSince) {
                $url = config('services.bitbucket_integration.get_merged_pull_requests_url');
                $url = str_replace(['{workspaceSlug}','{repoSlug}'],[$workspaceSlug,$repoSlug],$url);
                $response = $client->get($url, [
                    'state' => 'MERGED',
                    'pagelen' => $limit,
                    'sort' => '-updated_on',
                    'q' => "updated_on >= \"{$updatedSince->toIsoString()}\"",
                ]);
                $response->throw();
                return $response->json('values', []);
            },
        );
    }
    public function getExtendedInfo(string $token,string $url):array
    {
        $additions = 0;
        $deletions = 0;
        $client = Http::withToken($token);
        $this->throttleService->for(
            ServiceConnectionsEnum::BITBUCKET,
            function () use($client, $url,&$additions,&$deletions) {
                $statsResponse = $client->get($url);
                if ($statsResponse->ok()) {
                    foreach ($statsResponse->json('values', []) as $stat) {
                        $additions += $stat['lines_added'] ?? 0;
                        $deletions += $stat['lines_removed'] ?? 0;
                    }
                }
            },
        );
        return ['additions' => $additions, 'deletions' => $deletions];
    }
    public function getCommits(string $token,string $workspaceSlug, string $repoSlug,int $limit,string $defaultBranch): array
    {
        $client = Http::withToken($token);
        return $this->throttleService->for(
            ServiceConnectionsEnum::BITBUCKET,
            function () use($client, $workspaceSlug,$repoSlug,$limit,$defaultBranch) {
                $url = config('services.bitbucket_integration.get_merged_pull_requests_url');
                $url = str_replace(['{workspaceSlug}','{repoSlug}'],[$workspaceSlug,$repoSlug],$url);
                $response = $client->get($url, [
                    'include' => $defaultBranch,
                    'pagelen' => $limit,
                ]);
                $response->throw();
                return $response->json('values', []);
            },
        );
    }
    public function registerWebhook(Integration $integration,string $workspaceSlug,string $repoSlug,string $repositoryId):array
    {
        return $this->throttleService->for(ServiceConnectionsEnum::BITBUCKET,function () use($repositoryId,$integration,$workspaceSlug,$repoSlug){
            $token = $integration->access_token;
            $client = Http::withToken($token);

            $url = config('services.bitbucket_integration.get_hooks_url');
            $url = str_replace(['{workspaceSlug}','{repoSlug}'],[$workspaceSlug,$repoSlug],$url);

            $webhookUrl = route('webhook', ['service' => 'bitbucket']);

            $existingHooksResponse = $client->get($url);
            $existingHooksResponse->throw();
            $existingHooks = $existingHooksResponse->json('values') ?? [];

            foreach ($existingHooks as $hook) {
                if (isset($hook['url']) && $hook['url'] === $webhookUrl) {
                    $webhook = $this->webhookRepository->find(
                        function (Builder $query) use($hook){
                            return $query->where('webhook_id', $hook['uuid']);
                        }
                    );
                    if ($webhook) {
                        return $webhook->toArray();
                    }
                    return [];
                }
            }

            $webhookSecret = bin2hex(random_bytes(32));

            $payload = [
                'description' => 'Webhook for app integration',
                'url' => $webhookUrl,
                'active' => true,
                'events' => [
                    'repo:push',
                    'pullrequest:fulfilled'
                ],
                'secret' => $webhookSecret,
            ];

            $response = $client->post($url, $payload);
            $response->throw();

            if(!$response->successful()) {
                return [];
            }

            $hook = $response->json();

            return [
                'integration_id' => $integration->id,
                'repository' => "$workspaceSlug" . "/" ."$repoSlug",
                'repository_id' => $repositoryId,
                'webhook_id' => $hook['uuid'],
                'secret' => $webhookSecret,
                'events' => json_encode($payload['events']),
                'active' => $hook['active'] ?? true,
            ];
        });
    }
}
