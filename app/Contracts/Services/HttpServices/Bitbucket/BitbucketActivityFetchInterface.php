<?php

namespace App\Contracts\Services\HttpServices\Bitbucket;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;

interface BitbucketActivityFetchInterface
{
    public function getMergedPullRequests(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,CarbonImmutable $updatedSince): array;
    public function getCommits(PendingRequest $client,string $workspaceSlug, string $repoSlug,int $limit,string $defaultBranch): array;
    public function getExtendedInfo(PendingRequest $client,string $url):array;
}
