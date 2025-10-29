<?php

namespace App\Contracts\Services\HttpServices\Bitbucket;

use App\Models\Integration;
use Carbon\CarbonImmutable;

interface BitbucketActivityFetchInterface
{
    public function getMergedPullRequests(Integration $integration,string $workspaceSlug, string $repoSlug,int $limit,CarbonImmutable $updatedSince): array;
    public function getCommits(Integration $integration,string $workspaceSlug, string $repoSlug,int $limit,string $defaultBranch): array;
    public function getExtendedInfo(Integration $integration,string $url):array;
}
