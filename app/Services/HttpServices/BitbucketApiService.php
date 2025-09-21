<?php

namespace App\Services\HttpServices;

use App\Contracts\Services\HttpServices\BitbucketApiServiceInterface;
use App\Exceptions\ApiRateLimitExceededException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Redis;

class BitbucketApiService implements BitbucketApiServiceInterface
{
    public function syncRepositories(PendingRequest $client,\Closure $closure):void
    {
        $nextPageUrl = "https://api.bitbucket.org/2.0/repositories?role=member";

        do {
            $repositoriesOnPage = Redis::throttle('bitbucket-api')->allow(20)->every(60)->then(
                function () use ($client, &$nextPageUrl)
                {
                    $response = $client->get($nextPageUrl);
                    $response->throw();

                    $pageJson = $response->json();
                    $nextPageUrl = $pageJson['next'] ?? null;

                    return $pageJson['values'];
                },
                function () {
                    throw new ApiRateLimitExceededException('GitHub API rate limit exceeded.',30);
                }
            );

            foreach ($repositoriesOnPage as $repository) {
                $closure($repository);
            }
        } while ($nextPageUrl);
    }
    public function getMergedPullRequests(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,CarbonImmutable $updatedSince): array
    {
        return Redis::throttle('bitbucket-api')->allow(20)->every(60)->then(
            function () use($client, $workspaceSlug,$repoSlug, $limit,$updatedSince) {
                $response = $client->get("https://api.bitbucket.org/2.0/repositories/{$workspaceSlug}/{$repoSlug}/pullrequests", [
                    'state' => 'MERGED',
                    'pagelen' => $limit,
                    'sort' => '-updated_on',
                    'q' => "updated_on >= \"{$updatedSince->toIsoString()}\"",
                ]);
                $response->throw();
                return $response->json('values', []);
            },
            function(){
                throw new ApiRateLimitExceededException('GitLab API rate limit exceeded.',30);
            }
        );
    }
    public function getExtendedInfo(PendingRequest $client,string $url):array
    {
        $additions = 0;
        $deletions = 0;
        Redis::throttle('bitbucket-api')->allow(20)->every(60)->then(
            function () use($client, $url,&$additions,&$deletions) {
                $statsResponse = $client->get($url);
                if ($statsResponse->ok()) {
                    foreach ($statsResponse->json('values', []) as $stat) {
                        $additions += $stat['lines_added'] ?? 0;
                        $deletions += $stat['lines_removed'] ?? 0;
                    }
                }
            },
            function(){
                throw new ApiRateLimitExceededException('GitLab API rate limit exceeded.',30);
            }
        );
        return ['additions' => $additions, 'deletions' => $deletions];
    }
    public function getCommits(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,string $defaultBranch): array
    {
        return Redis::throttle('bitbucket-api')->allow(20)->every(60)->then(
            function () use($client, $workspaceSlug,$repoSlug,$limit,$defaultBranch) {
                $response = $client->get("https://api.bitbucket.org/2.0/repositories/{$workspaceSlug}/{$repoSlug}/commits", [
                    'include' => $defaultBranch,
                    'pagelen' => $limit,
                ]);
                $response->throw();
                return $response->json('values', []);
            },
            function(){
                throw new ApiRateLimitExceededException('GitLab API rate limit exceeded.',30);
            }
        );
    }
}
