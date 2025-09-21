<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Services\HttpServices\JiraApiServiceInterface;
use App\Exceptions\ApiRateLimitExceededException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Redis;

class JiraApiService implements JiraApiServiceInterface
{
    public function getWorkspaces(string $token,PendingRequest $client): array
    {
        return Redis::throttle('asana-api')->allow(20)->every(60)->then(
            function () use($token,$client) {
                $providerInstanceUrl = config('services.jira_integration.provider_instance_url');
                $response = $client
                    ->get($providerInstanceUrl);
                $response->throw();

                return $response->json();
            },
            function () {
                throw new ApiRateLimitExceededException('Jira API rate limit exceeded.',100);
            }
        );
    }

    public function getProjects(string $token,string $cloudId,PendingRequest $client): array
    {
        $allProjects = [];
        $startAt = 0;
        $maxResults = 50;

        do {
            $responseJson = Redis::throttle('jira-api')->allow(20)->every(60)->block(10)->then(
                function () use ($token, $cloudId, $startAt, $maxResults,$client) {
                    $url = "https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/project/search";
                    $response = $client->asJson()->get($url, [
                        'startAt' => $startAt,
                        'maxResults' => $maxResults,
                    ]);
                    $response->throw();
                    return $response->json();
                },
                function () {
                    throw new ApiRateLimitExceededException('Jira API rate limit exceeded.', 100);
                }
            );

            $projects = $responseJson['values'] ?? [];
            $allProjects = array_merge($allProjects, $projects);

            $startAt += count($projects);
            $isLast = $responseJson['isLast'] ?? true;

        } while (!$isLast);

        return $allProjects;
    }

    public function syncCompletedIssuesForProject(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        CarbonImmutable $updatedSince,
        string $token,
        string $projectKey,
        string $cloudId,
        PendingRequest $client,
        \Closure $closure
    ):void
    {
        $startAt = 0;
        $maxResults = 100;
        $updatedSinceFormatted = $updatedSince->format('Y-m-d H:i');
        $jql = "project = \"{$projectKey}\" AND status = Done AND updated >= \"{$updatedSinceFormatted}\"";

        do {
            // 3. Троттлинг также перенесен внутрь этого цикла
            $responseJson = Redis::throttle('jira-api')->allow(20)->every(60)->block(10)->then(
                function () use ($token, $client,$cloudId, $jql, $startAt, $maxResults) {
                    $url = "https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/search";
                    $response = $client->asJson()->get($url, [
                        'jql' => $jql,
                        'fields' => 'summary,resolutiondate,description,project,issuetype,status',
                        'startAt' => $startAt,
                        'maxResults' => $maxResults
                    ]);
                    $response->throw();
                    return $response->json();
                },
                function () {
                    throw new ApiRateLimitExceededException('Jira API rate limit exceeded.', 100);
                }
            );

            $issues = $responseJson['issues'] ?? [];
            foreach ($issues as $issue) {
                $closure($issue);
            }

            $startAt += count($issues);

        } while ($startAt < ($responseJson['total'] ?? 0));
    }
}
