<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Contracts\Repositories\Achievement\AchievementUpdateOrCreateRepositoryInterface;
use Illuminate\Http\Client\PendingRequest;

interface AsanaProjectServiceInterface
{


    public function syncCompletedIssuesForProject(
        string                                       $projectKey,
        AchievementUpdateOrCreateRepositoryInterface $repository,
        string                                       $projectName,
        string                                       $token,
        \Closure                                     $closure
    );
}
