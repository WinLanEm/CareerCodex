<?php

namespace App\Contracts\Services\HttpServices\Github;

use Illuminate\Http\Client\PendingRequest;

interface GithubRepositorySyncInterface
{
    public function syncRepositories(PendingRequest $client, \Closure $closure): void;
}
