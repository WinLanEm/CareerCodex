<?php

namespace App\Contracts\Services\HttpServices;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

interface JiraApiServiceInterface
{
    public function getWorkspaces(string $token,PendingRequest $client): array;
    public function getProjects(string $token,string $cloudId,PendingRequest $client): array;
    public function syncCompletedIssuesForProject(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        CarbonImmutable $updatedSince,
        string $token,
        string $projectKey,
        string $cloudId,
        PendingRequest $client,
        \Closure $closure
    ):void;
}
