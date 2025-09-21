<?php

namespace App\Contracts\Services\HttpServices;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

interface GitlabApiServiceInterface
{
    public function syncRepositories(PendingRequest $client, \Closure $closure): void;
    public function getMergedPullRequests(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince): array;
    public function getCommits(PendingRequest $client,int $projectId, int $limit,CarbonImmutable $updatedSince,string $branch): array;
}
