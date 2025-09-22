<?php

namespace App\Contracts\Services\HttpServices\Gitlab;

use Illuminate\Http\Client\PendingRequest;

interface GitlabRepositorySyncInterface
{
    public function syncRepositories(PendingRequest $client, \Closure $closure): void;
}
