<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Services\HttpServices\AsanaApiServiceInterface;
use App\Exceptions\ApiRateLimitExceededException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class AsanaApiService implements AsanaApiServiceInterface
{
    public function getWorkspaces(string $token,PendingRequest $client): array
    {
        return Redis::throttle('asana-api')->allow(150)->every(60)->then(
            function () use($token,$client) {
            $providerInstanceUrl = config('services.asana_integration.provider_instance_url');
            $response = $client
                ->withHeaders(['accept' => 'application/json'])
                ->get($providerInstanceUrl);

            $response->throw();

            return $response->json()['data'];
            },
            function () {
                throw new ApiRateLimitExceededException('Asana API rate limit exceeded.',100);
            }
        );
    }

    public function getProjects(string $token,string $cloudId,PendingRequest $client): array
    {
        $allProjects = [];
        $url = 'https://app.asana.com/api/1.0/projects';
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
            $responseJson = Redis::throttle('asana-api')->allow(150)->every(60)->block(10)->then(
                function () use ($token, $url, $params,$client) {
                    $response = $client->asJson()->get($url, $params);
                    $response->throw();
                    return $response->json();
                },
                function () {
                    throw new ApiRateLimitExceededException('Asana API rate limit exceeded.', 100);
                }
            );

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
        $url = 'https://app.asana.com/api/1.0/tasks';
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

            $responseJson = Redis::throttle('asana-api')->allow(150)->every(60)->block(10)->then(
                function () use ($token, $url, $params,$client) {
                    $response = $client->asJson()->get($url, $params);
                    $response->throw();
                    return $response->json();
                },
                function () {
                    throw new ApiRateLimitExceededException('Asana API rate limit exceeded.', 100);
                }
            );

            $tasks = $responseJson['data'] ?? [];
            foreach ($tasks as $task) {
                $closure($task);
            }
            $nextPageOffset = $responseJson['next_page']['offset'] ?? null;

        } while ($nextPageOffset);
    }
}
