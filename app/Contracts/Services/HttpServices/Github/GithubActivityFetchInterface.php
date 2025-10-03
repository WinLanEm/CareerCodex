<?php

namespace App\Contracts\Services\HttpServices\Github;

use Illuminate\Http\Client\PendingRequest;

interface GithubActivityFetchInterface
{
    public function getMergedPullRequests(string $token,string $searchQuery, int $limit): array;
    public function getCommits(string $token,string $owner, string $repo, string $branch, string $since, int $limit): array;
}
