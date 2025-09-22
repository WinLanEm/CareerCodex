<?php

namespace App\Contracts\Services\HttpServices\Bitbucket;

use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;

interface BitbucketRepositorySyncInterface
{
    public function syncRepositories(PendingRequest $client, \Closure $closure): void;
}
