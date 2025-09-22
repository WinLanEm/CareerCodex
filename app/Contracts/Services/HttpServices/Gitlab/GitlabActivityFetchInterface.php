<?php

namespace App\Contracts\Services\HttpServices\Gitlab;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

interface GitlabActivityFetchInterface
{
    public function getMergedPullRequests(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince): array;
    public function getCommits(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince,string $branch): array;
}
