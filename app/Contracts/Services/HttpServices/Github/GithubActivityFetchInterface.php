<?php

namespace App\Contracts\Services\HttpServices\Github;

use App\Models\Integration;

interface GithubActivityFetchInterface
{
    public function getMergedPullRequests(Integration $integration,string $searchQuery, int $limit): array;
    public function getCommits(Integration $integration,string $owner, string $repo, string $branch, string $since, int $limit): array;
}
