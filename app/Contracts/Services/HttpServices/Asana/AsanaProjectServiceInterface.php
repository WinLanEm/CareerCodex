<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use Illuminate\Http\Client\PendingRequest;

interface AsanaProjectServiceInterface
{


    public function syncCompletedIssuesForProject(
        string $projectKey,
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        string $projectName,
        string $updatedSince,
        string $token,
        PendingRequest $client,
        \Closure $closure
    );
}
