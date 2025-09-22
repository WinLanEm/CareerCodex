<?php

namespace App\Contracts\Services\HttpServices\Github;

use Illuminate\Http\Client\PendingRequest;

interface GithubActivityFetchInterface
{
    public function getMergedPullRequests(PendingRequest $client,string $searchQuery, int $limit): array;
    public function getCommits(PendingRequest $client,string $owner, string $repo, string $branch, string $since, int $limit): array;
}
