<?php

namespace App\Contracts\Services\HttpServices\Bitbucket;

use Carbon\CarbonImmutable;

interface BitbucketActivityFetchInterface
{
    public function getMergedPullRequests(string $token,string $workspaceSlug, string $repoSlug,int $limit,CarbonImmutable $updatedSince): array;
    public function getCommits(string $token,string $workspaceSlug, string $repoSlug,int $limit,string $defaultBranch): array;
    public function getExtendedInfo(string $token,string $url):array;
}
