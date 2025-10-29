<?php

namespace App\Contracts\Services\HttpServices\Gitlab;

use App\Models\Integration;
use Carbon\CarbonImmutable;

interface GitlabActivityFetchInterface
{
    public function getMergedPullRequests(Integration $integration,int $projectId, int $limit,CarbonImmutable $updatedSince): array;
    public function getCommits(Integration $integration,int $projectId, int $limit,CarbonImmutable $updatedSince,string $branch): array;
}
