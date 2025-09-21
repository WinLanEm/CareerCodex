<?php

namespace App\Services\HttpServices;

use App\Contracts\Services\HttpServices\GitlabApiServiceInterface;
use App\Exceptions\ApiRateLimitExceededException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Redis;

class GitlabApiService implements GitlabApiServiceInterface
{
    public function syncRepositories(PendingRequest $client, \Closure $closure): void
    {
        $page = 1;
        do {
            $repositoriesOnPage = Redis::throttle('gitlab_api')->allow(40)->every(60)->block(5)->then(
                function () use($client, &$page) {
                    $response = $client->get('https://gitlab.com/api/v4/projects', [
                            'membership' => true,
                            'per_page' => 100,
                            'page' => $page,
                        ]);

                    $response->throw();
                    $page++;
                    return $response->json();
                },
                function () {
                    throw new ApiRateLimitExceededException('Gitlab API rate limit exceeded while fetching repositories.',30);
                }
            );

            foreach ($repositoriesOnPage as $repository) {
                $closure($repository);
            }
        } while (!empty($repositoriesOnPage));
    }

    public function getMergedPullRequests(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince): array
    {
        return Redis::throttle('gitlab_api')->allow(40)->every(60)->then(
            function () use($client, $updatedSince,$projectId, $limit) {
                $response = $client
                    ->get("https://gitlab.com/api/v4/projects/{$projectId}/merge_requests", [
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
            function(){
                throw new ApiRateLimitExceededException('GitLab API rate limit exceeded.',30);
            }
        );
    }
    public function getCommits(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince,string $branch): array
    {
        return Redis::throttle('gitlab_api')->allow(40)->every(60)->then(
            function () use($client, $projectId, $limit, $updatedSince,$branch) {
                $response = $client
                    ->get("https://gitlab.com/api/v4/projects/{$projectId}/repository/commits", [
                        'ref_name' => $branch,
                        'since' => $updatedSince->toIsoString(),
                        'with_stats' => true,
                        'per_page' => $limit,
                    ]);

                $response->throw();
                return $response->json();
            },
            function(){
                throw new ApiRateLimitExceededException('GitLab API rate limit exceeded.',30);
            }
        );
    }
}
