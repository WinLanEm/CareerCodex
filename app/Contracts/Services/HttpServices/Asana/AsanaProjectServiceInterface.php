<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Contracts\Repositories\Achievement\AchievementUpdateOrCreateRepositoryInterface;
use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;

interface AsanaProjectServiceInterface
{


    public function syncCompletedIssuesForProject(
        string                                       $projectKey,
        AchievementUpdateOrCreateRepositoryInterface $repository,
        string                                       $projectName,
        Integration                                   $integration,
        \Closure                                     $closure
    );
}
