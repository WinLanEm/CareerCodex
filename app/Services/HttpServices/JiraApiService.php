<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Services\HttpServices\JiraApiServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

class JiraApiService implements JiraApiServiceInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
    )
    {}
    public function getWorkspaces(string $token,PendingRequest $client): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::JIRA,
            function () use($token,$client) {
                $providerInstanceUrl = config('services.jira_integration.provider_instance_url');
                $response = $client
                    ->timeout(30)
                    ->get($providerInstanceUrl);
                $response->throw();

                return $response->json();
            },
        );
    }

    public function getProjects(string $token,string $cloudId,PendingRequest $client): array
    {
        $allProjects = [];
        $startAt = 0;
        $maxResults = 50;

        do {
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::JIRA,
                function () use ($token, $cloudId, $startAt, $maxResults,$client) {
                    $url = config('services.jira_integration.projects_url');
                    $url = str_replace('{cloudId}', $cloudId, $url);
                    $response = $client->asJson()->timeout(30)->get($url, [
                        'startAt' => $startAt,
                        'maxResults' => $maxResults,
                    ]);
                    $response->throw();
                    return $response->json();
                },
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
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::JIRA,
                function () use ($token, $client,$cloudId, $jql, $startAt, $maxResults) {
                    $url = config('services.jira_integration.sync_issue');
                    $url = str_replace('{cloudId}', $cloudId, $url);
                    $response = $client->asJson()->timeout(30)->get($url, [
                        'jql' => $jql,
                        'fields' => 'summary,resolutiondate,description,project,issuetype,status',
                        'startAt' => $startAt,
                        'maxResults' => $maxResults
                    ]);
                    $response->throw();
                    return $response->json();
                },
            );

            $issues = $responseJson['issues'] ?? [];
            foreach ($issues as $issue) {
                $closure($issue);
            }

            $startAt += count($issues);

        } while ($startAt < ($responseJson['total'] ?? 0));
    }
}
