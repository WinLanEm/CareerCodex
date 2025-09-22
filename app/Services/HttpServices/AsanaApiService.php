<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Services\HttpServices\AsanaApiServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use Illuminate\Http\Client\PendingRequest;

class AsanaApiService implements AsanaApiServiceInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
    )
    {}

    public function getWorkspaces(string $token,PendingRequest $client): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::ASANA,
            function () use($token,$client) {
                $providerInstanceUrl = config('services.asana_integration.provider_instance_url');
                $response = $client
                    ->withHeaders(['accept' => 'application/json'])
                    ->get($providerInstanceUrl);

                $response->throw();

                return $response->json()['data'];
            }
        );
    }

    public function getProjects(string $token,string $cloudId,PendingRequest $client): array
    {
        $allProjects = [];
        $url = config('services.asana_integration.projects_url');
        $params = [
            'workspace' => $cloudId,
            'limit' => 100,
            'opt_fields' => 'name,gid'
        ];
        $nextPageOffset = null;

        do {
            if ($nextPageOffset) {
                $params['offset'] = $nextPageOffset;
            }
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::ASANA,
                function () use($client,$url,$params,$token){
                    $response = $client->asJson()->get($url, $params);
                    $response->throw();
                    return $response->json();
                });

            $projectsOnPage = $responseJson['data'] ?? [];
            $allProjects = array_merge($allProjects, $projectsOnPage);
            $nextPageOffset = $responseJson['next_page']['offset'] ?? null;

        } while ($nextPageOffset);

        return $allProjects;
    }

    public function syncCompletedIssuesForProject(
        string $projectKey,
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        string $projectName,
        string $updatedSince,
        string $token,
        PendingRequest $client,
        \Closure $closure
    )
    {
        $url = config('services.asana_integration.sync_issue');
        $params = [
            'project' => $projectKey,
            'completed_since' => $updatedSince,
            'limit' => 100,
            'opt_fields' => 'name,completed,completed_at,notes,permalink_url'
        ];
        $nextPageOffset = null;

        do {
            if ($nextPageOffset) {
                $params['offset'] = $nextPageOffset;
            }

            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::ASANA,
                function () use($client,$url,$params,$token){
                    $response = $client->asJson()->get($url, $params);
                    $response->throw();
                    return $response->json();
                });

            $tasks = $responseJson['data'] ?? [];
            foreach ($tasks as $task) {
                $closure($task);
            }
            $nextPageOffset = $responseJson['next_page']['offset'] ?? null;

        } while ($nextPageOffset);
    }
}
