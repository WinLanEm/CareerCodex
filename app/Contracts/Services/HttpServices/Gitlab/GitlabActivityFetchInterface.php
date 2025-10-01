<?php

namespace App\Contracts\Services\HttpServices\Gitlab;

use Carbon\CarbonImmutable;

interface GitlabActivityFetchInterface
{
    public function getMergedPullRequests(string $token,int $projectId, int $limit,CarbonImmutable $updatedSince): array;
    public function getCommits(string $token,int $projectId, int $limit,CarbonImmutable $updatedSince,string $branch): array;
}
