<?php

namespace App\Contracts\Services\HttpServices;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

interface BitbucketApiServiceInterface
{
    public function syncRepositories(PendingRequest $client,\Closure $closure):void;
    public function getMergedPullRequests(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,CarbonImmutable $updatedSince): array;
    public function getExtendedInfo(PendingRequest $client,string $url):array;
    public function getCommits(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,string $defaultBranch): array;
}
