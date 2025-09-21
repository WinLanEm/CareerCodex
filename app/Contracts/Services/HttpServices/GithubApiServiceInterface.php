<?php

namespace App\Contracts\Services\HttpServices;

use Illuminate\Http\Client\PendingRequest;

interface GithubApiServiceInterface
{
    public function syncRepositories(PendingRequest $client, \Closure $closure): void;
    public function getMergedPullRequests(PendingRequest $client,string $searchQuery, int $limit): array;
    public function getCommits(PendingRequest $client,string $owner, string $repo, string $branch, string $since, int $limit): array;
}
