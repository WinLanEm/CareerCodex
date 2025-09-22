<?php

namespace App\Services\HttpServices;

use App\Contracts\Services\HttpServices\Bitbucket\BitbucketActivityFetchInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRepositorySyncInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

class BitbucketApiService implements BitbucketRepositorySyncInterface, BitbucketRegisterWebhookInterface, BitbucketActivityFetchInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
    )
    {}
    public function syncRepositories(PendingRequest $client,\Closure $closure):void
    {
        $nextPageUrl = config('services.bitbucket_integration.sync_repositories_url');

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
    public function getMergedPullRequests(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,CarbonImmutable $updatedSince): array
    {
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
    public function getExtendedInfo(PendingRequest $client,string $url):array
    {
        $additions = 0;
        $deletions = 0;
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
    public function getCommits(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,string $defaultBranch): array
    {
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
    public function registerWebhook(Integration $integration,string $fullRepoName):void
    {

    }
}
