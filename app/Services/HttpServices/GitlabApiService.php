<?php

namespace App\Services\HttpServices;

use App\Contracts\Services\HttpServices\GitlabApiServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

class GitlabApiService implements GitlabApiServiceInterface
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
}
