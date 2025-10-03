<?php

namespace App\Contracts\Services\HttpServices\Jira;

use App\Contracts\Repositories\Achievement\AchievementUpdateOrCreateRepositoryInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;

interface JiraProjectServiceInterface
{
    public function getProjects(Integration $integration,string $cloudId): array;
    public function syncCompletedIssuesForProject(
        AchievementUpdateOrCreateRepositoryInterface $repository,
        Integration                                  $integration,
        string                                       $projectKey,
        string                                       $cloudId,
        \Closure                                     $closure
    ):void;
}
